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
        parent::addAttribute('desenvolvedor_id');
        parent::addAttribute('dt_publicacao');
        parent::addAttribute('capa');
        parent::addAttribute('genero_id');
        parent::addAttribute('tipo_id');
    }

    public function get_distribuidora()
    {
        if (empty($this->distribuidora))
        {
            $this->distribuidora = new Distribuidora($this->distribuidora_id);
        }
        return $this->distribuidora;
    }

    public function get_desenvolvedor()
    {
        if (empty($this->desenvolvedor))
        {
            $this->desenvolvedor = new Desenvolvedor($this->desenvolvedor_id);
        }
        return $this->desenvolvedor;
    }

    public function get_genero()
    {
        if (empty($this->genero))
        {
            $this->genero = new Genero($this->genero_id);
        }
        return $this->genero;
    }

    public function get_tipo()
    {
        if (empty($this->tipo))
        {
            $this->tipo = new Tipo($this->tipo_id);
        }
        return $this->tipo;
    }

    public function onBeforeStore($object)
    {
        $data = $object;
        //Caminho
        $targetPath = "app/images/capa/";

        if (!empty($data->capa)) {
            $source_file = 'tmp/' . $data->capa;

            if (file_exists($source_file)) {
                $unique_name = uniqid() . '-' . $data->capa;
                $target_file = $targetPath . $unique_name;

                // Move o arquivo
                rename($source_file, $target_file);

                // Salva o caminho no banco
                $object->capa = $target_file;
            }
        }
    }
}