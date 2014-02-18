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
            $this->manager->RegisterEvent($this->name,"Bread.ProcessRequest","Setup");
            $this->manager->RegisterEvent($this->name,"Bread.GetNavbarIndex","GetPages");
        }
        
        function Setup()
        {
            $rootSettings = Site::$settingsManager->FindModuleDir("navbar");
            Site::$settingsManager->CreateSettingsFiles($rootSettings . "index.json", new BreadNavbarIndex());
            $this->settings = Site::$settingsManager->RetriveSettings($rootSettings . "index.json");
        }
        
        function GetPages()
        {
            return $this->settings->links;//to meeeee.
        }
}

class BreadNavbarIndex
{
    public $links = array();
    function __construct()
    {
        $this->links[] = new BreadLinkStructure();
    }
}