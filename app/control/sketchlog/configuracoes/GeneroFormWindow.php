<?php

class GeneroFormWindow extends TWindow
{
    use Adianti\Base\AdiantiStandardFormTrait;
    public function __construct($param)
    {
        parent::__construct($param);
        parent::setSize(0.5, null);
        parent::setMinWidth(0.5, 700);
        parent::removePadding();
        parent::disableEscape();
        parent::setTitle('Cadastro de Gênero');

        $this->form = new GeneroForm(['window' => 1]);

        parent::add($this->form);
    }

    /**
     * Redirect calls to decorated object
     */
    public function onEdit($param)
    {
        $this->form->onEdit($param);
    }

    public static function onClose()
    {
        parent::closeWindow();
    }
}