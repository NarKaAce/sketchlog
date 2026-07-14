<?php

class Jogo extends TRecord
{
    const TABLENAME  = 'jogo';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial'; // {max, serial}

    private $jogo_desenvolvedores;
    private $jogo_distribuidoras;
    private $jogo_generos;

    public function __construct ($id = null)
    {
        parent::__construct($id);
        parent::addAttribute('nome');
        parent::addAttribute('dt_publicacao');
        parent::addAttribute('capa');
        parent::addAttribute('steam_appid');
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

    public function get_jogo_desenvolvedores()
    {
        if (empty($this->jogo_desenvolvedores))
        {
            $criteria = new TCriteria;
            $criteria->add(new TFilter('jogo_id', '=', $this->id));

            $repository = new TRepository('JogoDesenvolvedores');
            $this->jogo_desenvolvedores = $repository->load($criteria);
        }

        return $this->jogo_desenvolvedores;
    }

    public function get_nomes_desenvolvedores()
    {
        $nomes = [];

        $jogo_desenvolvedores = $this->get_jogo_desenvolvedores();

        if ($jogo_desenvolvedores)
        {
            foreach ($jogo_desenvolvedores as $item)
            {
                if ($item->desenvolvedor)
                {
                    $nomes[] = $item->desenvolvedor->nome;
                }
            }
        }

        return implode(', ', $nomes);
    }

    public function get_jogo_distribuidoras()
    {
        if (empty($this->jogo_distribuidoras))
        {
            $criteria = new TCriteria;
            $criteria->add(new TFilter('jogo_id', '=', $this->id));

            $repository = new TRepository('JogoDistribuidoras');
            $this->jogo_distribuidoras = $repository->load($criteria);
        }

        return $this->jogo_distribuidoras;
    }

    public function get_nomes_distribuidoras()
    {
        $nomes = [];

        $jogo_distribuidoras = $this->get_jogo_distribuidoras();

        if ($jogo_distribuidoras)
        {
            foreach ($jogo_distribuidoras as $item)
            {
                if ($item->distribuidora)
                {
                    $nomes[] = $item->distribuidora->nome;
                }
            }
        }

        return implode(', ', $nomes);
    }

    public function get_jogo_generos()
    {
        if (empty($this->jogo_generos))
        {
            $criteria = new TCriteria;
            $criteria->add(new TFilter('jogo_id', '=', $this->id));

            $repository = new TRepository('JogoGeneros');
            $this->jogo_generos = $repository->load($criteria);
        }

        return $this->jogo_generos;
    }

    public function get_nomes_generos()
    {
        $nomes = [];

        $jogo_generos = $this->get_jogo_generos();

        if ($jogo_generos)
        {
            foreach ($jogo_generos as $item)
            {
                if ($item->genero)
                {
                    $nomes[] = $item->genero->nome;
                }
            }
        }

        return implode(', ', $nomes);
    }
}