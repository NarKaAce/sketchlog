<?php

class Nota extends TRecord
{
    const TABLENAME  = 'nota';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial'; // {max, serial}

    public function __construct ($id = null)
    {
        parent::__construct($id);
        parent::addAttribute('valor');
        parent::addAttribute('descricao');
    }
}