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
 * The manager responsible for serving all settings files from json storage
 * to stdObject. It deals with loading and converting, saving and rasing errors and
 * logging them where appropriate.
 */
class SettingsManager {
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
     * We are changing this soon.
     * @see https://github.com/Half-Shot/bread/issues/51
     * @param string $dirname The module name.
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
        $dir = dirname($filename);
        if(!file_exists($dir)){
            mkdir(dirname($filename), $this::SAVEMODE,true);
        }
        file_put_contents($filename, '');
        $this->settings[$filename] = $template;
        chmod($filename, $this::SAVEMODE);
        $this->SaveSetting($this->settings[$filename],$filename,True); //Save to be safe.
        return True;
    }
    /**
     * Replace a setting file in the stack with a new object.
     * @param string $path The path of the setting
     * @param StdClass $newObj 
     */
    function ChangeSetting($path,$newObj)
    {
        $this->settings[$path] = $newObj;
    }
    
    /**
     * Removes a setting from the filesystem/database entirely. Use at your own risk!
     * @param type $path
     */
    function DeleteSetting($path,$removeFromStack = true){
        
        if(file_exists($path)){
            if($removeFromStack){
                $this->RemoveSettingFromStack($path);
            }
            return unlink ($path);
        }
        return false;
    }
    
    function RemoveSettingFromStack($path)
    {
        if(array_key_exists($path, $this->settings)){
            unset($this->settings[$path]);
            return True;
        }
        else
        {
            Site::$Logger->writeError ("Setting file " . $path . " not loaded!", \Bread\Logger::SEVERITY_MEDIUM, "core");  
            return false;
        }
    }
    
    
    
    /**
     * Retrives the settings file.
     * @param string $path The path of the file.
     * @param boolean $dontsave Don't save the file to the array.
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
            Site::$Logger->writeError ("Couldn't load path '" . $path . "' for parsing settings.", \Bread\Logger::SEVERITY_MEDIUM, "core" , True, "Bread\Settings\FileNotFoundException");   
        
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
           Site::$Logger->writeError ("Couldn't open path '" . $path . "' for parsing settings.", \Bread\Logger::SEVERITY_MEDIUM, "core" , True, "Bread\Settings\FileNotFoundException");
        
        $jsonObj = \json_decode($contents);
        if(is_null($jsonObj)) //Stops php from interpreting a empty object as null. That was a really bad bug.
           Site::$Logger->writeError ("Couldn't parse file '" . $path . "' for reading settings.", \Bread\Logger::SEVERITY_MEDIUM, "core" ,True, "Bread\Settings\FailedToParseException");   
        return $jsonObj;
    }
    /**
     * Converts a Json Object into its string notation.
     * @param stdObject $object
     * @return string The JSON string. 
     */
    public static function CompileJson($object)
    {
        $obj = json_encode($object,JSON_PRETTY_PRINT);
        if($obj == False)
           Site::$Logger->writeError ("Couldn't parse object into json string.", \Bread\Logger::SEVERITY_MEDIUM, "core" , True, "Bread\Settings\FailedToParseException");   
        return $obj;
    }
    
    /**
     * Saves all the settings files currently open.
     */
    public function SaveChanges()
    {
        Site::$Logger->writeError ("Preforming Dump of all saveable settings.", \Bread\Logger::SEVERITY_MESSAGE, "SettingsManager");
        foreach($this->settings as $path => $obj){
            $this->SaveSetting($obj,$path,False); //Don't throw on such a large operation.
        }
    }
    /**
     * Serializes an object into json before saving it into a file.
     * @param any $object Object to save.
     * @param string $path The relative path to save to in relation to root.
     * @param bool $shouldThrow Should this function throw an error if it fails
     */
    public function SaveSetting($object,$path,$shouldThrow = True)
    {
         Site::$Logger->writeMessage ("Saving " . $path, "SettingsManager");
         $string = $this->CompileJson($object);
         $worked = \file_put_contents($path, $string);
         if($worked == False || is_null($string))    
             Site::$Logger->writeError ("Couldn't write json to file. path: '" . $path . "'", \Bread\Logger::SEVERITY_MEDIUM,"core" , $shouldThrow, "Bread\Settings\FileNotWrittenException");                  
    }
}
