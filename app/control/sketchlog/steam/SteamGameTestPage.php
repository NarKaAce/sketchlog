<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Wrapper\BootstrapFormBuilder;
use TTransaction;

class SteamGameTestPage extends TPage
{
    private $form;
    private $container;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_steam_game_test');
        $this->form->setFormTitle('Teste API Steam');

        $appid = new TEntry('appid');
        $appid->setSize('100%');
        $appid->placeholder = 'Ex: 730';

        $this->form->addFields(
            [new TLabel('Steam App ID'), $appid]
        );

        $this->form->addAction(
            'Buscar',
            new TAction([$this, 'onBuscar']),
            'fa:search blue'
        );

        $this->container = new TElement('div');
        $this->container->style = 'width: 100%';
        $this->container->id = 'steam_result_container';

        $box = new TVBox;
        $box->style = 'width: 100%';
        $box->add($this->form);
        $box->add($this->container);

        parent::add($box);
    }

    public function onBuscar($param)
    {
        try {
            $data = $this->form->getData();
            $this->form->setData($data);

            $appid = (int)($data->appid ?? 0);

            if (!$appid) {
                throw new Exception('Informe um App ID válido.');
            }

            $details = $this->buscarDetalhes($appid);

            if (!$details) {
                throw new Exception('Jogo não encontrado na Steam.');
            }

            $this->mostrarDados($appid, $details);

            /*
            // Quando quiser salvar, descomente este bloco.
            TTransaction::open('sketchlog');

            $service = new SteamGameImporterService();
            $jogoId = $service->salvarJogoPublico($appid, $details);
            $service->salvarRelacionamentosPublico($jogoId, $details);

            TTransaction::close();

            new TMessage('info', 'Jogo salvo com sucesso.');
            */

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
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

    private function mostrarDados(int $appid, array $details): void
    {
        $this->container->children = [];

        $box = new TElement('div');
        $box->style = '
        margin-top: 20px;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 6px;
        background: #fff;
        ';

        $titulo = new TElement('h3');
        $titulo->add($details['name'] ?? 'Sem nome');
        $box->add($titulo);

        if (!empty($details['header_image'])) {
            $img = new TElement('img');
            $img->src = $details['header_image'];
            $img->style = 'max-width: 460px; width: 100%; display: block; margin-bottom: 15px;';
            $box->add($img);
        }

        $box->add($this->linha('App ID', $appid));
        $box->add($this->linha('Tipo', $details['type'] ?? null));
        $box->add($this->linha('Data publicação', $details['release_date']['date'] ?? null));
        $box->add($this->linha('Grátis?', !empty($details['is_free']) ? 'Sim' : 'Não'));
        $box->add($this->linha('Capa/header_image', $details['header_image'] ?? null));
        $box->add($this->linha('Capsule image', $details['capsule_image'] ?? null));
        $box->add($this->linha('Background', $details['background'] ?? null));

        $box->add($this->linha('Desenvolvedores', implode(', ', $details['developers'] ?? [])));
        $box->add($this->linha('Distribuidoras', implode(', ', $details['publishers'] ?? [])));
        $box->add($this->linha('Gêneros', $this->formatarDescricoes($details['genres'] ?? [])));
        $box->add($this->linha('Categorias Steam', $this->formatarDescricoes($details['categories'] ?? [])));

        if (!empty($details['short_description'])) {
            $box->add($this->linha('Descrição curta', $details['short_description']));
        }

        if (!empty($details['screenshots'])) {
            $sub = new TElement('h4');
            $sub->add('Screenshots');
            $box->add($sub);

            foreach (array_slice($details['screenshots'], 0, 5) as $screenshot) {
                $img = new TElement('img');
                $img->src = $screenshot['path_thumbnail'] ?? $screenshot['path_full'] ?? '';
                $img->style = 'width: 160px; margin: 4px; border-radius: 4px;';
                $box->add($img);
            }
        }

        $this->container->add($box);
    }

    private function linha(string $label, $valor): TElement
    {
        $p = new TElement('p');

        $strong = new TElement('strong');
        $strong->add($label . ': ');

        $p->add($strong);
        $p->add($valor ?: '-');

        return $p;
    }

    private function formatarDescricoes(array $itens): string
    {
        $nomes = [];

        foreach ($itens as $item) {
            if (!empty($item['description'])) {
                $nomes[] = $item['description'];
            }
        }

        return implode(', ', $nomes);
    }

    private function getJson(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'header' => "User-Agent: AdiantiSteamTest/1.0\r\n",
            ],
        ]);

        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            throw new Exception("Erro ao buscar URL: {$url}");
        }

        return json_decode($response, true) ?: [];
    }
}