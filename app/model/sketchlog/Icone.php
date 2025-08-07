<?php

class Icone extends TRecord
{
    const TABLENAME  = 'icone';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial'; // {max, serial}

    public function __construct ($id = null)
    {
        parent::__construct($id);
        parent::addAttribute('nome');
        parent::addAttribute('imagem');
    }
}