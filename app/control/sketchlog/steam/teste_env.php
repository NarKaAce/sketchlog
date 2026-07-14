<?php

class teste_env extends TPage
{
    public function __construct()
    {
        parent::__construct();

        $html = '<pre>';
        $html .= 'getenv: ';
        $html .= var_export(getenv('STEAM_API_KEY'), true) . "\n";

        $html .= 'apache_getenv: ';
        $html .= var_export(function_exists('apache_getenv') ? apache_getenv('STEAM_API_KEY') : null, true) . "\n";

        $html .= '$_SERVER: ';
        $html .= var_export($_SERVER['STEAM_API_KEY'] ?? null, true) . "\n";

        $html .= '$_ENV: ';
        $html .= var_export($_ENV['STEAM_API_KEY'] ?? null, true) . "\n";
        $html .= '</pre>';

        $div = new TElement('div');
        $div->add($html);

        parent::add($div);
    }
}