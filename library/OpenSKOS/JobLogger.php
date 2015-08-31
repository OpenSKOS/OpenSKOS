<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 26/08/2015
 * Time: 16:38
 */

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class OpenSKOS_JobLogger extends AbstractLogger
{
    /**
     * @var \OpenSKOS_Db_Table_Row_Job
     */
    private $job;

    /**
     * OpenSKOS_JobLogger constructor.
     * @param OpenSKOS_Db_Table_Row_Job $job
     */
    public function __construct(OpenSKOS_Db_Table_Row_Job $job)
    {
        $this->job = $job;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        $currentInfo = $this->job->getInfo();
        $escapedMessage = nl2br(htmlentities($message));

        if ($level === LogLevel::ERROR) {
            $escapedMessage = '<span style="color:red;">' . $message . '</span>';
        }

        if ($level === LogLevel::WARNING) {
            $escapedMessage = '<span style="color:orange;">' . $message . '</span>';
        }

        if ($currentInfo) {
            $escapedMessage = $currentInfo . "<br />" . $escapedMessage;
        }
        $this->job->setInfo($escapedMessage);
    }


}