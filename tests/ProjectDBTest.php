<?php
require dirname(dirname(__FILE__)) . '/vendor/autoload.php';
require 'TestBase.php';


use DotLogics\DB\ProjectDB;
use DotLogics\DB\UserDB;

class UserProjectDBTest extends TestBase
{
    public function setUp()
    {
        parent::setUp();
        $stmt = self::$db->prepare("SET FOREIGN_KEY_CHECKS=0;TRUNCATE TABLE " . UserDB::TABLE_NAME.";SET FOREIGN_KEY_CHECKS=1");
        $stmt->execute();
        $stmt = self::$db->prepare("SET FOREIGN_KEY_CHECKS=0;TRUNCATE TABLE " . ProjectDB::TABLE_NAME.";SET FOREIGN_KEY_CHECKS=1");
        $stmt->execute();
    }

    public function testAddProject()
    {
        ### create user ########################################

        $user = new UserDB(self::$db, self::$log);
        $email = 'test-project@test.hu';
        $password = 'Bmk342%';
        $lastName = 'Doe';
        $firstName = 'John';
        $createdBy = '1';

        $user->setEmail($email);
        $user->setPassword($password);
        $user->setLastName($lastName);
        $user->setFirstName($firstName);
        $user->setCreatedBy($createdBy);
        $user->setActive('1');
        $userId = $user->save();
        $this->assertGreaterThan(0, $userId);

        ### create project #######################################

        $name = 'Test project';
        $description = 'Description of testproject';
        $parentId = 1;
        $createdBy = $userId;

        $project = new ProjectDB(self::$db, self::$log);
        $project->setActive(1);
        $project->setName($name);
        $project->setDescription($description);
        $project->setParentId($parentId);
        $project->setCreatedBy($createdBy);
        $projectId = $project->save();

        $pr = new ProjectDB(self::$db, self::$log);
        $pr->setId($projectId);
        $pr->get();
        $this->assertEquals(1, $pr->getActive());
        $this->assertEquals($name, $pr->getName());
        $this->assertEquals($description, $pr->getDescription());
        $this->assertEquals($name, $pr->getName());
        $this->assertEquals($parentId, $pr->getParentId());
        $this->assertEquals($createdBy, $pr->getCreatedBy());

        $list = $pr->getAllCreatedBy($createdBy);

        $this->assertEquals(1, count($list));

        ### delete project #########################################

        $pr->delete();
        $pr = new ProjectDB(self::$db, self::$log);
        $pr->setId($projectId);
        $temp = $pr->get();

        $this->assertEquals(NULL, $temp);

        ### delete user ############################################

        $user = $user->getUserByEmail($email);
        $user->delete();

        $user = $user->getUserByEmail($email);
        $this->assertEquals(NULL, $user);
    }
}