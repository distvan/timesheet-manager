<?php
namespace DotLogics\DB;

use DotLogics\Password;
use DateTime;
use PDO;
use Exception;

/**
 * Database User Handling class
 *
 * @author Istvan Dobrentei
 * @copyright DotLogics Hungary Kft.
 * @url https://www.dotlogics.hu
 *
 */
class UserDB extends BaseDB
{
    const TABLE_NAME = 'user';

    private $_id;
    private $_email;
    private $_password;
    private $_last_name;
    private $_first_name;
    private $_active;
    private $_created_at;
    private $_created_by;
    
    public function getUserByEmail($email)
    {
        try
        {
            $stmt = $this->_db->prepare("SELECT id, email, password, last_name, first_name, active, created_at, created_by FROM " . self::TABLE_NAME . " WHERE email=:email");
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $row = $stmt->fetch();
            if($row)
            {
                $this->_id = $row['id'];
                $this->_email = $row['email'];
                $this->_password = $row['password'];
                $this->_last_name = $row['last_name'];
                $this->_first_name = $row['first_name'];
                $this->_active = $row['active'];
                $this->_created_at = $row['created_at'];
                $this->_created_by = $row['created_by'];

                return $this;
            }
        }
        catch(PDOException $e)
        {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());
        }

        return false;
    }

    public function getAllUser($showOnlyActive=false)
    {
        $result = array();

        try
        {
            if($showOnlyActive)
            {
                $stmt = $this->_db->prepare("SELECT * FROM " . self::TABLE_NAME . " WHERE active=:active");
                $stmt->bindParam(":active", $showOnlyActive);
            }
            else
            {
                $stmt = $this->_db->prepare("SELECT * FROM " . self::TABLE_NAME);
            }
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);

            foreach($stmt->fetchAll() as $item)
            {
                $uDB = new UserDB($this->_db, $this->_log);
                $uDB->setId($item['id']);
                $uDB->setEmail($item['email']);
                $uDB->setFirstName($item['first_name']);
                $uDB->setLastName($item['last_name']);
                $uDB->setCreatedBy($item['created_by']);
                $uDB->setCreatedAt($item['created_at']);
                $uDB->setActive($item['active']);

                array_push($result, $uDB);
            }
        }
        catch(PDOException $e)
        {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());

            return $e;
        }

        return $result;
    }

    public function getAllUserCreatedBy($createdBy)
    {
        $result = array();

        if(empty($createdBy))
        {
            throw new Exception('Empty Parameter (created_by)!', ExceptionMessagesDB::EXCEPTION_USER_NO_CREATED_BY);
        }

        try
        {
            $stmt = $this->_db->prepare("SELECT * FROM " . self::TABLE_NAME . " WHERE created_by=:created_by");
            $stmt->bindParam(":created_by", $createdBy);
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);

            foreach($stmt->fetchAll() as $item)
            {
                $uDB = new UserDB($this->_db, $this->_log);
                $uDB->setId($item['id']);
                $uDB->setEmail($item['email']);
                $uDB->setFirstName($item['first_name']);
                $uDB->setLastName($item['last_name']);
                $uDB->setCreatedBy($item['created_by']);
                $uDB->setCreatedAt($item['created_at']);
                $uDB->setActive($item['active']);

                array_push($result, $uDB);
            }
        }
        catch(PDOException $e)
        {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());

            return $e;
        }

        return $result;
    }

    public function get()
    {
        // TODO: Implement get() method.
    }

    public function save()
    {
        $password = new Password();
        $now = new DateTime('now');
        $encodedPassword = $password->encode($this->_password);
        $dateTimeStr = $now->format("Y-m-d H:i:s");

        try
        {
            $stmt = $this->_db->prepare("INSERT INTO " . self::TABLE_NAME . " SET email=:email, password=:password, last_name=:last_name, first_name=:first_name, active=:active, created_at=:created_at, created_by=:created_by");
            $stmt->bindParam(":email", $this->_email);
            $stmt->bindParam(":password", $encodedPassword);
            $stmt->bindParam(":last_name", $this->_last_name);
            $stmt->bindParam(":first_name", $this->_first_name);
            $stmt->bindParam(":active", $this->_active);
            $stmt->bindParam(":created_at", $dateTimeStr);
            $stmt->bindParam(":created_by", $this->_created_by);
            $stmt->execute();

            return $this->_db->lastInsertId();
        }
        catch(Exception $e)
        {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());
            return $e;
        }

        return false;
    }

    public function isValidUserPassword($passwordIn)
    {
        $password = new Password();

        $user = $this->getUserByEmail($this->_email);

        return $password->isValid($passwordIn, $this->_password);
    }

    public function delete()
    {
        if(empty($this->_id))
        {
            throw new Exception('Empty user Id!', ExceptionMessagesDB::EXCEPTION_USER_EMPTY_USERID);
        }

        try
        {
            $stmt = $this->_db->prepare("DELETE FROM " . self::TABLE_NAME . " WHERE id=:userid");
            $stmt->bindParam(":userid", $this->_id);
            $stmt->execute();
        }
        catch(PDOException $e)
        {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());
        }
    }

    public function addUserToPackage()
    {
        if(empty($this->_company))
        {
            throw new Exception('No company!', ExceptionMessagesDB::EXCEPTION_USER_NO_COMPANY);
        }
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->_email = $email;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->_password = $password;
    }

    /**
     * @param mixed $last_name
     */
    public function setLastName($last_name)
    {
        $this->_last_name = $last_name;
    }

    /**
     * @param mixed $first_name
     */
    public function setFirstName($first_name)
    {
        $this->_first_name = $first_name;
    }

    /**
     * @param mixed $active
     */
    public function setActive($active)
    {
        $this->_active = $active;
    }

    /**
     * @param mixed $created_at
     */
    public function setCreatedAt($created_at)
    {
        $this->_created_at = $created_at;
    }

    /**
     * @param mixed $created_by
     */
    public function setCreatedBy($created_by)
    {
        $this->_created_by = $created_by;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->_email;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->_last_name;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->_first_name;
    }

    /**
     * @return mixed
     */
    public function getActive()
    {
        return $this->_active;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->_created_at;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy()
    {
        return $this->_created_by;
    }
}
?>