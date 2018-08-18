<?php

namespace DotLogics\DB;

use Exception;

/**
 * Database Invoice Items Handling class
 *
 * @author Istvan Dobrentei
 * @copyright DotLogics Hungary Kft.
 * @url https://www.dotlogics.hu
 *
 */
class InvoiceItemsDB extends BaseDB
{
    const TABLE_NAME = 'invoice_item';

    private $_invoice_item_id;
    private $_invoice_id;
    private $_wt_id;

    public function save()
    {
        try
        {
            $stmt = $this->_db->prepare("INSERT INTO " . self::TABLE_NAME . " SET invoice_id=:invoice_id, wt_id=:wt_id");
            $stmt->bindParam(":invoice_id", $this->_invoice_id);
            $stmt->bindParam(":wt_id", $this->_wt_id);

            $stmt->execute();

            $this->_invoice_item_id = (int)$this->_db->lastInsertId();

            return $this->_invoice_item_id;
        }
        catch(Exception $e)
        {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());

            throw $e;
        }
    }

    public function get()
    {
        // TODO: Implement get() method.
    }

    public function delete()
    {
        // TODO: Implement delete() method.
    }

    /**
     * @return mixed
     */
    public function getInvoiceItemId()
    {
        return $this->_invoice_item_id;
    }

    /**
     * @param mixed $invoice_item_id
     */
    public function setInvoiceItemId($invoice_item_id)
    {
        $this->_invoice_item_id = $invoice_item_id;
    }

    /**
     * @return mixed
     */
    public function getInvoiceId()
    {
        return $this->_invoice_id;
    }

    /**
     * @param mixed $invoice_id
     */
    public function setInvoiceId($invoice_id)
    {
        $this->_invoice_id = $invoice_id;
    }

    /**
     * @return mixed
     */
    public function getWtId()
    {
        return $this->_wt_id;
    }

    /**
     * @param mixed $wt_id
     */
    public function setWtId($wt_id)
    {
        $this->_wt_id = $wt_id;
    }
}