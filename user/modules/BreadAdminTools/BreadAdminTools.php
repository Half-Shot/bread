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
        /**
         *
         * @var Bread\Modules\BreadAdminToolsSettings
         */
        private $settings;
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
            $this->manager->RegisterHook($this->name,"BreadAdminTools.DoUpdate", "UpdateBread",array(), \Bread\Modules\ModuleManager::EVENT_EXTERNAL);
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
            
            //Get a settings file.
            $rootSettings = Site::$settingsManager->FindModuleDir("breadadmintools");
            $this->settingspath = $rootSettings . "settings.json";
            Site::$settingsManager->CreateSettingsFiles($this->settingspath, new BreadAdminToolsSettings());
            $this->settings = Site::$settingsManager->RetriveSettings($this->settingspath);
            
            $Params = Site::getURLParams();
            if(array_key_exists("cpanel_cpindex", $Params)){
                $this->CurrentModuleIndex = $Params["cpanel_cpindex"];
                if(array_key_exists("cpanel_tabindex", $Params)){
                    $this->CurrentTabIndex = $Params["cpanel_tabindex"];
                }
                else
                {
                    $this->CurrentTabIndex = $this->settings->defaultModule;
                }
            }
            else
            {
            $this->CurrentTabIndex = $this->settings->defaultTab;
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
        
        function CoreSetting_UpdateBread($Tab_UpdateBread)
        {
            $TimeBetweenChecks = 15;
            $CurrentVer = Site::Configuration()->core->version;
            $IsGit = !is_numeric($CurrentVer);
                
            $Panel_About = new \Bread\Structures\BreadCPPanel;
            $Panel_About->Name = "aboutUpdater";
            $Panel_About->HumanTitle = $this->manager->FireEvent("Theme.Icon","megaphone")[0] . " About Updates";
            $Panel_About->Body = file_get_contents(Util::FindFile(Util::GetDirectorySubsection(__DIR__,0,1) . "aboutUpdater.html"));
            $Tab_UpdateBread->Panels[] = $Panel_About;
            
            $Panel_Updater = new \Bread\Structures\BreadCPPanel;
            $Panel_Updater->Name = "updaterPanel";
            $Panel_Updater->HumanTitle = $this->manager->FireEvent("Theme.Icon","cog")[0] . "Deploy THAR Update!";
            if(time() - ($this->settings->coreSettings->lastRequest + $TimeBetweenChecks) > 0){
                $this->settings->coreSettings->lastRequest = time();
                $ReleaseRequest = \Unirest::get("https://api.github.com/repos/Half-Shot/bread/releases");
                if($ReleaseRequest->code != 200)
                {
                    $Panel_Updater->Body = "<h3 style='text-align:center;'>You are running Bread <strong>". Site::Configuration()->core->version ."</strong></h3><br><p> Couldn't reach github at the moment. Please try again later.</p><pre>" . var_export($ReleaseRequest->body,true) . "</pre>";
                    $Tab_UpdateBread->Panels[] = $Panel_Updater;
                    return false;
                }
                $StableDate   = new \DateTime("1-1-1970");
                $StableBuild  = false;
                $ReleaseDate  = new \DateTime("1-1-1970");
                $ReleaseBuild = false;
                foreach($ReleaseRequest->body as $Release)
                {
                    $time = new \DateTime($Release->published_at);
                    if($Release->prerelease = true && $time > $ReleaseDate){
                        $ReleaseDate = $time;
                        $ReleaseBuild = $Release;
                    }
                    elseif($Release->prerelease = false && $time > $StableDate){
                        $StableDate = $time;
                        $StableBuild = $Release;
                    }
                }
                $this->settings->coreSettings->releaseBuild = $ReleaseBuild;
                $this->settings->coreSettings->stableBuild = $StableBuild;
            }
            else
            {
                $ReleaseBuild = $this->settings->coreSettings->releaseBuild;
                $StableBuild = $this->settings->coreSettings->stableBuild;
            }
            //Stable Data
            $StablePanel = new \stdClass();
            $StablePanel->id = "lts-panel";
            $StablePanel->header = "LTS Channel";
            
                        
            //ApplyButton
            $ApplyButtonsForm = new \Bread\Structures\BreadForm();
            $ApplyButtonsForm->action = "";
            $ApplyButtonsForm->isinline = true;
            $ApplyButton = new \Bread\Structures\BreadFormElement();
            $ApplyButton->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $ApplyButton->value = "Update";
            $ApplyButton->class = "btn-info BATapplyButton";
            $ApplyButtonsForm->elements[] = $ApplyButton;
            if($StableBuild){
                $version = Site::filterNumeric($StableBuild->target_commitish);
                if($version > Site::Configuration()->core->version || $IsGit)
                {
                    $ApplyButton->readonly = false;
                    $ApplyButton->onclick="requestUpdate(0)";
                    $Message = "See About Updates for infomation on this channel.";
                }
                else
                {
                    $ApplyButton->readonly = true;
                    $Message = "You are already up to date on this channel!";
                }
                $StablePanel->body = "<p>The latest LTS release is " . $StableBuild->target_commitish . ", " . $StableBuild->name . "</p>"
                                    ."<p><u>Release Notes:</u></p><p>" . $StableBuild->body . "</p>" . "<small>". $Message."</small>"
                                    . $this->manager->FireEvent("Theme.Form",$ApplyButtonsForm)[0];
            }
            else
            {
                $StablePanel->body = "No stable builds have been released yet. Please use the Release Channel."; 
            }
            
            $version = Site::filterNumeric($ReleaseBuild->target_commitish);
            if($version > Site::Configuration()->core->version || $IsGit)
            {
                $ApplyButton->readonly = false;
                $ApplyButton->onclick="requestUpdate(1)";
                $Message = "See About Updates for infomation on this channel.";
            }
            else
            {
                $ApplyButton->readonly = true;
                $Message = "You are already up to date on this channel!";
            }
            
            //Release Data
            $ReleasePanel = new \stdClass();
            $ReleasePanel->id = "release-panel";
            $ReleasePanel->header = "Release Channel";
            $ReleasePanel->body = "<p>The latest release is " . $ReleaseBuild->target_commitish . ", " . $ReleaseBuild->name . "</p>"
                                 ."<p><u>Release Notes:</u></p><p>" . $ReleaseBuild->body . "</p>" . "<small>". $Message."</small>"
                                 . $this->manager->FireEvent("Theme.Form",$ApplyButtonsForm)[0];

            
            
            if(time() - ($this->settings->coreSettings->lastRequest + 15) > 0){
                $ReleaseRequest = \Unirest::get("https://api.github.com/repos/Half-Shot/bread/commits");
                $LatestGit = $ReleaseRequest->body[0];
                $this->settings->coreSettings->GitData = $LatestGit;
            }
            else
            {
                $LatestGit = $this->settings->coreSettings->GitData;
            }
            $ApplyButton->readonly = false;
            $ApplyButton->onclick="requestUpdate(2)";
            $ApplyButton->class = "btn-warning BATapplyButton";
            //Git Data
            $GitPanel = new \stdClass();
            $GitPanel->id = "git-panel";
            $GitPanel->header = "Git Channel";
            $GitPanel->body = "The latest commit was by " . $LatestGit->commit->author->name . "&lt" . $LatestGit->commit->author->email . "&gt </br>"
                            . "The SHA is:" . $LatestGit->sha . "</br>"
                            . "The Message was: <pre>" . $LatestGit->commit->message . "</pre></br>"
                            . $this->manager->FireEvent("Theme.Form",$ApplyButtonsForm)[0];
            
            $Panel_Updater->Body = "<h3 style='text-align:center;'>You are running Bread <strong>". Site::Configuration()->core->version ."</strong></h3><br>" . $this->manager->FireEvent("Theme.Collapse",array("id"=>"ReleaseList","panels"=>array($ReleasePanel,$StablePanel,$GitPanel)))[0];
            $Tab_UpdateBread->Panels[] = $Panel_Updater;

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
            
            $Tab_UpdateBread = new \Bread\Structures\BreadCPSetting;
            $Tab_UpdateBread->Name = "BreadUpdate";
            $Tab_UpdateBread->HumanTitle = $this->manager->FireEvent("Theme.Icon","download-alt")[0] . " Update Bread";
            
            $Tab_LayoutEditor = new \Bread\Structures\BreadCPSetting;
            $Tab_LayoutEditor->Name = "LayoutEditor";
            $Tab_LayoutEditor->HumanTitle = $this->manager->FireEvent("Theme.Icon","pencil")[0] . " Edit Layouts";           
            
            $Tab_RequestSettings = new \Bread\Structures\BreadCPSetting;
            $Tab_RequestSettings->Name = "RequestSettings";
            $Tab_RequestSettings->HumanTitle = $this->manager->FireEvent("Theme.Icon","tasks")[0] . " Request Settings";   
            
            //Tab Index;
            switch($args[1]){
                case 0:
                    if(!$this->manager->FireEvent("Bread.Security.GetPermission","BreadAdminTools.CorePanel.Settings.Read")[0]){
                        break;
                    }
                    Site::AddScript(Util::FindFile(Util::GetDirectorySubsection(__DIR__,0,1) . "js/corepanelSettings.js") , true);
                    $this->CoreSetting_Settings($Tab_CoreSettings);
                    break;
                case 1:
                    if(!$this->manager->FireEvent("Bread.Security.GetPermission","BreadAdminTools.CorePanel.Logger.Read")[0]){
                        break;
                    }
                    $this->CoreSetting_Logging($Tab_Logging);
                    break;
                case 2:
                    if(!$this->manager->FireEvent("Bread.Security.GetPermission","BreadAdminTools.CorePanel.Updater.Read")[0]){
                        break;
                    }
                    $this->CoreSetting_UpdateBread($Tab_UpdateBread);
                default:
                    break;
            }
            
            $CoreSettingsCP->SettingsGroups[] = $Tab_CoreSettings;
            $CoreSettingsCP->SettingsGroups[] = $Tab_Logging;
            $CoreSettingsCP->SettingsGroups[] = $Tab_UpdateBread;
            $CoreSettingsCP->SettingsGroups[] = $Tab_RequestSettings;
            return $CoreSettingsCP;
            
        }
        
        function UpdateBread()
        {            
            if(!$this->manager->FireEvent("Bread.Security.GetPermission","Bread.SystemUpdate")[0])
                return false;
            $updatePackage = -1;
            return false;
        }

}

class BreadAdminToolsSettings{
    function __construct() {
        $this->coreSettings = new \Bread\Modules\BATCoreSettings();
    }
    /**
     *
     * @var Bread\Modules\BATCoreSettings
     */
    public $coreSettings;
    public $defaultModule = 0;
    public $defaultTab = 0;
}

class BATCoreSettings{
    public $updateChannel = 1; //Release
    public $lastUpdate = 0;
    public $lastRequest = 0;
    public $releaseBuild = false;
    public $stableBuild = false;
    public $GitData = false;
}