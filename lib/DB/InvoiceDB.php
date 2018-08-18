<?php

namespace DotLogics\DB;

use Exception;

/**
 * Database Invoice Handling class
 *
 * @author Istvan Dobrentei
 * @copyright DotLogics Hungary Kft.
 * @url https://www.dotlogics.hu
 *
 */
class InvoiceDB extends BaseDB
{
    const TABLE_NAME = 'invoice';

    private $_invoice_id;
    private $_invoice_no;
    private $_items;
    private $_saved_by;

    public function save()
    {
        if(empty($this->_invoice_no))
        {
            throw new Exception('No invoice number!', ExceptionMessagesDB::EXCEPTION_INVOICE_NO_INVOICE_NUMBER);
        }

        if(empty($this->_items))
        {
            throw new Exception('No invoice items!', ExceptionMessagesDB::EXCEPTION_INVOICE_NO_INVOICE_ITEM);
        }

        $result = $this->createInvoice();

        if(is_numeric($result))
        {
            foreach($this->_items as $itemId)
            {
                $invItem = new InvoiceItemsDB($this->_db, $this->_log);
                $invItem->setInvoiceId($result);
                $invItem->setWtId($itemId);
                $invItem->save();

                $wt = new WorkingTimeDB($this->_db, $this->_log);
                $wt->approveWorkingTime($itemId, $this->_saved_by, 1);
            }

            return true;
        }

        throw $result;
    }

    /**
     * Create invoice record
     *
     * @return int | Exception
     */
    protected function createInvoice()
    {
        try
        {
            $stmt = $this->_db->prepare("INSERT INTO " . self::TABLE_NAME . " SET invoice_no=:invoice_no");
            $stmt->bindParam(":invoice_no", $this->_invoice_no);

            $stmt->execute();

            return $this->_db->lastInsertId();
        }
        catch(Exception $e)
        {
            $this->_log->error('[' . __CLASS__ . '::' . __FUNCTION__ . ']' . $e->getMessage());

            return $e;
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
    public function getInvoiceNo()
    {
        return $this->_invoice_no;
    }

    /**
     * @param mixed $invoice_no
     */
    public function setInvoiceNo($invoice_no)
    {
        $this->_invoice_no = $invoice_no;
    }

    /**
     * @return mixed
     */
    public function getItems()
    {
        return $this->_items;
    }

    /**
     * @param mixed $items
     */
    public function setItems($items)
    {
        $this->_items = $items;
    }

    /**
     * @param mixed $saved_by
     */
    public function setSavedBy($saved_by)
    {
        $this->_saved_by = $saved_by;
    }
}