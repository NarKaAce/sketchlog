<?php

class Jogo extends TRecord
{
    const TABLENAME  = 'jogo';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial'; // {max, serial}

    private $distribuidora;
    private $desenvolvedor;
    private $capa;
    private $genero;
    private $tipo;

    public function __construct ($id = null)
    {
        parent::__construct($id);
        parent::addAttribute('nome');
        parent::addAttribute('distribuidora_id');
        parent::addAttribute('deselvolvedor_id');
        parent::addAttribute('dt_publicacao');
        parent::addAttribute('capa_id');
        parent::addAttribute('genero_id');
        parent::addAttribute('tipo_id');
    }

    public function setDistribuidora($distribuidora)
    {
        $this->distribuidora = $distribuidora;
    }

    public function getDistribuidora()
    {
        return $this->distribuidora;
    }

    public function setDesenvolvedor($desenvolvedor)
    {
        $this->desenvolvedor = $desenvolvedor;
    }

    public function getDesenvolvedor()
    {
        return $this->desenvolvedor;
    }

    public function setCapa($capa)
    {
        $this->capa = $capa;
    }

    public function getCapa()
    {
        return $this->capa;
    }

    public function setGenero($genero)
    {
        $this->genero = $genero;
    }

    public function getGenero()
    {
        return $this->genero;
    }

    public function setTipo($tipo)
    {
        $this->tipo = $tipo;
    }

    public function getTipo()
    {
        return $this->tipo;
    }
}