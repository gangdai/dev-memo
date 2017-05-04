<?php
/*
  includes/classes/devlog.php
*/
class devlog {
    const ERROR_LEVEL = 255;
    const DEBUG = 1;
    const NOTICE = 2;
    const WARNING = 4;
    const ERROR = 8;
    protected $logdir = 'D:/programs/lamp/apache/htdocs/2017/cache/';
    protected $filename;

    public function __construct ($filename="") {
        if (pathinfo($filename, PATHINFO_EXTENSION) == 'log')
            $this->filename = $filename;
        else
            $this->filename = 'devlog.log';
    }

    public function log($input="", $lv = self::DEBUG) {
        if (file_exists($this->logdir . $this->filename)) {
            $fh = fopen($this->logdir . $this->filename, 'a') or die("can't open file");
        } else {
            $fh = fopen($this->logdir . $this->filename, 'w') or die("can't open new file");
        }
        
        switch ($lv) {
            case self::DEBUG: $lv = ' **DEBUG** '; break;
            case self::NOTICE: $lv = ' **NOTICE** '; break;
            case self::WARNING: $lv = ' **WARNING** '; break;
            case self::ERROR: $lv = ' **ERROR** '; break;
            default: $lv = self::DEBUG;
        }

        if (is_array($input)) $output = "\n" . "<pre>" . print_r($input, true) . "</pre>";
        else $output = $input;

        fwrite($fh, date("Ymd h:i:sa") . $lv . ": " . $output . "\n\n");
        fclose($fh);
    }


}
?>