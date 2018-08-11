<?php
require dirname(dirname(__FILE__)) . '/vendor/autoload.php';
require 'TestBase.php';

use GuzzleHttp\Client;
use DotLogics\DB\UserDB;
use DotLogics\DB\ProjectDB;

class ApiProjectActionTest extends TestBase
{
    private static $_token;
    private static $_userId;
    private static $_projectData;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $stmt = self::$db->prepare("SET FOREIGN_KEY_CHECKS=0;TRUNCATE TABLE " . ProjectDB::TABLE_NAME . ";SET FOREIGN_KEY_CHECKS=1");
        $stmt->execute();
        $stmt->closeCursor();
        $stmt = self::$db->prepare("SET FOREIGN_KEY_CHECKS=0;TRUNCATE TABLE " . UserDB::TABLE_NAME . ";SET FOREIGN_KEY_CHECKS=1");
        $stmt->execute();
        $stmt->closeCursor();

        $email = 'test@test.hu';
        $password = 'xiz+3=lklk';
        $user = new UserDB(self::$db, self::$log);
        $user->setEmail($email);
        $user->setLastName('Test');
        $user->setFirstName('Elek');
        $user->setCreatedBy(1);
        $user->setActive('1');
        $user->setPassword($password);
        self::$_userId = $user->save();

        $http = new Client(['base_uri' => TestBase::BASE_URL, 'http_errors' => false]);
        $response = $http->request('POST', '/api/login',
            array(
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
            )
        );

        $body = json_decode($response->getBody(), true);
        self::$_token = $body['token_id'];

        self::$_projectData['name'] = 'Testproject';
        self::$_projectData['description'] = 'A project to testing add function';
        self::$_projectData['active'] = '1';
    }

    public function setUp()
    {
        fwrite(STDOUT, __METHOD__ . "\n");
    }

    public function testAddProject()
    {
        $http = new Client(['base_uri' => TestBase::BASE_URL, 'http_errors' => false]);
        $response = $http->request('POST', '/api/project/add',
            array(
                'headers' => array(
                    'Authorization' => self::$_token
                ),
                'multipart'=> array(
                    array(
                        'name' => 'name',
                        'contents' => self::$_projectData['name']
                    ),
                    array(
                        'name' => 'description',
                        'contents' => self::$_projectData['description']
                    ),
                    array(
                        'name' => 'active',
                        'contents' => self::$_projectData['active']
                    ),
                    array(
                        'name' => 'parent',
                        'contents' => '0'
                    ),
                    array(
                        'name' => 'created_by',
                        'contents' => self::$_userId
                    )
                )
            )
        );
        $code = $response->getStatusCode();
        fwrite(STDOUT, $response->getBody() . "\n");
        $this->assertTrue($code == 200);

        $body = json_decode($response->getBody(), true);

        $projectId = isset($body['result']) ? (int)$body['result'] : 0;

        $this->assertTrue($projectId > 0);
    }

    public function testGetProject()
    {
        $http = new Client(['base_uri' => TestBase::BASE_URL, 'http_errors' => false]);
        $response = $http->request('POST', '/api/project/getAll/' . self::$_userId,
            array('headers' => array('Authorization' => self::$_token))
        );
        $code = $response->getStatusCode();
        $this->assertTrue($code == 200);

        $body = json_decode($response->getBody(), true);

        $projectList = isset($body['result']) ? $body['result'] : array();

        $this->assertEquals(1, count($projectList));
        $this->assertEquals($projectList[0]['name'], self::$_projectData['name']);
        $this->assertEquals($projectList[0]['description'], self::$_projectData['description']);
    }
}