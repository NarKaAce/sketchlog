<?php

use Adianti\Widget\Datagrid\TDataGridColumn;

class JogoList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'sketchlog';
    private static $activeRecord = 'Jogo';
    private static $primaryKey = 'id';
    private static $formName = 'form_JogoList';
    private $limit = 20;

    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct($param = null)
    {
        parent::__construct();

        if (!empty($param['target_container'])) {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);

        // define the form title
        $this->form->setFormTitle("Jogo");
        $this->limit = 20;

        $nome = new TEntry('nome');
        $distribuidora_id = new \Adianti\Widget\Wrapper\TDBUniqueSearch('distribuidora_id', 'sketchlog', 'Distribuidora', 'id', 'nome');
        $desenvolvedor_id = new \Adianti\Widget\Wrapper\TDBUniqueSearch('desenvolvedor_id', 'sketchlog', 'Desenvolvedor', 'id', 'nome');
        $genero_id = new \Adianti\Widget\Wrapper\TDBUniqueSearch('genero_id', 'sketchlog', 'Genero', 'id', 'nome');
        $tipo_id = new \Adianti\Widget\Wrapper\TDBUniqueSearch('tipo_id', 'sketchlog', 'Tipo', 'id', 'nome');

        $nome->setSize('100%');

        $distribuidora_id->setMinLength(0);
        $distribuidora_id->setSize('100%');

        $desenvolvedor_id->setMinLength(0);
        $desenvolvedor_id->setSize('100%');

        $genero_id->setMinLength(0);
        $genero_id->setSize('100%');

        $tipo_id->setMinLength(0);
        $tipo_id->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Nome:", null, '14px', null, '100%'), $nome], [new TLabel("Distribuidora:", null, '14px', null, '100%'), $distribuidora_id]);
        $row1->layout = ['col-sm-6', 'col-sm-6'];
        $row2 = $this->form->addFields([new TLabel("Desenvolvedor:", null, '14px', null, '100%'), $desenvolvedor_id], [new TLabel("Genero:", null, '14px', null, '100%'), $genero_id]);
        $row2->layout = ['col-sm-6', 'col-sm-6'];
        $row3 = $this->form->addFields([new TLabel("Tipo:", null, '14px', null, '100%'), $tipo_id], []);
        $row3->layout = ['col-sm-6', 'col-sm-6'];

        $btn_onsearch = $this->form->addAction("Buscar", new TAction([$this, 'onSearch']), 'fas:search #ffffff');
        $this->btn_onsearch = $btn_onsearch;
        $btn_onsearch->addStyleClass('btn-primary');

        $btn_onshow = $this->form->addAction("Cadastrar", new TAction(['JogoForm', 'onShow']), 'fas:plus #69aa46');
        $this->btn_onshow = $btn_onshow;

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->disableHtmlConversion();
        $this->datagrid->setId(__CLASS__ . '_datagrid');

        $this->datagrid_form = new TForm('datagrid_' . self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->filter_criteria = new TCriteria;

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(250);

        $column_nome = new TDataGridColumn('nome', "Nome", 'left');
        $column_distribuidora = new TDataGridColumn('distribuidora->nome', 'Distribuidora', 'left');
        $column_desenvolvedor = new TDataGridColumn('desenvolvedor->nome', "Desenvolvedor", 'left');
        $column_dt_publicacao = new TDataGridColumn('dt_publicacao', "Data de Publicação", 'left');
        $column_genero = new TDataGridColumn('genero->nome', "Gênero", 'left');
        $column_tipo = new TDataGridColumn('tipo->nome', "Tipo", 'left');

        $column_dt_publicacao->setTransformer(function ($value, $object, $row, $cell = null, $last_row = null) {
            if($value)
            {
                $dados = explode('-',$value);
                return "$dados[1]/$dados[0]";
            }
        });

        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_distribuidora);
        $this->datagrid->addColumn($column_desenvolvedor);
        $this->datagrid->addColumn($column_dt_publicacao);
        $this->datagrid->addColumn($column_genero);
        $this->datagrid->addColumn($column_tipo);

        $action_onEdit = new TDataGridAction(array('JogoForm', 'onEdit'));
        $action_onEdit->setUseButton(false);
        $action_onEdit->setButtonClass('btn btn-default btn-sm');
        $action_onEdit->setLabel("Editar");
        $action_onEdit->setImage('far:edit #478fca');
        $action_onEdit->setField(self::$primaryKey);

        $this->datagrid->addAction($action_onEdit);

        $action_onDelete = new TDataGridAction(array('JogoList', 'onDelete'));
        $action_onDelete->setUseButton(false);
        $action_onDelete->setButtonClass('btn btn-default btn-sm');
        $action_onDelete->setLabel("Excluir");
        $action_onDelete->setImage('fas:trash-alt #dd5a43');
        $action_onDelete->setField(self::$primaryKey);

        $this->datagrid->addAction($action_onDelete);

        // create the datagrid model
        $this->datagrid->createModel();

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup();
        $panel->datagrid = 'datagrid-container';
        $this->datagridPanel = $panel;
        $this->datagrid_form->add($this->datagrid);
        $panel->add($this->datagrid_form);

        $panel->getBody()->class .= ' table-responsive';

        $panel->addFooter($this->pageNavigation);

        $headerActions = new TElement('div');
        $headerActions->class = ' datagrid-header-actions ';
        $headerActions->style = 'justify-content: space-between;';

        $head_left_actions = new TElement('div');
        $head_left_actions->class = ' datagrid-header-actions-left-actions ';

        $head_right_actions = new TElement('div');
        $head_right_actions->class = ' datagrid-header-actions-left-actions ';

        $headerActions->add($head_left_actions);
        $headerActions->add($head_right_actions);

        $panel->getBody()->insert(0, $headerActions);

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';

        $container->add($this->form);
        $container->add($panel);

        parent::add($container);

    }

    public function onDelete($param = null)
    {
        if (isset($param['delete']) && $param['delete'] == 1) {
            try {
                // get the paramseter $key
                $key = $param['key'];
                // open a transaction with database
                TTransaction::open(self::$database);

                // instantiates object
                $object = new Jogo($key, FALSE);

                // deletes the object from the database
                $object->delete();

                // close the transaction
                TTransaction::close();

                // reload the listing
                $this->onReload($param);
                // shows the success message
                TToast::show('success', AdiantiCoreTranslator::translate('Record deleted'), 'topRight', 'far:check-circle');
            } catch (Exception $e) // in case of exception
            {
                // shows the exception error message
                new TMessage('error', $e->getMessage());
                // undo all pending operations
                TTransaction::rollback();
            }
        } else {
            // define the delete action
            $action = new TAction(array($this, 'onDelete'));
            $action->setParameters($param); // pass the key paramseter ahead
            $action->setParameter('delete', 1);
            // shows a dialog to the user
            new TQuestion(AdiantiCoreTranslator::translate('Do you really want to delete ?'), $action);
        }
    }

    /**
     * Register the filter in the session
     */
    public function onSearch($param = null)
    {
        $data = $this->form->getData();
        $filters = [];

        TSession::setValue(__CLASS__ . '_filter_data', NULL);
        TSession::setValue(__CLASS__ . '_filters', NULL);

        if (isset($data->nome) and ((is_scalar($data->nome) and $data->nome !== '') or (is_array($data->nome) and (!empty($data->nome))))) {

            $filters[] = new TFilter('nome', 'ilike', "%{$data->nome}%");// create the filter
        }

        if (isset($data->distribuidora_id) and ((is_scalar($data->distribuidora_id) and $data->distribuidora_id !== '') or (is_array($data->distribuidora_id) and (!empty($data->distribuidora_id))))) {

            $filters[] = new TFilter('distribuidora_id', '=', $data->distribuidora_id);// create the filter
        }

        if (isset($data->desenvolvedor_id) and ((is_scalar($data->desenvolvedor_id) and $data->desenvolvedor_id !== '') or (is_array($data->desenvolvedor_id) and (!empty($data->desenvolvedor_id))))) {

            $filters[] = new TFilter('desenvolvedor_id', '=', $data->desenvolvedor_id);// create the filter
        }

        if (isset($data->genero_id) and ((is_scalar($data->genero_id) and $data->genero_id !== '') or (is_array($data->genero_id) and (!empty($data->genero_id))))) {

            $filters[] = new TFilter('genero_id', '=', $data->genero_id);// create the filter
        }

        if (isset($data->tipo_id) and ((is_scalar($data->tipo_id) and $data->tipo_id !== '') or (is_array($data->tipo_id) and (!empty($data->tipo_id))))) {

            $filters[] = new TFilter('tipo_id', '=', $data->tipo_id);// create the filter
        }

        // fill the form with data again
        $this->form->setData($data);

        // keep the search data in the session
        TSession::setValue(__CLASS__ . '_filter_data', $data);
        TSession::setValue(__CLASS__ . '_filters', $filters);

        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }

    /**
     * Load the datagrid with data
     */
    public function onReload($param = NULL)
    {
        try {
            // open a transaction with database 'composer'
            TTransaction::open(self::$database);

            // creates a repository for PropriedadeEntidade
            $repository = new TRepository(self::$activeRecord);

            $criteria = clone $this->filter_criteria;

            if (empty($param['order'])) {
                $param['order'] = 'id';
            }

            if (empty($param['direction'])) {
                $param['direction'] = 'desc';
            }

            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $this->limit);

            if ($filters = TSession::getValue(__CLASS__ . '_filters')) {
                foreach ($filters as $filter) {
                    $criteria->add($filter);
                }
            }

            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);

            $this->datagrid->clear();
            if ($objects) {
                // iterate the collection of active records
                foreach ($objects as $object) {

                    $row = $this->datagrid->addItem($object);
                    $row->id = "row_{$object->id}";
                    if(!empty($object->capa))
                    {
                        $row->popover = 'true';
                        $row->popside = 'top';
                        $row->popcontent = "<img src='$object->capa' style='max-width:200px;'>";
                        $row->poptitle = 'Item details';
                    }
                }
            }

            // reset the criteria for record count
            $criteria->resetProperties();
            $count = $repository->count($criteria);

            $this->pageNavigation->setCount($count); // count of records
            $this->pageNavigation->setProperties($param); // order, page
            $this->pageNavigation->setLimit($this->limit); // limit

            // close the transaction
            TTransaction::close();
            $this->loaded = true;

            return $objects;
        } catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }

    public function onShow($param = null)
    {

    }

    /**
     * method show()
     * Shows the page
     */
    public function show()
    {
        $this->onReload();
        parent::show();
    }

    public static function manageRow($id, $param = [])
    {
        $list = new self($param);

        $openTransaction = TTransaction::getDatabase() != self::$database ? true : false;

        if ($openTransaction) {
            TTransaction::open(self::$database);
        }

        $object = new Capa($id);

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if ($openTransaction) {
            TTransaction::close();
        }

        TDataGrid::replaceRowById(__CLASS__ . '_datagrid', $row->id, $row);
    }

}

