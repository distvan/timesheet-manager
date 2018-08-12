<?php
require dirname(dirname(__FILE__)) . '/vendor/autoload.php';
require 'TestBase.php';

use GuzzleHttp\Client;
use DotLogics\DB\UserDB;
use DotLogics\DB\ProjectDB;
use DotLogics\DB\ExceptionMessagesDB;

class ApiProjectActionTest extends TestBase
{
    private static $_token;
    private static $_userId;
    private static $_projectData;
    private static $_http;

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

        self::$_http = new Client(['base_uri' => TestBase::BASE_URL, 'http_errors' => false]);
        $response = self::$_http->request('POST', '/api/login',
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

    public function testAddProjectWithEmptyName()
    {
        $response = self::$_http->request('POST', '/api/project/add',
            array(
                'headers' => array(
                    'Authorization' => self::$_token
                ),
                'multipart'=> array(
                    array(
                        'name' => 'name',
                        'contents' => ''
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
        $this->assertTrue($code == 500);

        $body = json_decode($response->getBody(), true);
        $this->assertEquals($body['code'], ExceptionMessagesDB::EXCEPTION_PROJECT_NO_NAME);
    }

    public function testAddProject()
    {
        $response = self::$_http->request('POST', '/api/project/add',
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
        $this->assertTrue($code == 200);

        $body = json_decode($response->getBody(), true);

        $projectId = isset($body['result']) ? (int)$body['result'] : 0;

        self::$_projectData['id'] = $projectId;

        $this->assertTrue($projectId > 0);
    }

    /**
     * @depends testAddProject
     */
    public function testSetStatusOfProject()
    {
        $status = 0;
        $response = self::$_http->request('POST', '/api/project/setStatus/' . self::$_projectData['id'] . '/' . $status,
            array('headers' => array('Authorization' => self::$_token))
        );
        $code = $response->getStatusCode();
        $this->assertTrue($code == 200);
        $project = new ProjectDB(self::$db, self::$log);
        $project->setId(self::$_projectData['id']);
        $obj = $project->get();
        $this->assertTrue($obj instanceof ProjectDB);
        $this->assertEquals($status, $obj->getActive());

        $status = 1;
        $response = self::$_http->request('POST', '/api/project/setStatus/' . self::$_projectData['id'] . '/' . $status,
            array('headers' => array('Authorization' => self::$_token))
        );
        $code = $response->getStatusCode();
        $this->assertTrue($code == 200);
        $project = new ProjectDB(self::$db, self::$log);
        $project->setId(self::$_projectData['id']);
        $obj = $project->get();
        $this->assertTrue($obj instanceof ProjectDB);
        $this->assertEquals($status, $obj->getActive());
    }

    /**
     * @depends testAddProject
     */
    public function testModifyProject()
    {
        $response = self::$_http->request('POST', '/api/project/modify/' . self::$_projectData['id'],
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
                        'name' => 'parent',
                        'contents' => '0'
                    )
                )
            )
        );

        $code = $response->getStatusCode();
        $this->assertTrue($code == 200);
        $project = new ProjectDB(self::$db, self::$log);
        $project->setId(self::$_projectData['id']);
        $obj = $project->get();
        $this->assertTrue($obj instanceof ProjectDB);
        $this->assertEquals(self::$_projectData['name'], $obj->getName());
        $this->assertEquals(self::$_projectData['description'], $obj->getDescription());
        $this->assertEquals(1, $obj->getActive());
        $this->assertEquals(0, $obj->getParentId());
    }

    /**
     * @depends testAddProject
     */
    public function testGetProject()
    {
        $response = self::$_http->request('GET', '/api/project/getAll/' . self::$_userId,
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