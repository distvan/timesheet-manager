<?php
require dirname(dirname(__FILE__)) . '/vendor/autoload.php';
require 'TestBase.php';

use DotLogics\Config;

/*  A working_time táblán lévő update és delete triggerek tesztelése
 *  A triggerek célja, hogy az adott projektre jóváírt munkaidőt összesítse,
 *  illetve riportkészítéshez adjon támogatást, összegezze a munkaidőket felhasználó és adott napra vonatkozóan (working_time_summary)
 *
 * */
class DatabaseTriggerTest extends TestBase
{
    private $_dateNow;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$db->query("DELETE FROM working_time_summary");
        self::$db->query("DELETE FROM working_time");
        self::$db->query("DELETE FROM project");
        self::$db->query("DELETE FROM user");
    }

    /**
     * (non-PHPdoc)
     * @see OC_UnitTestBase::setUp()
     */
    public function setUp()
    {
        parent::setUp();
        $dt = new DateTime();
        $this->_dateNow = $dt->format('Y-m-d H:i:s');
    }

    public function testTriggersWithinOneDay()
    {
        $startDate = '1900-07-09 10:00:00';
        $endDate = '1900-07-09 11:15:00';
        $year = 1900;
        $month = 7;
        $day = 9;
        $diffInMinutes = 75;

        //##########################################################
        //létrehozok egy tesztusert
        $stmt = self::$db->prepare("INSERT INTO user (email, password, last_name, first_name, active, created_at) VALUES('doebrentei@hotmail.com', '', 'István', 'Döbrentei', 1, '" . $this->_dateNow . "')");
        $stmt->execute();
        $userId = self::$db->lastInsertId();

        //létrehozok egy tesztprojektet
        $stmt = self::$db->prepare("INSERT INTO project (name, description, active, created_at, created_by) VALUES ('Teszt', 'próbaprojekt', 1, '" . $this->_dateNow . "', " . $userId . ")");
        $stmt->execute();
        $projectId = self::$db->lastInsertId();
        //létrehozok egy munkaidőt napon belül
        $stmt = self::$db->prepare("INSERT INTO working_time (project_id, date_from, date_to, description, created_at, created_by) VALUES ('" . $projectId . "', '" . $startDate . "', '" . $endDate . "', 'teszt', '" . $this->_dateNow . "', '" . $userId . "')");
        $stmt->execute();
        $workTimeId = self::$db->lastInsertId();

        //lekérdezem a létrejött értékeket és összevetem a feltételezett értékkel
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $year . "' AND month='" . $month . "' AND day='" . $day . "' AND user_id='" . $userId . "'");
        $rowNumExpectation = 0==$sth->fetchColumn();
        $this->assertTrue($rowNumExpectation);

        //triggerelek egy update -et (jóváhagyom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='1' WHERE id='" . $workTimeId . "'");
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $year . "' AND month='" . $month . "' AND day='" . $day . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes);

        //triggerelek egy update -et (visszautasítom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='0' WHERE id='" . $workTimeId . "'");
        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $year . "' AND month='" . $month . "' AND day='" . $day . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        //triggerelek egy delete -et (törlöm a bejegyzett munkaidőt)
        self::$db->query("DELETE FROM working_time WHERE id='" . $workTimeId . "'");
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $year . "' AND month='" . $month . "' AND day='" . $day . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        //törlöm a teszteléshez létrehozott adatokat
        self::$db->query("DELETE FROM project WHERE id='" . $projectId . "'");
        self::$db->query("DELETE FROM user WHERE id='" . $userId . "'");
    }

    /* Teszteset arra, amikor egy napon belül több időpontot rögzítek
     *
     * */
    public function testAddingMoreWorkingTimeWithinOneDay()
    {

        //létrehozok egy tesztusert
        $stmt = self::$db->prepare("INSERT INTO user (email, password, last_name, first_name, active, created_at) VALUES('doebrentei@hotmail.com', '', 'István', 'Döbrentei', 1, '" . $this->_dateNow . "')");
        $stmt->execute();
        $userId = self::$db->lastInsertId();

        //létrehozok egy tesztprojektet
        $stmt = self::$db->prepare("INSERT INTO project (name, description, active, created_at, created_by) VALUES ('Teszt', 'próbaprojekt', 1, '" . $this->_dateNow . "', " . $userId . ")");
        $stmt->execute();
        $projectId = self::$db->lastInsertId();


        ##########################
        # Első időpont rögzítése #
        ##########################

        $startDate1 = '1800-07-09 13:00:00';
        $endDate1 = '1800-07-09 13:15:00';
        $year = 1800;
        $month = 7;
        $day = 9;
        $diffInMinutes1 = 15;

        //létrehozok egy munkaidőt napon belül
        $stmt = self::$db->prepare("INSERT INTO working_time (project_id, date_from, date_to, description, created_at, created_by) 
                                      VALUES ('" . $projectId . "', '" . $startDate1 . "', '" . $endDate1 . "', 'teszt', '" . $this->_dateNow . "', '" . $userId . "')");
        $stmt->execute();
        $workTimeId1 = self::$db->lastInsertId();

        #############################
        # Második időpont rögzítése #
        #############################

        $startDate2 = '1800-07-09 15:10:00';
        $endDate2 = '1800-07-09 16:00:00';
        $diffInMinutes2 = 50;

        //létrehozok egy munkaidőt napon belül
        $stmt = self::$db->prepare("INSERT INTO working_time (project_id, date_from, date_to, description, created_at, created_by) 
                                      VALUES ('" . $projectId . "', '" . $startDate2 . "', '" . $endDate2 . "', 'teszt', '" . $this->_dateNow . "', '" . $userId . "')");
        $stmt->execute();
        $workTimeId2 = self::$db->lastInsertId();

        ##############################
        # Harmadik időpont rögzítése #
        ##############################

        $startDate3 = '1800-07-09 20:00:00';
        $endDate3 = '1800-07-09 21:05:00';
        $diffInMinutes3 = 65;

        //létrehozok egy munkaidőt napon belül
        $stmt = self::$db->prepare("INSERT INTO working_time (project_id, date_from, date_to, description, created_at, created_by) 
                                      VALUES ('" . $projectId . "', '" . $startDate3 . "', '" . $endDate3 . "', 'teszt', '" . $this->_dateNow . "', '" . $userId . "')");
        $stmt->execute();
        $workTimeId3 = self::$db->lastInsertId();


        ###################
        # Feltételezések  #
        ###################
        //A projekt összesítő még nulla
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        //A munkaidő összesítő szintén nulla
        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $year . "' AND month='" . $month . "' AND day='" . $day . "' AND user_id='" . $userId . "'");
        $rowNumExpectation = 0==$sth->fetchColumn();
        $this->assertTrue($rowNumExpectation);


        ############################
        # Jóváhagyom az időket     #
        ############################

        //triggerelek egy update -et (jóváhagyom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='1' WHERE id='" . $workTimeId1 . "'");
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes1);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $year . "' AND month='" . $month . "' AND day='" . $day . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes1);

        //triggerelek egy update -et (jóváhagyom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='1' WHERE id='" . $workTimeId2 . "'");
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes1 + $diffInMinutes2);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $year . "' AND month='" . $month . "' AND day='" . $day . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes1 + $diffInMinutes2);

        //triggerelek egy update -et (jóváhagyom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='1' WHERE id='" . $workTimeId3 . "'");
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes1 + $diffInMinutes2 + $diffInMinutes3);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $year . "' AND month='" . $month . "' AND day='" . $day . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes1 + $diffInMinutes2 + $diffInMinutes3);


        #################################
        # Visszavonom az 1-es időpontot #
        #################################

        //triggerelek egy update -et (visszautasítom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='0' WHERE id='" . $workTimeId1 . "'");
        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $year . "' AND month='" . $month . "' AND day='" . $day . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes2 + $diffInMinutes3);

        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes2 + $diffInMinutes3);


        ################################
        # Visszavonom a 3-as időpontot #
        ################################

        //triggerelek egy update -et (visszautasítom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='0' WHERE id='" . $workTimeId3 . "'");
        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $year . "' AND month='" . $month . "' AND day='" . $day . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes2);

        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes2);

        ################################
        # Törlöm a 3-as időpontot      #
        ################################

        //triggerelek egy delete -et (törlöm a bejegyzett munkaidőt)
        self::$db->query("DELETE FROM working_time WHERE id='" . $workTimeId3 . "'");
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes2);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $year . "' AND month='" . $month . "' AND day='" . $day . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes2);


        //törlöm a teszteléshez létrehozott adatokat
        self::$db->query("DELETE FROM working_time_summary WHERE user_id='" . $userId . "'");
        self::$db->query("DELETE FROM working_time WHERE project_id='" . $projectId . "'");
        self::$db->query("DELETE FROM project WHERE id='" . $projectId . "'");
        self::$db->query("DELETE FROM user WHERE id='" . $userId . "'");
    }


    /* Teszteset arra, amikor több egy napon belüli időpontot hozok létre több projekthez egy adott felhasználóhoz
     *
     * */

    public function testTriggerWithinOneDayMoreProject()
    {
        //létrehozok egy tesztusert
        $stmt = self::$db->prepare("INSERT INTO user (email, password, last_name, first_name, active, created_at) VALUES('doebrentei@hotmail.com', '', 'István', 'Döbrentei', 1, '" . $this->_dateNow . "')");
        $stmt->execute();
        $userId = self::$db->lastInsertId();

        //létrehozok egy tesztprojektet
        $stmt = self::$db->prepare("INSERT INTO project (name, description, active, created_at, created_by) VALUES ('Teszt1', 'próbaprojekt1', 1, '" . $this->_dateNow . "', " . $userId . ")");
        $stmt->execute();
        $projectId1 = self::$db->lastInsertId();

        //létrehozok egy tesztprojektet
        $stmt = self::$db->prepare("INSERT INTO project (name, description, active, created_at, created_by) VALUES ('Teszt2', 'próbaprojekt2', 1, '" . $this->_dateNow . "', " . $userId . ")");
        $stmt->execute();
        $projectId2 = self::$db->lastInsertId();


        ##########################
        # Első időpont rögzítése #
        ##########################

        $startDate1 = '1700-06-09 09:00:00';
        $endDate1 = '1700-06-09 09:15:00';
        $year = 1700;
        $month = 6;
        $day = 9;
        $diffInMinutes1 = 15;

        //létrehozok egy munkaidőt napon belül
        $stmt = self::$db->prepare("INSERT INTO working_time (project_id, date_from, date_to, description, created_at, created_by) 
                                      VALUES ('" . $projectId1 . "', '" . $startDate1 . "', '" . $endDate1 . "', 'teszt', '" . $this->_dateNow . "', '" . $userId . "')");
        $stmt->execute();
        $workTimeId1 = self::$db->lastInsertId();

        #############################
        # Második időpont rögzítése #
        #############################

        $startDate2 = '1700-06-09 15:10:00';
        $endDate2 = '1700-06-09 16:00:00';
        $diffInMinutes2 = 50;

        //létrehozok egy munkaidőt napon belül
        $stmt = self::$db->prepare("INSERT INTO working_time (project_id, date_from, date_to, description, created_at, created_by) 
                                      VALUES ('" . $projectId2 . "', '" . $startDate2 . "', '" . $endDate2 . "', 'teszt', '" . $this->_dateNow . "', '" . $userId . "')");
        $stmt->execute();
        $workTimeId2 = self::$db->lastInsertId();

        ###################
        # Feltételezések  #
        ###################
        //A projekt összesítő még nulla
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId1 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        //A projekt összesítő még nulla
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId2 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        //A munkaidő összesítő szintén nulla
        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $year . "' AND month='" . $month . "' AND day='" . $day . "' AND user_id='" . $userId . "'");
        $rowNumExpectation = 0==$sth->fetchColumn();
        $this->assertTrue($rowNumExpectation);

        ##########################################
        # Jóváhagyom az idő1-et a projekt1-en    #
        ##########################################

        //triggerelek egy update -et (jóváhagyom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='1' WHERE id='" . $workTimeId1 . "'");
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId1 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes1);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $year . "' AND month='" . $month . "' AND day='" . $day . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes1);

        ####################################################
        # Visszavonom az 1-es időpontot az 1 -es projekten #
        ####################################################

        //triggerelek egy update -et (visszautasítom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='0' WHERE id='" . $workTimeId1 . "'");
        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $year . "' AND month='" . $month . "' AND day='" . $day . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId1 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);


        ##########################################
        # Jóváhagyom az idő2-et a projekt2-en    #
        ##########################################

        //triggerelek egy update -et (jóváhagyom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='1' WHERE id='" . $workTimeId2 . "'");
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId2 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes2);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $year . "' AND month='" . $month . "' AND day='" . $day . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes2);


        ################################
        # Törlöm az 1-es időpontot     #
        ################################

        //triggerelek egy delete -et (törlöm a bejegyzett munkaidőt)
        self::$db->query("DELETE FROM working_time WHERE id='" . $workTimeId1 . "'");
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId1 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $year . "' AND month='" . $month . "' AND day='" . $day . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes2);

        //törlöm a teszteléshez létrehozott adatokat
        self::$db->query("DELETE FROM working_time_summary WHERE user_id='" . $userId . "'");
        self::$db->query("DELETE FROM working_time WHERE project_id='" . $projectId1 . "'");
        self::$db->query("DELETE FROM working_time WHERE project_id='" . $projectId2 . "'");
        self::$db->query("DELETE FROM project WHERE id='" . $projectId1 . "'");
        self::$db->query("DELETE FROM project WHERE id='" . $projectId2 . "'");
        self::$db->query("DELETE FROM user WHERE id='" . $userId . "'");
    }

    /* Teszteset, amikor több napon belüli időpontot hozok létre egy projekthez egy userhez
     *
     * */
    public function testTriggerWithinOneDayMoreProjectMoreUser()
    {
        //létrehozok egy tesztusert
        $stmt = self::$db->prepare("INSERT INTO user (email, password, last_name, first_name, active, created_at) VALUES('doebrentei@hotmail.com', '', 'István', 'Döbrentei', 1, '" . $this->_dateNow . "')");
        $stmt->execute();
        $userId = self::$db->lastInsertId();

        //létrehozok egy tesztprojektet
        $stmt = self::$db->prepare("INSERT INTO project (name, description, active, created_at, created_by) VALUES ('Teszt1', 'próbaprojekt1', 1, '" . $this->_dateNow . "', " . $userId . ")");
        $stmt->execute();
        $projectId = self::$db->lastInsertId();


        ##########################
        # Időpont rögzítése      #
        ##########################

        $startDate = '1600-06-09 23:15:00';
        $endDate = '1600-06-10 00:15:00';
        $yearStart = 1600;
        $monthStart = 6;
        $dayStart = 9;
        $yearEnd = 1600;
        $monthEnd = 6;
        $dayEnd = 10;
        $diffInMinutesStart = 45;
        $diffInMinutesEnd = 15;

        //létrehozok egy munkaidőt napon belül
        $stmt = self::$db->prepare("INSERT INTO working_time (project_id, date_from, date_to, description, created_at, created_by) 
                                      VALUES ('" . $projectId . "', '" . $startDate . "', '" . $endDate . "', 'teszt', '" . $this->_dateNow . "', '" . $userId . "')");
        $stmt->execute();
        $workTimeId = self::$db->lastInsertId();

        ##########################
        # Feltételezések         #
        ##########################

        //lekérdezem a létrejött értékeket és összevetem a feltételezett értékkel
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearStart . "' AND month='" . $monthStart . "' AND day='" . $dayStart . "' AND user_id='" . $userId . "'");
        $rowNumExpectation = 0==$sth->fetchColumn();
        $this->assertTrue($rowNumExpectation);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearEnd . "' AND month='" . $monthEnd . "' AND day='" . $dayEnd . "' AND user_id='" . $userId . "'");
        $rowNumExpectation = 0==$sth->fetchColumn();
        $this->assertTrue($rowNumExpectation);


        ############################
        # Jóváhagyom az időt       #
        ############################

        //triggerelek egy update -et (jóváhagyom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='1' WHERE id='" . $workTimeId . "'");
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutesStart + $diffInMinutesEnd);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearStart . "' AND month='" . $monthStart . "' AND day='" . $dayStart . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutesStart);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearEnd . "' AND month='" . $monthEnd . "' AND day='" . $dayEnd . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutesEnd);


        ############################
        # Visszautasítom az időt   #
        ############################

        //triggerelek egy update -et (visszautasítom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='0' WHERE id='" . $workTimeId . "'");
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearStart . "' AND month='" . $monthStart . "' AND day='" . $dayStart . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearEnd . "' AND month='" . $monthEnd . "' AND day='" . $dayEnd . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);



        ##########################
        # Időpont rögzítése      #
        ##########################

        $startDate = '1500-05-31 23:30:00';
        $endDate = '1500-06-01 00:45:00';
        $yearStart = 1500;
        $monthStart = 5;
        $dayStart = 31;
        $yearEnd = 1500;
        $monthEnd = 6;
        $dayEnd = 1;
        $diffInMinutesStart = 30;
        $diffInMinutesEnd = 45;

        //létrehozok egy munkaidőt napon belül
        $stmt = self::$db->prepare("INSERT INTO working_time (project_id, date_from, date_to, description, created_at, created_by) 
                                      VALUES ('" . $projectId . "', '" . $startDate . "', '" . $endDate . "', 'teszt', '" . $this->_dateNow . "', '" . $userId . "')");
        $stmt->execute();
        $workTimeId = self::$db->lastInsertId();


        ##########################
        # Feltételezések         #
        ##########################

        //lekérdezem a létrejött értékeket és összevetem a feltételezett értékkel
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearStart . "' AND month='" . $monthStart . "' AND day='" . $dayStart . "' AND user_id='" . $userId . "'");
        $rowNumExpectation = 0==$sth->fetchColumn();
        $this->assertTrue($rowNumExpectation);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearEnd . "' AND month='" . $monthEnd . "' AND day='" . $dayEnd . "' AND user_id='" . $userId . "'");
        $rowNumExpectation = 0==$sth->fetchColumn();
        $this->assertTrue($rowNumExpectation);


        ############################
        # Jóváhagyom az időt       #
        ############################

        //triggerelek egy update -et (jóváhagyom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='1' WHERE id='" . $workTimeId . "'");
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutesStart + $diffInMinutesEnd);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearStart . "' AND month='" . $monthStart . "' AND day='" . $dayStart . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutesStart);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearEnd . "' AND month='" . $monthEnd . "' AND day='" . $dayEnd . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutesEnd);


        ############################
        # Visszautasítom az időt   #
        ############################

        //triggerelek egy update -et (visszautasítom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='0' WHERE id='" . $workTimeId . "'");
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearStart . "' AND month='" . $monthStart . "' AND day='" . $dayStart . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearEnd . "' AND month='" . $monthEnd . "' AND day='" . $dayEnd . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);



        ##########################
        # Időpont rögzítése      #
        ##########################

        $startDate = '1910-12-31 23:30:00';
        $endDate = '1911-01-01 05:45:00';
        $yearStart = 1910;
        $monthStart = 12;
        $dayStart = 31;
        $yearEnd = 1911;
        $monthEnd = 1;
        $dayEnd = 1;
        $diffInMinutesStart = 30;
        $diffInMinutesEnd = 345;

        //létrehozok egy munkaidőt napon belül
        $stmt = self::$db->prepare("INSERT INTO working_time (project_id, date_from, date_to, description, created_at, created_by) 
                                      VALUES ('" . $projectId . "', '" . $startDate . "', '" . $endDate . "', 'teszt', '" . $this->_dateNow . "', '" . $userId . "')");
        $stmt->execute();
        $workTimeId = self::$db->lastInsertId();


        ##########################
        # Feltételezések         #
        ##########################

        //lekérdezem a létrejött értékeket és összevetem a feltételezett értékkel
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearStart . "' AND month='" . $monthStart . "' AND day='" . $dayStart . "' AND user_id='" . $userId . "'");
        $rowNumExpectation = 0==$sth->fetchColumn();
        $this->assertTrue($rowNumExpectation);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearEnd . "' AND month='" . $monthEnd . "' AND day='" . $dayEnd . "' AND user_id='" . $userId . "'");
        $rowNumExpectation = 0==$sth->fetchColumn();
        $this->assertTrue($rowNumExpectation);


        ############################
        # Jóváhagyom az időt       #
        ############################

        //triggerelek egy update -et (jóváhagyom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='1' WHERE id='" . $workTimeId . "'");
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutesStart + $diffInMinutesEnd);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearStart . "' AND month='" . $monthStart . "' AND day='" . $dayStart . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutesStart);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearEnd . "' AND month='" . $monthEnd . "' AND day='" . $dayEnd . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutesEnd);


        ############################
        # Visszautasítom az időt   #
        ############################

        //triggerelek egy update -et (visszautasítom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='0' WHERE id='" . $workTimeId . "'");
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearStart . "' AND month='" . $monthStart . "' AND day='" . $dayStart . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearEnd . "' AND month='" . $monthEnd . "' AND day='" . $dayEnd . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);



        ##########################
        # Időpont rögzítése      #
        ##########################

        $startDate = '1915-12-30 21:30:00';
        $endDate = '1916-01-10 02:45:00';
        $yearStart = 1915;
        $monthStart = 12;
        $dayStart = 30;
        $yearEnd = 1916;
        $monthEnd = 1;
        $dayEnd = 10;
        $diffInMinutesStart = 150;
        $diffInMinutesEnd = 165;
        $dayNum = 10;

        //létrehozok egy munkaidőt napon belül
        $stmt = self::$db->prepare("INSERT INTO working_time (project_id, date_from, date_to, description, created_at, created_by) 
                                      VALUES ('" . $projectId . "', '" . $startDate . "', '" . $endDate . "', 'teszt', '" . $this->_dateNow . "', '" . $userId . "')");
        $stmt->execute();
        $workTimeId = self::$db->lastInsertId();


        ##########################
        # Feltételezések         #
        ##########################

        //lekérdezem a létrejött értékeket és összevetem a feltételezett értékkel
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearStart . "' AND month='" . $monthStart . "' AND day='" . $dayStart . "' AND user_id='" . $userId . "'");
        $rowNumExpectation = 0==$sth->fetchColumn();
        $this->assertTrue($rowNumExpectation);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearEnd . "' AND month='" . $monthEnd . "' AND day='" . $dayEnd . "' AND user_id='" . $userId . "'");
        $rowNumExpectation = 0==$sth->fetchColumn();
        $this->assertTrue($rowNumExpectation);


        ############################
        # Jóváhagyom az időt       #
        ############################

        //triggerelek egy update -et (jóváhagyom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='1' WHERE id='" . $workTimeId . "'");
        //sleep(1);
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutesStart + ($dayNum * 24 * 60) + $diffInMinutesEnd);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearStart . "' AND month='" . $monthStart . "' AND day='" . $dayStart . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutesStart);

        $dateTime = new DateTime($startDate);
        for($i=1;$i<=$dayNum;$i++)
        {
            $newDate = $dateTime->modify("+1 day");
            $y = $dateTime->format('Y');
            $m = $dateTime->format('n');
            $d = $dateTime->format('j');

            //echo 'YMD:' . $y . ' ' . $m . ' ' . $d. "\n";
            $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $y . "' AND month='" . $m . "' AND day='" . $d . "' AND user_id='" . $userId . "'");
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals($row['wt_sum_minutes'], 24*60);

            $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $y . "' AND month='" . $m . "' AND day='" . $d . "' AND user_id='" . $userId . "'");
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals($row['wt_sum_minutes'], 24*60);
        }

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearEnd . "' AND month='" . $monthEnd . "' AND day='" . $dayEnd . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutesEnd);


        ############################
        # Visszautasítom az időt   #
        ############################

        //triggerelek egy update -et (visszautasítom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='0' WHERE id='" . $workTimeId . "'");
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearStart . "' AND month='" . $monthStart . "' AND day='" . $dayStart . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearEnd . "' AND month='" . $monthEnd . "' AND day='" . $dayEnd . "' AND user_id='" . $userId . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);


        //törlöm a teszteléshez létrehozott adatokat
        self::$db->query("DELETE FROM working_time_summary WHERE user_id='" . $userId . "'");
        self::$db->query("DELETE FROM working_time WHERE project_id='" . $projectId . "'");
        self::$db->query("DELETE FROM project WHERE id='" . $projectId . "'");
        self::$db->query("DELETE FROM project WHERE id='" . $projectId . "'");
        self::$db->query("DELETE FROM user WHERE id='" . $userId . "'");
    }

    /*
     * 3x projekt
     * 2x felhasználó
     * 2x napon túli munkaidő
     * 3x napon belüli munkaidő
     *
     * */
    public function testTriggersWithinMoreDays()
    {
        //létrehozom az első tesztusert
        $stmt = self::$db->prepare("INSERT INTO user (email, password, last_name, first_name, active, created_at) VALUES('doebrentei@hotmail.com', '', 'Teszt1 keresztnév', 'Teszt1 vezetéknév', 1, '" . $this->_dateNow . "')");
        $stmt->execute();
        $userId1 = self::$db->lastInsertId();

        //létrehozom a második tesztusert
        $stmt = self::$db->prepare("INSERT INTO user (email, password, last_name, first_name, active, created_at) VALUES('doebrentei2@hotmail.com', '', 'Teszt2 keresztnév', 'Teszt2 vezetéknév', 1, '" . $this->_dateNow . "')");
        $stmt->execute();
        $userId2 = self::$db->lastInsertId();

        //létrehozom az első tesztprojektet
        $stmt = self::$db->prepare("INSERT INTO project (name, description, active, created_at, created_by) VALUES ('Teszt1', 'próbaprojekt1', 1, '" . $this->_dateNow . "', " . $userId1 . ")");
        $stmt->execute();
        $projectId1 = self::$db->lastInsertId();


        ##########################
        # Időpont rögzítése      #
        ##########################

        $startDate1 = '1915-12-30 21:30:00';
        $endDate1 = '1916-01-10 02:45:00';
        $yearStart1 = 1915;
        $monthStart1 = 12;
        $dayStart1 = 30;
        $yearEnd1 = 1916;
        $monthEnd1 = 1;
        $dayEnd1 = 10;
        $diffInMinutesStart1 = 150;
        $diffInMinutesEnd1 = 165;
        $dayNum1 = 10;

        //létrehozok egy munkaidőt több napon belül
        $stmt = self::$db->prepare("INSERT INTO working_time (project_id, date_from, date_to, description, created_at, created_by) 
                                      VALUES ('" . $projectId1 . "', '" . $startDate1 . "', '" . $endDate1 . "', 'teszt', '" . $this->_dateNow . "', '" . $userId1 . "')");
        $stmt->execute();
        $workTimeId1 = self::$db->lastInsertId();

        $startDate2 = '1970-12-30 21:30:00';
        $endDate2 = '1971-01-10 02:45:00';
        $yearStart2 = 1970;
        $monthStart2 = 12;
        $dayStart2 = 30;
        $yearEnd2 = 1971;
        $monthEnd2 = 1;
        $dayEnd2 = 10;
        $diffInMinutesStart2 = 150;
        $diffInMinutesEnd2 = 165;
        $dayNum2 = 10;

        //létrehozok egy munkaidőt több napon belül
        $stmt = self::$db->prepare("INSERT INTO working_time (project_id, date_from, date_to, description, created_at, created_by) 
                                      VALUES ('" . $projectId1 . "', '" . $startDate2 . "', '" . $endDate2 . "', 'teszt', '" . $this->_dateNow . "', '" . $userId1 . "')");
        $stmt->execute();
        $workTimeId2 = self::$db->lastInsertId();


        $startDate3 = '1970-01-10 02:30:00';
        $endDate3 = '1970-01-10 03:30:00';
        $year3 = 1970;
        $month3 = 1;
        $day3 = 10;
        $diffInMinutes3 = 60;

        //létrehozok egy munkaidőt napon belül
        $stmt = self::$db->prepare("INSERT INTO working_time (project_id, date_from, date_to, description, created_at, created_by) 
                                      VALUES ('" . $projectId1 . "', '" . $startDate3 . "', '" . $endDate3 . "', 'teszt', '" . $this->_dateNow . "', '" . $userId1 . "')");
        $stmt->execute();
        $workTimeId3 = self::$db->lastInsertId();


        $startDate4 = '1970-01-16 18:30:00';
        $endDate4 = '1970-01-16 19:00:00';
        $year4 = 1970;
        $month4 = 1;
        $day4 = 16;
        $diffInMinutes3 = 60;

        //létrehozok egy munkaidőt napon belül
        $stmt = self::$db->prepare("INSERT INTO working_time (project_id, date_from, date_to, description, created_at, created_by) 
                                      VALUES ('" . $projectId1 . "', '" . $startDate4 . "', '" . $endDate4 . "', 'teszt', '" . $this->_dateNow . "', '" . $userId2 . "')");
        $stmt->execute();
        $workTimeId4 = self::$db->lastInsertId();


        ##########################
        # Feltételezések         #
        ##########################

        //lekérdezem a létrejött értékeket és összevetem a feltételezett értékkel
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId1 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearStart1 . "' AND month='" . $monthStart1 . "' AND day='" . $dayStart1 . "' AND user_id='" . $userId1 . "'");
        $rowNumExpectation = 0==$sth->fetchColumn();
        $this->assertTrue($rowNumExpectation);

        $dateTime = new DateTime($startDate1);
        for($i=1;$i<=$dayNum1;$i++)
        {
            $newDate = $dateTime->modify("+1 day");
            $y = $dateTime->format('Y');
            $m = $dateTime->format('n');
            $d = $dateTime->format('j');

            //echo 'YMD:' . $y . ' ' . $m . ' ' . $d. "\n";
            $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $y . "' AND month='" . $m . "' AND day='" . $d . "' AND user_id='" . $userId1 . "'");
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals($row['wt_sum_minutes'], 0);

            $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $y . "' AND month='" . $m . "' AND day='" . $d . "' AND user_id='" . $userId1 . "'");
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals($row['wt_sum_minutes'], 0);
        }

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearEnd1 . "' AND month='" . $monthEnd1 . "' AND day='" . $dayEnd1 . "' AND user_id='" . $userId1 . "'");
        $rowNumExpectation = 0==$sth->fetchColumn();
        $this->assertTrue($rowNumExpectation);

        ##################################################

        //lekérdezem a létrejött értékeket és összevetem a feltételezett értékkel
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId1 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearStart2 . "' AND month='" . $monthStart2 . "' AND day='" . $dayStart2 . "' AND user_id='" . $userId1 . "'");
        $rowNumExpectation = 0==$sth->fetchColumn();
        $this->assertTrue($rowNumExpectation);

        $dateTime = new DateTime($startDate1);
        for($i=1;$i<=$dayNum1;$i++)
        {
            $newDate = $dateTime->modify("+1 day");
            $y = $dateTime->format('Y');
            $m = $dateTime->format('n');
            $d = $dateTime->format('j');

            //echo 'YMD:' . $y . ' ' . $m . ' ' . $d. "\n";
            $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $y . "' AND month='" . $m . "' AND day='" . $d . "' AND user_id='" . $userId . "'");
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals($row['wt_sum_minutes'], 0);

            $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $y . "' AND month='" . $m . "' AND day='" . $d . "' AND user_id='" . $userId . "'");
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals($row['wt_sum_minutes'], 0);
        }

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearEnd2 . "' AND month='" . $monthEnd2 . "' AND day='" . $dayEnd2 . "' AND user_id='" . $userId1 . "'");
        $rowNumExpectation = 0==$sth->fetchColumn();
        $this->assertTrue($rowNumExpectation);

        ##################################################

        //A projekt összesítő még nulla
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId1 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        //A munkaidő összesítő szintén nulla
        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $year3 . "' AND month='" . $month3 . "' AND day='" . $day3 . "' AND user_id='" . $userId1 . "'");
        $rowNumExpectation = 0==$sth->fetchColumn();
        $this->assertTrue($rowNumExpectation);

        //A projekt összesítő még nulla
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId1 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        //A munkaidő összesítő szintén nulla
        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $year4 . "' AND month='" . $month4 . "' AND day='" . $day4 . "' AND user_id='" . $userId2 . "'");
        $rowNumExpectation = 0==$sth->fetchColumn();
        $this->assertTrue($rowNumExpectation);

        ##################################################

        ############################
        # Jóváhagyom az időt       #
        ############################


        //triggerelek egy update -et (jóváhagyom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='1' WHERE id='" . $workTimeId1 . "'");
        //sleep(1);
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId1 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutesStart1 + ($dayNum1 * 24 * 60) + $diffInMinutesEnd1);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearStart1 . "' AND month='" . $monthStart1 . "' AND day='" . $dayStart1 . "' AND user_id='" . $userId1 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutesStart1);

        $dateTime = new DateTime($startDate1);
        for($i=1;$i<=$dayNum1;$i++)
        {
            $newDate = $dateTime->modify("+1 day");
            $y = $dateTime->format('Y');
            $m = $dateTime->format('n');
            $d = $dateTime->format('j');

            //echo 'YMD:' . $y . ' ' . $m . ' ' . $d. "\n";
            $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $y . "' AND month='" . $m . "' AND day='" . $d . "' AND user_id='" . $userId1 . "'");
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals($row['wt_sum_minutes'], 24*60);

            $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $y . "' AND month='" . $m . "' AND day='" . $d . "' AND user_id='" . $userId1 . "'");
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals($row['wt_sum_minutes'], 24*60);
        }

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearEnd1 . "' AND month='" . $monthEnd1 . "' AND day='" . $dayEnd1 . "' AND user_id='" . $userId1 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutesEnd1);

        ##################################################

        //triggerelek egy update -et (jóváhagyom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='1' WHERE id='" . $workTimeId2 . "'");
        //sleep(1);
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId1 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], ($diffInMinutesStart1 + ($dayNum1 * 24 * 60) + $diffInMinutesEnd1) + ($diffInMinutesStart2 + ($dayNum2 * 24 * 60) + $diffInMinutesEnd2));

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearStart2 . "' AND month='" . $monthStart2 . "' AND day='" . $dayStart2 . "' AND user_id='" . $userId1 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutesStart2);

        $dateTime = new DateTime($startDate2);
        for($i=1;$i<=$dayNum2;$i++)
        {
            $newDate = $dateTime->modify("+1 day");
            $y = $dateTime->format('Y');
            $m = $dateTime->format('n');
            $d = $dateTime->format('j');

            //echo 'YMD:' . $y . ' ' . $m . ' ' . $d. "\n";
            $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $y . "' AND month='" . $m . "' AND day='" . $d . "' AND user_id='" . $userId1 . "'");
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals($row['wt_sum_minutes'], 24*60);

            $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $y . "' AND month='" . $m . "' AND day='" . $d . "' AND user_id='" . $userId1 . "'");
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals($row['wt_sum_minutes'], 24*60);
        }

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearEnd2 . "' AND month='" . $monthEnd2 . "' AND day='" . $dayEnd2 . "' AND user_id='" . $userId1 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutesEnd2);

        ##################################################

        //triggerelek egy update -et (jóváhagyom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='1' WHERE id='" . $workTimeId3 . "'");
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId1 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], ($diffInMinutesStart1 + ($dayNum1 * 24 * 60) + $diffInMinutesEnd1) + ($diffInMinutesStart2 + ($dayNum2 * 24 * 60) + $diffInMinutesEnd2) + $diffInMinutes3);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $year3 . "' AND month='" . $month3 . "' AND day='" . $day3 . "' AND user_id='" . $userId1 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutes3);

        ############################
        # Visszautasítom az időt   #
        ############################

        //triggerelek egy update -et (jóváhagyom a bejegyzett munkaidőt)
        self::$db->query("UPDATE working_time SET approved='0' WHERE id='" . $workTimeId1 . "'");
        //sleep(1);
        $sth = self::$db->query("SELECT * FROM project WHERE id='" . $projectId1 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], $diffInMinutesStart2 + ($dayNum2 * 24 * 60) + $diffInMinutesEnd2 + $diffInMinutes3);

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearStart1 . "' AND month='" . $monthStart1 . "' AND day='" . $dayStart1 . "' AND user_id='" . $userId1 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        $dateTime = new DateTime($startDate1);
        for($i=1;$i<=$dayNum1;$i++)
        {
            $newDate = $dateTime->modify("+1 day");
            $y = $dateTime->format('Y');
            $m = $dateTime->format('n');
            $d = $dateTime->format('j');

            //echo 'YMD:' . $y . ' ' . $m . ' ' . $d. "\n";
            $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $y . "' AND month='" . $m . "' AND day='" . $d . "' AND user_id='" . $userId1 . "'");
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals($row['wt_sum_minutes'], 0);

            $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $y . "' AND month='" . $m . "' AND day='" . $d . "' AND user_id='" . $userId1 . "'");
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals($row['wt_sum_minutes'], 0);
        }

        $sth = self::$db->query("SELECT * FROM working_time_summary WHERE year='" . $yearEnd1 . "' AND month='" . $monthEnd1 . "' AND day='" . $dayEnd1 . "' AND user_id='" . $userId1 . "'");
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['wt_sum_minutes'], 0);

        ##################################################
        //törlöm a teszteléshez létrehozott adatokat
        self::$db->query("DELETE FROM working_time_summary WHERE user_id='" . $userId1 . "'");
        self::$db->query("DELETE FROM working_time_summary WHERE user_id='" . $userId2 . "'");
        self::$db->query("DELETE FROM working_time WHERE project_id='" . $projectId1 . "'");
        self::$db->query("DELETE FROM project WHERE id='" . $projectId1 . "'");
        self::$db->query("DELETE FROM project WHERE id='" . $projectId1 . "'");
        self::$db->query("DELETE FROM user WHERE id='" . $userId1 . "'");
        self::$db->query("DELETE FROM user WHERE id='" . $userId2 . "'");
    }
}
?>