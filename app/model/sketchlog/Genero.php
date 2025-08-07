<?php

class Genero extends TRecord
{
    const TABLENAME  = 'genero';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial'; // {max, serial}

    private $icone;
    public function __construct ($id = null)
    {
        parent::__construct($id);
        parent::addAttribute('nome');
        parent::addAttribute('icone_id');
    }

    public function getIcone()
    {
        return $this->icone;
    }

    public function setIcone ($icone)
    {
        $this->icone = $icone;
    }
}