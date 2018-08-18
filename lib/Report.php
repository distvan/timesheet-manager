<?php
namespace DotLogics;

use DotLogics\DB\WorkingTimeDB;
use DotLogics\DB\ProjectDB;
use DotLogics\DB\ExceptionMessagesDB;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Exception;
use DateTime;

class Report
{
    const TO_FILE = 'file';
    const TO_DISPLAY = 'display';
    const OUTPUT_FILENAME = 'report';
    const TEMPLATE_FILENAME = 'report.xls';
    const LANGUAGE_HU = 'hu_HU';
    const LANGUAGE_DE = 'de_DE';
    const LANGUAGE_EN = 'en_US';

    private $_db;
    private $_log;
    private $_output;
    private $_templateDir;
    private $_language;
    private $_translator;

    public function __construct($db, $log, $output='file')
    {
        require_once 'PHPReport.php';
        //setting PDF rendering library
        $rendererName = \PHPExcel_Settings::PDF_RENDERER_TCPDF;
        $rendererLibraryPath = dirname(dirname(__FILE__)) . '/vendor/tecnickcom/tcpdf';
        if(!\PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath))
        {
            throw new Exception('Please set PDF renderer!!');
        }
        $this->_db = $db;
        $this->_log = $log;
        $this->_output = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'tests/' . self::OUTPUT_FILENAME.'.pdf';
        $this->_templateDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'report'.DIRECTORY_SEPARATOR;
        $this->_language = self::LANGUAGE_EN;
    }

    public function generateSummaryTimeReport($fromDate, $toDate, $projectId, $format='pdf')
    {
        if(empty($projectId))
        {
            throw new Exception('No project ID!', ExceptionMessagesDB::EXCEPTION_REPORT_NO_PROJECT_ID);
        }

        $dir = dirname(dirname(__FILE__));
        $this->_translator = new Translator($this->_language);
        $this->_translator->setFallbackLocales(['hu_HU']);
        $this->_translator->addLoader('php', new PhpFileLoader());
        $this->_translator->addResource('php', $dir . '/lang/hu_HU.php' , 'hu_HU');
        $this->_translator->addResource('php', $dir . '/lang/en_US.php' , 'en_US');
        $this->_translator->addResource('php', $dir . '/lang/de_DE.php' , 'de_DE');

        $project = new ProjectDB($this->_db, $this->_log);
        $project->setId($projectId);
        $projectInfo = $project->get();

        $wt = new WorkingTimeDB($this->_db, $this->_log);
        $result = $wt->getBetween($fromDate, $toDate, $projectId);
        $tasks = array();
        if($result)
        {
            $sumMins = 0;
            foreach($result as $item)
            {
                $dateFrom = new DateTime($item['date_from']);
                $dateTo = new DateTime($item['date_to']);
                $diff = $dateTo->diff($dateFrom);
                $mins = $diff->days * 24 * 60;
                $mins += $diff->h * 60;
                $mins += $diff->i;
                $dt = new DateTime($item['date_from']);
                array_push($tasks, array('description' => $item['description'], 'date' => $dt->format($this->_translator->trans('date_format')), 'long' => $mins));
                $sumMins += $mins;
            }

            $config = array(
                'template' => self::TEMPLATE_FILENAME,
                'templateDir' => $this->_templateDir
            );

            $report = new \PHPReport($config);

            $report->load(array(
                array(
                    'id' => 'doc',
                    'data' => array(
                        'title' => $this->_translator->trans('timesheet_summary'),
                        'project_name' => $projectInfo->getName(),
                        'footer' => $this->_translator->trans('footer'),
                        'task' => $this->_translator->trans('task'),
                        'date' => $this->_translator->trans('date'),
                        'long' => $this->_translator->trans('long'),
                        'total' => $this->_translator->trans('total'),
                        'sumMins' => $sumMins/60,
                        'unit' => $this->_translator->trans('hour')
                    )
                ),
                array(
                    'id' => 'task',
                    'repeat' => true,
                    'data' => $tasks,
                    'format' => array(
                        'long' => array('number' => array('sufix' => $this->_translator->trans('minute'), 'decimals' => 2))
                    )
                )
            ));
            $pdf = $report->render('pdf', $this->_output);

            return $pdf;
            //file_put_contents($pdf, 'test.pdf');
        }
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->_language;
    }

    /**
     * @param mixed $language
     */
    public function setLanguage($language)
    {
        $this->_language = $language;
    }
}
?>