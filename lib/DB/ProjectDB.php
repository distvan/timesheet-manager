<?php
namespace DotLogics\DB;

use Exception;
use PDO;
use DateTime;

class ProjectDB extends BaseDB
{
    const TABLE_NAME = 'project';

    private $_id;
    private $_parent_id;
    private $_name;
    private $_description;
    private $_wt_sum_minutes;
    private $_active;
    private $_created_at;
    private $_created_by;

    public function __construct($db, $log)
    {
        parent::__construct($db, $log);
        $dt = new DateTime('now');
        $this->_created_at = $dt->format('Y-m-d H:i:s');
    }

    public function save()
    {
        if(empty($this->_created_by))
        {
            throw new Exception('Empty created_by field!', ExceptionMessagesDB::EXCEPTION_PROJECT_NO_CREATOR_ID);
        }

        try
        {
            $stmt = $this->_db->prepare("INSERT INTO " . self::TABLE_NAME . " SET parent_id=:parent_id, name=:name, description=:description, active=:active, created_at=:created_at, created_by=:created_by");

            $stmt->bindParam(":parent_id", $this->_parent_id);
            $stmt->bindParam(":name", $this->_name);
            $stmt->bindParam(":description", $this->_description);
            $stmt->bindParam(":active", $this->_active);
            $stmt->bindParam(":created_at", $this->_created_at);
            $stmt->bindParam(":created_by", $this->_created_by);

            $stmt->execute();

            return $this->_db->lastInsertId();
        }
        catch(Exception $e)
        {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());
            return $e;
        }
    }

    public function delete()
    {
        if(empty($this->_id))
        {
            throw new Exception('No project ID!', ExceptionMessagesDB::EXCEPTION_PROJECT_NO_PROJECT_ID);
        }

        try
        {
            $stmt = $this->_db->prepare("DELETE FROM " . self::TABLE_NAME . " WHERE id=:id");
            $stmt->bindParam(":id", $this->_id);
            $stmt->execute();
        }
        catch(Exception $e)
        {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());
        }
    }

    public function get()
    {
        if(empty($this->_id))
        {
            throw new Exception('No project ID!', ExceptionMessagesDB::EXCEPTION_PROJECT_NO_PROJECT_ID);
        }

        try
        {
            $stmt = $this->_db->prepare("SELECT id, parent_id, name, description, active, wt_sum_minutes, created_at, created_by FROM " . self::TABLE_NAME . " WHERE id=:id");

            $stmt->bindParam(":id", $this->_id);
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $row = $stmt->fetch();

            if($row)
            {
                $this->_id = $row['id'];
                $this->_parent_id = $row['parent_id'];
                $this->_name = $row['name'];
                $this->_description = $row['description'];
                $this->_active = $row['active'];
                $this->_wt_sum_minutes = $row['wt_sum_minutes'];
                $this->_created_at = $row['created_at'];
                $this->_created_by = $row['created_by'];

                return $this;
            }
        }
        catch(Exception $e)
        {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());
        }

        return false;
    }


    public function getAllCreatedBy($createdBy)
    {
        $result = array();

        if(empty($createdBy))
        {
            throw new Exception('Empty Parameter (created_by)!', ExceptionMessagesDB::EXCEPTION_USER_NO_CREATED_BY);
        }

        try
        {
            $stmt = $this->_db->prepare("SELECT id, parent_id, name, description, active, wt_sum_minutes, created_at, created_by FROM " . self::TABLE_NAME . " WHERE created_by=:created_by");
            $stmt->bindParam(":created_by", $createdBy);
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);

            foreach($stmt->fetchAll() as $item)
            {
                $pDB = new ProjectDB($this->_db, $this->_log);
                $pDB->setId($item['id']);
                $pDB->setParentId($item['parent_id']);
                $pDB->setName($item['name']);
                $pDB->setDescription($item['description']);
                $pDB->setActive($item['active']);
                $pDB->setWtSumMinutes($item['wt_sum_minutes']);
                $pDB->setCreatedAt($item['created_at']);
                $pDB->setCreatedBy($item['created_by']);

                array_push($result, $pDB);
            }
        }
        catch(PDOException $e)
        {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());

            return $e;
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->_parent_id;
    }

    /**
     * @param mixed $parent_id
     */
    public function setParentId($parent_id)
    {
        $this->_parent_id = $parent_id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->_description = $description;
    }

    /**
     * @return mixed
     */
    public function getWtSumMinutes()
    {
        return $this->_wt_sum_minutes;
    }

    /**
     * @param mixed $wt_sum_minutes
     */
    public function setWtSumMinutes($wt_sum_minutes)
    {
        $this->_wt_sum_minutes = $wt_sum_minutes;
    }

    /**
     * @return mixed
     */
    public function getActive()
    {
        return $this->_active;
    }

    /**
     * @param mixed $active
     */
    public function setActive($active)
    {
        $this->_active = $active;
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->_created_at;
    }

    /**
     * @param string $created_at
     */
    public function setCreatedAt($created_at)
    {
        $this->_created_at = $created_at;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy()
    {
        return $this->_created_by;
    }

    /**
     * @param mixed $created_by
     */
    public function setCreatedBy($created_by)
    {
        $this->_created_by = $created_by;
    }
}
?>