<?php

use Adianti\Widget\Form\TEntry;

class DificuldadeForm extends TPage
{
    private $form;
    public static $formName = 'form_DificuldadeForm';
    public static $database = 'sketchlog';

    public function __construct($param)
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder;
        $this->form->setFormTitle("Cadastro de Dificuldade");
        $this->form->generateAria(); // automatic aria-label

        $id = new TEntry('id');
        $valor = new TEntry('valor');
        $descricao = new TEntry('descricao');

        $id->setEditable(FALSE);
        $valor->setMask('S!');

        $id->setSize("100%");
        $valor->setSize("100%");
        $descricao->setSize("100%");

        $row1 = $this->form->addFields([new TLabel('ID', null, '14px', null, "100%"), $id], [new TLabel('Valor', null, '14px', null, "100%"), $valor]);
        $row1->layout = ['col-sm-6','col-sm-6'];
        $row2 = $this->form->addFields([new TLabel('Descrição', null, '14px', null, "100%"), $descricao], []);
        $row2->layout = ['col-sm-6','col-sm-6'];

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

            $obj = new Dificuldade();
            $obj->fromArray((array) $data);

            $obj->store();

            TTransaction::close();

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            AdiantiCoreApplication::loadPage('DificuldadeList', 'onReload');
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

                $object = new Dificuldade($key); // instantiates the Active Recor

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