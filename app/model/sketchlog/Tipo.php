<?php

class Tipo extends TRecord
{
    const TABLENAME  = 'tipo';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial'; // {max, serial}

    private $genero;
    public function __construct ($id = null)
    {
        parent::__construct($id);
        parent::addAttribute('nome');
        parent::addAttribute('icone_id');
        parent::addAttribute('genero_id');
    }

    public function set_genero(Genero $object)
    {
        $this->genero = $object;
        $this->genero_id = $object->id;
    }

    public function get_genero()
    {
        // carrega sob demanda
        if (empty($this->genero))
            $this->genero = new Genero($this->genero_id);
        return $this->genero;
    }
}