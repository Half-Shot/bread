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
            $this->manager->RegisterEvent($this->name,"Bread.LowPriorityScripts","GenerateHTML");
            $this->manager->RegisterEvent($this->name,"BreadPageSystem.DrawRecentPosts","DrawRecentPosts");
            $this->manager->RegisterEvent($this->name,"BreadPageSystem.Title","DrawTitle");
            $this->manager->RegisterEvent($this->name,"BreadPageSystem.PlainMarkdown","DrawPlainMarkdown");
            $this->manager->RegisterEvent($this->name,"BreadPageSystem.BreadCrumbs","DrawBreadCrumbs");
        }
        
        function AddPages()
        {
            if($this->settings->navbar->enabled)
            {
                return $this->GenerateNavbar();
            }
            
            return False;
        }
        
        function DrawPlainMarkdown($args)
        {
            return "<div class='bps-content'><div class='bps-markdown'>" . $args ."</div></div>";
        }
        
        function GenerateNavbar()
        {
            $pages = array();
            foreach($this->settings->Pageindex as $id => $page)
            {
                $parts = array();
                $parts["request"] = $this->settings->RequestToLinkTo;
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
                    if(isset(pathinfo($pageData->url)['extension']))
                        if(pathinfo($pageData->url)['extension'] == "md")
                            $this->settings->Pageindex[] = $pageData;
                }
            }
            $this->settings->BuildTime = time();
            Site::$Logger->writeMessage("BPS: Built Page Index!");
        }
        
        function DrawPage()
        {
           $page = $this->GetActivePage();
           if($page == False)
            return False;
           $markdown = file_get_contents($this->settings->Pagedir . "/" . $page->url);
           return "<div class='bps-content' editor><div class='bps-markdown'>" . $markdown ."</div></div>";
        }
        
        function GenerateHTML()
        {
            Site::AddScript(Site::ResolvePath("%user-modules/BreadPageSystem/js/doMarkdown.js"), true);
            Site::AddRawScriptCode("DoMarkdown();",true);
        }
        
        function DrawRecentPosts()
        {
            $pages = $this->GenerateNavbar();
            return Site::$moduleManager->HookEvent("Theme.VerticalNavbar",$pages);
        }
        
        function DrawTitle()
        {
           $page = $this->GetActivePage();
           if($page == False)
            return False;
           $html = Site::$moduleManager->HookEvent("Theme.Title",$page->name)[0];
           return $html . Site::$moduleManager->HookEvent("Theme.Subtitle",$page->title)[0];
        }
        
        function GetActivePage()
        {
            $request = Site::getRequest();
           if(!isset($request->arguments["page"])){
               return False;
           }
           $pageid = $request->arguments["page"];
           return $this->settings->Pageindex[$pageid];
        }
        
        function DrawBreadCrumbs()
        {
           $page = $this->GetActivePage();
           if($page == False)
            return False;
           $breadcrumbs = $page->categorys;
           $html = "";
           foreach($breadcrumbs as $crumb){
            $html .= $crumb . "  -";
           }
           return $html;
           
        }
        
}

class BreadPageSystemSettings
{
    public $Pageindex = array();
    public $Pagedir;
    public $BuildTime = 0;
    public $CheckIndexEvery = 4;
    public $RequestToLinkTo = "post";
    public $navbar;
    function __construct() {
       $this->Pagedir = Site::ResolvePath("%user-pages");
       $this->navbar = new BreadPageSystemNavBarSettings();
    }
}

class BreadPageSystemNavBarSettings
{
    public $enabled = true;
}
