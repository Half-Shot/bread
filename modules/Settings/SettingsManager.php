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
    private $filters;
    private $interfaces = [];
    const SAVEMODE = 0755;
    function __construct() {
        $this->settings = array();
    }
    
    function Setup($filterpath){
        if(file_exists($filterpath)){
            $FileData = file_get_contents($filterpath);
            $this->filters = json_decode($FileData);
        }
        else
        {
            //Regenerate
            Site::$Logger->writeError("Settings Filter file does not exist. Creating the default", \Bread\Logger::SEVERITY_MEDIUM);
            $this->filters = array();
            $JsonCatchallFilter = new SettingsFilter();
            $JsonCatchallFilter->type = "Json";
            $JsonCatchallFilter->module = "*";
            $JsonCatchallFilter->setting = "*";
            $this->filters = $JsonCatchallFilter;
            $FileData = json_encode($JsonCatchallFilter);
            file_put_contents($filterpath, $FileData);
        }
        
        if($this->filters === null){
            Site::$Logger->writeError("Settings Filter file at " . $filterpath . " appear to be corrupt. Bread cannot start.", \Bread\Logger::SEVERITY_CRITICAL,true);
        }
        
        //Load interfaces
            //JSON
            $this->interfaces["Json"] = new SettingsInterfaceJson();
    }
    
    /**
     * Replace a setting file in the stack with a new object.
     * @param string $path The path of the setting
     * @param StdClass $newObj 
     */
    function ChangeSetting($path,$newObj)
    {
        $this->settings[$path]->data = $newObj;
    }
    
    /**
     * Removes a setting from the filesystem/database entirely. Use at your own risk!
     * @param type $path
     */
    function DeleteSetting($path,$removeFromStack = true)
    {
        if(file_exists($path)){
            if($removeFromStack){
                $this->RemoveSettingFromStack($path);
            }
            if(!array_key_exists($path, $this->settings)){
                return false;
            }
            $Interface = $this->settings[$path]->interface;
            return $this->interfaces[$Interface]->DeleteSetting($path);
        }
        return false;
    }
    
    function RemoveSettingFromStack($path,$template = null)
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
    
    function GetHashPath($path){
        //Extract Settings File
        $Parts = explode('#',$path);
        
        if(count($Parts) === 2){
            $Parts[1] = str_replace('.json','',$Parts[1]);
            
            $dirname = Site::ResolvePath("%system-settings") . "/" . $Parts[0] . "/";
            if(!file_exists($dirname)){
                mkdir($dirname);
                chmod($dirname, $this::SAVEMODE);
            }
            $path = $dirname . $Parts[1] . '.json';
        }
        return $path;
    }
    
    function FindCorrectInterface($path){
        //Does the string have an extension
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if($extension == ""){
            //Extension Based Interface Find (fallback)
            foreach($this->interfaces as $name => $interface){
               if(in_array($extension,$interface->MatchExtensions)){
                   return $name;
               }
            }
            return false; //No interface found!
        }
        else{
           //Filter Based Interface Find (preferred)
           $Parts = explode('#', $path);
           foreach($this->filters as $Filter){
              $ModuleMatch = fnmatch($this->filters->module, $Parts[0]);
              $SettingMatch = fnmatch($this->filters->setting, $Parts[1]);
              if($SettingMatch && $ModuleMatch){
                  return $Filer->type;
              }
           }
        }
    }
    
    /**
     * Retrives the settings file.
     * @param string $path The path of the file. Should be in the format of 'ModuleName#FileName'.
     * @param boolean $dontsave Don't save the files on exit.
     * @param any $template New files should have this as default. Leave as null to disable.
     * @throws FileNotFoundException
     * @throws FailedToParseException
     */
    function RetriveSettings($path,$dontsave = False,$template = null,$ignorefail = false)
    {
        if(array_key_exists($path, $this->settings))
        {
            return $this->settings[$path]->data;
        }
        
        //Find a correct interface.
        $InterfaceName = $this->FindCorrectInterface($path);
        if($InterfaceName == false){
            Site::$Logger->writeError ("Couldn't load path '" . $path . "' for parsing settings. No interface found", \Bread\Logger::SEVERITY_MEDIUM, "core" , !$ignorefail, "Bread\Settings\FileNotFoundException");  
        }
        
        if($template !== null){
            //Does the file exist.
        }
        
        if(!file_exists($path)){
            Site::$Logger->writeError ("Couldn't load path '" . $path . "' for parsing settings.", \Bread\Logger::SEVERITY_MEDIUM, "core" , !$ignorefail, "Bread\Settings\FileNotFoundException");   
        }        
        try{
            $jsonObj = $this->GetJsonObject($path);
        } catch (FailedToParseException $ex) {
            if(!$ignorefail){
                throw $ex;
            }
            else{
                $jsonObj = $template;
            }
        }
        if(!$dontsave){
            $this->settings[$path] = $jsonObj;
        }
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
        $path = $this->GetHashPath($path);
        Site::$Logger->writeMessage ("Saving " . $path, "SettingsManager");
        $string = $this->CompileJson($object);
        $worked = \file_put_contents($path, $string);
        if($worked == False || is_null($string)){    
            Site::$Logger->writeError ("Couldn't write json to file. path: '" . $path . "'", \Bread\Logger::SEVERITY_MEDIUM,"core" , $shouldThrow, "Bread\Settings\FileNotWrittenException");     
        }
    }
}
