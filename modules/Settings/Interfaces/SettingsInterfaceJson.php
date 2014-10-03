<?php

/*
 * The MIT License
 *
 * Copyright 2014 will.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Bread\Settings;
use Bread\Site as Site;
/**
 * Description of SettingsInterfaceJson
 *
 * @author will
 */
class SettingsInterfaceJson implements SettingsInterface {
    
    public $InterfaceName = "Json";
    public $MatchExtensions = array("json","js");
    const SAVEMODE = 0755;
    
    public function DeleteSetting($BreadPath) {
        $path = Site::$settingsManager->GetHashFilePath($BreadPath);
        return unlink ($path);
    }

    /**
     * Convert a path to a json object. Better to use RetriveSettings for long term use.
     * @param type $path Path of the JSON File.
     * @return stdObject Json Object
     * @see SettingsManager->RetriveSettings()
     */
    public function RetriveSettings($BreadPath) {
        $path = Site::$settingsManager->GetHashFilePath($BreadPath);
        $contents = \file_get_contents($path);
      
        if($contents == "")
        {
            Site::$Logger->writeMessage("Settings file is empty (" . $path . "), this could be a bug or there really isn't any settings yet.'");
            $contents = "{}"; //The equivalent of a empty file but cleaner.
        }
        if($contents == FALSE)
           Site::$Logger->writeError ("Couldn't open path '" . $path . "' for parsing settings.", \Bread\Logger::SEVERITY_MEDIUM, "core" , True, "Bread\Settings\FileNotFoundException");
        
        $jsonObj = \json_decode($contents);
        if (is_null($jsonObj)) { //Stops php from interpreting a empty object as null. That was a really bad bug.
            Site::$Logger->writeError("Couldn't parse file '" . $path . "' for reading settings.", \Bread\Logger::SEVERITY_MEDIUM, "core", True, "Bread\Settings\FailedToParseException");
        }
        return $jsonObj;
    }

    public function SaveSetting($BreadPath,$Object,$ShouldThrow = true) {     
        $path =  Site::$settingsManager->GetHashFilePath($BreadPath);
        Site::$Logger->writeMessage ("Saving " . $path, "SettingsManager");
        $string = $this->Serialize($Object);
        $worked = \file_put_contents($path, $string);
        if($worked == False || is_null($string)){    
            Site::$Logger->writeError ("Couldn't write json to file. path: '" . $path . "'", \Bread\Logger::SEVERITY_MEDIUM,"core" , $ShouldThrow, "Bread\Settings\FileNotWrittenException");     
        }
    }
    
    public function Serialize($object){
        $obj = json_encode($object,JSON_PRETTY_PRINT);
        if ($obj == False) {
            Site::$Logger->writeError("Couldn't parse object into json string.", \Bread\Logger::SEVERITY_MEDIUM, "core", True, "Bread\Settings\FailedToParseException");
        }
        return $obj;
    }
    
    public function CreateSetting($BreadPath, $Template) {
        $filename = Site::$settingsManager->GetHashFilePath($BreadPath);
        $dir = dirname($filename);
        if(!file_exists($dir)){
            mkdir($dir, SettingsInterfaceJson::SAVEMODE,true);
        }
        file_put_contents($filename, '');
        chmod($filename, $this::SAVEMODE);
        $this->SaveSetting($BreadPath,$Template,True); //Save to be safe.
        return TRUE;
    }

    public function SettingExists($BreadPath) {
        $path = Site::$settingsManager->GetHashFilePath($BreadPath);
        return (file_exists($path));
    }

}
