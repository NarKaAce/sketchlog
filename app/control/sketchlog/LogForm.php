<?php

use Adianti\Control\TAction;

class LogForm extends TPage
{
    private $form;
    public static $formName = 'form_LogForm';
    public static $database = 'sketchlog';

    public function __construct($param)
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setFormTitle("Cadastro de Log");
        $this->form->generateAria(); // automatic aria-label


        $this->form->appendPage('Dados');

        $id = new TEntry('id');
        $sketch_id = new \Adianti\Widget\Wrapper\TDBUniqueSearch('sketch_id', 'sketchlog', 'Sketch', 'id', 'nome');
        $jogo_id = new \Adianti\Widget\Wrapper\TDBUniqueSearch('jogo_id', 'sketchlog', 'Jogo', 'id', 'nome');
        $tempo = new \Adianti\Widget\Form\TEntry('tempo');
        $nota_id = new \Adianti\Widget\Wrapper\TDBUniqueSearch('nota_id', 'sketchlog', 'Nota', 'id', 'descricao', 'valor desc');
        $dificuldade_id = new \Adianti\Widget\Wrapper\TDBUniqueSearch('dificuldade_id', 'sketchlog', 'Dificuldade', 'id', 'descricao', 'id desc');
        $log_goty = new \Adianti\Widget\Form\TRadioGroup('log_goty');
        $console_id = new \Adianti\Widget\Wrapper\TDBUniqueSearch('console_id', 'sketchlog', 'Console', 'id', 'nome');
        $conquistas_totais = new \Adianti\Widget\Form\TEntry('conquistas_totais');
        $conquistas_feitas = new \Adianti\Widget\Form\TEntry('conquistas_feitas');
        $review = new \Adianti\Widget\Form\TText('review');
        $status_id = new \Adianti\Widget\Form\TUniqueSearch('status_id');
        $dt = new \Adianti\Widget\Form\TEntry('dt');

        $id->setEditable(FALSE);
        $id->setSize("100%");

        $dt->setSize("30%");
        $dt->setValue(date('Y'));

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
        $jogo_id->setChangeAction(new TAction([$this, 'onChangeJogo']));

        $status_id->setMinLength(0);
        $status_id->setSize('100%');
        $status_id->addItems([1 => "Zerado", 2 => "Platinado", 3 => "Dropado", 4 => "Jogando", 5 => "Replay"]);
        $status_id->setChangeAction(new TAction([$this, 'onChangeStatus']));

        $tempo->setSize('30%');
        $tempo->setMask('9999');

        $log_goty->setBooleanMode();

        $conquistas_feitas->setMask('999');
        $conquistas_feitas->setSize('30%');

        $conquistas_totais->setEditable(FALSE);
        $conquistas_totais->setMask('999');
        $conquistas_totais->setSize('30%');

        $review->setSize('100%', 371);

        $row1 = $this->form->addFields([new TLabel('ID', null, '14px', null, "100%"), $id], [new TLabel('Sketch', null, '14px', null, "100%"), $sketch_id]);
        $row1->layout = ['col-sm-6','col-sm-6'];
        $row2 = $this->form->addFields([new TLabel('Jogo', null, '14px', null, "100%"), $jogo_id], [new TLabel('Tempo de Jogo (horas totais)', null, '14px', null, "100%"), $tempo]);
        $row2->layout = ['col-sm-6','col-sm-6'];
        $row4 = $this->form->addFields([new TLabel('Ano do log', null, '14px', null, "100%"), $dt], [new TLabel('Nota', null, '14px', null, "100%"), $nota_id]);
        $row4->layout = ['col-sm-6','col-sm-6'];
        $row4 = $this->form->addFields([new TLabel('Dificuldade', null, '14px', null, "100%"), $dificuldade_id], [new TLabel('Console', null, '14px', null, "100%"), $console_id]);
        $row4->layout = ['col-sm-6','col-sm-6'];
        $row6 = $this->form->addFields([new TLabel('Status', null, '14px', null, "100%"), $status_id], [new TLabel('GOTY?', null, '14px', null, "100%"), $log_goty]);
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

            if((int)$obj->status_id == 2 and !empty($obj->status_id))
            {
                /** @var mixed $obj */
                $obj->conquistas_feitas = $obj->jogo->conquistas_totais;
            }

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

                $object = new Log($key); // instantiates the Active Record

                $array = [];
                $array['jogo_id'] = $object->jogo_id;
                $array['status_id'] = $object->status_id;
                self::onChangeJogo($array);
                self::onChangeStatus($array);

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

    public static function onChangeJogo($param)
    {
        $data = new stdClass;
        $data->conquistas_totais = '';

        if(!empty($param['jogo_id']))
        {
            TTransaction::open(self::$database);

            $jogo = new Jogo($param['jogo_id']);

            if($jogo)
            {
                $data->conquistas_totais = $jogo->conquistas_totais;
            }

            TTransaction::close();
        }

        TForm::sendData(self::$formName, $data);
    }

    public static function onChangeStatus($param)
    {
        if(!empty($param['status_id']) and $param['status_id'] == 2)
        {
            TForm::disableField(self::$formName, 'conquistas_feitas');

            $data = new stdClass;
            $data->conquistas_feitas = '';

            TForm::sendData(self::$formName, $data);
        }else{
            TForm::enableField(self::$formName, 'conquistas_feitas');
        }
    }

    public function onShow($param = null)
    {

    }
}