<?php
namespace Bread\Modules;
use Bread\Site as Site;
class BreadPageSystem extends Module
{
        private $settings;
        private $settingspath;
        public $EnableEditor = false;
        function __construct($manager,$name)
        {
	        parent::__construct($manager,$name);
        }

        function RegisterEvents()
        {
            $this->manager->RegisterHook($this->name,"Bread.DrawModule","DrawPage");
            $this->manager->RegisterHook($this->name,"Bread.ProcessRequest","Setup",array("Bread.Security.GetPermission"=>"BreadUserSystem"));
            $this->manager->RegisterHook($this->name,"Bread.LowPriorityScripts","GenerateHTML");
            $this->manager->RegisterHook($this->name,"BreadPageSystem.DrawRecentPosts","DrawRecentPosts");
            $this->manager->RegisterHook($this->name,"Bread.Title","DrawTitle");
            $this->manager->RegisterHook($this->name,"BreadPageSystem.PlainMarkdown","DrawPlainMarkdown");
            $this->manager->RegisterHook($this->name,"BreadPageSystem.BreadCrumbs","DrawBreadcrumbs");
            $this->manager->RegisterHook($this->name,"BreadPageSystem.Infomation","DrawPostInfomation");
            $this->manager->RegisterHook($this->name,"BreadPageSystem.EditorButton","DrawMarkdownToggleswitch");
            $this->manager->RegisterHook($this->name,"BreadPageSystem.SavePost","SavePost");
            $this->manager->RegisterHook($this->name,"Bread.Security.LoggedIn","CheckEditorRights");
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
            if(count($args) == 0)
                return False;
            return "<div class='bps-content'><div class='bps-markdown'>" . $args[0] ."</div></div>";
        }
        
        function DrawMarkdownToggleswitch()
        {
            if($this->EnableEditor){
                return "<button id='bps-mdtoggle' onclick='toggleMarkdown();'>Open Editor</button>"
                . "<button id='bps-mdsave' onclick='saveMarkdown();'>Save/Publish</button>";
            }
            
            return "";
        }
        
        function GenerateNavbar()
        {
            $pages = array();
            foreach(get_object_vars($this->settings->postindex) as $id => $page)
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
            $this->settingspath = $rootSettings . "settings.json";
            Site::$settingsManager->CreateSettingsFiles($this->settingspath, new BreadPageSystemSettings());
            $this->settings = Site::$settingsManager->RetriveSettings($this->settingspath);
            if( ( time() - $this->settings->BuildTime) > $this->settings->CheckIndexEvery){
                $this->BuildIndex();
            }
            //TODO: Add a way to determine a user who can and can't edit the page. This would go here.
            Site::AddScript(Site::ResolvePath("%user-modules/BreadPageSystem/js/showdown.js")); //For just parsing.
            
        }
        
        function CheckEditorRights()
        {
           //See if the user is an editor
           if($this->manager->FireEvent("Bread.Security.GetPermission","Editor")[0]){
                   $this->EnableEditor = true;
           }
        }
        
        function BuildIndex()
        {
            $this->settings->postindex = new \stdClass;//Wipe object, we are rebuilding it.
            foreach(new \recursiveIteratorIterator( new \recursiveDirectoryIterator($this->settings->postdir)) as $file)
            {
                if(pathinfo($file->getFilename())['extension'] == "json")
                {
                    $path = $file->getPathname();
                    $pageData = Site::$settingsManager->RetriveSettings($path);
                    $pageData->jsonurl = $path;
                    if(isset(pathinfo($pageData->url)['extension']))
                        if(pathinfo($pageData->url)['extension'] == "md"){
                            if(!isset($pageData->id))
                                $pageData->id = $this->GenerateID();
                            $id = $pageData->id;
                            $this->settings->postindex->$id = $pageData;
                        }
                }
            }
            $this->settings->BuildTime = time();
            Site::$Logger->writeMessage("Built Page Index!",$this->name);
        }
        
