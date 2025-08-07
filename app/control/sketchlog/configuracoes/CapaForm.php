<?php

class CapaForm extends TPage
{
    private $form;
    public static $formName = 'form_CapaForm';
    public static $database = 'sketchlog';

    public function __construct($param)
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder;
        $this->form->setFormTitle("Cadastro de Capa");
        $this->form->generateAria(); // automatic aria-label

        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $imagem = new TFile('imagem');

        $id->setEditable(FALSE);

        $id->setSize("100%");
        $nome->setSize("100%");
        $imagem->setSize("100%");

        $imagem->setAllowedExtensions( ['png', 'jpg', 'jpeg'] );
        $imagem->enableImageGallery();

        $row1 = $this->form->addFields([new TLabel('ID', null, '14px', null, "100%"), $id], [new TLabel('Nome', null, '14px', null, "100%"), $nome]);
        $row1->layout = ['col-sm-6','col-sm-6'];
        $row2 = $this->form->addFields([new TLabel('Imagem', null, '14px', null, "100%"), $imagem], []);
        $row2->layout = ['col-sm-6','col-sm-6'];

        $this->form->addAction('Salvar', new TAction(array($this, 'onSave')), 'far:check-circle green');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        //teste

        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);
    }

    public function onSave($param)
    {
        try {
            $data = $this->form->getData();

            TTransaction::open(self::$database);

            $obj = new Capa();
            $obj->fromArray((array) $data);

            //Caminho
            $targetPath = "app/images/capa/";

            if (!empty($data->imagem)) {
                $source_file = 'tmp/' . $data->imagem;

                if (file_exists($source_file)) {
                    $unique_name = uniqid() . '-' . $data->imagem;
                    $target_file = $targetPath . $unique_name;

                    // Move o arquivo
                    rename($source_file, $target_file);

                    // Salva o caminho no banco
                    $obj->imagem = $target_file;
                }
            }

            $obj->store();

            TTransaction::close();

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            AdiantiCoreApplication::loadPage('CapaList', 'onReload');
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

                $object = new Capa($key); // instantiates the Active Recor

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