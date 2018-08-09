<?php
require dirname(dirname(__FILE__)) . '/vendor/autoload.php';
date_default_timezone_set("Europe/Budapest");
error_reporting(1);


class TestBase extends PHPUnit_Framework_TestCase
{
    const BASE_URL = 'http://timesheet-manager.docker';
    protected static $db;
    protected static $log;
    protected $_db;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$db = new PDO("mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME'), getenv('DB_USER'),
            getenv('DB_PASS'));
        self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$log = Logger::getLogger('timesheetlogger');
        fwrite(STDOUT, __METHOD__ . "\n");
    }

    public function setUp()
    {
        parent::setUp();
        $this->_db = self::$db;
        fwrite(STDOUT, __METHOD__ . "\n");
    }
}

?>