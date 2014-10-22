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

/**
 * Description of SettingsInterfacePDO
 *
 * @author will
 */

use Bread\Utilitys as Util;
use Bread\Site as Site;

class SettingsInterfacePDO implements SettingsInterface {
    
    public static $InterfaceName = "PDO";
    public $MatchExtensions = array();
    public $databaseConnections = array();
    
    function CheckArgs(SettingsFile $File){
        if(isset($File->args->dbtype)){
            if(!in_array($File->args->dbtype,\PDO::getAvailableDrivers())){
                \Bread\Site::$Logger->writeError("Database connection isn't avaliable from the list of installed drivers for PHP", \Bread\Logger::SEVERITY_HIGH);
            }
            else{
                //Check for specifics
                switch($File->args->dbtype){
                    case 'mysql':
                        if(!isset($File->args->username) || !isset($File->args->password) || isset($File->args->host) || isset($File->args->dbname)){
                            \Bread\Site::$Logger->writeError("Username and Password", \Bread\Logger::SEVERITY_HIGH);
                        }
                        break;
                    case 'sqlite':
                        break;
                }
            }
        }
        else{
            \Bread\Site::$Logger->writeError("Database connection didn't set a type of connection (e.g. mysql)", \Bread\Logger::SEVERITY_HIGH);
            return false;
        }
        return true;
    }
    /**
     * @param string $filepath
     * @param object $args
     * @return PDO
     */
    public function GetDatabaseConnection($filepath,$args){
        if(in_array($filepath, $this->databaseConnections)){
            return $this->databaseConnections[$filepath];
        }
        else{
            $connectionString = "";
            //ConnectionType
            switch($args->dbtype){
                case "sqlite":
                    $connectionString = $args->dbtype . ":" . $args->path;
                    break;
                default:
                    $connectionString = $args->dbtype . ":";
                    $connectionString .= "host=" . $args->host . ";";
                    if(isset($args->port)){
                        $connectionString .= "port=" . $args->port . ";";
                    }
                    if(isset($args->dbname)){
                        $connectionString .= "dbname=" . $args->port . ";";
                    }
                    break;
            }
            $this->databaseConnections[$filepath] = new \PDO($connectionString);
            $this->databaseConnections[$filepath]->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $this->databaseConnections[$filepath];
        }
    }
    
    
    public function TablesFromObject($object,$rootTableName){
        $tables = array();
        if(is_array($object)){
            $tables[] = $object[0];
        }
        
        foreach($tables as &$object){
            $iterator = new \RecursiveIteratorIterator($object);
            foreach($iterator as $key => $value){
                echo $key "=>" $value;
            }
        }
    }
    
    public function ObjectToMYSQL($object,$rootTableName){
        if(!is_object($object)){
            if(is_array($object)){
                $object = \Bread\Utilitys::ArrayToStdObject($object);
            }
            else{
                //The object is no use.
                return false;
            }
        }
        else{
            foreach($object as $key => $value){
               continue;
            }
        }
    }
    
    public function CreateSetting(SettingsFile $File, $Template) {
        if(!$this->CheckArgs($File)){
            return false;
        }
        $File->data = $Template;
        $File->dbnewfile = true;
        
        $DBC = $this->GetDatabaseConnection($File->path,$File->args);
        if($DBC == false){
            return false;
        }
        $Worked = $DBC->exec($this->TableFromObject($File->data,$File->path));
        return $Worked;
    }

    public function DeleteSetting(SettingsFile $File) {
        if(!$this->CheckArgs($File)){
            return false;
        }        
    }

    public function RetriveSettings(SettingsFile $File) {
        if(!$this->CheckArgs($File)){
            return false;
        }        
        $conn = $this->GetDatabaseConnection($File->path,$File->args);
        $Statement = "SELECT * FROM '" . $this->GetTableNameFromPath($File->path) . "'";
        $Object = $conn->query($Statement);
        $Object = $Object->fetchAll(\PDO::FETCH_OBJ);

        if(count($Object) === 1){
            $Object = $Object[0];
        }
        else if(count($Object) === 0){
            $Object = False;
        }
        if($Object === false){
            Site::$Logger->writeError("Setting not found in database.", \Bread\Logger::SEVERITY_HIGH, "SettingsManager", true);
        }
        if(isset($Object->BreadDB_ID)){
            $File->BreadDB_ID = $Object->BreadDB_ID;
        }
        
        //Get Objects
        $this->DecompressObject($Object);
        
        $File->data = $Object;
        
        return $File;
    }
    
    public function DecompressObject(&$object){
        foreach($Object as $name => $value){
            if(is_string($Object)){
                $value = explode('json::', $value);
                $realname = explode('json::', $value);
                if(count($value) > 1 && count($realname) > 1){
                    $realname = $realname[1];
                    unset($Object->$name);
                    $Object->$realname = json_decode($value[1]);
                }
            }
        }
    }
               

    public function SaveSetting(SettingsFile $File, $ShouldThrow = true) {
        if(!$this->CheckArgs($File)){
            return false;
        }
        $tableName =  $this->GetTableNameFromPath($File->path);
        $Statement = "UPDATE " . $tableName . " SET ";
        $UpdateStatements = array();
        foreach($File->data as $key => $value){
            if(is_object($value) || is_array($value)){
                continue;//Skip for now
                /* TODO: Get object/array saving working */
            }
            $UpdateStatements[] = "'" . $key . "' = '" . $value . "'";
        }
        $Statement .= implode(', ', $UpdateStatements);
        $Statement .= "WHERE BreadDB_ID = " . $File->BreadDB_ID;
        $conn = $this->GetDatabaseConnection($File->path,$File->args);
        $Statement = $conn->prepare($Statement);
        $res = $Statement->execute();
        return $res;
    }
    
    public function GetTableNameFromPath($path){
         $Path = str_replace('#', '_' ,$path);
         return $Path;
    }

    public function SettingExists(SettingsFile $File) {
        if(!$this->CheckArgs($File)){
            return false;
        }        
        $pdo = $this->GetDatabaseConnection($File->path,$File->args);
        $Path = $this->GetTableNameFromPath($File->path);
        try {
            $result = $pdo->query("SELECT * FROM '".$Path."' LIMIT 1;");
            $result = $result->execute();
        } catch (\PDOException $e) {
            // We got an exception == table not found
            return FALSE;
        }

        // Result is either boolean FALSE (no table found) or PDOStatement Object (table found)
        return $result !== FALSE;
    }

    public function CloseConnection(SettingsFile $File) {
        return true;
    }

//put your code here
}
