<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Translator;

#Own libraries
use DotLogics\Config;
use DotLogics\Action\HomeAction;
use DotLogics\Action\ApiLoginAction;

$config = Config::getPortalConfig();
$app = new App($config);


#### Setup dependencies ##################################

$container = $app->getContainer();

$container['errorHandler'] = function ($c) {
    return function ($request, $response, $exception) use ($c) {
        $data = [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => explode("\n", $exception->getTraceAsString()),
        ];

        return $c->get('response')->withStatus(500)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($data));
    };
};
$container['view'] = function($c){
    $language = isset($_GET["lang"]) ? $_GET["lang"] : 'hu';
    $translator = new Translator($language, new MessageSelector());
    $translator->setFallbackLocales(['hu_HU']);
    $translator->addLoader('php', new PhpFileLoader());
    $translator->addResource('php', './lang/hu_HU.php' , 'hu_HU');
    $translator->addResource('php', './lang/en_US.php' , 'en_US');
    $translator->addResource('php', './lang/de_DE.php' , 'de_DE');

    $config = $c->get('settings');
    $view = new Twig($config['view']['template_path'], $config['view']['twig']);
    $view->addExtension(new TwigExtension($c->get('router'), $c->get('request')->getUri()));
    $view->addExtension(new Twig_Extension_Debug());
    $view->addExtension(new TranslationExtension($translator));

    return $view;
};
$container['logger'] = function($c){
    Logger::configure(dirname(__DIR__) . '/log_config.xml');
    $log = Logger::getLogger('timesheetlogger');

    return $log;
};
$container['db'] = function($c){
    return new PDO("mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME'),
        getenv('DB_USER'), getenv('DB_PASS'), array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
};

#### Action factories ####################################

$container['DotLogics\Action\HomeAction'] = function($c){
    return new HomeAction($c->get('view'), $c->get('db'));
};
$container['DotLogics\Action\ApiLoginAction'] = function($c){
    return new ApiLoginAction($c->get('db'), $c->get('logger'));
};
#### Path ################################################

$app->get('/', 'DotLogics\Action\HomeAction:index')
    ->setName('homepage');

$app->post('/api/login', 'DotLogics\Action\ApiLoginAction:login')
    ->setName('login');

$app->run();