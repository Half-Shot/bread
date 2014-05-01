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
            $this->manager->RegisterHook($this->name,"BreadAdminTools.Mainpanel","Mainpanel");
            $this->manager->RegisterHook($this->name,"BreadAdminTools.SaveCoreSettings", "SaveCore",array(), \Bread\Modules\ModuleManager::EVENT_EXTERNAL);
            $this->manager->RegisterHook($this->name,"Bread.PageTitle", "SetTitle");

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
                    if(!isset(Site::Configuration()[$cat][$key]))
                        return 1; //Failed Value, Could be a hack!
                    Site::EditConfigurationValue($cat,$key,$val);
                    }
                }
            return 0;
        }
                
        function GenerateModules()
        {
            if($this->HasGenerated)
                return true;
            $this->ModuleSettings += ($this->manager->FireEvent("BreadAdminTools.AddModuleSettings"));
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
                $Panel = new \stdClass();
                $Panel->body = $this->manager->FireEvent("Theme.Panel",array("title"=>$Setting->HumanTitle,"body"=>$Setting->Body,"footer"=>$this->manager->FireEvent("Theme.Form",$Setting->ApplyButtons)[0]))[0];
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
        
        function AddCoreSettings()
        {
            if(!$this->manager->FireEvent("Bread.Security.GetPermission","BreadAdminTools.CorePanel.Read")[0])
                return false;
            Site::AddScript(Util::FindFile(Util::GetDirectorySubsection(__DIR__,0,1) . "js/corepanel.js") , true);
            $CoreSettingsCP = new \Bread\Structures\BreadConfigurationPanel;
            $CoreSettingsCP->Name = "Core";
            
            $Tab_CoreSettings = new \Bread\Structures\BreadCPSetting;
            $Tab_CoreSettings->Name = "coreSettings";
            $Tab_CoreSettings->HumanTitle = $this->manager->FireEvent("Theme.Icon","cog")[0] . " General";
            
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
            
            foreach(Site::Configuration()["core"] as $key => $value)
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
            
            foreach(Site::Configuration()["logger"] as $key => $value)
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
            
            foreach(Site::Configuration()["strings"] as $key => $value)
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
            
            foreach(Site::Configuration()["directorys"] as $key => $value)
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
            
            $CoreSettingsCP->SettingsGroups[] = $Tab_CoreSettings;
            
            $Tab_JSONEditor = new \Bread\Structures\BreadCPSetting;
            $Tab_JSONEditor->Name = "JsonEditor";
            $Tab_JSONEditor->HumanTitle = "Settings File Editor";
            
            $CoreSettingsCP->SettingsGroups[] = $Tab_JSONEditor;
            return $CoreSettingsCP;
            
        }

}
