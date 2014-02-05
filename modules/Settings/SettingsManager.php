<?php
namespace Bread\Settings;
use Bread\Site as Site;
/**
 * Base Exception for other Settings Exceptions
 */
class SettingsException extends \Exception { }
/**
 * Thrown if a file could not be found
 */
class FileNotFoundException extends SettingsException { }

/**
 * Thrown if a file could not be written.
 */
class FileNotWrittenException extends SettingsException { }

/**
 * The JSON File could not be parsed
 */
class FailedToParseException extends SettingsException { }
/**
 * Not used yet. Will be implemented when permissions are used.
 * @ignore
 */
class UnauthorizedException extends SettingsException { }

/**
 * Description of SettingsManager
 *
 */
class SettingsManager {
    //put your code here
    /**
     * An array of settings files to their classes.
     * "path" => stdClass.
     * @see SettingsManager->RetriveSettings()
     * @var array
     */
    private $settings;
    
    function __construct() {
        $this->settings = array();
    }
    
    /**
     * Retrives the settings file.
     * @param string $path The path of the file.
     * @param boolean $dontsave Don't save the file to the array
     * @throws FileNotFoundException
     * @throws FailedToParseException
     */
    function RetriveSettings($path,$dontsave = False)
    {
        if(array_key_exists($path, $this->settings))
        {
            return $this->settings[$path];
        }
            //Extract Settings File
        if(!file_exists($path))
            Site::$Logger->writeError ("Couldn't load path '" . $path . "' for parsing settings.", 1, True, "Bread\Settings\FileNotFoundException");   
        
        $jsonObj = $this->GetJsonObject($path);
        
        if(!$dontsave)
            $this->settings[$path] = $jsonObj;
        
        return $jsonObj;
    }
    
    /**
     * Convert a path to a json object. Better to use RetriveSettings for long term use.
     * @param type $path Path of the JSON File.
     * @return stdObject Json Object
     * @see SettingsManager->RetriveSettings()
     */
    public static function GetJsonObject($path)
    {
        $contents = \file_get_contents($path);
        if(!$contents)
           Site::$Logger->writeError ("Couldn't open path '" . $path . "' for parsing settings.", 1, True, "Bread\Settings\FileNotFoundException");

        $jsonObj = \json_decode($contents);
        if($jsonObj == NULL)
           Site::$Logger->writeError ("Couldn't parse file '" . $path . "' for reading settings.", 1, True, "Bread\Settings\FailedToParseException");   
        return $jsonObj;
    }
    /**
     * Converts a Json Object into its string notation.
     * @param stdObject $object
     * @return string The JSON string. 
     */
    public static function CompileJson($object)
    {
        $obj = json_encode($object);
        if($obj == False)
           Site::$Logger->writeError ("Couldn't parse object into json string.", 1, True, "Bread\Settings\FailedToParseException");   
        return $obj;
    }
    
    public function SaveChanges()
    {
        foreach($this->settings as $path => $obj)
        {
            $string = $this->CompileJson($obj);
            $worked = \file_put_contents($path, $string);
            if($worked == False)    
                Site::$Logger->writeError ("Couldn't write json to file. path: '" . $path . "'", 1, True, "Bread\Settings\FileNotWrittenException");                  
        }
    }
}
