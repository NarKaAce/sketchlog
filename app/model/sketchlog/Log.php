<?php

class Log extends TRecord
{
    const TABLENAME  = 'log';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';

    private $jogo;
    private $nota;
    private $dificuldade;
    private $console;
    private $sketch;

    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('sketch_id');
        parent::addAttribute('jogo_id');
        parent::addAttribute('data');
        parent::addAttribute('tempo');
        parent::addAttribute('nota_id');
        parent::addAttribute('dificuldade_id');
        parent::addAttribute('log_replay');
        parent::addAttribute('log_zerado');
        parent::addAttribute('log_platinado');
        parent::addAttribute('console_id');
        parent::addAttribute('conquistas_feitas');
        parent::addAttribute('conquistas_totais');
        parent::addAttribute('log_goty');
        parent::addAttribute('usuario_id');
        parent::addAttribute('review');
    }

    // --- Relações (padrão Adianti) ---

    public function set_jogo(Jogo $object)
    {
        $this->jogo = $object;
        $this->jogo_id = $object->id;
    }

    public function get_jogo()
    {
        // carrega sob demanda
        if (empty($this->jogo))
            $this->jogo = new Jogo($this->jogo_id);
        return $this->jogo;
    }

    public function set_nota(Nota $object)
    {
        $this->nota = $object;
        $this->nota_id = $object->id;
    }

    public function get_nota()
    {
        if (empty($this->nota))
            $this->nota = new Nota($this->nota_id);
        return $this->nota;
    }

    public function set_dificuldade(Dificuldade $object)
    {
        $this->dificuldade = $object;
        $this->dificuldade_id = $object->id;
    }

    public function get_dificuldade()
    {
        if (empty($this->dificuldade))
            $this->dificuldade = new Dificuldade($this->dificuldade_id);
        return $this->dificuldade;
    }

    public function set_console(Console $object)
    {
        $this->console = $object;
        $this->console_id = $object->id;
    }

    public function get_console()
    {
        if (empty($this->console))
            $this->console = new Console($this->console_id);
        return $this->console;
    }

    public function set_sketch(Sketch $object)
    {
        $this->sketch = $object;
        $this->sketch_id = $object->id;
    }

    public function get_sketch()
    {
        if (empty($this->sketch))
            $this->sketch = new Sketch($this->sketch_id);
        return $this->sketch;
    }
}
