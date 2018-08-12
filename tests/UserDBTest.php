<?php
require dirname(dirname(__FILE__)) . '/vendor/autoload.php';
require 'TestBase.php';

use DotLogics\DB\UserDB;
use DotLogics\DB\ExceptionMessagesDB;

class UserDBTest extends TestBase
{
    private $_user;

    public function setUp()
    {
        parent::setUp();
        $stmt = self::$db->prepare("SET FOREIGN_KEY_CHECKS=0;TRUNCATE TABLE " . UserDB::TABLE_NAME.";SET FOREIGN_KEY_CHECKS=1");
        $stmt->execute();
        $this->_user = new UserDB(self::$db, self::$log);
    }

    public function testAddUser()
    {
        $email = 'test-add-user@test.hu';
        $password = 'password';
        $lastName = 'Teszt';
        $firstName = 'Elek';
        $createdBy = '1';

        $this->_user->setEmail($email);
        $this->_user->setLastName($lastName);
        $this->_user->setFirstName($firstName);
        $this->_user->setCreatedBy($createdBy);
        $this->_user->setPassword($password);
        $this->_user->setActive('1');

        $userId = $this->_user->save();

        $this->assertGreaterThan(0, $userId);
    }

    public function testAddUserWithAlreadyExistingEmailAddress()
    {
        $email = 'test-add-user2@test.hu';
        $password = 'password';
        $lastName = 'Teszt';
        $firstName = 'elek';
        $createdBy = '1';

        $this->_user->setEmail($email);
        $this->_user->setLastName($lastName);
        $this->_user->setFirstName($firstName);
        $this->_user->setCreatedBy($createdBy);
        $this->_user->setActive('1');
        $this->_user->setPassword($password);

        $userId = $this->_user->save();

        $this->assertGreaterThan(0, $userId);

        $this->_user->setEmail($email);
        $this->_user->setLastName('Teszt');
        $this->_user->setFirstName('Elek2');
        $this->_user->setCreatedBy($createdBy);
        $this->_user->setActive('1');
        $this->_user->setPassword($password);
        $userId = $this->_user->save();
        $this->assertTrue($userId instanceof Exception);
    }

    public function testDeleteUser()
    {
        $email = 'test-add-user3@test.hu';
        $password = 'password';
        $lastName = 'Teszt';
        $firstName = 'Elek3';
        $createdBy = '1';

        $this->_user->setEmail($email);
        $this->_user->setLastName($lastName);
        $this->_user->setFirstName($firstName);
        $this->_user->setCreatedBy($createdBy);
        $this->_user->setPassword($password);
        $this->_user->setActive('1');

        $userId = $this->_user->save();
        $this->assertGreaterThan(0, $userId);
        $user = $this->_user->getUserByEmail($email);
        $this->assertTrue($user instanceof UserDB);
        $user->delete();
        $user = $this->_user->getUserByEmail($email);
        $this->assertEquals(NULL, $user);
    }

    public function testDeleteUserWithEmptyId()
    {
        $exceptionRaised = false;
        $this->_user->setId(0);
        try
        {
            $this->_user->delete();
        }
        catch(Exception $e)
        {
            $exceptionRaised = true;
            $this->assertEquals(ExceptionMessagesDB::EXCEPTION_USER_EMPTY_USERID, $e->getCode());
        }

        $this->assertTrue($exceptionRaised);
    }

    public function testGetAllUser()
    {
        $users = array(
            array(
                'last_name' => 'Teszt',
                'first_name' => 'Elek1',
                'email' => 'teszt-1@test.hu',
                'created_by' => 1,
                'password' => 'password'
            ),
            array(
                'last_name' => 'Teszt',
                'first_name' => 'Elek2',
                'email' => 'teszt-2@test.hu',
                'created_by' => 1,
                'password' => 'password'
            ),
            array(
                'last_name' => 'Teszt',
                'first_name' => 'Elek3',
                'email' => 'teszt-3@test.hu',
                'created_by' => 1,
                'password' => 'password'
            ),
            array(
                'last_name' => 'Teszt',
                'first_name' => 'Elek4',
                'email' => 'teszt-4@test.hu',
                'created_by' => 1,
                'password' => 'password'
            )
        );

        foreach($users as $user)
        {
            $this->_user->setEmail($user['email']);
            $this->_user->setLastName($user['last_name']);
            $this->_user->setFirstName($user['first_name']);
            $this->_user->setCreatedBy($user['created_by']);
            $this->_user->setActive('1');
            $this->_user->setPassword($user['password']);
            $this->_user->save();
        }

        $list = $this->_user->getAllUser();

        $this->assertEquals(count($users), count($list));

        $list = $this->_user->getAllUserCreatedBy(1);

        $this->assertEquals(count($users), count($list));

        foreach($list as $user)
        {
            $user->delete();
        }

        $list = $this->_user->getAllUser();
        $this->assertEquals(0, count($list));
    }

    public function One()
    {
        $email = 'test@test.hu';
        $password = 'test';
        $lastName = 'Teszt';
        $firstName = 'Elek';
        $createdBy = '1';

        $this->_user->setEmail($email);
        $this->_user->setLastName($lastName);
        $this->_user->setFirstName($firstName);
        $this->_user->setCreatedBy($createdBy);
        $this->_user->setPassword($password);
        $this->_user->setActive('1');

        $userId = $this->_user->save();
    }
}