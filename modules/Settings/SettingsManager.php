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
    const SAVEMODE = 0755;
    function __construct() {
        $this->settings = array();
    }
    /**
     * Creates a settings folder for your module or just returns the path.
     * You will also want to create settings files.
     * PLEASE use module names and not anything else.
     * @param string $dirname The module name.
     * @see $this::CreateSettingsFile()
     * @todo Add a secure way to lock settings files.
     */
    function FindModuleDir($dirname)
    {
        $dirname = Site::ResolvePath("%system-settings") . "/" . $dirname . "/";
        if(!file_exists($dirname)){
            mkdir($dirname);
            chmod($dirname, $this::SAVEMODE);
        }
        return $dirname;
    }
    
    /**
     * Creates a new settings file, sets the permissions and uses the specified template. If the file exists then returns False, else True.
     * @param string $filename Filename. Not relative (use CreateModInfo to get the directory).
     * @param stdClass $template Specify a template to use for the settings file. Could be a included json file with your module (must be decoded as a class).
     * @see $this::CreateModDir()
     */
    function CreateSettingsFiles($filename,$template)
    {
        if(file_exists($filename))
            return False;
        file_put_contents($filename, '');
        $this->settings[$filename] = $template;
        chmod($filename, $this::SAVEMODE);
        $this->SaveSetting($this->settings[$filename],$filename,True); //Save to be safe.
        return True;
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
      
        if($contents == "")
        {
            Site::$Logger->writeMessage("Settings file is empty (" . $path . "), this could be a bug or there really isn't any settings yet.'");
            $contents = "{}"; //The equivalent of a empty file but cleaner.
        }
        if($contents == FALSE)
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
    
    /**
     * Saves all the settings files currently open.
     */
    public function SaveChanges()
    {
        foreach($this->settings as $path => $obj){
            $this->SaveSetting($obj,$path,False); //Don't throw on such a large operation.
        }
    }
    
    public function SaveSetting($object,$path,$shouldThrow = True)
    {
         $string = $this->CompileJson($object);
         $worked = \file_put_contents($path, $string);
         if($worked == False)    
             Site::$Logger->writeError ("Couldn't write json to file. path: '" . $path . "'", 1, $shouldThrow, "Bread\Settings\FileNotWrittenException");                  
    }
}