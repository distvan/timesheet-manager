<?php
namespace DotLogics\Action;

use PDO;
use Slim\Views\Twig;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

final class HomeAction
{
    private $_view;
    private $_db;
    private $_log;

    public function __construct(Twig $view, PDO $db)
    {
        $this->_view = $view;
        $this->_db = $db;
    }
    public function index(Request $request, Response $response, $args)
    {
        $this->_view->render($response, 'home.html', array());

        return $response;
    }
}
?>