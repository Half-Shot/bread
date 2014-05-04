<?php
namespace Bread\Modules;
use Bread\Site as Site;
use Bread\Utilitys as Util;
class BreadAdminTools extends Module
{
        private $CurrentModuleIndex = 0;
        private $CurrentTabIndex = 0;
        private $ModuleSettings = array();
        private $HasGenerated = false;
        
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}
	function RegisterEvents()
	{
            $this->manager->RegisterHook($this->name,"Bread.DrawModule","ReturnFirstArgument");
            $this->manager->RegisterHook($this->name,"BreadAdminTools.Button","CPButton");
            $this->manager->RegisterHook($this->name,"Bread.ProcessRequest","Setup",array("Bread.ProcessRequest"=>"BreadUserSystem"));
            $this->manager->RegisterHook($this->name,"BreadAdminTools.AddModuleSettings","AddCoreSettings");
            $this->manager->RegisterHook($this->name,"BreadAdminTools.Banner","Banner");
            $this->manager->RegisterHook($this->name,"BreadAdminTools.Sidebar","Sidebar");
            $this->manager->RegisterHook($this->name,"BreadAdminTools.MessageTray","SetupMessageTray");
            $this->manager->RegisterHook($this->name,"BreadAdminTools.Mainpanel","Mainpanel");
            $this->manager->RegisterHook($this->name,"BreadAdminTools.SaveCoreSettings", "SaveCore",array(), \Bread\Modules\ModuleManager::EVENT_EXTERNAL);
            $this->manager->RegisterHook($this->name,"Bread.PageTitle", "SetTitle");

	}
        
        function SetupMessageTray()
        {
            $messageStruct = array("class"=>"alert-success alert-template","canClose"=>true,"body"=>"");
            $successAlert = $this->manager->FireEvent("Theme.Alert",$messageStruct)[0];
            $messageStruct = array("class"=>"alert-info alert-template","canClose"=>true,"body"=>"");
            $infoAlert = $this->manager->FireEvent("Theme.Alert",$messageStruct)[0];
            $messageStruct = array("class"=>"alert-warning alert-template","canClose"=>true,"body"=>"");
            $warningAlert = $this->manager->FireEvent("Theme.Alert",$messageStruct)[0];
            $messageStruct = array("class"=>"alert-danger alert-template","canClose"=>true,"body"=>"");
            $dangerAlert = $this->manager->FireEvent("Theme.Alert",$messageStruct)[0];
            $extraMessagesHTML = "";
            $extraMessages = $this->manager->FireEvent("Bread.ShowAdminMessage", $messageStruct);
            if(is_array($extraMessages)){
                $extraMessages = Util::MashArraysToSingleArray($extraMessages);
                foreach($extraMessages as $messageStruct){
                    $extraMessagesHTML += $this->manager->FireEvent("Theme.Alert",$messageStruct)[0];
                }
            }
            return $successAlert . $infoAlert . $warningAlert . $dangerAlert . $extraMessagesHTML;
        }
    
        function SetTitle()
        {
            $Params = Site::getURLParams();
            if(!array_key_exists("cpanel_cpindex", $Params)){
                return false;
            }
            return "Control Panel - " . $this->ModuleSettings[$this->CurrentModuleIndex]->Name;
        }
        
        function SaveCore()
        {
            if(!$this->manager->FireEvent("Bread.Security.GetPermission","BreadAdminTools.CorePanel.Write")[0])
                return 0;
            $newObj = new \stdClass();
            foreach($_POST as $prop => $val)
            {
                $key = substr($prop, 3);
                $cat = "";
                switch(substr($prop, 0,2)){
                    case "cf":
                        $cat = "core";
                        break;
                    case "df":
                        $cat = "logger";
                        break;
                    case "sf":
                        $cat = "strings";
                        break;
                    case "lf":
                        $cat = "directorys";
                        break;
                    default:
                        break;
                }
                if($cat != ""){
                    if(!isset(Site::Configuration()->$cat->$key))
                        return 1; //Failed Value, Could be a hack!
                    if(is_numeric($val)){
                        $val = floatval($val);
                    }
                    elseif($val === "true")
                    {
                        $val = true;
                    }
                    elseif($val === "false")
                    {
                        $val = false;
                    }
                    $newObj->$cat->$key = $val;
                }
            }
            Site::EditConfigurationValues($newObj);
            return 0;
        }
                
        function GenerateModules()
        {
            if($this->HasGenerated)
                return true;
            $this->ModuleSettings += ($this->manager->FireEvent("BreadAdminTools.AddModuleSettings",array($this->CurrentModuleIndex == count($this->ModuleSettings),  $this->CurrentTabIndex)));
            if(count($this->ModuleSettings) - 1){
                Site::$Logger->writeError("The specifed module index is invalid for the Admin Panel", \Bread\Logger::SEVERITY_MEDIUM, $this->name, true);
            }
            if($this->CurrentTabIndex > count($this->ModuleSettings[$this->CurrentModuleIndex]->SettingsGroups) - 1){
                Site::$Logger->writeError("The specifed tab index is invalid for the Admin Panel", \Bread\Logger::SEVERITY_MEDIUM, $this->name, true);
            }
            $this->HasGenerated = true;
            return true;
        }
        
        function Banner()
        {
            $this->GenerateModules();
            return $this->manager->FireEvent("Theme.Title",array("Control Panel",$this->ModuleSettings[$this->CurrentModuleIndex]->Name))[0];
        }
        
        function Sidebar()
         {
            $this->GenerateModules();
            $links = array();
            $Args = Site::getURLParams();
            unset($Args["BASEURL"]);
            foreach($this->ModuleSettings as $i => $Setting){
                $link = new \Bread\Structures\BreadLinkStructure();
                $Args["cpanel_cpindex"] = $i;
                $link->url = Site::CondenseURLParams(false,$Args);
                $link->text = $Setting->Name;
                $link->active = ($i == $this->CurrentModuleIndex);
                $links[] = $link;
            }
            return Site::$moduleManager->FireEvent("Theme.VerticalNavbar",$links)[0];       
        }
        
        function Mainpanel()
        {
            $this->GenerateModules();
            $Tabs = array();
            $Args = Site::getURLParams();
            $Args["cpanel_cpindex"] = $this->CurrentModuleIndex;
            unset($Args["BASEURL"]);
            foreach($this->ModuleSettings[$this->CurrentModuleIndex]->SettingsGroups as $i => $Setting){
                $Tab = new \Bread\Structures\BreadLinkStructure();
                $Args["cpanel_tabindex"] = $i;
                $Tab->text = $Setting->HumanTitle;
                $Tab->url = Site::CondenseURLParams(false,$Args);
                $Tab->active = ($i == $this->CurrentTabIndex);
                $Tabs[] = $Tab;
            }
            
            $TabsHTML = $this->manager->FireEvent("Theme.Tabs",$Tabs)[0];    
            $SettingsPanels = array();
            foreach($this->ModuleSettings[$this->CurrentModuleIndex]->SettingsGroups[$this->CurrentTabIndex]->Panels as $Setting){
                $Footer = "";
                $Panel = new \stdClass();
                if($Setting->ApplyButtons){
                    $Panel->body = $this->manager->FireEvent("Theme.Panel",array("title"=>$Setting->HumanTitle,"body"=>$Setting->Body,"footer"=>$this->manager->FireEvent("Theme.Form",$Setting->ApplyButtons)[0]))[0];
                }
                else
                {  
                    $Panel->body = $this->manager->FireEvent("Theme.Panel",array("title"=>$Setting->HumanTitle,"body"=>$Setting->Body))[0];
                }
                $SettingsPanels[] = $Panel;
            }
            $TabsHTML = $this->manager->FireEvent("Theme.Tabs",$Tabs)[0];    
            $SettingsPanelsHTML = $this->manager->FireEvent("Theme.Layout.Grid.HorizonalStack",$SettingsPanels)[0];
            return $TabsHTML . $this->manager->FireEvent("Theme.Layout.Well",array("value"=>$SettingsPanelsHTML))[0];
        }
        
        function CPButton($args)
        {
            $link = new \Bread\Structures\BreadLinkStructure();
            $link->request = "controlpanel";
            $Button = array();
            $Button["onclick"] = "window.location = '" . $link->createURL() .  "'";
            $Button["class"] = "btn-info " . $args[0];
            $Button["value"] = "Control Panel";
            
            return $this->manager->FireEvent("Theme.Button",$Button)[0];
        }
        
	function ReturnFirstArgument($arguments)
	{
	    return "<p>Empty Admin Panel</p>";
        }
        
        function Setup()
        {
            if(!$this->manager->FireEvent("Bread.Security.GetCurrentUser")[0]){
                $this->manager->UnregisterModule($this->name);
                return false;
            }
            $Params = Site::getURLParams();
            if(array_key_exists("cpanel_cpindex", $Params)){
                $this->CurrentModuleIndex = $Params["cpanel_cpindex"];
                if(array_key_exists("cpanel_tabindex", $Params)){
                    $this->CurrentTabIndex = $Params["cpanel_tabindex"];
                }
            }
        }
        
        function CoreSetting_Logging($Tab_Logging)
        {
            $Panel_Cur = new \Bread\Structures\BreadCPPanel;
            $Panel_Cur->Name = "currentLog";
            $Panel_Cur->HumanTitle = "Current Log";
            
            $Panel_Prev = new \Bread\Structures\BreadCPPanel;
            $Panel_Prev->Name = "previousLogs";
            $Panel_Prev->HumanTitle = "Previous Logs";
            
            $Tab_Logging->Panels[] = $Panel_Cur;
            $LogMsgBody = "";
            foreach(Site::$Logger->getMessageStack() as $categoryName => $messages)
            {
                $LogMsgBody .= "<h4>" . $categoryName . "</h4><pre style='width:100%;height:500px;overflow:scroll;'>";
                foreach($messages as $msg)
                {
                    $LogMsgBody .= $msg->ToString() . "<br>";
                }
                $LogMsgBody .= "</pre>";
            }
            $Panel_Cur->Body = "<code>" . $LogMsgBody . "</pre></code>";
            $Tab_Logging->Panels[] = $Panel_Prev;
        }
        
        function CoreSetting_Settings($Tab_CoreSettings)
        {
            $ApplyButtonsForm = new \Bread\Structures\BreadForm();
            $ApplyButtonsForm->action = "";
            $ApplyButtonsForm->isinline = true;
            
            $ApplyButton = new \Bread\Structures\BreadFormElement();
            $ApplyButton->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $ApplyButton->value = "Apply";
            $ApplyButton->class = "btn-success BATapplyButton";
            
            $ApplyButtonsForm->elements[] = $ApplyButton;
            
            $TCS_Panel_Main = new \Bread\Structures\BreadCPPanel;
            $TCS_Panel_Main->Name = "main";
            $TCS_Panel_Main->HumanTitle = $this->manager->FireEvent("Theme.Icon","cog")[0] . " Core Settings";
            $TCS_Panel_Main->ApplyButtons = $ApplyButtonsForm;
            
            $TCS_Panel_Logger = new \Bread\Structures\BreadCPPanel;
            $TCS_Panel_Logger->Name = "logging";
            $TCS_Panel_Logger->HumanTitle = $this->manager->FireEvent("Theme.Icon","book")[0] . "Debug Logging";
            $TCS_Panel_Logger->ApplyButtons = $ApplyButtonsForm;
            
            $TCS_Panel_Strings = new \Bread\Structures\BreadCPPanel;
            $TCS_Panel_Strings->Name = "strings";
            $TCS_Panel_Strings->HumanTitle = $this->manager->FireEvent("Theme.Icon","pencil")[0] . "Strings";
            $TCS_Panel_Strings->ApplyButtons = $ApplyButtonsForm;
            
            $TCS_Panel_Directorys = new \Bread\Structures\BreadCPPanel;
            $TCS_Panel_Directorys->Name = "directorys";
            $TCS_Panel_Directorys->HumanTitle = $this->manager->FireEvent("Theme.Icon","file")[0] . "Locations";
            $TCS_Panel_Directorys->ApplyButtons = $ApplyButtonsForm;
            
            //Form Time!
            $SettingFormCore = new \Bread\Structures\BreadForm();
            $SettingFormCore->name = "coreform";
            $SettingFormCore->id = "coreform";
            
            foreach(Site::Configuration()->core as $key => $value)
            {
                if(is_object($value) || is_array($value))
                    continue;
                $Element = new \Bread\Structures\BreadFormElement;
                $Element->id = "cf_" . strtolower($key);
                $Element->required = true;
                if(is_numeric($value))
                    $Element->type = "number";
                if(is_bool($value))
                    $Element->type = "checkbox";
                $Element->value = $value;
                $Element->label = $key;
                $SettingFormCore->elements[] = $Element;
            }
            
            $TCS_Panel_Main->Body = $this->manager->FireEvent("Theme.Form",$SettingFormCore); //Form
            
            $Tab_CoreSettings->Panels[] = $TCS_Panel_Main;
            
            //Form Time!
            $SettingFormDebug = new \Bread\Structures\BreadForm();
            $SettingFormDebug->name = "debugform";
            $SettingFormDebug->id = "debugform";
            
            foreach(Site::Configuration()->logger as $key => $value)
            {
                if(is_object($value) || is_array($value))
                    continue;
                $Element = new \Bread\Structures\BreadFormElement;
                $Element->id = "df_" . strtolower($key);
                $Element->required = true;
                if(is_numeric($value))
                    $Element->type = "number";
                if(is_bool($value))
                    $Element->type = "checkbox";
                $Element->value = $value;
                $Element->label = $key;
                $SettingFormDebug->elements[] = $Element;
            }
            
            $TCS_Panel_Logger->Body = $this->manager->FireEvent("Theme.Form",$SettingFormDebug); //Form
            
            $Tab_CoreSettings->Panels[] = $TCS_Panel_Logger;
            
            //Form Time!
            $SettingFormStrings = new \Bread\Structures\BreadForm();
            $SettingFormStrings->name = "stringform";
            $SettingFormStrings->id = "stringform";
            
            foreach(Site::Configuration()->strings as $key => $value)
            {
                if(is_object($value) || is_array($value))
                    continue;
                $Element = new \Bread\Structures\BreadFormElement;
                $Element->id = "sf_" . strtolower($key);
                $Element->required = true;
                $Element->value = $value;
                $Element->label = $key;
                $SettingFormStrings->elements[] = $Element;
            }
            
            $TCS_Panel_Strings->Body = $this->manager->FireEvent("Theme.Form",$SettingFormStrings); //Form
            
            $Tab_CoreSettings->Panels[] = $TCS_Panel_Strings;
            
                        //Form Time!
            $SettingFormDirectorys = new \Bread\Structures\BreadForm();
            $SettingFormDirectorys->name = "directorysform";
            $SettingFormDirectorys->id = "directorysform";
            
            foreach(Site::Configuration()->directorys as $key => $value)
            {
                if(is_object($value) || is_array($value))
                    continue;
                $Element = new \Bread\Structures\BreadFormElement;
                $Element->id = "lf_" . strtolower($key);
                $Element->required = true;
                $Element->value = $value;
                $Element->label = $key;
                $SettingFormDirectorys->elements[] = $Element;
            }
            
            $TCS_Panel_Directorys->Body = $this->manager->FireEvent("Theme.Form",$SettingFormDirectorys); //Form
            
            $Tab_CoreSettings->Panels[] = $TCS_Panel_Directorys;
        }
        
        function AddCoreSettings($args)
        {
            if(!$this->manager->FireEvent("Bread.Security.GetPermission","BreadAdminTools.CorePanel.Read")[0])
                return false;
            $CoreSettingsCP = new \Bread\Structures\BreadConfigurationPanel;
            $CoreSettingsCP->Name = "Core";
            
            if(!$args[0])
                return $CoreSettingsCP;
            $Tab_CoreSettings = new \Bread\Structures\BreadCPSetting;
            $Tab_CoreSettings->Name = "coreSettings";
            $Tab_CoreSettings->HumanTitle = $this->manager->FireEvent("Theme.Icon","cog")[0] . " General";
            
            $Tab_Logging = new \Bread\Structures\BreadCPSetting;
            $Tab_Logging->Name = "loggingSettings";
            $Tab_Logging->HumanTitle = $this->manager->FireEvent("Theme.Icon","book")[0] . " Logs";
            
            $Tab_JSONEditor = new \Bread\Structures\BreadCPSetting;
            $Tab_JSONEditor->Name = "JsonEditor";
            $Tab_JSONEditor->HumanTitle = "Settings File Editor";
            //Tab Index;
            switch($args[1]){
                case 0:
                    Site::AddScript(Util::FindFile(Util::GetDirectorySubsection(__DIR__,0,1) . "js/corepanelSettings.js") , true);
                    $this->CoreSetting_Settings($Tab_CoreSettings);
                    break;
                case 1:
                    $this->CoreSetting_Logging($Tab_Logging);
                    break;
                default:
                    break;
            }
            
            $CoreSettingsCP->SettingsGroups[] = $Tab_CoreSettings;
            $CoreSettingsCP->SettingsGroups[] = $Tab_Logging;
            $CoreSettingsCP->SettingsGroups[] = $Tab_JSONEditor;
            
            return $CoreSettingsCP;
            
        }

}
