<?php
namespace DotLogics\DB;

use Exception;
use PDO;

class UserProjectDB extends BaseDB
{
    const TABLE_NAME = 'user_project';

    private $_user_id;
    private $_project_id;
    private $_is_leader;

    public function save()
    {
        if(empty($this->_user_id) && empty($this->_project_id))
        {
            throw new Exception('Empty user_id and project_id field!', ExceptionMessagesDB::EXCEPTION_USER_PROJECT_NO_USER_PROJECT_ID);
        }

        try
        {
            $stmt = $this->_db->prepare("INSERT INTO " . self::TABLE_NAME . " SET user_id=:user_id, project_id=:project_id, is_leader=:is_leader");

            $stmt->bindParam(":user_id", $this->_user_id);
            $stmt->bindParam(":project_id", $this->_project_id);
            $stmt->bindParam(":is_leader", $this->_is_leader);

            $stmt->execute();

            return true;
        }
        catch(Exception $e)
        {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());

            return $e;
        }
    }

    public function delete()
    {
        try
        {
            $stmt = $this->_db->prepare("DELETE FROM " . self::TABLE_NAME . " WHERE user_id=:user_id AND project_id=:project_id");

            $stmt->bindParam(":user_id", $this->_user_id);
            $stmt->bindParam(":project_id", $this->_project_id);

            $stmt->execute();

            return true;
        }
        catch(Exception $e)
        {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());

            return $e;
        }
    }

    public function get()
    {
        try
        {
            $stmt = $this->_db->prepare("SELECT user_id, project_id, is_leader FROM " . self::TABLE_NAME . " WHERE user_id=:user_id AND project_id=:project_id");

            $stmt->bindParam(":user_id", $this->_user_id);
            $stmt->bindParam(":project_id", $this->_project_id);

            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $row = $stmt->fetch();

            if($row)
            {
                $this->_user_id = $row['user_id'];
                $this->_project_id = $row['project_id'];
                $this->_is_leader = $row['is_leader'];

                return $this;
            }
        }
        catch(Exception $e)
        {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());
        }
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->_user_id;
    }

    /**
     * @param mixed $user_id
     */
    public function setUserId($user_id)
    {
        $this->_user_id = $user_id;
    }

    /**
     * @return mixed
     */
    public function getProjectId()
    {
        return $this->_project_id;
    }

    /**
     * @param mixed $project_id
     */
    public function setProjectId($project_id)
    {
        $this->_project_id = $project_id;
    }

    /**
     * @return mixed
     */
    public function getIsLeader()
    {
        return $this->_is_leader;
    }

    /**
     * @param mixed $is_leader
     */
    public function setIsLeader($is_leader)
    {
        $this->_is_leader = $is_leader;
    }
}
?>