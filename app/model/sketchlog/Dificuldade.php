<?php

class Dificuldade extends TRecord
{
    const TABLENAME  = 'dificuldade';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial'; // {max, serial}

    public function __construct ($id = null)
    {
        parent::__construct($id);
        parent::addAttribute('valor');
        parent::addAttribute('descricao');
    }
}