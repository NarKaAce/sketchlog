<?php

class Capa extends TRecord
{
    const TABLENAME  = 'capa';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial'; // {max, serial}

    public function __construct ($id = null)
    {
        parent::__construct($id);
        parent::addAttribute('nome');
        parent::addAttribute('imagem');
    }
}