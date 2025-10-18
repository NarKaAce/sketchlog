<?php

use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Wrapper\TDBUniqueSearch;

class JogoForm extends TPage
{
    private $form;
    public static $formName = 'form_JogoForm';
    public static $database = 'sketchlog';

    public function __construct($param)
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setFormTitle("Cadastro de Jogo");
        $this->form->generateAria(); // automatic aria-label

        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $distribuidora_id = new TDBUniqueSearch('distribuidora_id', 'sketchlog', 'Distribuidora', 'id', 'nome');
        $desenvolvedor_id = new TDBUniqueSearch('desenvolvedor_id', 'sketchlog', 'Desenvolvedor', 'id', 'nome');
        $dt_publicacao = new TDate('dt_publicacao');
        $capa = new TFile('capa');
        $genero_id = new TDBUniqueSearch('genero_id', 'sketchlog', 'Genero', 'id', 'nome');
        $tipo_id = new TCombo('tipo_id');

        $button = new TActionLink('', new TAction(['DistribuidoraFormWindow', 'onEdit']), 'green', null, null, 'fa:plus-circle');
        $button->class = 'btn btn-default inline-button';
        $button->title = _t('New');
        $distribuidora_id->after($button);

        $button = new TActionLink('', new TAction(['DesenvolvedorFormWindow', 'onEdit']), 'green', null, null, 'fa:plus-circle');
        $button->class = 'btn btn-default inline-button';
        $button->title = _t('New');
        $desenvolvedor_id->after($button);

        $id->setEditable(FALSE);

        $id->setSize("100%");
        $nome->setSize("100%");
        $capa->setSize("100%");
        $distribuidora_id->setSize('calc(100% - 40px)');
        $desenvolvedor_id->setSize('calc(100% - 40px)');
        $genero_id->setSize('100%');
        $tipo_id->setSize('100%');

        $distribuidora_id->setMinLength(0);
        $desenvolvedor_id->setMinLength(0);
        $genero_id->setMinLength(0);

        $capa->setAllowedExtensions( ['png', 'jpg', 'jpeg'] );
        $capa->enableImageGallery();

        $dt_publicacao->setMask('mm/yyyy');
        $dt_publicacao->setDatabaseMask('yyyy-mm');

        $genero_id->setChangeAction( new TAction([$this, 'onChangeGenero'], $param));

        $row1 = $this->form->addFields([new TLabel('ID', null, '14px', null, "100%"), $id], [new TLabel('Nome', null, '14px', null, "100%"), $nome]);
        $row1->layout = ['col-sm-6','col-sm-6'];
        $row2 = $this->form->addFields([new TLabel('Distribuidora', null, '14px', null, "100%"), $distribuidora_id], [new TLabel('Desenvolvedor', null, '14px', null, "100%"), $desenvolvedor_id]);
        $row2->layout = ['col-sm-6','col-sm-6'];
        $row3 = $this->form->addFields([new TLabel('Data de Publicação', null, '14px', null, "100%"), $dt_publicacao], [new TLabel('Capa', null, '14px', null, "100%"), $capa]);
        $row3->layout = ['col-sm-6','col-sm-6'];
        $row4 = $this->form->addFields([new TLabel('Gênero', null, '14px', null, "100%"), $genero_id], [new TLabel('Tipo', null, '14px', null, "100%"), $tipo_id]);
        $row4->layout = ['col-sm-6','col-sm-6'];

        $this->form->addAction('Salvar', new TAction(array($this, 'onSave')), 'far:check-circle green');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);

        TScript::create("$('[name=tipo_id]').closest('.col-sm-6.fb-field-container').hide();");
    }

    public function onSave($param)
    {
        try {
            $data = $this->form->getData();

            TTransaction::open(self::$database);

            $obj = new Jogo();
            $obj->fromArray((array) $data);

            $obj->store();

            TTransaction::close();

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            AdiantiCoreApplication::loadPage('JogoList', 'onReload');
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

                TScript::create("$('[name=tipo_id]').closest('.col-sm-6.fb-field-container').show();");
                $key = $param['key'];  // get the parameter $key
                TTransaction::open(self::$database); // open a transaction

                $object = new Jogo($key); // instantiates the Active Record

                self::onChangeGenero(['genero_id' => $object->genero_id]);

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

    public static function onChangeGenero($param)
    {
        if(!empty($param['genero_id']))
        {
            TScript::create("$('[name=tipo_id]').closest('.col-sm-6.fb-field-container').show();");
            $options = [];
            \Adianti\Database\TTransaction::openFake(self::$database);
            $tipos = Tipo::where('genero_id', '=', $param['genero_id'])->load();
            \Adianti\Database\TTransaction::close();

            if(!empty($tipos))
            {
                foreach ($tipos as $tipo)
                {
                    $options[$tipo->id] = $tipo->nome;
                }
            }

            TCombo::reload(self::$formName, 'tipo_id', $options, true);
        }else{
            TScript::create("$('[name=tipo_id]').closest('.col-sm-6.fb-field-container').hide();");
        }
    }
}