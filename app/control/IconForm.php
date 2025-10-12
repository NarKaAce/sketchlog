<?php
require_once __DIR__ . '/../widget/form/SIconesCombo.php';

use Adianti\Widget\Form\SIconesCombo;

class IconForm extends TPage
{
    public function __construct()
    {
        parent::__construct();

        $form = new BootstrapFormBuilder('form_icon');

        $icone = new SIconesCombo('icone_id');
        $icone->setSize('10%');

        $form->addFields([new TLabel('Ícone')], [$icone]);

        parent::add($form);
    }
}