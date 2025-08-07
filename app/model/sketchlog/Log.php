<?php

class Log extends TRecord
{
    const TABLENAME  = 'log';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial'; // {max, serial}

    private $sketch;
    private $jogo;
    private $nota;
    private $dificuldade;
    private $console;


    public function __construct ($id = null)
    {
        parent::__construct($id);
        parent::addAttribute('nome');
        parent::addAttribute('sketch_id');
        parent::addAttribute('jogo_id');
        parent::addAttribute('dt_hr_ini');
        parent::addAttribute('dt_hr_fim');
        parent::addAttribute('tempo');
        parent::addAttribute('nota_id');
        parent::addAttribute('dificuldade_id');
        parent::addAttribute('log_replay');
        parent::addAttribute('log_zerado');
        parent::addAttribute('log_platinado');
        parent::addAttribute('console_id');
        parent::addAttribute('conquista');
        parent::addAttribute('log_goty');
        parent::addAttribute('usuario_id');
        parent::addAttribute('review');
    }

    public function getSketch ()
    {
        return $this->sketch;
    }

    public function setSketch ($sketch)
    {
        $this->sketch = $sketch;
    }

    public function getJogo ()
    {
        return $this->jogo;
    }

    public function setJogo ($jogo)
    {
        $this->jogo = $jogo;
    }

    public function getNota()
    {
        return $this->nota;
    }

    public function setNota($nota)
    {
        $this->nota = $nota;
    }

    public function getDificuldade ()
    {
        return $this->dificuldade;
    }

    public function setDificuldade ($dificuldade)
    {
        $this->dificuldade = $dificuldade;
    }

    public function getConsole ()
    {
        return $this->console;
    }

    public function setConsole ($console)
    {
        $this->console = $console;
    }
}