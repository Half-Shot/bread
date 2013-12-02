<?php
namespace Bread;
use Bread\Site as Site;
class Logger
{
    private $FILEMODE = "w";
    public $logPath = "";
    public $errorStack = array();
    public $messageStack = array();
    private $fileStream;
    function __construct($filepath)
    {
        $logPath = $filepath;
        try
        {
            $this->fileStream = fopen($logPath,$FILEMODE);
        }
        catch(Exception $ex)
        {
            throw new Exception("Couldn't write a new log file. File name " . $logPath);
        }
    }
    
    function writeMessage($message)
    {
        $msg = "[MSG][" . time() - Site::$TimeStarted . "]" . $message;
        fwrite($this->fileStream,$msg);
        fflush($fileStream);
    }
    
    function writeError($message,$severity)
    {
        $msg = "[ERR " . $severity ."][" . time() - Site::$TimeStarted . "]" . $message;
        fwrite($this->fileStream,$msg);
        fflush($fileStream);
    }
    
    function closeStream()
    {
        writeMessage("Closing Log");
        fclose($fileStream);
    }
}
?>
