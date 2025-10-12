<?php

class Sketch extends TRecord
{
    const TABLENAME  = 'sketch';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial'; // {max, serial}

    public function __construct ($id = null)
    {
        parent::__construct($id);
        parent::addAttribute('nome');
        parent::addAttribute('imagem');
    }

    public function onBeforeStore($object)
    {
        $data = $object;
        $pasta = strtolower(__CLASS__);
        //Caminho
        $targetPath = "app/images/$pasta/";

        if (!empty($data->imagem)) {
            $source_file = 'tmp/' . $data->imagem;

            if (file_exists($source_file)) {
                $unique_name = uniqid() . '-' . $data->imagem;
                $target_file = $targetPath . $unique_name;

                // Move o arquivo
                rename($source_file, $target_file);

                // Salva o caminho no banco
                $object->imagem = $target_file;
            }
        }
    }
}