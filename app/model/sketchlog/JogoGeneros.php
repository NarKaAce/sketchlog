<?php

class JogoGeneros extends TRecord
{
    const TABLENAME  = 'jogo_generos';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial'; // {max, serial}

    private $genero;
    public function __construct ($id = null)
    {
        parent::__construct($id);
        parent::addAttribute('jogo_id');
        parent::addAttribute('genero_id');
    }

    public function get_genero()
    {
        if (empty($this->genero))
        {
            $this->genero = new Genero($this->genero_id);
        }
        return $this->genero;
    }
}