<?php
namespace DotLogics;

use Exception;
use DotLogics\Action\ApiLoginAction;

/**
 * AuthMiddleware Class
 *
 * Check JWT token
 *
 * @author Istvan Dobrentei
 * @copyright DotLogics Hungary Kft.
 * @url https://www.dotlogics.hu
 *
 */
class AuthMiddleware
{
    private $_allow;
    private $_userId;
    private $_db;
    private $_log;

    public function __construct($db, $log)
    {
        $this->_userId = 0;
        $this->_db = $db;
        $this->_log = $log;
    }

    /**
     *  Check User and its right
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($request, $response, $next)
    {
        //$response->getBody()->write('BEFORE');

        $header = $request->getHeader('Authorization');
        $token = isset($header[0]) ? $header[0] : '';
        $validToken = $this->isValidToken($token);

        $newRequest = $request->withAttribute('user_id', $this->_userId);

        if(empty($token) || !$validToken)
        {
            return $response->withStatus(401);
        }

        $response = $next($newRequest, $response);

        //$response->getBody()->write('AFTER');

        return $response;
    }

    private function isValidToken($token)
    {
        try
        {
            $this->_userId = ApiLoginAction::isValidToken($token);

            return $this->_userId > 0;
        }
        catch(Exception $e)
        {
            $this->_log->info('Middleware restrict access: ' . $e->getMessage());
            $response = false;
        }

        return $response;
    }
}
?>