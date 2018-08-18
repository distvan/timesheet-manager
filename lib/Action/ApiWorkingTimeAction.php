<?php
namespace DotLogics\Action;

use DotLogics\DB\InvoiceDB;
use DotLogics\DB\InvoiceItemsDB;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use DotLogics\DB\WorkingTimeDB;
use DotLogics\DB\WorkingTimeModificationDB;
use DotLogics\Report;

class ApiWorkingTimeAction
{
    private $_db;
    private $_log;

    public function __construct($db, $log)
    {
        $this->_log = $log;
        $this->_db = $db;
    }

    /* Add WorkingTime
    *
    * input: projectid, datefrom, dateto, description, createdby
    * output: error or userId
    *
    * */
    public function addWorkingTime(Request $request, Response $response, $args)
    {
        $params = json_decode(file_get_contents('php://input'));

        $projectId = isset($params->projectid) ? $params->projectid : $request->getParam('projectid');
        $dateFrom = isset($params->datefrom) ? $params->datefrom : $request->getParam('datefrom');
        $dateTo = isset($params->dateto) ? $params->dateto : $request->getParam('dateto');
        $description = isset($params->description) ? $params->description : $request->getParam('description');
        $createdBy = (int)$request->getAttribute('user_id');    //it comes from the token in middleware

        $workingTime = new WorkingTimeDB($this->_db, $this->_log);
        $workingTime->setProjectId($projectId);
        $workingTime->setDateFrom($dateFrom);
        $workingTime->setDateTo($dateTo);
        $workingTime->setDescription($description);
        $workingTime->setCreatedBy($createdBy);
        $workingTime->setApproved(0);
        $workingTimeId = $workingTime->save();

        if(!is_numeric($workingTimeId) && $workingTimeId instanceof Exception)
        {
            return $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode(array('code' => $workingTimeId->getCode(), 'error' => $workingTimeId->getMessage())));
        }

        return $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(array('result' => $workingTimeId)));
    }


    /* Approving WorkingTime
     *
     * input: workingtimeid, approve, approveby (optional: comment, changing)
     * output: error or success json
     *
     * */
    public function approveWorkingTime(Request $request, Response $response, $args)
    {
        $params = json_decode(file_get_contents('php://input'));

        $workingTimeId = isset($params->workingtimeid) ? (int)$params->workingtimeid : (int)$request->getParam('workingtimeid');
        $approve = isset($params->approve) ? (int)$params->approve : (int)$request->getParam('approve');
        $approvedBy = isset($params->approvedby) ? (int)$params->approvedby : (int)$request->getParam('approvedby');

        $comment = isset($params->comment) ? $params->comment : $request->getParam('comment');
        $changing = isset($params->changing) ? $params->changing : $request->getParam('changing');

        $workingTime = new WorkingTimeDB($this->_db, $this->_log);
        $answer = $workingTime->approveWorkingTime($workingTimeId, $approvedBy, $approve);

        if(is_a($answer, 'Exception'))
        {
            return $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode(array('code' => $answer->getCode(), 'error' => $answer->getMessage())));
        }

        if(!$approve && !is_a($answer, 'Exception') && (!empty($comment) || !empty($changing)))
        {
            $wtMod = new WorkingTimeModificationDB($this->_db, $this->_log);
            $wtMod->setWorkingTimeId($workingTimeId);
            $wtMod->setComment($comment);
            $wtMod->setChanging($changing);
            $wtMod->setCreatedBy($approvedBy);
            $wtmId = $wtMod->save();
            if(is_a($wtmId, 'Exception'))
            {
                return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode(array('code' => $wtmId->getCode(), 'error' => $wtmId->getMessage())));
            }
        }

        return $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(array('result' => $answer)));
    }

    /**
     * Get today all recorded working times for current user
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function getTodayWorkingTimes(Request $request, Response $response, $args)
    {
        $createdBy = (int)$request->getAttribute('user_id');    //it comes from the token in middleware

        $workingTime = new WorkingTimeDB($this->_db, $this->_log);
        $workingTime->setCreatedBy($createdBy);

        $workingTimes = $workingTime->getAllForUser();

        return $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(array('result' => $workingTimes)));
    }

    /**
     * Delete one selected working time
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     * @throws \Exception
     */
    public function deleteWorkingTime(Request $request, Response $response, $args)
    {
        $params = json_decode(file_get_contents('php://input'));
        $workingTimeId = isset($params->id) ? (int)$params->id : (int)$request->getParam('id');

        $workingTime = new WorkingTimeDB($this->_db, $this->_log);
        $workingTime->setId($workingTimeId);
        $workingTime->delete();

        return $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(array('result' => 'ok')));
    }

    /**
     * Get filtered working times
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function getFiltered(Request $request, Response $response, $args)
    {
        $projectId = (int)$args['project_id'];
        $from = $args['from'];
        $to = $args['to'];
        $createdBy = (int)$request->getAttribute('user_id');    //it comes from the token in middleware

        $workingTime = new WorkingTimeDB($this->_db, $this->_log);
        $workingTime->setProjectId($projectId);
        $workingTime->setCreatedBy($createdBy);
        $workingTime->setDateFrom($from);
        $workingTime->setDateTo($to);
        $result = $workingTime->getAllForUser();

        return $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(array('result' => $result)));
    }

    public function createExport(Request $request, Response $response, $args)
    {
        $projectId = (int)$args['project_id'];
        $from = $args['from'];
        $to = $args['to'];
        $createdBy = (int)$request->getAttribute('user_id');    //it comes from the token in middleware
        $language = $args['lang'];
        $format = isset($args['format']) ? $args['format'] : 'pdf';

        $report = new Report($this->_db, $this->_log);
        $report->setLanguage($language);
        $pdfContent = $report->generateSummaryTimeReport($from, $to, $projectId);

        return $response->withStatus(200)
            ->withHeader('Content-type', 'application/pdf')
            ->write($pdfContent);
    }


    /**
     * Attach a period of worktimes to an invoice
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function attachInvoice(Request $request, Response $response, $args)
    {
        $params = json_decode(file_get_contents('php://input'));
        $workingTimeIds = isset($params->wt_ids) ? $params->wt_ids : $request->getParam('wt_ids');
        $invoiceNo = isset($params->invoice_no) ? $params->invoice_no : $request->getParam('invoice_no');
        $savedBy = (int)$request->getAttribute('user_id');    //it comes from the token in middleware

        if(is_array($workingTimeIds))
        {
            $invoice = new InvoiceDB($this->_db, $this->_log);
            $invoice->setSavedBy($savedBy);
            $invoice->setInvoiceNo($invoiceNo);
            $invoice->setItems($workingTimeIds);
            $invoice->save();
        }

        return $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(array('result' => 'ok')));
    }
}
?>