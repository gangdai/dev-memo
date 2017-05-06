<?php

/*
includes/classes/osclog.php
usage:
    OSCLogger::writeLog($message, OSCLogger::DEBUG);
    OSCLogger::writeLog($message, OSCLogger::NOTICE);
    OSCLogger::writeLog($message, OSCLogger::WARNING);
    OSCLogger::writeLog($message, OSCLogger::ERROR);
*/

class OSCLoggerException extends RuntimeException {
}

class OSCLogger {
    const ERROR_LEVEL = 255;
    const DEBUG = 1;
    const NOTICE = 2;
    const WARNING = 4;
    const ERROR = 8;

    static protected $logdir = DIR_FS_CATALOG . 'cache/';
    static protected $logfile = 'osc.log';

    static protected $instance;
    static protected $enabled = false;
    static protected $filename;

    protected $file;

    static public function setFileName($filename) {
        self::$filename = self::$logdir . $filename;
    }
    static public function getFileName() {
        if (self::$filename == null) {
            self::$filename = self::$logdir . self::$logfile;
        }
        return self::$filename;
    }
    static public function enableIf($condition = true) {
        if ((bool) $condition) {
            self::$enabled = true;
        }
    }
    static public function disable() {
        self::$enabled = false;
    }
    static protected function getInstance() {
        if (!self::hasInstance()) {
            //self::$instance = new self("osc.log");
            self::$instance = new self();
        }
        return self::$instance;
    }
    static protected function hasInstance() {
        return self::$instance instanceof self;
    }
    static public function writeIfEnabled($message, $level = self::DEBUG) {
        if (self::$enabled) {
            self::writeLog($message, $level);
        }
    }
    static public function writeIfEnabledAnd($condition, $message, $level = self::DEBUG) {
        if (self::$enabled) {
            self::writeIf($condition, $message, $level);
        }
    }
    static public function writeLog($message, $level = self::DEBUG) {
        self::getInstance()->writeLine($message, $level);
    }
    static public function writeIf($condition, $message, $level = self::DEBUG) {
        if ($condition) {
            self::writeLog($message, $level);
        }
    }
    protected function __construct($filename = NULL) {
        if ($filename !== NULL) self::setFileName($filename);

        if (!$this->file = fopen(self::getFileName(), 'a+')) {
            throw new OSCLoggerException(sprintf("Could not open file '%s' for writing.", self::getFileName()));
        }
        //$this->writeLine("\n===================== STARTING =====================", 0);
    }
    public function __destruct() {
        //$this->writeLine("\n===================== ENDING =====================", 0);
        fclose($this->file);
    }
    protected function writeLine($message, $level) {
        if ($level & self::ERROR_LEVEL) {
            $date = new DateTime();
            $en_tete = $date->format('Ymd h:i:sa');
            switch($level) {
            case self::DEBUG:
                $en_tete = sprintf("%s (debug)", $en_tete);
                break;
            case self::NOTICE:
                $en_tete = sprintf("%s (notice)", $en_tete);
                break;
            case self::WARNING:
                $en_tete = sprintf("%s WARNING", $en_tete);
                break;
            case self::ERROR:
                $en_tete = sprintf("\n%s **ERROR**", $en_tete);
                break;
            }

            if (is_array($message)) $message = "\n" . "<pre>" . print_r($message, true) . "</pre>";

            $message = sprintf("%s -- %s\n",  $en_tete, $message);
            fwrite($this->file, $message);
        }
    }
}
?>