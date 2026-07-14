<?php

use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Wrapper\TDBMultiCombo;

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
        $distribuidora_id = new TDBMultiCombo('distribuidora_id', 'sketchlog', 'Distribuidora', 'id', 'nome');
        $desenvolvedor_id = new TDBMultiCombo('desenvolvedor_id', 'sketchlog', 'Desenvolvedor', 'id', 'nome');
        $dt_publicacao = new TDate('dt_publicacao');
        $capa = new TFile('capa');
        $genero_id = new TDBMultiCombo('genero_id', 'sketchlog', 'Genero', 'id', 'nome');

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

        $capa->setAllowedExtensions( ['png', 'jpg', 'jpeg'] );
        $capa->enableImageGallery();

        $dt_publicacao->setMask('mm/yyyy');
        $dt_publicacao->setDatabaseMask('yyyy-mm');

        $row1 = $this->form->addFields([new TLabel('ID', null, '14px', null, "100%"), $id], [new TLabel('Nome', null, '14px', null, "100%"), $nome]);
        $row1->layout = ['col-sm-6','col-sm-6'];
        $row2 = $this->form->addFields([new TLabel('Distribuidora', null, '14px', null, "100%"), $distribuidora_id], [new TLabel('Desenvolvedor', null, '14px', null, "100%"), $desenvolvedor_id]);
        $row2->layout = ['col-sm-6','col-sm-6'];
        $row3 = $this->form->addFields([new TLabel('Data de Publicação', null, '14px', null, "100%"), $dt_publicacao], [new TLabel('Capa', null, '14px', null, "100%"), $capa]);
        $row3->layout = ['col-sm-6','col-sm-6'];
        $row4 = $this->form->addFields([new TLabel('Gênero', null, '14px', null, "100%"), $genero_id], []);
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

            $generos = (array) ($data->genero_id ?? []);
            $desenvolvedores = (array) ($data->desenvolvedor_id ?? []);
            $distribuidoras = (array) ($data->distribuidora_id ?? []);

            unset($data->genero_id, $data->desenvolvedor_id, $data->distribuidora_id);

            TTransaction::open(self::$database);

            $obj = new Jogo($data->id ?? null);
            $obj->fromArray((array) $data);

            $obj->dt_publicacao = $obj->dt_publicacao . "-01";

            $obj->store();

            JogoGeneros::where('jogo_id', '=', $obj->id)->delete();
            JogoDesenvolvedores::where('jogo_id', '=', $obj->id)->delete();
            JogoDistribuidoras::where('jogo_id', '=', $obj->id)->delete();

            foreach ($generos as $genero_id) {
                $jogo_genero = new JogoGeneros();
                $jogo_genero->jogo_id = $obj->id;
                $jogo_genero->genero_id = $genero_id;
                $jogo_genero->store();
            }

            foreach ($desenvolvedores as $desenvolvedor_id) {
                $jogo_desenvolvedor = new JogoDesenvolvedores();
                $jogo_desenvolvedor->jogo_id = $obj->id;
                $jogo_desenvolvedor->desenvolvedor_id = $desenvolvedor_id;
                $jogo_desenvolvedor->store();
            }

            foreach ($distribuidoras as $distribuidora_id) {
                $jogo_distribuidora = new JogoDistribuidoras();
                $jogo_distribuidora->jogo_id = $obj->id;
                $jogo_distribuidora->distribuidora_id = $distribuidora_id;
                $jogo_distribuidora->store();
            }

            TTransaction::close();

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            AdiantiCoreApplication::loadPage('JogoList', 'onReload');
        } catch (Exception $e) {
            TTransaction::rollback();

            if (isset($data)) {
                $data->genero_id = $generos ?? [];
                $data->desenvolvedor_id = $desenvolvedores ?? [];
                $data->distribuidora_id = $distribuidoras ?? [];

                $this->form->setData($data);
            }

            new TMessage('error', $e->getMessage());
            $this->form->setData($this->form->getData());
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

                $object = new Jogo($key); // instantiates the Active Record

                $object->genero_id = JogoGeneros::where('jogo_id', '=', $key)
                    ->getIndexedArray('genero_id', 'genero_id');

                $object->desenvolvedor_id = JogoDesenvolvedores::where('jogo_id', '=', $key)
                    ->getIndexedArray('desenvolvedor_id', 'desenvolvedor_id');

                $object->distribuidora_id = JogoDistribuidoras::where('jogo_id', '=', $key)
                    ->getIndexedArray('distribuidora_id', 'distribuidora_id');

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