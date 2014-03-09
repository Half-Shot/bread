<?php
namespace Bread\Modules;
use Bread\Site as Site;
class BreadPageSystem extends Module
{
        private $settings;
        public $EnableEditor = false;
        function __construct($manager,$name)
        {
	        parent::__construct($manager,$name);
        }

        function RegisterEvents()
        {
            $this->manager->RegisterEvent($this->name,"Bread.DrawModule","DrawPage");
            $this->manager->RegisterEvent($this->name,"Bread.ProcessRequest","Setup",array("Bread.ProcessRequest"=>"RIUS"));
            $this->manager->RegisterEvent($this->name,"Bread.LowPriorityScripts","GenerateHTML");
            $this->manager->RegisterEvent($this->name,"BreadPageSystem.DrawRecentPosts","DrawRecentPosts");
            $this->manager->RegisterEvent($this->name,"Bread.Title","DrawTitle");
            $this->manager->RegisterEvent($this->name,"BreadPageSystem.PlainMarkdown","DrawPlainMarkdown");
            $this->manager->RegisterEvent($this->name,"BreadPageSystem.BreadCrumbs","DrawBreadcrumbs");
            $this->manager->RegisterEvent($this->name,"BreadPageSystem.Infomation","DrawPostInfomation");
            $this->manager->RegisterEvent($this->name,"BreadPageSystem.EditorButton","DrawMarkdownToggleswitch");
            $this->manager->RegisterEvent($this->name,"BreadPageSystem.SavePost","SavePost");
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
        
        function DrawMarkdownToggleswitch()
        {
            if($this->EnableEditor){
                return "<button id='bps-mdtoggle' onclick='toggleMarkdown();'>Open Editor</button>"
                . "<button id='bps-mdsave' onclick='saveMarkdown();'>Save/Publish</button>";
            }
        }
        
        function GenerateNavbar()
        {
            $pages = array();
            foreach($this->settings->postindex as $id => $page)
            {
                if(isset($page->hidden))
                    if($page->hidden)
                        continue;
                $parts = array();
                $parts["request"] = $this->settings->RequestToLinkTo;
                $parts["post"] = $id;
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
            
           //See if the user is an editor
           $User = $this->manager->HookEvent("Bread.Security.GetCurrentUser",NULL)[0];
           if($User){
               if(in_array("Editor", $User->rights))
                   $this->EnableEditor = true;
           }
        }
        
        
        function BuildIndex()
        {
            $this->settings->postindex = array();//Wipe array, we are rebuilding it.
            foreach(new \recursiveIteratorIterator( new \recursiveDirectoryIterator($this->settings->postdir)) as $file)
            {
                if(pathinfo($file->getFilename())['extension'] == "json")
                {
                    $path = $file->getPathname();
                    $pageData = Site::$settingsManager->RetriveSettings($path,True);
                    if(isset(pathinfo($pageData->url)['extension']))
                        if(pathinfo($pageData->url)['extension'] == "md")
                            $this->settings->postindex[] = $pageData;
                }
            }
            $this->settings->BuildTime = time();
            Site::$Logger->writeMessage("BPS: Built Page Index!");
        }
        
        function DrawPage()
        {
           $page = $this->GetActivePost();
           if($page == False)
            return False;
           $markdown = file_get_contents($this->settings->postdir . "/" . $page->url);
           $editor = "";
           if($this->EnableEditor){
               $editor = "editor";
               Site::AddRawScriptCode("var epiceditor_basepath ='" . Site::ResolvePath("%user-modules/BreadPageSystem/css/") . "';");//Dirty Hack
               Site::AddScript(Site::ResolvePath("%user-modules/BreadPageSystem/js/epiceditor.min.js"), true);
           }
           return "<div class='bps-content' " . $editor . "><textarea class='bps-markdown'>" . $markdown ."</textarea></div>";
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
           $page = $this->GetActivePost();
           if($page == False)
            return False;
           $html = Site::$moduleManager->HookEvent("Theme.Post.Title",$page->name)[0];
           return $html . Site::$moduleManager->HookEvent("Theme.Post.Subtitle",$page->title)[0];
        }
        
        function GetActivePost()
        {
           $request = Site::getRequest();
           if(isset($request->arguments["post"]))
               return $this->settings->postindex[$request->arguments["post"]];
           
           foreach($request->arguments as $key => $value)
           {
               $pageid = $this->GetPostIDByKey($key,$value);
               if($pageid !== False)
                   return $this->settings->postindex[$pageid];
           }
           return False;
        }
        /**
         * Get the post ID by a key in the posts data.
         * @param string $key
         * @param object $value
         * @return type
         */
        function GetPostIDByKey($key,$value)
        {
            foreach ($this->settings->postindex as $index => $post)
            {
                if(!isset($post->$key))
                    continue;
                if($post->$key == $value)
                {
                    return $index;
                }
            }
            return False;
        }
        
        function DrawBreadcrumbs()
        {
           $page = $this->GetActivePost();
           if($page == False)
            return False;
           $breadcrumbs = $page->categorys;
           return Site::$moduleManager->HookEvent("Theme.Post.Breadcrumbs",$breadcrumbs);
        }
        
        function DrawPostInfomation()
        {
            $page = $this->GetActivePost();
            if($page === False)
                return False;
            $info = array();
            $info["Author"] = $page->author;
            $info["Last Modified"] = \date("F d Y H:i:s.", \filemtime($this->settings->postdir . "/" . $page->url));;
            return Site::$moduleManager->HookEvent("Theme.Post.Infomation",$info);
        }
        
        function SavePost()
        {
             //Need a login check here.
             $url = $_POST["url"];
             $md = $_POST["markdown"];
             
             $url_data = Site::DigestURL($url);
             if(isset($url_data["name"]))
             {
                 $id = $this->GetPostIDByKey("name",$url_data["name"]);
             }
             else if(isset($url_data["post"]))
             {
                 $id = $url_data["post"];
             }
             else
             {
                 Site::$Logger->writeError("Coudln't find the post for saving markdown file. Nonstandard URL!'");
                 return "0";
             }
             $url = $this->settings->postdir . "/" . $this->settings->postindex[$id]->url;
             file_put_contents($url, $md);
             Site::$Logger->writeError("Modified " . $url . " with new data.");
             return "1";
        }
}

class BreadPageSystemSettings
{
    public $postindex = array();
    public $postdir;
    public $BuildTime = 0;
    public $CheckIndexEvery = 4;
    public $RequestToLinkTo = "post";
    public $navbar;
    function __construct() {
       $this->postdir = Site::ResolvePath("%user-pages");
       $this->navbar = new BreadPageSystemNavBarSettings();
    }
}

class BreadPageSystemNavBarSettings
{
    public $enabled = true;
}
