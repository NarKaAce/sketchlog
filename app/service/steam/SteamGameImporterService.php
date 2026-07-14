<?php

use Adianti\Database\TTransaction;

class SteamGameImporterService
{
    private string $database = 'sketchlog';
    private string $steamApiKey;

    public function __construct()
    {
        $this->steamApiKey = getenv('STEAM_API_KEY')
            ?: (function_exists('apache_getenv') ? apache_getenv('STEAM_API_KEY') : null)
                ?: ($_SERVER['STEAM_API_KEY'] ?? null)
                    ?: ($_ENV['STEAM_API_KEY'] ?? null);

        if (!$this->steamApiKey) {
            throw new Exception('STEAM_API_KEY não configurada.');
        }
    }

    public function importar(int $limite = 50, ?int $inicio = null): void
    {
        TTransaction::open($this->database);

        try {
            if ($inicio === null) {
                $inicio = $this->getControleInt('steam_last_appid', 0);
            }

            $apps = $this->buscarListaSteam($limite, $inicio);

            foreach ($apps as $app) {
                $appid = (int) $app['appid'];
                $details = $this->buscarDetalhes($appid);

                if (!$details || ($details['type'] ?? null) !== 'game') {
                    continue;
                }

                if (!$this->jogoJaFoiLancado($details)) {
                    continue;
                }

                $jogoId = $this->salvarJogo($appid, $details);

                $this->salvarGeneros($jogoId, $details['genres'] ?? []);
                $this->salvarDesenvolvedores($jogoId, $details['developers'] ?? []);
                $this->salvarDistribuidoras($jogoId, $details['publishers'] ?? []);

                sleep(1);
            }

            if (!empty($apps)) {
                $ultimoApp = end($apps);
                $this->setControleInt('steam_last_appid', (int) $ultimoApp['appid']);
            }

            TTransaction::close();
        } catch (Throwable $e) {
            TTransaction::rollback();
            throw $e;
        }
    }

    private function buscarListaSteam(int $limite, int $lastAppid = 0): array
    {
        return $this->buscarListaSteamIStoreService($limite, $lastAppid);
    }

    private function jogoJaFoiLancado(array $details): bool
    {
        if (!empty($details['release_date']['coming_soon'])) {
            return false;
        }

        $data = $this->formatarDataSteam($details['release_date']['date'] ?? null);

        if (!$data) {
            return false;
        }

        return $data <= date('Y-m-d');
    }

