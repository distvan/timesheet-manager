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
use DotLogics\Action\ApiProjectAction;
use DotLogics\Action\ApiWorkingTimeAction;
use DotLogics\AuthMiddleware;

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
$container['DotLogics\Action\ApiProjectAction'] = function($c){
    return new ApiProjectAction($c->get('db'), $c->get('logger'));
};
$container['DotLogics\Action\ApiWorkingTimeAction'] = function($c){
    return new ApiWorkingTimeAction($c->get('db'), $c->get('logger'));
};
#### Path ################################################

$app->get('/', 'DotLogics\Action\HomeAction:index')
    ->setName('homepage');

$app->post('/api/login', 'DotLogics\Action\ApiLoginAction:login')
    ->setName('login');

### PROJECTS
$app->group('/api/project', function() use ($app, $container){
    $app->post('/add', 'DotLogics\Action\ApiProjectAction:addProject')
        ->setName('addProject')
        ->add(new AuthMiddleware($container['db'], $container['logger']));

    $app->post('/setStatus/{id}/{status}', 'DotLogics\Action\ApiProjectAction:setProjectStatus')
        ->setName('setProjectStatus')
        ->add(new AuthMiddleware($container['db'], $container['logger']));

    $app->post('/modify/{id}', 'DotLogics\Action\ApiProjectAction:modifyProject')
        ->setName('modifyProject')
        ->add(new AuthMiddleware($container['db'], $container['logger']));

    $app->get('/getAll/{userId}', 'DotLogics\Action\ApiProjectAction:getAllProjectForUser')
        ->setName('getAllProjectForUser')
        ->add(new AuthMiddleware($container['db'], $container['logger']));
});

### WORKINGTIMES
$app->group('/api/workingtime', function() use ($app, $container){
    $app->post('/add', 'DotLogics\Action\ApiWorkingTimeAction:addWorkingTime')
        ->setName('addWorkingTime')
        ->add(new AuthMiddleware($container['db'], $container['logger']));

    $app->post('/delete', 'DotLogics\Action\ApiWorkingTimeAction:deleteWorkingTime')
        ->setName('deleteWorkingTime')
        ->add(new AuthMiddleware($container['db'], $container['logger']));

    $app->post('/attachInvoice', 'DotLogics\Action\ApiWorkingTimeAction:attachInvoice')
        ->setName('attachInvoice')
        ->add(new AuthMiddleware($container['db'], $container['logger']));

    $app->get('/getAllToday', 'DotLogics\Action\ApiWorkingTimeAction:getTodayWorkingTimes')
        ->setName('getTodayWorkingTime')
        ->add(new AuthMiddleware($container['db'], $container['logger']));

    $app->get('/getFiltered/{from}/{to}/{project_id}', 'DotLogics\Action\ApiWorkingTimeAction:getFiltered')
        ->setName('getFilteredWorkingTime')
        ->add(new AuthMiddleware($container['db'], $container['logger']));

    $app->get('/export/{from}/{to}/{project_id}/{lang}/{format}', 'DotLogics\Action\ApiWorkingTimeAction:createExport')
        ->setName('exportWorkingTime')
        ->add(new AuthMiddleware($container['db'], $container['logger']));
});

$app->run();