<?php


namespace Adianti\Widget\Form;

use Adianti\Database\TTransaction;
use Adianti\Widget\Form\TCombo;
use Icone;

class SIconesCombo extends TCombo
{
    public function __construct($name)
    {
        parent::__construct($name);

        TTransaction::open('sketchlog');

        $icons = Icone::getIndexedArray('id', 'imagem');

        TTransaction::close();

        $items = [];
        foreach ($icons as $key => $path)
        {
            $items[$key] = "<img src='{$path}' style='width:16px; vertical-align:middle; margin-right:6px;'>";
        }

        $this->addItems($items);
        $this->enableSearch();
        $this->setProperty('escape', 'false');
    }
}