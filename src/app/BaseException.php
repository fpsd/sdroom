<?php
/**
 * Created by FPHD.COM.
 * User: linmaogan
 * Date: 2017/12/7
 * Time: 上午1:12
 */

namespace app;

use Exception;
use Monolog\Logger;

class BaseException extends \Exception
{
    public function __construct($message, $code = -1, Exception $previous = null, $logMessage = '', $level = Logger::DEBUG)
    {
//        parent::__construct($message, $code, $previous);
//        if ($logMessage) {
//            print_r($message);
////            get_instance()->log->addRecord($level, json_encode($logMessage, JSON_UNESCAPED_UNICODE));
//        }
    }
}