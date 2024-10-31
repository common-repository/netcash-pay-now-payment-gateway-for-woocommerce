<?php

namespace Netcash\PayNow;

class Log
{
    protected $filepath = "";
    protected static $DEBUG = true;

    public function __construct($filepath)
    {
        // if (!$filepath) {
        //     $pathinfo = pathinfo(__FILE__);
        //     $filepath = $pathinfo['dirname'] . '/paynow.log';
        // }

        // if (!file_exists($filepath) || !is_writable($filepath)) {
        //     throw new \Exception("Cannot write to log file at {$filepath}!");
        // }

        // $this->filepath = $filepath;
    }

    public function log($msg = '', $extra = [], $close = false)
    {

        // Only log if debugging is enabled
        if (self::$DEBUG) {

            $line = date('Y-m-d H:i:s') . ' : ' . $msg . "\n";
            if(!empty($extra)) {
                $line .= print_r($extra, true) . "\n";
            }
            error_log($line);

        }
    }
}
