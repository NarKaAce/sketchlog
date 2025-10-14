<?php

class LogForm extends TPage
{
    private $form;
    public static $formName = 'form_LogForm';
    public static $database = 'sketchlog';

    public function __construct($param)
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder;
        $this->form->setFormTitle("Cadastro de Log");
        $this->form->generateAria(); // automatic aria-label

        $this->form->appendPage('Dados');

        $id = new TEntry('id');
        $sketch_id = new \Adianti\Widget\Wrapper\TDBUniqueSearch('sketch_id', 'sketchlog', 'Sketch', 'id', 'nome');
        $jogo_id = new \Adianti\Widget\Wrapper\TDBUniqueSearch('jogo_id', 'sketchlog', 'Jogo', 'id', 'nome');
        $dt_hr_ini = new \Adianti\Widget\Form\TDateTime('dt_hr_ini');
        $dt_hr_fim = new \Adianti\Widget\Form\TDateTime('dt_hr_fim');
        $tempo = new \Adianti\Widget\Form\TEntry('tempo');
        $nota_id = new \Adianti\Widget\Wrapper\TDBUniqueSearch('nota_id', 'sketchlog', 'Nota', 'id', 'descricao', 'valor desc');
        $dificuldade_id = new \Adianti\Widget\Wrapper\TDBUniqueSearch('dificuldade_id', 'sketchlog', 'Dificuldade', 'id', 'descricao', 'id desc');
        $log_replay = new \Adianti\Widget\Form\TRadioGroup('log_replay');
        $log_zerado = new \Adianti\Widget\Form\TRadioGroup('log_zerado');
        $log_platinado = new \Adianti\Widget\Form\TRadioGroup('log_platinado');
        $log_goty = new \Adianti\Widget\Form\TRadioGroup('log_goty');
        $console_id = new \Adianti\Widget\Wrapper\TDBUniqueSearch('console_id', 'sketchlog', 'Console', 'id', 'nome');
        $conquistas_totais = new \Adianti\Widget\Form\TEntry('conquistas_totais');
        $conquistas_feitas = new \Adianti\Widget\Form\TEntry('conquistas_feitas');
        $review = new \Adianti\Widget\Form\TText('review');

        $id->setEditable(FALSE);
        $id->setSize("100%");

        $button = new TActionLink('', new TAction(['NotaFormWindow', 'onEdit']), 'green', null, null, 'fa:plus-circle');
        $button->class = 'btn btn-default inline-button';
        $button->title = _t('New');
        $nota_id->after($button);

        $crit = new TCriteria;
        $crit->setProperty('order', 'valor');

        $nota_id->setProperty('criteria', $crit);

        $nota_id->setSize('calc(100% - 40px)');
        $nota_id->setMinLength(0);
        $nota_id->setMask('{valor} - {descricao}');

        $button = new TActionLink('', new TAction(['DificuldadeFormWindow', 'onEdit']), 'green', null, null, 'fa:plus-circle');
        $button->class = 'btn btn-default inline-button';
        $button->title = _t('New');
        $dificuldade_id->after($button);

        $dificuldade_id->setSize('calc(100% - 40px)');
        $dificuldade_id->setMinLength(0);
        $dificuldade_id->setMask('{valor} - {descricao}');

        $button = new TActionLink('', new TAction(['ConsoleFormWindow', 'onEdit']), 'green', null, null, 'fa:plus-circle');
        $button->class = 'btn btn-default inline-button';
        $button->title = _t('New');
        $console_id->after($button);

        $console_id->setSize('calc(100% - 40px)');
        $console_id->setMinLength(0);

        $sketch_id->setMinLength(0);
        $sketch_id->setSize('100%');

        $jogo_id->setMinLength(0);
        $jogo_id->setSize('100%');

