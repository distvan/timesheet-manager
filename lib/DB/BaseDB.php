<?php
namespace DotLogics\DB;


abstract class BaseDB implements iDB
{
    protected $_db;
    protected $_log;

    public function __construct($db, $log)
    {
        $this->_db = $db;
        $this->_log = $log;
    }
}
?>