        function DrawPage()
        {
           $page = $this->GetActivePost();
           if($page == False)
            return False;
           $markdown = file_get_contents($this->settings->postdir . "/" . $page->url);
           if(empty($markdown)){
               Site::$Logger->writeError ("Couldn't retrive markdown for post!",  \Bread\Logger::SEVERITY_MEDIUM, $this->name);
           }
           $editor = "";
           if(isset($page->liveedit) && $this->EnableEditor)
                $this->EnableEditor = $page->liveedit;
           
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
            $request = Site::getRequest();
            $pages = $this->GenerateNavbar();
            $links = array();
            $currentid = $this->GetActivePostPageId();
            foreach($pages as $name => $url)
            {
                $link = new \Bread\Structures\BreadLinkStructure();
                $link->url = $url;
                $link->text = $name;
                $postid = Site::DigestURL($url)["post"];
                $link->active = ($currentid == $postid);
                $links[] = $link;
            }
            return Site::$moduleManager->FireEvent("Theme.VerticalNavbar",$links)[0];
        }
        
        function GenerateID()
        {
            $newIDNeeded = true;
            while($newIDNeeded){
                $randomString = substr(md5(microtime()),rand(0,26),8);
                $newIDNeeded = false;
                foreach($this->settings->postindex as $post)
                {
                    if(!isset($post->id))
                        continue;
                    if($post->id == $randomString){
                        $newIDNeeded = true;
                        break;
                    }

                }
            }
            return $randomString;
        }
        
        function DrawTitle()
        {
           $page = $this->GetActivePost();
           if($page == False)
            return False;
           return Site::$moduleManager->FireEvent("Theme.Post.Title",array("<div id='bps-title'>" . $page->name . "</div>","<div id='bps-subtitle'>" . $page->title . "</div>"))[0];
        }
        
        function GetActivePostPageId()
        {
           $request = Site::getRequest();
           if(isset($request->arguments["post"]))
               return $request->arguments["post"];
           
           foreach($request->arguments as $key => $value)
           {
               $pageid = $this->GetPostIDByKey($key,$value);
               if($pageid !== False){
                   return $pageid;
               }
           }
           return false;
        }
        
        function GetActivePost()
        {
           $postid = $this->GetActivePostPageId();
           if($postid !== false)
           {
                if(!isset($this->settings->postindex->$postid))
                   return false;
                return $this->settings->postindex->$postid;
           }
           else
           {
                return False;
           }
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
           $HTML = Site::$moduleManager->FireEvent("Theme.Post.Breadcrumbs",$breadcrumbs)[0];
           return $HTML;
        }
        
        function DrawPostInfomation()
        {
            $page = $this->GetActivePost();
            if($page === False)
                return False;
            $info = array();
            $info["Author"] = $page->author;
            $info["Last Modified"] = \date("F d Y H:i:s.", \filemtime($this->settings->postdir . "/" . $page->url));;
            return Site::$moduleManager->FireEvent("Theme.Post.Infomation",$info)[0];
        }
        
        function SavePost()
        {
             $canSave = $this->manager->FireEvent("Bread.Security.GetPermission","Editor")[0];
             if(!$canSave){
                 Site::$Logger->writeError("User tried to save markdown without permission somehow, blocked!",\Bread\Logger::SEVERITY_HIGH,"breadpagesystem");
                 return "0";
             }
             $url = $_POST["url"];
             $md = $_POST["markdown"];
             $title = $_POST["title"];
             $subtitle = $_POST["subtitle"];
             
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
                 Site::$Logger->writeError("Couldn't find the post for saving markdown file. Nonstandard URL!'",\Bread\Logger::SEVERITY_HIGH,"breadpagesystem");
                 return "0";
             }
             $url = $this->settings->postdir . "/" . $this->settings->postindex[$id]->url;
             file_put_contents($url, $md);
             $pageData = Site::$settingsManager->RetriveSettings($this->settings->postindex[$id]->jsonurl,True); //Get actual file
             $this->BuildTime = 0; //Reset Index.
             $pageData->name = $title;
             $pageData->title = $subtitle; //Needs changing.
             try
             {
                Site::$settingsManager->SaveSetting($pageData,$this->settings->postindex[$id]->jsonurl,True);
             }
             catch(Exception $e)
             {
                 Site::$Logger->writeError("[BPS]Coudln't save bread page system settings. Gave an " . get_class($e),\Bread\Logger::SEVERITY_HIGH,"breadpagesystem");
                 return "0";
             }
             Site::$Logger->writeMessage("Modified " . $url . " with new data.","breadpagesystem");
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
