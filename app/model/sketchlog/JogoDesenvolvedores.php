<?php

class JogoDesenvolvedores extends TRecord
{
    const TABLENAME  = 'jogo_desenvolvedores';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial'; // {max, serial}

    private $desenvolvedor;

    public function __construct ($id = null)
    {
        parent::__construct($id);
        parent::addAttribute('jogo_id');
        parent::addAttribute('desenvolvedor_id');
    }

    public function get_desenvolvedor()
    {
        if (empty($this->desenvolvedor))
        {
            $this->desenvolvedor = new Desenvolvedor($this->desenvolvedor_id);
        }
        return $this->desenvolvedor;
    }
}