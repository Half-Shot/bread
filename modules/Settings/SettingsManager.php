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
    private $files;
    private $filters;
    private $interfaces = [];
    const SAVEMODE = 0755;
    function __construct() {
        $this->files = array();
    }
    
    function Setup($filterpath){
        if(file_exists($filterpath)){
            $FileData = file_get_contents($filterpath);
            $this->filters = json_decode($FileData);
        }
        else
        {
            //Regenerate
            Site::$Logger->writeError("Settings Filter file does not exist. Creating the default.", \Bread\Logger::SEVERITY_MEDIUM);
            $this->filters = array();
            $JsonCatchallFilter = new SettingsFilter();
            $JsonCatchallFilter->type = "Json";
            $JsonCatchallFilter->module = "*";
            $JsonCatchallFilter->setting = "*";
            $JsonCatchallFilter->args = new \stdClass();
            $this->filters = array($JsonCatchallFilter);
            $FileData = json_encode($this->filters);
            file_put_contents($filterpath, $FileData);
        }
        
        if($this->filters === null){
            Site::$Logger->writeError("Settings Filter file at " . $filterpath . " appear to be corrupt. Bread cannot start.", \Bread\Logger::SEVERITY_CRITICAL,true);
        }
        
        //Load interfaces
        
        //JSON
        $this->interfaces[SettingsInterfaceJson::$InterfaceName] = new SettingsInterfaceJson();
        
        //PDO - Disabled while Half-Shot thinks on it.
        //$this->interfaces[SettingsInterfacePDO::$InterfaceName] = new SettingsInterfacePDO();
        
        //XML - Disabled while Half-Shot thinks on it.
        $this->interfaces[SettingsInterfaceXML::$InterfaceName] = new SettingsInterfaceXML();
    }
    
    /**
     * Replace a setting file in the stack with a new object.
     * @param string $path The path of the setting
     * @param StdClass $newObj 
     */
    function ChangeSetting($path,$newObj)
    {
        $this->files[$path]->data = $newObj;
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
            if(!array_key_exists($path, $this->files)){
                return false;
            }
            $Interface = $this->files[$path]->interface;
            return $Interface->DeleteSetting($this->files[$path]);
        }
        return false;
    }
    
    function RemoveSettingFromStack($path,$template = null)
    {
        if(array_key_exists($path, $this->files)){
            unset($this->files[$path]);
            return True;
        }
        else
        {
            Site::$Logger->writeError ("Setting file " . $path . " not loaded!", \Bread\Logger::SEVERITY_MEDIUM, "core");  
            return false;
        }
    }
    
    function GetHashFilePath($path){
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
        if($extension !== ""){
            //Extension Based Interface Find (fallback)
            foreach($this->interfaces as $name => $interface){
               if(in_array($extension,$interface->MatchExtensions)){
                   $args = new \stdClass();
                   return array($name,$args);
               }
            }
            return false; //No interface found!
        }
        else{
           //Filter Based Interface Find (preferred)
           $Parts = explode('#', $path);
           foreach($this->filters as $Filter){
              $ModuleMatch = fnmatch($Filter->module, $Parts[0]);
              $SettingMatch = fnmatch($Filter->setting, $Parts[1]);
              if($SettingMatch && $ModuleMatch){
                  return array($Filter->type,$Filter->args);
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
        if(array_key_exists($path, $this->files))
        {
            return $this->files[$path]->data;
        }
        $args = null;
        //Find a correct interface.
        $Interface = $this->FindCorrectInterface($path,$args);
        if($Interface === false){
            Site::$Logger->writeError ("Couldn't load path '" . $path . "' for parsing settings. No interface found", \Bread\Logger::SEVERITY_MEDIUM, "core" , !$ignorefail, "Bread\Settings\FileNotFoundException");  
        }
        $File = new SettingsFile();
        $File->args = $Interface[1];
        $File->interface = $this->interfaces[$Interface[0]];
        $File->path = $path;
        if($template !== null && !$File->interface->SettingExists($File)){
            //Does the file exist.
            $File->interface->CreateSetting($File,$template);
        }
        
        if(!$File->interface->SettingExists($File)){
            Site::$Logger->writeError ("Couldn't load path '" . $path . "' for parsing settings.", \Bread\Logger::SEVERITY_MEDIUM, "core" , !$ignorefail, "Bread\Settings\FileNotFoundException");   
        }        
        try{
            $File = $File->interface->RetriveSettings($File);
            $File->MD5OnOpen = md5(serialize($File->data));
        } catch (FailedToParseException $ex) {
            if(!$ignorefail){
                throw $ex;
            }
            else{
                $File->data = $template;
                Site::$Logger->writeError ("Couldn't load path '" . $path . "' for parsing settings. Using template.", \Bread\Logger::SEVERITY_MEDIUM, "core");   
            }
        }
        if(!$dontsave){
            $this->files[$path] = $File;
        }
        return $File->data;
    }
    
    /**
     * Saves all the settings files currently open.
     */
    public function SaveChanges()
    {
        Site::$Logger->writeError ("Preforming dump of all saveable settings.", \Bread\Logger::SEVERITY_MESSAGE, "SettingsManager");
        foreach($this->files as $path => $obj){
            if($obj->MD5OnOpen !== md5(serialize($obj->data))){ //Did the file actually change?
                $obj->interface->SaveSetting($obj,false); //Don't throw on such a large operation.
            }
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
       $this->files[$path]->data = $object;
       $result = false;
       try {
           $result = $this->files[$path]->interface->SaveSetting($this->files[$path]);
       } catch (Exception $ex) {
           if($shouldThrow){
               throw $ex;
           }
       }
       return $result;
    }
}
