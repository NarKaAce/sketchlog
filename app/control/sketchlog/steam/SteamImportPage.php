<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TToast;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Wrapper\BootstrapFormBuilder;

class SteamImportPage extends TPage
{
    private $form;
    private $resultBox;

    public function __construct($param = null)
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_SteamImportPage');
        $this->form->setFormTitle('Importar jogos da Steam');
        $this->form->generateAria();

        $limite = new TEntry('limite');
        $limite->setSize('100%');
        $limite->setValue(50);

        $this->form->addFields(
            [new TLabel('Limite de jogos', null, '14px', null), $limite]
        );

        $this->form->addAction(
            'Importar 10',
            new TAction([$this, 'onImportar'], ['limite_padrao' => 10]),
            'fa:play green'
        );

        $this->form->addAction(
            'Importar 50',
            new TAction([$this, 'onImportar'], ['limite_padrao' => 50]),
            'fa:play blue'
        );

        $this->form->addAction(
            'Importar limite informado',
            new TAction([$this, 'onImportar']),
            'fa:database orange'
        );

        $this->resultBox = new TElement('div');
        $this->resultBox->style = '
            margin-top: 16px;
            padding: 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #fff;
            line-height: 1.5;
        ';

        $box = new TVBox;
        $box->style = 'width: 100%';
        $box->add($this->form);
        $box->add($this->resultBox);

        parent::add($box);
    }

    public function onImportar($param)
    {
        try {
            $data = $this->form->getData();

            $limite = (int) ($param['limite_padrao'] ?? $data->limite ?? 50);

            if ($limite <= 0) {
                throw new Exception('Informe um limite maior que zero.');
            }

            if ($limite > 500) {
                throw new Exception('Para rodar pela tela, use no máximo 500 jogos por vez.');
            }

            $data->limite = $limite;
            $this->form->setData($data);

            // Evita timeout em lotes pequenos/medios. Para importar tudo, prefira CLI/cron.
            @set_time_limit(0);

            $inicio = microtime(true);

            $service = new SteamGameImporterService();
            $service->importar($limite);

            $segundos = round(microtime(true) - $inicio, 2);

            TToast::show(
                'success',
                "Importação concluída: {$limite} jogos processados.",
                'topRight',
                'far:check-circle'
            );

            $this->showResult(
                'Importação concluída',
                "Lote solicitado: {$limite}<br>Tempo aproximado: {$segundos}s"
            );
        } catch (Throwable $e) {
            new TMessage('error', $e->getMessage());

            $this->showResult(
                'Erro na importação',
                htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            );
        }
    }

    private function showResult(string $title, string $message): void
    {
        $h4 = new TElement('h4');
        $h4->style = 'margin-top: 0;';
        $h4->add($title);

        $p = new TElement('p');
        $p->add($message);

        $this->resultBox->add($h4);
        $this->resultBox->add($p);
    }
}