        $dataAtual = new DateTime();
        $dt_hr_ini->setMask('dd/mm/yyyy hh:mm:ss');
        $dt_hr_ini->setDatabaseMask('yyyy-mm-dd hh:mm:ss');
        $dt_hr_ini->setValue($dataAtual->format('d/m/Y H:i:s'));

        $dt_hr_fim->setMask('dd/mm/yyyy hh:mm:ss');
        $dt_hr_fim->setDatabaseMask('yyyy-mm-dd hh:mm:ss');

        $tempo->setSize('30%');
        $tempo->setMask('999');

        $log_replay->setBooleanMode();
        $log_platinado->setBooleanMode();
        $log_goty->setBooleanMode();
        $log_zerado->setBooleanMode();

        $conquistas_feitas->setMask('999');
        $conquistas_feitas->setSize('30%');

        $conquistas_totais->setMask('999');
        $conquistas_totais->setSize('30%');

        $review->setSize('100%', 503);

        $row1 = $this->form->addFields([new TLabel('ID', null, '14px', null, "100%"), $id], []);
        $row1->layout = ['col-sm-6','col-sm-6'];
        $row2 = $this->form->addFields([new TLabel('Sketch', null, '14px', null, "100%"), $sketch_id], [new TLabel('Jogo', null, '14px', null, "100%"), $jogo_id]);
        $row2->layout = ['col-sm-6','col-sm-6'];
        $row3 = $this->form->addFields([new TLabel('Data de inicio', null, '14px', null, "100%"), $dt_hr_ini], [new TLabel('Data de Fim', null, '14px', null, "100%"), $dt_hr_fim]);
        $row3->layout = ['col-sm-6','col-sm-6'];
        $row4 = $this->form->addFields([new TLabel('Tempo de Jogo', null, '14px', null, "100%"), $tempo], [new TLabel('Nota', null, '14px', null, "100%"), $nota_id]);
        $row4->layout = ['col-sm-6','col-sm-6'];
        $row4 = $this->form->addFields([new TLabel('Dificuldade', null, '14px', null, "100%"), $dificuldade_id], [new TLabel('Console', null, '14px', null, "100%"), $console_id]);
        $row4->layout = ['col-sm-6','col-sm-6'];
        $row5 = $this->form->addFields([new TLabel('Replay?', null, '14px', null, "100%"), $log_replay], [new TLabel('Zerado?', null, '14px', null, "100%"), $log_zerado]);
        $row5->layout = ['col-sm-6','col-sm-6'];
        $row6 = $this->form->addFields([new TLabel('Platinado?', null, '14px', null, "100%"), $log_platinado], [new TLabel('GOTY?', null, '14px', null, "100%"), $log_goty]);
        $row6->layout = ['col-sm-6','col-sm-6'];
        $row7 = $this->form->addFields([new TLabel('Conquistas Feitas', null, '14px', null, "100%"), $conquistas_feitas], [new TLabel('Conquistas Totais', null, '14px', null, "100%"), $conquistas_totais]);
        $row7->layout = ['col-sm-6','col-sm-6'];

        $this->form->appendPage('Review');

        $row8 = $this->form->addFields([new TLabel('Review', null, '14px', null, "100%"), $review]);
        //$row8->layout = ['col-sm-6','col-sm-6'];

        $this->form->addAction('Salvar', new TAction(array($this, 'onSave')), 'far:check-circle green');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);
    }

    public function onSave($param)
    {
        try {
            $data = $this->form->getData();

            TTransaction::open(self::$database);

            $obj = new Log();
            $obj->fromArray((array) $data);

            $obj->store();

            TTransaction::close();

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            AdiantiCoreApplication::loadPage('LogList', 'onReload');
        }catch (Exception $e){
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }

    }

    public function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open(self::$database); // open a transaction

                $object = new Log($key); // instantiates the Active Recor

                $this->form->setData($object); // fill the form

                TTransaction::close(); // close the transaction
            }
            else
            {
                $this->form->clear();
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }

    public function onShow($param = null)
    {

    }
}