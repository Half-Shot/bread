<?php
namespace Bread\Modules;
use Bread\Site as Site;
class BreadPageSystem extends Module
{
        private $settings;
            function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}

	function RegisterEvents()
	{
            //$this->manager->RegisterEvent($this->name,"Bread.ProcessRequest","CreatePageIndex");
            $this->manager->RegisterEvent($this->name,"Bread.ProcessRequest","Setup");

	}
        
        function RegisterPage($pathtofile)
        {
            
        }
        
        function Setup()
        {
            //Get a settings file.
            $rootSettings = Site::$settingsManager->CreateModDir("breadpages");
            Site::$settingsManager->CreateSettingsFiles($rootSettings . "settings.json", new BreadPageSystemSettings());
            $settings = Site::$settingsManager->RetriveSettings($rootSettings . "settings.json");
        }

}

class BreadPageSystemSettings
{
    public $Pageindex = array();
    public $BuildTime = 0;
    public $CheckIndexEvery = 4;
}