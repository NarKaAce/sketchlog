<?php

use Adianti\Database\TTransaction;

class SteamGameImporterService
{
    private string $database = 'sketchlog';
    private string $steamApiKey;

    public function __construct()
    {
        $steamApiKey = trim((string) (
            $_ENV['STEAM_API_KEY']
            ?? $_SERVER['STEAM_API_KEY']
            ?? ''
        ));

        if ($steamApiKey === '') {
            throw new RuntimeException(
                'STEAM_API_KEY não foi encontrada no ambiente.'
            );
        }

        if (!preg_match('/^[a-f0-9]{32}$/i', $steamApiKey)) {
            throw new RuntimeException(
                'STEAM_API_KEY possui um formato inválido.'
            );
        }

        $this->steamApiKey = $steamApiKey;
    }

    public function getOwnedGames(string $identificador): array
    {
        $steamId64 = $this->resolverSteamId64($identificador);

        $input = [
            'steamid' => $steamId64,
            'include_appinfo' => true,
            'include_played_free_games' => true,
        ];

        $json = $this->getSteamJson(
            '/IPlayerService/GetOwnedGames/v1/',
            ['input_json' => json_encode($input)]
        );

        return $json['response']['games'] ?? [];
    }

    public function resolverSteamId64(string $identificador): string
    {
        $identificador = trim($identificador);

        if ($identificador === '') {
            throw new InvalidArgumentException(
                'Informe o SteamID64 ou o identificador personalizado.'
            );
        }

        if (preg_match('/^\d{17}$/', $identificador)) {
            return $identificador;
        }

        if (filter_var($identificador, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(
                'Informe somente o SteamID64 ou o identificador personalizado, sem a URL.'
            );
        }

        $json = $this->getSteamJson(
            '/ISteamUser/ResolveVanityURL/v1/',
            ['vanityurl' => $identificador]
        );

        if (
            ($json['response']['success'] ?? 0) !== 1
            || empty($json['response']['steamid'])
        ) {
            throw new RuntimeException(
                'Não foi possível encontrar o perfil Steam informado.'
            );
        }

        return (string) $json['response']['steamid'];
    }

    public function getDetalhesJogo(int $appid): ?array
    {
        $url = 'https://store.steampowered.com/api/appdetails?' .
            http_build_query([
                'appids' => $appid,
                'cc' => 'BR',
                'l' => 'brazilian'
            ]);

        $json = $this->getJson($url);
        $resultado = $json[(string) $appid] ?? null;

        if (!$resultado || !($resultado['success'] ?? false)) {
            return null;
        }

        return $resultado['data'] ?? null;
    }

    private function salvarJogo(int $appid, array $detalhes): void
    {
        $jogo = new Jogo();
        $jogo->steam_appid = $appid;
        $jogo->nome = $detalhes['name'] ?? '';
        $jogo->dt_publicacao = $this->formatSteamDate(
            $detalhes['release_date']['date'] ?? null
        );
        $jogo->capa = $detalhes['header_image'] ?? null;
        $jogo->conquistas_totais =
            $detalhes['achievements']['total'] ?? 0;

        $jogo->store();

        $this->gerenciarRelacionamentos(
            $detalhes,
            $jogo->id
        );
    }

    public function sincronizarLote(array $jogos, int $offset, int $limite = 10): array {

        $lote = array_slice($jogos, $offset, $limite);
        $importados = 0;
        $ignorados = 0;

        TTransaction::open($this->database);

        try {
            foreach ($lote as $jogoSteam) {
                $appid = (int) $jogoSteam['appid'];

                $jogoBanco = Jogo::where('steam_appid', '=', $appid)->first();

                if ($jogoBanco instanceof Jogo) {
                    $ignorados++;
                    continue;
                }

                usleep(2000000); // 1 segundo

                $detalhes = $this->getDetalhesJogo($appid);

                if ($detalhes === null) {
                    $ignorados++;
                    continue;
                }

                if (!$this->deveSincronizarJogo($detalhes)) {
                    $ignorados++;
                    continue;
                }

                $this->salvarJogo($appid, $detalhes);
                $importados++;
            }

            TTransaction::close();
        } catch (Throwable $e) {
            TTransaction::rollback();
            throw $e;
        }

        $proximoOffset = $offset + count($lote);

        return [
            'importados'     => $importados,
            'ignorados'      => $ignorados,
            'processados'    => $proximoOffset,
            'total'          => count($jogos),
            'proximo_offset' => $proximoOffset,
            'concluido'      => $proximoOffset >= count($jogos),
        ];
    }

    private function deveSincronizarJogo(array $detalhes): bool
    {
        if (($detalhes['type'] ?? null) !== 'game') {
            return false;
        }

        $nome = mb_strtolower(
            trim($detalhes['name'] ?? '')
        );

        if ($nome === '') {
            return false;
        }

        $padroesIgnorados = [
            '/\bplaytest\b/iu',
            '/\bbeta\b/iu',
            '/\balpha\b/iu',
            '/\bdemo\b/iu',
            '/\bteste\b/iu',
            '/\btest\b/iu',
            '/\btesting\b/iu',
            '/\btechnical test\b/iu',
            '/\bpublic test\b/iu',
            '/\btest server\b/iu',
            '/\bdedicated server\b/iu',
            '/\bbenchmark\b/iu',
        ];

        foreach ($padroesIgnorados as $padrao) {
            if (preg_match($padrao, $nome)) {
                return false;
            }
        }

        return true;
    }

    public function formatSteamDate(?string $data): ?string
    {
        if (empty($data)) {
            return null;
        }

        $meses = [
            'jan' => 1,
            'fev' => 2,
            'mar' => 3,
            'abr' => 4,
            'mai' => 5,
            'jun' => 6,
            'jul' => 7,
            'ago' => 8,
            'set' => 9,
            'out' => 10,
            'nov' => 11,
            'dez' => 12,
        ];

        $data = mb_strtolower(trim($data));

        if (!preg_match('/^(\d{1,2})\/([a-zç]+)\.?\/(\d{4})$/u',$data,$partes)) {
            return null;
        }

        $dia = (int) $partes[1];
        $mesTexto = $partes[2];
        $ano = (int) $partes[3];

        $mes = $meses[$mesTexto] ?? null;

        if ($mes === null || !checkdate($mes, $dia, $ano)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
    }

    public function gerenciarRelacionamentos($detalhes, $jogoId)
    {
        if($detalhes !== null)
        {
            $desenvolvedores = $detalhes['developers'] ?? [];
            $distribuidoras = $detalhes['publishers'] ?? [];
            $generos = $detalhes['genres'] ?? [];

            $this->setGeneros($generos, $jogoId);
            $this->setDistribuidoras($distribuidoras, $jogoId);
            $this->setDesenvolvedores($desenvolvedores, $jogoId);
        }
    }

    public function setGeneros($generos, $jogoId)
    {
        if($generos !== null) {
            foreach ($generos as $genero) {

                $obj = Genero::where('nome', '=', $genero['description'])->first();

                if($obj === null)
                {
                    $obj = new Genero();
                    $obj->nome = $genero['description'];
                    $obj->store();
                }

                $relacionamento = new JogoGeneros();
                $relacionamento->jogo_id = $jogoId;
                $relacionamento->genero_id = $obj->id;
                $relacionamento->store();
            }
        }
    }

    public function setDesenvolvedores($desenvolvedores, $jogoId)
    {
        if($desenvolvedores !== null) {
            foreach ($desenvolvedores as $desenvolvedor) {

                $obj = Desenvolvedor::where('nome', '=', $desenvolvedor)->first();

                if($obj === null)
                {
                    $obj = new Desenvolvedor();
                    $obj->nome = $desenvolvedor;
                    $obj->store();
                }

                $relacionamento = new JogoDesenvolvedores();
                $relacionamento->jogo_id = $jogoId;
                $relacionamento->desenvolvedor_id = $obj->id;
                $relacionamento->store();
            }
        }
    }

    public function setDistribuidoras($distribuidoras, $jogoId)
    {
        if($distribuidoras !== null) {
            foreach ($distribuidoras as $distribuidora) {

                $obj = Distribuidora::where('nome', '=', $distribuidora)->first();

                if($obj === null)
                {
                    $obj = new Distribuidora();
                    $obj->nome = $distribuidora;
                    $obj->store();
                }

                $relacionamento = new JogoDistribuidoras();
                $relacionamento->jogo_id = $jogoId;
                $relacionamento->distribuidora_id = $obj->id;
                $relacionamento->store();
            }
        }
    }

    private function getSteamJson(string $endpoint, array $parametros = []): array
    {
        $url = 'https://api.steampowered.com' . $endpoint . '?'
            . http_build_query([
                'key' => $this->steamApiKey,
                ...$parametros,
            ]);

        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'ignore_errors' => true,
                'header' => implode("\r\n", [
                        'User-Agent: SketchlogSteamIntegration/1.0',
                        'Accept: application/json, text/javascript, */*; q=0.01',
                    ]) . "\r\n",
            ],
        ]);

