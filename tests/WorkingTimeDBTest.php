<?php
require dirname(dirname(__FILE__)) . '/vendor/autoload.php';
require 'TestBase.php';

use DotLogics\DB\WorkingTimeDB;
use DotLogics\DB\ProjectDB;
use DotLogics\DB\UserDB;

class WorkingTimeDBTest extends TestBase
{
    private $_wt;

    public function setUp()
    {
        parent::setUp();
        $stmt = self::$db->prepare("SET FOREIGN_KEY_CHECKS=0;TRUNCATE TABLE " . UserDB::TABLE_NAME.";SET FOREIGN_KEY_CHECKS=1");
        $stmt->execute();
        $stmt = self::$db->prepare("SET FOREIGN_KEY_CHECKS=0;TRUNCATE TABLE " . ProjectDB::TABLE_NAME.";SET FOREIGN_KEY_CHECKS=1");
        $stmt->execute();
        $stmt = self::$db->prepare("SET FOREIGN_KEY_CHECKS=0;TRUNCATE TABLE " . WorkingTimeDB::TABLE_NAME.";SET FOREIGN_KEY_CHECKS=1");
        $stmt->execute();

        $this->_wt = new WorkingTimeDB(self::$db, self::$log);
    }

    public function testFromDateGreaterThanToDate()
    {
        $exception = false;

        try
        {
            $this->_wt->setProjectId(1);
            $this->_wt->setDescription('test');
            $this->_wt->setDateFrom('2018-12-30 12:00:00');
            $this->_wt->setDateTo('2018-04-11 13:00:00');
            $this->_wt->setApproved(0);
            $this->_wt->setApprovedAt('');
            $this->_wt->setApprovedBy('');
            $this->_wt->setCreatedBy(1);
            $this->_wt->save();
        }
        catch(Exception $e)
        {
            $exception = true;
        }

        $this->assertTrue($exception);
    }

    public function testInsertWorkingTime()
    {
        //add user
        $email = 'test-wt@test.hu';
        $password = 'tiresome';
        $lastName = 'István';
        $firstName = 'Döbrentei';
        $createdBy = '1';

        $user = new UserDB(self::$db, self::$log);
        $user->setEmail($email);
        $user->setLastName($lastName);
        $user->setFirstName($firstName);
        $user->setCreatedBy($createdBy);
        $user->setActive('1');
        $userId = $user->save();

        //add project
        $name = 'Teszprojekt - WT';
        $description = 'Tesztprojekt bejegyzés - WT';
        $parentId = 1;
        $createdBy = $userId;

        $project = new ProjectDB(self::$db, self::$log);
        $project->setActive(1);
        $project->setName($name);
        $project->setDescription($description);
        $project->setParentId($parentId);
        $project->setCreatedBy($createdBy);
        $projectId = $project->save();

        $testdata = array(
                        array(
                            'project_id' => $projectId,
                            'date_from' => '2016-06-07 11:00:00',
                            'date_to' => '2016-06-07 12:00:00',
                            'description' => 'Teszt1 Teszt1 loremipsumloremipsumloremipsum loremipsum loremipsum',
                            'approved' => '1',
                            'approved_at' => '2016-07-10 15:00:00',
                            'approved_by' => $userId,
                            'created_by' => $userId
                        ),
                        array(
                            'project_id' => $projectId,
                            'date_from' => '2016-06-07 14:15:00',
                            'date_to' => '2016-06-07 15:45:00',
                            'description' => 'Teszt2 Teszt2 loremipsumloremipsumloremipsum loremipsum loremipsum',
                            'approved' => '1',
                            'approved_at' => '2016-07-10 15:00:00',
                            'approved_by' => $userId,
                            'created_by' => $userId
                        ),
                        array(
                            'project_id' => $projectId,
                            'date_from' => '2016-06-07 17:00:00',
                            'date_to' => '2016-06-07 17:15:00',
                            'description' => 'Teszt3 Teszt3 loremipsumloremipsumloremipsum loremipsum loremipsum',
                            'approved' => '1',
                            'approved_at' => '2016-07-10 15:00:00',
                            'approved_by' => $userId,
                            'created_by' => $userId
                        )
                    );


        foreach($testdata as $item)
        {
            $this->_wt->setProjectId($item['project_id']);
            $this->_wt->setDescription($item['description']);
            $this->_wt->setDateFrom($item['date_from']);
            $this->_wt->setDateTo($item['date_to']);
            $this->_wt->setApproved($item['approved']);
            $this->_wt->setApprovedAt($item['approved_at']);
            $this->_wt->setApprovedBy($item['approved_by']);
            $this->_wt->setCreatedBy($item['created_by']);
            $this->_wt->save();
        }

        $result = $this->_wt->getBetween('2016-06-07 00:00:00', '2016-06-07 23:00:00', $projectId);
        $this->assertEquals(3, count($result));

        //delete workingtime
        foreach($result as $item)
        {
            $this->_wt->setId($item['id']);
            $this->_wt->delete();
        }

        $result = $this->_wt->getBetween('2016-06-07 00:00:00', '2016-06-07 23:00:00', $projectId);
        $this->assertEquals(0, count($result));

        //delete project
        $project->setId($projectId);
        $project->delete();

        //delete user
        $user->setId($userId);
        $user->delete();
    }

}