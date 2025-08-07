<?php

class Desenvolvedor extends TRecord
{
    const TABLENAME  = 'desenvolvedor';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial'; // {max, serial}

    public function __construct ($id = null)
    {
        parent::__construct($id);
        parent::addAttribute('nome');
    }
}