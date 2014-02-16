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
            $this->manager->RegisterEvent($this->name,"Bread.DrawModule","DrawPage");
            $this->manager->RegisterEvent($this->name,"Bread.ProcessRequest","Setup");
            $this->manager->RegisterEvent($this->name,"Bread.GenerateNavbar","GenerateNavbar");
            $this->manager->RegisterEvent($this->name,"Bread.LowPriorityScripts","GenerateHTML");
	}
        
        function GenerateNavbar($args)
        {
            $pages = array();
            foreach($this->settings->Pageindex as $id => $page)
            {
                $parts = array();
                $parts["request"] = "page";
                $parts["page"] = $id;
                $pages[$page->name] = Site::CondenseURLParams(false,$parts);
            }
            return $pages;
        }
        
        function Setup()
        {
            //Get a settings file.
            $rootSettings = Site::$settingsManager->FindModuleDir("breadpages");
            Site::$settingsManager->CreateSettingsFiles($rootSettings . "settings.json", new BreadPageSystemSettings());
            $this->settings = Site::$settingsManager->RetriveSettings($rootSettings . "settings.json");
            if( ( time() - $this->settings->BuildTime) > $this->settings->CheckIndexEvery){
                $this->BuildIndex();
            }
            //TODO: Add a way to determine a user who can and can't edit the page. This would go here.
            Site::AddScript(Site::ResolvePath("%user-modules/BreadPageSystem/js/showdown.js")); //For just parsing.
        }
        
        
        function BuildIndex()
        {
            $this->settings->Pageindex = array();//Wipe array, we are rebuilding it.
            foreach(new \recursiveIteratorIterator( new \recursiveDirectoryIterator($this->settings->Pagedir)) as $file)
            {
                if(pathinfo($file->getFilename())['extension'] == "json")
                {
                    $path = $file->getPathname();
                    $pageData = Site::$settingsManager->RetriveSettings($path,True);
                    $this->settings->Pageindex[] = $pageData;
                }
            }
            $this->settings->BuildTime = time();
            Site::$Logger->writeMessage("BPS: Built Page Index!");
        }
        
        function DrawPage()
        {
           $request = Site::getRequest();
           
           if(!isset($request->arguments["page"]))
               return False;
           $pageid = $request->arguments["page"];
           $markdown = file_get_contents($this->settings->Pagedir . "/" . $this->settings->Pageindex[$pageid]->url);
           return "<div class='bps-content'><div class='bps-markdown'>" . $markdown ."</div></div>";
        }
        
        function GenerateHTML()
        {
            Site::AddScript(Site::ResolvePath("%user-modules/BreadPageSystem/js/doMarkdown.js"), true);
        }
}

class BreadPageSystemSettings
{
    public $Pageindex = array();
    public $Pagedir;
    public $BuildTime = 0;
    public $CheckIndexEvery = 4;
    
    function __construct() {
       $this->Pagedir = Site::ResolvePath("%user-pages");
    }
}
