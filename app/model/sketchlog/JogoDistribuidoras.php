<?php

class JogoDistribuidoras extends TRecord
{
    const TABLENAME  = 'jogo_distribuidoras';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial'; // {max, serial}

    private $distribuidora;

    public function __construct ($id = null)
    {
        parent::__construct($id);
        parent::addAttribute('jogo_id');
        parent::addAttribute('distribuidora_id');
    }

    public function get_distribuidora()
    {
        if (empty($this->distribuidora))
        {
            $this->distribuidora = new Distribuidora($this->distribuidora_id);
        }
        return $this->distribuidora;
    }
}