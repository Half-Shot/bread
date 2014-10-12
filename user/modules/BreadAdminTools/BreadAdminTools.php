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
        
    function SetupMessageTray()
    {
        $messageStruct = array("class"=>"alert-success alert-template","canClose"=>true,"body"=>"");
        $successAlert = $this->manager->FireEvent("Theme.Alert",$messageStruct);
        $messageStruct = array("class"=>"alert-info alert-template","canClose"=>true,"body"=>"");
        $infoAlert = $this->manager->FireEvent("Theme.Alert",$messageStruct);
        $messageStruct = array("class"=>"alert-warning alert-template","canClose"=>true,"body"=>"");
        $warningAlert = $this->manager->FireEvent("Theme.Alert",$messageStruct);
        $messageStruct = array("class"=>"alert-danger alert-template","canClose"=>true,"body"=>"");
        $dangerAlert = $this->manager->FireEvent("Theme.Alert",$messageStruct);
        $extraMessagesHTML = "";
        $extraMessages = $this->manager->FireEvent("Bread.ShowAdminMessage", $messageStruct,false);
        if(is_array($extraMessages)){
            $extraMessages = Util::MashArraysToSingleArray($extraMessages);
            foreach($extraMessages as $messageStruct){
                $extraMessagesHTML += $this->manager->FireEvent("Theme.Alert",$messageStruct);
            }
        }
        return $successAlert . $infoAlert . $warningAlert . $dangerAlert . $extraMessagesHTML;
    }

    function SetTitle()
    {
        if(empty($this->ModuleSettings)){
            return false;
        }
        return "Control Panel - " . $this->ModuleSettings[$this->CurrentModuleIndex]->Name;
    }
            
    function GenerateModules()
    {
        if(!$this->HasGenerated){
            $ModulesGenerating = true;
            $ModOffset = 0;
            $ListOffset = 0;
            while($ModulesGenerating){
                $NewSetting = $this->manager->FireEvent("BreadAdminTools.AddModuleSettings",array($this->CurrentModuleIndex == $ModOffset,  $this->CurrentTabIndex,$this->CurrentModuleIndex),true,true,$ModOffset);
                if($NewSetting !== False){
                    if($NewSetting !== NULL){
                        $this->ModuleSettings[] = $NewSetting;
                        //Swap if needed!
                        $ModuleData = $NewSetting;
                        if($ModuleData->OverrideIndex !== -1 && $ModuleData->OverrideIndex !== $ListOffset) 
                        {
                            if(array_key_exists($ModuleData->OverrideIndex, $this->ModuleSettings)){
                                $SwapSpace = clone $this->ModuleSettings[$ModuleData->OverrideIndex];
                                $this->ModuleSettings[$ModuleData->OverrideIndex] = $ModuleData;
                                $this->ModuleSettings[$ListOffset] = $SwapSpace;
                            }
                            else
                            {
                                $this->ModuleSettings[$ModuleData->OverrideIndex] = clone $ModuleData;
                                unset($this->ModuleSettings[$ListOffset]);
                            }
                        }
                        $ListOffset++;
                    }
                    $ModOffset++;
                }
                else
                {
                    $ModulesGenerating = False;
                }
            }
        }
        if(!count($this->ModuleSettings) || !count($this->ModuleSettings[$this->CurrentModuleIndex]->SettingsGroups) || $this->CurrentModuleIndex > count($this->ModuleSettings) - 1  || $this->CurrentTabIndex > count($this->ModuleSettings[$this->CurrentModuleIndex]->SettingsGroups) - 1)
        {
            return false;
        }
        $this->HasGenerated = true;
        return true;
    }
    
    function Banner()
    {
        if($this->GenerateModules()){
            return $this->manager->FireEvent("Theme.Title",array("Control Panel",$this->ModuleSettings[$this->CurrentModuleIndex]->Name));
        }
        return "";
    }
    
    function Sidebar()
     {
        if(!$this->GenerateModules()){
            return "";
        }
        $links = array();
        $Args = Site::getURLParams();
        unset($Args["BASEURL"]);
        if(array_key_exists("cpanel_tabindex",$Args)){
            unset($Args["cpanel_tabindex"]);
        }
        foreach($this->ModuleSettings as $i => $Setting){
            $link = new \Bread\Structures\BreadLinkStructure();
            $Args["cpanel_cpindex"] = $i;
            $link->url = Site::CondenseURLParams(false,$Args);
            $link->text = $Setting->Name;
            $link->active = ($i == $this->CurrentModuleIndex);
            $links[] = $link;
        }
        return Site::$moduleManager->FireEvent("Theme.VerticalNavbar",$links);       
    }
    
    function Mainpanel()
    {   
        if(!$this->GenerateModules()){
            $msg = new \Bread\LoggerMessage;
            $msg->message = "You do not have permission to access this page.";
            $msg->severity = 2;
            $this->manager->UnregisterModule($this->name);
            return $this->manager->FireEvent("Theme.DrawError", $msg);
        }
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
            $TabsHTML = $this->manager->FireEvent("Theme.Tabs",$Tabs);    
        }
        else
        {
            $TabsHTML = "";
        }
        $SettingsPanels = array();
        foreach($this->ModuleSettings[$this->CurrentModuleIndex]->SettingsGroups[$this->CurrentTabIndex]->Panels as $Setting){
            $Footer = "";
            $Panel = new \stdClass();
            if($Setting->PercentageWidth != 0)
            {
                $Panel->size = round((12 / 100) * $Setting->PercentageWidth);
            }
            if($Setting->ApplyButtons){
                $Panel->body = $this->manager->FireEvent("Theme.Panel",array("title"=>$Setting->HumanTitle,"body"=>$Setting->Body,"footer"=>$this->manager->FireEvent("Theme.Form",$Setting->ApplyButtons)));
            }
            else
            {  
                $Panel->body = $this->manager->FireEvent("Theme.Panel",array("title"=>$Setting->HumanTitle,"body"=>$Setting->Body));
            }
            $SettingsPanels[] = $Panel;
        }  
        $SettingsPanelsHTML = $this->manager->FireEvent("Theme.Layout.Grid.HorizontalStack",$SettingsPanels);
        return $TabsHTML . $this->manager->FireEvent("Theme.Layout.Well",array("value"=>$SettingsPanelsHTML));
    }
    
    function CPButton($args)
    {
        $link = new \Bread\Structures\BreadLinkStructure();
        $link->request = "controlpanel";
        $Button = array();
        $Button["onclick"] = "window.location = '" . $link->createURL() .  "'";
        $Button["class"] = "btn-info " . $args[0];
        $Button["value"] = "Control Panel";
        return $this->manager->FireEvent("Theme.Button",$Button);
    }
    
    function Setup()
    {
        if(!$this->manager->FireEvent("Bread.Security.GetPermission","Bread.ViewControlPanel")){
            $this->manager->UnregisterModule($this->name);
        }
        
        //Get a settings file.
        $this->settings = Site::$settingsManager->RetriveSettings("breadadmintools#settings",false, new BreadAdminToolsSettings());
        
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