    private function getControleInt(string $chave, int $padrao = 0): int
    {
        $conn = TTransaction::get();

        $stmt = $conn->prepare("
        SELECT valor
        FROM steam_import_controle
        WHERE chave = :chave
    ");

        $stmt->execute([
            ':chave' => $chave,
        ]);

        $valor = $stmt->fetchColumn();

        return $valor !== false ? (int) $valor : $padrao;
    }

    private function setControleInt(string $chave, int $valor): void
    {
        $conn = TTransaction::get();

        $stmt = $conn->prepare("
        INSERT INTO steam_import_controle (
            chave,
            valor,
            atualizado_em
        )
        VALUES (
            :chave,
            :valor,
            CURRENT_TIMESTAMP
        )
        ON CONFLICT (chave) DO UPDATE SET
            valor = EXCLUDED.valor,
            atualizado_em = CURRENT_TIMESTAMP
    ");

        $stmt->execute([
            ':chave' => $chave,
            ':valor' => (string) $valor,
        ]);
    }

    private function buscarListaSteamIStoreService(int $limite, int $lastAppid = 0): array
    {
        $input = [
            'include_games' => true,
            'include_dlc' => false,
            'include_software' => false,
            'include_videos' => false,
            'include_hardware' => false,
            'max_results' => $limite,
        ];

        if ($lastAppid > 0) {
            $input['last_appid'] = $lastAppid;
        }

        $url = 'https://partner.steam-api.com/IStoreService/GetAppList/v1/?' . http_build_query([
                'key' => $this->steamApiKey,
                'input_json' => json_encode($input),
            ]);

        return $this->getJson($url)['response']['apps'] ?? [];
    }

    private function buscarListaSteamStoreSearch(int $limite): array
    {
        $url = 'https://store.steampowered.com/search/results/?' . http_build_query([
                'query' => '',
                'start' => 0,
                'count' => $limite,
                'dynamic_data' => '',
                'sort_by' => '_ASC',
                'category1' => 998,
                'infinite' => 1,
            ]);

        $json = $this->getJson($url);

        $html = $json['results_html'] ?? '';

        if (!$html) {
            throw new Exception('Steam Store Search não retornou HTML de resultados.');
        }

        preg_match_all('/\/app\/(\d+)\//', $html, $matches);

        $appids = array_values(array_unique($matches[1] ?? []));

        if (empty($appids)) {
            throw new Exception('Nenhum appid encontrado no resultado da Steam Store.');
        }

        $apps = [];

        foreach (array_slice($appids, 0, $limite) as $appid) {
            $apps[] = [
                'appid' => (int) $appid,
            ];
        }

        return $apps;
    }

    private function buscarDetalhes(int $appid): ?array
    {
        $url = 'https://store.steampowered.com/api/appdetails?' . http_build_query([
                'appids' => $appid,
                'cc' => 'br',
                'l' => 'portuguese',
            ]);

        $json = $this->getJson($url);

        if (
            empty($json[$appid]) ||
            empty($json[$appid]['success']) ||
            empty($json[$appid]['data'])
        ) {
            return null;
        }

        return $json[$appid]['data'];
    }

    private function salvarJogo(int $appid, array $details): int
    {
        $conn = TTransaction::get();

        $nome = trim($details['name'] ?? '');
        $capa = $details['header_image'] ?? null;
        $dtPublicacao = $this->formatarDataSteam($details['release_date']['date'] ?? null);

        $stmt = $conn->prepare("
        SELECT id
        FROM jogo
        WHERE steam_appid = :steam_appid
        LIMIT 1
    ");
        $stmt->execute([':steam_appid' => $appid]);

        $jogoId = $stmt->fetchColumn();

        if (!$jogoId && $nome) {
            $stmt = $conn->prepare("
            SELECT id
            FROM jogo
            WHERE LOWER(nome) = LOWER(:nome)
            LIMIT 1
        ");
            $stmt->execute([':nome' => $nome]);

            $jogoId = $stmt->fetchColumn();
        }

        if ($jogoId) {
            $stmt = $conn->prepare("
            UPDATE jogo
            SET
                steam_appid = COALESCE(steam_appid, :steam_appid),
                dt_publicacao = COALESCE(dt_publicacao, :dt_publicacao),
                capa = COALESCE(capa, :capa)
            WHERE id = :id
        ");

            $stmt->execute([
                ':id' => $jogoId,
                ':steam_appid' => $appid,
                ':dt_publicacao' => $dtPublicacao,
                ':capa' => $capa,
            ]);

            return (int) $jogoId;
        }

        $stmt = $conn->prepare("
        INSERT INTO jogo (
            steam_appid,
            nome,
            dt_publicacao,
            capa
        )
        VALUES (
            :steam_appid,
            :nome,
            :dt_publicacao,
            :capa
        )
        RETURNING id
    ");

        $stmt->execute([
            ':steam_appid' => $appid,
            ':nome' => $nome,
            ':dt_publicacao' => $dtPublicacao,
            ':capa' => $capa,
        ]);

        return (int) $stmt->fetchColumn();
    }

    private function salvarGeneros(int $jogoId, array $generos): void
    {
        foreach ($generos as $genero) {
            $nomeGenero = $genero['description'] ?? null;

            if (!$nomeGenero) {
                continue;
            }

            $generoId = $this->getOrCreate('genero', $nomeGenero);
            $this->vincularJogoGenero($jogoId, $generoId);
        }
    }

    private function salvarDesenvolvedores(int $jogoId, array $desenvolvedores): void
    {
        foreach ($desenvolvedores as $nomeDesenvolvedor) {
            if (!$nomeDesenvolvedor) {
                continue;
            }

            $desenvolvedorId = $this->getOrCreate('desenvolvedor', $nomeDesenvolvedor);
            $this->vincularJogoDesenvolvedor($jogoId, $desenvolvedorId);
        }
    }

    private function salvarDistribuidoras(int $jogoId, array $distribuidoras): void
    {
        foreach ($distribuidoras as $nomeDistribuidora) {
            if (!$nomeDistribuidora) {
                continue;
            }

            $distribuidoraId = $this->getOrCreate('distribuidora', $nomeDistribuidora);
            $this->vincularJogoDistribuidora($jogoId, $distribuidoraId);
        }
    }

    private function getOrCreate(string $tabela, string $nome): int
    {
        $conn = TTransaction::get();

        $stmt = $conn->prepare("
            INSERT INTO {$tabela} (nome)
            VALUES (:nome)
            ON CONFLICT (nome) DO UPDATE SET
                nome = EXCLUDED.nome
            RETURNING id
        ");

        $stmt->execute([
            ':nome' => trim($nome),
        ]);

        return (int) $stmt->fetchColumn();
    }

    private function vincularJogoGenero(int $jogoId, int $generoId): void
    {
        $conn = TTransaction::get();

        $stmt = $conn->prepare("
            INSERT INTO jogo_generos (
                jogo_id,
                genero_id
            )
            VALUES (
                :jogo_id,
                :genero_id
            )
            ON CONFLICT (jogo_id, genero_id) DO NOTHING
        ");

        $stmt->execute([
            ':jogo_id' => $jogoId,
            ':genero_id' => $generoId,
        ]);
    }

    private function vincularJogoDesenvolvedor(int $jogoId, int $desenvolvedorId): void
    {
        $conn = TTransaction::get();

        $stmt = $conn->prepare("
            INSERT INTO jogo_desenvolvedores (
                jogo_id,
                desenvolvedor_id
            )
            VALUES (
                :jogo_id,
                :desenvolvedor_id
            )
            ON CONFLICT (jogo_id, desenvolvedor_id) DO NOTHING
        ");

        $stmt->execute([
            ':jogo_id' => $jogoId,
            ':desenvolvedor_id' => $desenvolvedorId,
        ]);
    }

    private function vincularJogoDistribuidora(int $jogoId, int $distribuidoraId): void
    {
        $conn = TTransaction::get();

        $stmt = $conn->prepare("
            INSERT INTO jogo_distribuidoras (
                jogo_id,
                distribuidora_id
            )
            VALUES (
                :jogo_id,
                :distribuidora_id
            )
            ON CONFLICT (jogo_id, distribuidora_id) DO NOTHING
        ");

        $stmt->execute([
            ':jogo_id' => $jogoId,
            ':distribuidora_id' => $distribuidoraId,
        ]);
    }

    private function formatarDataSteam(?string $data): ?string
    {
        if (!$data || strtolower($data) === 'coming soon') {
            return null;
        }

        $timestamp = strtotime($data);

        if (!$timestamp) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    private function getJson(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'ignore_errors' => true,
                'header' => implode("\r\n", [
                        'User-Agent: Mozilla/5.0',
                        'Accept: application/json, text/javascript, */*; q=0.01',
                    ]) . "\r\n",
            ],
        ]);

        $response = file_get_contents($url, false, $context);

        $status = $http_response_header[0] ?? 'HTTP status desconhecido';

        if ($response === false) {
            throw new Exception("Falha ao buscar URL. Status: {$status}. URL: {$url}");
        }

        if (!str_contains($status, '200')) {
            throw new Exception("Status: {$status}. Resposta: " . substr($response, 0, 300));
        }

        $json = json_decode($response, true);

        if (!is_array($json)) {
            throw new Exception("Resposta não é JSON válido: " . substr($response, 0, 300));
        }

        return $json;
    }

    public function salvarJogoPublico(int $appid, array $details): int
    {
        return $this->salvarJogo($appid, $details);
    }

    public function salvarRelacionamentosPublico(int $jogoId, array $details): void
    {
        $this->salvarGeneros($jogoId, $details['genres'] ?? []);
        $this->salvarDesenvolvedores($jogoId, $details['developers'] ?? []);
        $this->salvarDistribuidoras($jogoId, $details['publishers'] ?? []);
    }
}