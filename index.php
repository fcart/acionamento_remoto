<?php
// Silencia erros no ambiente de produção, exibe em dev
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

// Autoloader simples para nossas classes MVC (Se não estiver usando PSR-4 no Composer)
spl_autoload_register(function ($class) {
    $path = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

use App\Controllers\OcorrenciaController;

// Sistema de roteamento simples baseado em "action"
$action = $_GET['action'] ?? 'dashboard';
$controller = new OcorrenciaController();

switch ($action) {
    case 'cadastrar':
        $controller->cadastrar();
        break;
    case 'callback':
        $controller->callback();
        break;
    case 'status':
        $controller->status();
        break;
    case 'dashboard':
    default:
        $controller->index();
        break;
}