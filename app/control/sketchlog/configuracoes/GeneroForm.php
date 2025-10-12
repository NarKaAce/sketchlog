<?php
require_once 'app/widget/form/SIconesCombo.php';

use Adianti\Database\TTransaction;
use Adianti\Widget\Form\SIconesCombo;
use Adianti\Widget\Form\TLabel;

class GeneroForm extends TPage
{
    private $form;
    public static $formName = 'form_GeneroForm';
    public static $database = 'sketchlog';

    public function __construct($param)
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder(self::$formName);
        if(empty($param['window']))
        {
            $this->form->setFormTitle("Cadastro de Gênero");
        }
        $this->form->generateAria(); // automatic aria-label

        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $icone_id = new SIconesCombo('icone_id');

        $id->setEditable(FALSE);

        $id->setSize("100%");
        $nome->setSize("100%");
        $icone_id->setSize("32%");

        $row1 = $this->form->addFields([new TLabel('ID', null, '14px', null, "100%"), $id], [new TLabel('Nome', null, '14px', null, "100%"), $nome]);
        $row1->layout = ['col-sm-6','col-sm-6'];
        $row2 = $this->form->addFields([new TLabel('Icone', null, '14px', null, "100%"), $icone_id], []);
        $row2->layout = ['col-sm-6','col-sm-6'];

        $this->form->addAction('Salvar', new TAction(array($this, 'onSave'), $param), 'far:check-circle green');

        if(empty($param['window']))
        {
            $btnClose = new TButton('closeCurtain');
            $btnClose->class = 'btn btn-sm btn-default';
            $btnClose->style = 'margin-right:10px;';
            $btnClose->onClick = "Template.closeRightPanel();";
            $btnClose->setLabel("Fechar");
            $btnClose->setImage('fas:times');

            $this->form->addHeaderWidget($btnClose);
        }

        parent::add($this->form);
    }

    public function onSave($param)
    {
        try {
            $data = $this->form->getData();

            TTransaction::open(self::$database);

            $obj = new Genero();
            $obj->fromArray((array) $data);

            $obj->store();

            TTransaction::close();

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            if (!empty($param['window'])) {
                TScript::create("
                    if (window.parent) {
                        window.parent.adianti_set_field_value('form_JogoForm', 'genero_id', '{$obj->id}');
                        window.parent.adianti_set_field_display_value('form_JogoForm', 'genero_id', '{$obj->nome}');
                        window.parent.adianti_close_window();
                    }
                ");
            } else {
                AdiantiCoreApplication::loadPage('GeneroList', 'onReload');
            }
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

                $object = new Genero($key); // instantiates the Active Recor

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