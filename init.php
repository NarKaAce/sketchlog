<?php
if (version_compare(PHP_VERSION, '8.2.0') == -1)
{
    die ('The minimum version required for PHP is 8.2.0');
}

// define the autoloader
require_once 'lib/adianti/core/AdiantiCoreLoader.php';
spl_autoload_register(array('Adianti\Core\AdiantiCoreLoader', 'autoload'));
Adianti\Core\AdiantiCoreLoader::loadClassMap();

// vendor autoloader
$loader = require 'vendor/autoload.php';
$loader->register();

// carrega o arquivo .env e substitui valores antigos do ambiente local
$dotenv = Dotenv\Dotenv::createMutable(__DIR__);
$dotenv->safeLoad();

// verifica se as configurações obrigatórias existem
$dotenv->required([
    'STEAM_API_KEY',
    'STEAM_ID64',
])->notEmpty();

// apply app configurations
AdiantiApplicationConfig::start();

// define constants
define('PATH', dirname(__FILE__));

setlocale(LC_ALL, 'C');
