<?php
require dirname(dirname(__FILE__)) . '/vendor/autoload.php';
require 'TestBase.php';

use DotLogics\Action\ApiLoginAction;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;
use GuzzleHttp\Client;
use DotLogics\DB\UserDB;


class SuccessMockApiLoginAction extends ApiLoginAction
{
    public function __construct($db)
    {
        parent::__construct($db);
        $user = new UserDB($this->_db);
        $user->setId(1233);
        $user->setActive(1);
        $this->setLoggedUser($user);
    }
}

class ApiLoginActionTest extends TestBase
{
    protected static $_user;
    protected $_environment;
    public $_http;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $stmt = self::$db->prepare("SET FOREIGN_KEY_CHECKS=0;TRUNCATE TABLE " . UserDB::TABLE_NAME.";SET FOREIGN_KEY_CHECKS=1");
        $stmt->execute();
    }

    public function setUp()
    {
        parent::setUp();

        $this->_environment = Environment::mock([
            'REQUEST_METHOD' => 'POST',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/api/login',
            'QUERY_STRING' => "user=fakeuser&password=fakepassword",
            'SERVER_NAME' => 'something',
            'SERVER_PORT' => '80',
            'HTTPS' => '',
            'CONTENT_TYPE' => 'application/json;charset=utf8'
        ]);

        $this->_http = new Client(['base_uri' => TestBase::BASE_URL, 'http_errors' => false]);
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->_http = null;
    }

    public function SuccessLogin()
    {
        $action = new SuccessMockApiLoginAction($this->_db);

        $request = Request::createFromEnvironment($this->_environment);
        $request->withHeader('Content-Type','application/json');

        $response = new Response();

        $response = $action->login($request, $response, []);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUnauthorizedLoginApi()
    {
        $response = $this->_http->request('POST', '/api/login', array(
            'multipart'=> array(
                array(
                    'name' => 'user',
                    'contents' => ''
                ),
                array(
                    'name' => 'password',
                    'contents' => ''
                )
            )
        ));
        $code = $response->getStatusCode();
        $this->assertTrue($code == 401);
    }

    public function testSuccessLoginApi()
    {
        $email = 'test@test.hu';
        $password  = 'passwordtesting';
        $user = new UserDB($this->_db, self::$log);
        $user->setActive(1);
        $user->setEmail($email);
        $user->setPassword($password);
        $user->setFirstName('firstname');
        $user->setLastName('lastname');
        $user->save();

        $response = $this->_http->request('POST', '/api/login', array(
            'multipart'=> array(
                array(
                    'name' => 'user',
                    'contents' => $email
                ),
                array(
                    'name' => 'password',
                    'contents' => $password
                )
            )
        ));

        $code = $response->getStatusCode();
        $this->assertTrue($code == 200);

        //check token validity
        $body = json_decode($response->getBody(), true);
        $token = $body['token_id'];
        $valid = ApiLoginAction::isValidToken($token);
        $this->assertTrue($valid > 0);
    }
}