<?php
namespace DotLogics\DB;

use PDO;
use Exception;
use DateTime;

/**
 * Database WorkingTime Handling class
 *
 * @author Istvan Dobrentei
 * @copyright DotLogics Hungary Kft.
 * @url https://www.dotlogics.hu
 *
 */
class WorkingTimeDB extends BaseDB
{
    const TABLE_NAME = 'working_time';

    private $_id;
    private $_project_id;
    private $_date_from;
    private $_date_to;
    private $_description;
    private $_approved;
    private $_approved_at;
    private $_approved_by;
    private $_created_at;
    private $_created_by;

    public function save()
    {
        try
        {
            $fromDate = new DateTime($this->_date_from);
            $toDate = new DateTime($this->_date_to);
        }
        catch(Exception $e)
        {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());
            return $e;
        }

        if($fromDate >= $toDate)
        {
            throw new Exception('The to date should be greater than the from date',
                ExceptionMessagesDB::EXCEPTION_WORKINGTIME_FROM_DATE_GREATER_THAN_TO_DATE);
        }

        $dt = new DateTime('now');
        $this->_created_at = $dt->format('Y-m-d H:i:s');

        try
        {
            $stmt = $this->_db->prepare("INSERT INTO " . self::TABLE_NAME . " SET project_id=:project_id, date_from=:date_from, date_to=:date_to, 
                            description=:description, approved=:approved, approved_at=:approved_at, approved_by=:approved_by, created_at=:created_at, created_by=:created_by");

            $stmt->bindParam(":project_id", $this->_project_id);
            $stmt->bindParam(":date_from", $this->_date_from);
            $stmt->bindParam(":date_to", $this->_date_to);
            $stmt->bindParam(":description", $this->_description);
            $stmt->bindParam(":approved", $this->_approved);
            $stmt->bindParam(":approved_at", $this->_approved_at);
            $stmt->bindParam(":approved_by", $this->_approved_by);
            $stmt->bindParam(":created_at", $this->_created_at);
            $stmt->bindParam(":created_by", $this->_created_by);

            $stmt->execute();

            return $this->_db->lastInsertId();
        }
        catch (Exception $e)
        {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());
            return $e;
        }
    }

    public function delete()
    {
        if (empty($this->_id))
        {
            throw new Exception('No workingtime ID!', ExceptionMessagesDB::EXCEPTION_WORKINGTIME_NO_ID);
        }

        try {
            $stmt = $this->_db->prepare("DELETE FROM " . self::TABLE_NAME . " WHERE id=:id");
            $stmt->bindParam(":id", $this->_id);
            $stmt->execute();
        } catch (Exception $e) {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());
        }
    }

    public function get()
    {
        if(empty($this->_id))
        {
            throw new Exception('No workingtime ID!', ExceptionMessagesDB::EXCEPTION_WORKINGTIME_NO_ID);
        }

        try
        {
            $stmt = $this->_db->prepare("SELECT id, project_id, date_from, date_to, description, approved, approved_at, approved_by, created_at, created_by FROM " . self::TABLE_NAME . " WHERE id=:id");
            $stmt->bindParam(":id", $this->_id);

            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $row = $stmt->fetch();

            if($row)
            {
                $this->_id = $row['id'];
                $this->_project_id = $row['project_id'];
                $this->_date_from = $row['date_from'];
                $this->_date_to = $row['date_to'];
                $this->_description = $row['description'];
                $this->_approved = $row['approved'];
                $this->_approved_at = $row['approved_at'];
                $this->_approved_by = $row['approved_by'];
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

    public function getBetween($dateFrom, $dateTo, $projectId)
    {
        try
        {
            $stmt = $this->_db->prepare("SELECT wt.id, wt.project_id, wt.date_from, wt.date_to, wt.description, wt.approved, wt.approved_at, wt.approved_by, wt.created_at, wt.created_by 
                                         FROM " . self::TABLE_NAME . " wt, " . ProjectDB::TABLE_NAME . " p 
                                         WHERE wt.project_id=p.id AND (p.parent_id=:project_id OR p.id=:project_id) AND wt.date_from>=:date_from  AND wt.date_to<=:date_to");

            $stmt->bindParam(":date_from", $dateFrom);
            $stmt->bindParam(":date_to", $dateTo);
            $stmt->bindParam(":project_id", $projectId);

            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);

            return $stmt->fetchALL();

        }
        catch(Exception $e)
        {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());
        }
    }

    public function approveWorkingTime($workingTimeId, $approvedBy, $approving)
    {
        try
        {
            $dt = new DateTime('now');
            $approvedAt = $dt->format('Y-m-d H:i:s');
            $appby = (int)$approvedBy;
            $app = (int)$approving;

            $stmt = $this->_db->prepare("UPDATE " . self::TABLE_NAME . " SET approved=:approved, approved_at=:approved_at, approved_by=:approved_by WHERE id=:workingtime_id");

            $stmt->bindParam(":workingtime_id", $workingTimeId);
            $stmt->bindParam(":approved_at", $approvedAt);
            $stmt->bindParam(":approved_by", $appby);
            $stmt->bindParam(":approved", $app);
            $stmt->execute();

            return true;
        }
        catch(Exception $e)
        {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());

            return $e;
        }
    }

    public function getAllTodayForUser()
    {
        $dt = new DateTime('NOW');
        $now = $dt->format('Y-m-d');
        $dateFrom = $now . ' 00:00:00';
        $dateTo = $now . ' 23:59:59';
        $createdBy = $this->getCreatedBy();

        try
        {
            $stmt = $this->_db->prepare("SELECT id, description,
                                        EXTRACT(HOUR FROM date_from) AS from_hour, 
                                        EXTRACT(MINUTE FROM date_from) AS from_min,
                                        EXTRACT(HOUR FROM date_to) AS to_hour, 
                                        EXTRACT(MINUTE FROM date_to) AS to_min,
                                        ROUND(TIMESTAMPDIFF(MINUTE, date_from, date_to)/60, 2) AS hours 
                                        FROM " . self::TABLE_NAME . " 
                                        WHERE created_by=:created_by AND date_from>=:date_from AND date_to<=:date_to");

            $stmt->bindParam(":created_by", $createdBy);
            $stmt->bindParam(":date_from", $dateFrom);
            $stmt->bindParam(":date_to", $dateTo);
            $stmt->execute();

            return $stmt->fetchALL();
        }
        catch(Exception $e)
        {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());

            return $e;
        }
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
    public function getDateFrom()
    {
        return $this->_date_from;
    }

    /**
     * @param mixed $date_from
     */
    public function setDateFrom($date_from)
    {
        $this->_date_from = $date_from;
    }

    /**
     * @return mixed
     */
    public function getDateTo()
    {
        return $this->_date_to;
    }

    /**
     * @param mixed $date_to
     */
    public function setDateTo($date_to)
    {
        $this->_date_to = $date_to;
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
    public function getApproved()
    {
        return $this->_approved;
    }

    /**
     * @param mixed $approved
     */
    public function setApproved($approved)
    {
        $this->_approved = $approved;
    }

    /**
     * @return mixed
     */
    public function getApprovedAt()
    {
        return $this->_approved_at;
    }

    /**
     * @param mixed $approved_at
     */
    public function setApprovedAt($approved_at)
    {
        $this->_approved_at = $approved_at;
    }

    /**
     * @return mixed
     */
    public function getApprovedBy()
    {
        return $this->_approved_by;
    }

    /**
     * @param mixed $approved_by
     */
    public function setApprovedBy($approved_by)
    {
        $this->_approved_by = $approved_by;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->_created_at;
    }

    /**
     * @param mixed $created_at
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