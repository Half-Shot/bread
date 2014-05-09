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
        private $updateFileCache = false;
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
            $this->manager->RegisterHook($this->name,"Bread.ProcessRequest","Setup",\Bread\Modules\ModuleManager::EVENT_INTERNAL,array("Bread.ProcessRequest"=>"BreadUserSystem"));
            $this->manager->RegisterHook($this->name,"BreadAdminTools.Banner","Banner");
            $this->manager->RegisterHook($this->name,"BreadAdminTools.Sidebar","Sidebar");
            $this->manager->RegisterHook($this->name,"BreadAdminTools.MessageTray","SetupMessageTray");
            $this->manager->RegisterHook($this->name,"BreadAdminTools.Mainpanel","Mainpanel");
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
                
        function GenerateModules()
        {
            if($this->HasGenerated)
                return true;
            $this->ModuleSettings += ($this->manager->FireEvent("BreadAdminTools.AddModuleSettings",array($this->CurrentModuleIndex == count($this->ModuleSettings),  $this->CurrentTabIndex)));
            
            foreach($this->ModuleSettings as $Index => $ModuleData)
            {
                if($ModuleData->OverrideIndex !== -1 && $ModuleData->OverrideIndex !== $Index) 
                {
                    if(array_key_exists($ModuleData->OverrideIndex, $this->ModuleSettings)){
                        $SwapSpace = clone $this->ModuleSettings[$ModuleData->OverrideIndex];
                        $this->ModuleSettings[$ModuleData->OverrideIndex] = $ModuleData;
                        $this->ModuleSettings[$Index] = $SwapSpace;
                    }
                    else
                    {
                        $this->ModuleSettings[$ModuleData->OverrideIndex] = clone $ModuleData;
                        unset($this->ModuleSettings[$Index]);
                    }
                }
            }
            
            if($this->CurrentModuleIndex > count($this->ModuleSettings) -1 ){
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
            $Args = Site::getURLParams();
            $Args["cpanel_cpindex"] = $this->CurrentModuleIndex;
            unset($Args["BASEURL"]);
            if(count($this->ModuleSettings[$this->CurrentModuleIndex]->SettingsGroups) > 1){
                $Tabs = array();
                foreach($this->ModuleSettings[$this->CurrentModuleIndex]->SettingsGroups as $i => $Setting){
                    $Tab = new \Bread\Structures\BreadLinkStructure();
                    $Args["cpanel_tabindex"] = $i;
                    $Tab->text = $Setting->HumanTitle;
                    $Tab->url = Site::CondenseURLParams(false,$Args);
                    $Tab->active = ($i == $this->CurrentTabIndex);
                    $Tabs[] = $Tab;
                }
                $TabsHTML = $this->manager->FireEvent("Theme.Tabs",$Tabs)[0];    
            }
            else
            {
                $TabsHTML = "";
            }
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
}

class BreadAdminToolsSettings{
    public $defaultModule = 0;
    public $defaultTab = 0;
}