        $response = file_get_contents($url, false, $context);
        $status = $http_response_header[0] ?? 'HTTP status desconhecido';

        if ($response === false) {
            throw new RuntimeException("Falha ao consultar a Steam. Status: {$status}.");
        }

        preg_match('/\s(\d{3})(?:\s|$)/', $status, $matches);
        $statusCode = (int) ($matches[1] ?? 0);

        if ($statusCode !== 200) {
            throw new RuntimeException(
                "A Steam respondeu com {$status}. Resposta: " . substr($response, 0, 300)
            );
        }

        $json = json_decode($response, true);

        if (!is_array($json)) {
            throw new RuntimeException(
                'A Steam nao retornou um JSON valido: ' . substr($response, 0, 300)
            );
        }

        return $json;
    }

    private function getJson(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'ignore_errors' => true,
                'header' => implode("\r\n", [
                        'User-Agent: SketchlogSteamIntegration/1.0',
                        'Accept: application/json, text/javascript, */*; q=0.01',
                    ]) . "\r\n",
            ],
        ]);

        $response = file_get_contents($url, false, $context);
        $status = $http_response_header[0] ?? 'HTTP status desconhecido';

        if ($response === false) {
            throw new RuntimeException(
                "Falha ao consultar a Steam. Status: {$status}."
            );
        }

        preg_match('/\s(\d{3})(?:\s|$)/', $status, $matches);
        $statusCode = (int) ($matches[1] ?? 0);

        if ($statusCode !== 200) {
            throw new RuntimeException(
                "A Steam respondeu com {$status}. Resposta: " .
                substr($response, 0, 300)
            );
        }

        $json = json_decode($response, true);

        if (!is_array($json)) {
            throw new RuntimeException(
                'A Steam não retornou um JSON válido: ' .
                substr($response, 0, 300)
            );
        }

        return $json;
    }
}
