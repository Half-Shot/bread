<?php
namespace Bread\Modules;
use Bread\Site as Site;
use Bread\Structures\BreadLinkStructure as BreadLinkStructure;
class BreadIndexSystem extends Module
{
        private $settings;
        function __construct($manager,$name)
        {
	        parent::__construct($manager,$name);
        }
        
        function RegisterEvents()
        {
            $this->manager->RegisterHook($this->name,"Bread.ProcessRequest","Setup");
            $this->manager->RegisterHook($this->name,"Bread.GetNavbarIndex","GetPages");
        }
        
        function Setup()
        {
            $rootSettings = Site::$settingsManager->FindModuleDir("navbar");
            Site::$settingsManager->CreateSettingsFiles($rootSettings . "index.json", array());
            $this->settings = Site::$settingsManager->RetriveSettings($rootSettings . "index.json");
        }
        
        function GetPages()
        {
            //Filter hidden
            $pages = array();
            foreach($this->settings as $page){
                if(!$page->hidden)
                    $pages[] = $page;
            }
            return $pages;
        }
}