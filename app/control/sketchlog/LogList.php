<?php

use Adianti\Widget\Datagrid\TDataGridColumn;

class LogList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'sketchlog';
    private static $activeRecord = 'Log';
    private static $primaryKey = 'id';
    private static $formName = 'form_LogList';
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
        $this->form->setFormTitle("Log");
        $this->limit = 20;

        $jogo_id = new \Adianti\Widget\Wrapper\TDBUniqueSearch('jogo_id', 'sketchlog', 'Jogo', 'id', 'nome');
        $tempo = new \Adianti\Widget\Form\TEntry('tempo');
        $nota_id = new \Adianti\Widget\Wrapper\TDBUniqueSearch('nota_id', 'sketchlog', 'Nota', 'id', 'descricao');
        $dificuldade_id = new \Adianti\Widget\Wrapper\TDBUniqueSearch('dificuldade_id', 'sketchlog', 'Dificuldade', 'id', 'descricao');
        $console_id = new \Adianti\Widget\Wrapper\TDBUniqueSearch('console_id', 'sketchlog', 'Console', 'id', 'nome');
        $conquistas = new \Adianti\Widget\Form\TEntry('conquistas_feitas');
        $log_replay = new \Adianti\Widget\Form\TRadioGroup('log_replay');
        $log_zerado = new \Adianti\Widget\Form\TRadioGroup('log_zerado');
        $log_platinado = new \Adianti\Widget\Form\TRadioGroup('log_platinado');
        $log_goty = new \Adianti\Widget\Form\TRadioGroup('log_goty');

        $tempo->setMask('999');

        $jogo_id->setMinLength(0);
        $jogo_id->setSize('100%');

        $nota_id->setMinLength(0);
        $nota_id->setSize('100%');

        $dificuldade_id->setMinLength(0);
        $dificuldade_id->setSize('100%');

        $console_id->setMinLength(0);
        $console_id->setSize('100%');

        $conquistas->setMask('9!');

        $log_replay->setBooleanMode();
        $log_zerado->setBooleanMode();
        $log_platinado->setBooleanMode();
        $log_goty->setBooleanMode();

        $row1 = $this->form->addFields([new TLabel("Jogo:", null, '14px', null, '100%'), $jogo_id], [new TLabel("Tempo:", null, '14px', null, '100%'),$tempo]);
        $row1->layout = ['col-sm-6', 'col-sm-6'];
        $row2 = $this->form->addFields([new TLabel("Nota:", null, '14px', null, '100%'), $nota_id], [new TLabel("Dificuldade:", null, '14px', null, '100%'),$dificuldade_id]);
        $row2->layout = ['col-sm-6', 'col-sm-6'];
        $row3 = $this->form->addFields([new TLabel("Console:", null, '14px', null, '100%'), $console_id], [new TLabel("Conquistas:", null, '14px', null, '100%'),$conquistas]);
        $row3->layout = ['col-sm-6', 'col-sm-6'];
        $row4 = $this->form->addFields([new TLabel("Replay?", null, '14px', null, '100%'), $log_replay], [new TLabel("Zerado?", null, '14px', null, '100%'),$log_zerado]);
        $row4->layout = ['col-sm-6', 'col-sm-6'];
        $row5 = $this->form->addFields([new TLabel("Platinado?", null, '14px', null, '100%'), $log_platinado], [new TLabel("GOTY?", null, '14px', null, '100%'),$log_goty]);
        $row5->layout = ['col-sm-6', 'col-sm-6'];

        $btn_onsearch = $this->form->addAction("Buscar", new TAction([$this, 'onSearch']), 'fas:search #ffffff');
        $this->btn_onsearch = $btn_onsearch;
        $btn_onsearch->addStyleClass('btn-primary');

        $btn_onshow = $this->form->addAction("Cadastrar", new TAction(['LogForm', 'onShow']), 'fas:plus #69aa46');
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

        $column_jogo = new TDataGridColumn('jogo->nome', "Jogo", 'left');
        $column_tempo = new TDataGridColumn('tempo', "Tempo", 'left');
        $column_nota = new TDataGridColumn('nota->descricao', "Nota", 'left');
        $column_dificuldade = new TDataGridColumn('dificuldade->descricao', "Dificuldade", 'left');
        $column_console = new TDataGridColumn('console->nome', "Console", 'left');
        $column_conquista = new TDataGridColumn('id', "Conquistas", 'left');

        $column_tempo->setTransformer(function ($value, $object, $row, $cell = null, $last_row = null) {
            if($value)
            {
                return "$value".'h';
            }
        });

        $column_conquista->setTransformer(function ($value, $object, $row, $cell = null, $last_row = null) {
            if($value)
            {
                $log = Log::where('id', '=', $value)->first();

                return "$log->conquistas_feitas".' de '."$log->conquistas_totais";
            }
        });

        $this->datagrid->addColumn($column_jogo);
        $this->datagrid->addColumn($column_tempo);
        $this->datagrid->addColumn($column_nota);
        $this->datagrid->addColumn($column_dificuldade);
        $this->datagrid->addColumn($column_console);
        $this->datagrid->addColumn($column_conquista);

        $action_onEdit = new TDataGridAction(array('LogForm', 'onEdit'));
        $action_onEdit->setUseButton(false);
        $action_onEdit->setButtonClass('btn btn-default btn-sm');
        $action_onEdit->setLabel("Editar");
        $action_onEdit->setImage('far:edit #478fca');
        $action_onEdit->setField(self::$primaryKey);

        $this->datagrid->addAction($action_onEdit);

        $action_onDelete = new TDataGridAction(array('LogList', 'onDelete'));
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
                $object = new Log($key, FALSE);

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

        if (isset($data->jogo_id) and ((is_scalar($data->jogo_id) and $data->jogo_id !== '') or (is_array($data->jogo_id) and (!empty($data->jogo_id))))) {

            $filters[] = new TFilter('jogo_id', '=', $data->jogo_id);// create the filter
        }

        if (isset($data->tempo) and ((is_scalar($data->tempo) and $data->tempo !== '') or (is_array($data->tempo) and (!empty($data->tempo))))) {

            $filters[] = new TFilter('tempo', '>=', $data->tempo);// create the filter
        }

        if (isset($data->nota_id) and ((is_scalar($data->nota_id) and $data->nota_id !== '') or (is_array($data->nota_id) and (!empty($data->nota_id))))) {

            $filters[] = new TFilter('nota_id', '=', $data->nota_id);// create the filter
        }

        if (isset($data->dificuldade_id) and ((is_scalar($data->dificuldade_id) and $data->dificuldade_id !== '') or (is_array($data->dificuldade_id) and (!empty($data->dificuldade_id))))) {

            $filters[] = new TFilter('dificuldade_id', '=', $data->dificuldade_id);// create the filter
        }

        if (isset($data->console_id) and ((is_scalar($data->console_id) and $data->console_id !== '') or (is_array($data->console_id) and (!empty($data->console_id))))) {

            $filters[] = new TFilter('console_id', '=', $data->console_id);// create the filter
        }

        if (isset($data->conquistas_feitas) and ((is_scalar($data->conquistas_feitas) and $data->conquistas_feitas !== '') or (is_array($data->conquistas_feitas) and (!empty($data->conquistas_feitas))))) {

            $filters[] = new TFilter('conquistas_feitas', '=', $data->conquistas_feitas);// create the filter
        }

        if (isset($data->log_replay) and ((is_scalar($data->log_replay) and $data->log_replay !== '') or (is_array($data->log_replay) and (!empty($data->log_replay))))) {

            $filters[] = new TFilter('log_replay', '=', $data->log_replay);// create the filter
        }

        if (isset($data->log_zerado) and ((is_scalar($data->log_zerado) and $data->log_zerado !== '') or (is_array($data->log_zerado) and (!empty($data->log_zerado))))) {

            $filters[] = new TFilter('log_zerado', '=', $data->log_zerado);// create the filter
        }

        if (isset($data->log_platinado) and ((is_scalar($data->log_platinado) and $data->log_platinado !== '') or (is_array($data->log_platinado) and (!empty($data->log_platinado))))) {

            $filters[] = new TFilter('log_platinado', '=', $data->log_platinado);// create the filter
        }

        if (isset($data->log_goty) and ((is_scalar($data->log_goty) and $data->log_goty !== '') or (is_array($data->log_goty) and (!empty($data->log_goty))))) {

            $filters[] = new TFilter('log_goty', '=', $data->log_goty);// create the filter
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

                    if(!empty($object->sketch_id))
                    {
                        $sketch = Sketch::where('id', '=', $object->sketch_id)->first();
                        $row->popover = 'true';
                        $row->popside = 'top';
                        $row->popcontent = "<img src='$sketch->imagem' style='max-width:200px;'>";
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

