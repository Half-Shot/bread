<?php
namespace Bread\Modules;
use Bread\Site as Site;
class BreadPageSystem extends Module
{
        private $settings;
        private $settingspath;
        private $isnewpost = false;
        private $activePost = false;
        public $EnableEditor = false;
        function __construct($manager,$name)
        {
	        parent::__construct($manager,$name,__DIR__);
        }

        function RegisterEvents()
        {
            $this->manager->RegisterHook($this->name,"Bread.DrawModule","DrawPage");
            $this->manager->RegisterHook($this->name,"Bread.ProcessRequest","Setup",array("Bread.ProcessRequest"=>"BreadUserSystem"));
            $this->manager->RegisterHook($this->name,"Bread.LowPriorityScripts","GenerateHTML");
            $this->manager->RegisterHook($this->name,"BreadPageSystem.DrawRecentPosts","DrawRecentPosts");
            $this->manager->RegisterHook($this->name,"Bread.Title","DrawTitle");
            $this->manager->RegisterHook($this->name,"BreadPageSystem.PlainMarkdown","DrawPlainMarkdown");
            $this->manager->RegisterHook($this->name,"BreadPageSystem.BreadCrumbs","DrawBreadcrumbs");
            $this->manager->RegisterHook($this->name,"BreadPageSystem.Infomation","DrawPostInfomation");
            $this->manager->RegisterHook($this->name,"BreadPageSystem.EditorButton","DrawMarkdownToggleswitch");
            $this->manager->RegisterHook($this->name,"BreadPageSystem.EditorToolbar","EditorToolbar");
            $this->manager->RegisterHook($this->name,"BreadPageSystem.SavePost","SavePost");
            $this->manager->RegisterHook($this->name,"Bread.Security.LoggedIn","CheckEditorRights");
            $this->manager->RegisterHook($this->name,"BreadPageSystem.EditorInfomation","PostEditorInfomationPanel");
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
                return "<button id='bps-mdtoggle' onclick='toggleMarkdown();'>Open Editor</button>";
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
            Site::AddScript(Site::ResolvePath("%user-modules/BreadPageSystem/js/showdown.js")); //For just parsing.
            
            $this->EnableEditor = $this->CheckEditorRights();
            if(array_key_exists("newpost", Site::getRequest()->arguments))
            {   
               if(!$this->manager->FireEvent("Bread.Security.GetPermission","NewPost")[0])
                    Site::$Logger->writeError("Request to create new post but user has no right!",  \Bread\Logger::SEVERITY_MEDIUM, $this->name, true);
               $this->isnewpost = true;
               if(empty($this->settings->templatePath)){
                    Site::$Logger->writeError ("No template path set!", \Bread\Logger::SEVERITY_HIGH, $this->name);
                    return false;
               }
               $path = $this->path . "/" . $this->settings->templatePath;
               $pageData = Site::$settingsManager->RetriveSettings($path);
               $this->activePost = $pageData;
            }
        }
        
        function CheckEditorRights()
        {
           //See if the user is an editor
           return ($this->manager->FireEvent("Bread.Security.GetPermission","Editor")[0]);
        }
        
        function PostEditorInfomationPanel()
        {
            if(!$this->CheckEditorRights())
                return "";
            $panel = new \stdClass();
            $panel->title = "Post Details";
            $form = new \Bread\Structures\BreadForm;
            $form->id = "bps-editorinfo";
            
            $E_PostName = new \Bread\Structures\BreadFormElement;
            $E_PostName->id = "e_postname";
            $E_PostName->class = "bps-editorinfo-input";
            $E_PostName->type = \Bread\Structures\BreadFormElement::TYPE_TEXTBOX;
            $E_PostName->label = "Post Name";
            if(!$this->isnewpost)
                $E_PostName->value = $this->GetActivePost ()->name;
            $E_PostName->readonly = true;
            $form->elements[] = $E_PostName;
            
            $E_Author = new \Bread\Structures\BreadFormElement;
            $E_Author->id = "e_author";
            $E_Author->type = \Bread\Structures\BreadFormElement::TYPE_TEXTBOX;
            $E_Author->label = "Author";
            $E_Author->readonly = true;
            $E_Author->class = "";
            if(!$this->isnewpost)
                $E_Author->value = $this->GetActivePost ()->author;
            else
                $E_Author->value = $this->GetActivePost ()->name = $this->manager->FireEvent("Bread.Security.GetCurrentUser")[0]->username;
            $form->elements[] = $E_Author;
            
            $E_Submit = new \Bread\Structures\BreadFormElement;
            $E_Submit->class = "bps-editorinfo-input btn-success";
            $E_Submit->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $E_Submit->value = "Save";
            $E_Submit->readonly = true;
            $E_Submit->onclick = "saveMarkdown();";
            $form->elements[] = $E_Submit;
            
            
            $E_OpenEditor = new \Bread\Structures\BreadFormElement;
            $E_OpenEditor->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $E_OpenEditor->value = "Toggle Editor";
            $E_OpenEditor->onclick = "toggleMarkdown();";
            $E_OpenEditor->class = "btn-primary";
            $E_OpenEditor->toggle = true;
            $E_OpenEditor->readonly = !$this->EnableEditor;
            $OpenEditorHTML = $this->manager->FireEvent("Theme.InputElement",$E_OpenEditor)[0];
            $panel->body = $OpenEditorHTML . "<br>" . $this->manager->FireEvent("Theme.Form",$form)[0];
            return $this->manager->FireEvent("Theme.Panel",$panel)[0];
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
           $markdown = "";
           $markdown = file_get_contents($this->GetPostPath($page->url));
           if(empty($markdown)){
               Site::$Logger->writeError ("Couldn't retrive markdown for post!",  \Bread\Logger::SEVERITY_MEDIUM, $this->name);
           }
           $editor = "";
           if(isset($page->liveedit) && $this->EnableEditor){
                $this->EnableEditor = $page->liveedit;
           }
           $ToolbarHTML = "";
           if($this->EnableEditor){
               //Toolbar
               $ToolbarHTML = $this->GenerateEditorToolbar();
               $editor = "editor";
               Site::AddRawScriptCode("var epiceditor_basepath ='" . Site::ResolvePath("%user-modules/BreadPageSystem/css/") . "';");//Dirty Hack
               Site::AddScript(Site::ResolvePath("%user-modules/BreadPageSystem/js/epiceditor.min.js"), true);
           }
           return "<div class='bps-content' " . $editor . "><textarea class='bps-markdown'>" . $markdown ."</textarea></div>" . $ToolbarHTML;
        }
        
        function GenerateEditorToolbar()
        {
            $E_ButtonA = new \Bread\Structures\BreadFormElement;
            $E_ButtonA->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $E_ButtonA->value = $this->manager->FireEvent("Theme.Icon","bold")[0];
            $E_ButtonA->onclick = "wrap('**','**');";
            $ButtonAHTML = $this->manager->FireEvent("Theme.InputElement",$E_ButtonA)[0];
            
            $E_ButtonB = new \Bread\Structures\BreadFormElement;
            $E_ButtonB->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $E_ButtonB->value = $this->manager->FireEvent("Theme.Icon","italic")[0];
            $E_ButtonB->onclick = "wrap('*','*');";
            $ButtonBHTML = $this->manager->FireEvent("Theme.InputElement",$E_ButtonB)[0];
            
            $Group = $this->manager->FireEvent("Theme.Layout.ButtonGroup",$ButtonAHTML . $ButtonBHTML)[0];
            
            $E_List = new \Bread\Structures\BreadFormElement;
            $E_List->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $E_List->value = $this->manager->FireEvent("Theme.Icon","list")[0];
            $E_List->onclick = "wrap('*  ','');";
            $GroupTwo = $this->manager->FireEvent("Theme.Layout.ButtonGroup",$this->manager->FireEvent("Theme.InputElement",$E_List)[0])[0];
            
            $Toolbar = $this->manager->FireEvent("Theme.Layout.ButtonToolbar",$Group . $GroupTwo)[0];
            
            return "<div id='bps-editor-toolbar' style='display:none;'>" . $Toolbar . "</div>";
        }
        
        function GenerateHTML()
        {
            Site::AddScript(Site::ResolvePath("%user-modules/BreadPageSystem/js/doMarkdown.js"), true);
            Site::AddRawScriptCode("DoMarkdown();",true);
            if($this->isnewpost)
                Site::AddRawScriptCode ("toggleMarkdown();", true);
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
           return Site::$moduleManager->FireEvent("Theme.Post.Title",array("<div id='bps-title'>" . $page->title . "</div>","<div id='bps-subtitle'>" . $page->subtitle . "</div>"))[0];
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
           if(!$this->activePost){
                $postid = $this->GetActivePostPageId();
                if($postid !== false || isset($this->settings->postindex->$postid))
                {
                     $this->activePost = $this->settings->postindex->$postid;
                }
           }
           return $this->activePost;
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
    
        function GetPostPath($url)
        {
           $path = array();
           $path[0] = $this->settings->postdir . "/" . $url;
           $path[1] = $url;
           $path[2] = $this->path . "/" . $url;
           foreach($path as $p)
           {
               if(file_exists($p))
                   return $p;
           }
           return false;
        }
        
        function DrawPostInfomation()
        {
            $page = $this->GetActivePost();
            if($page === False)
                return False;
            $info = array();
            $info["Author"] = $page->author;
            $info["Last Modified"] = \date("F d Y H:i:s", \filemtime($this->GetPostPath($page->url)));;
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
             else if(isset($url_data["newpost"]))
             {
                 $id = $this->GenerateID();
                 $post = new BreadPageSystemPost;
                 $post->name = $_POST["name"];
                 //$this->settings->postindex->$id->categorys = $_POST["categorys"];
                 $post->author = $_POST["author"];
                 $post->url = $post->name . ".md";
                 $post->jsonurl =  $this->settings->postdir . "/" . $post->name . ".json";
                 $post->id = $id;
                 $this->settings->postindex->$id = $post;
                 Site::$settingsManager->SaveSetting($post,$post->jsonurl,True);
             }
             else
             {
                 Site::$Logger->writeError("Couldn't find the post for saving markdown file. Nonstandard URL!'",\Bread\Logger::SEVERITY_HIGH,"breadpagesystem");
                 return "0";
             }
             $url = $this->settings->postdir . "/" . $this->settings->postindex->$id->url;
             file_put_contents($url, $md);
             $pageData = Site::$settingsManager->RetriveSettings($this->settings->postindex->$id->jsonurl,True); //Get actual file
             $this->BuildTime = 0; //Reset Index.
             $pageData->title = $title;
             $pageData->subtitle = $subtitle; //Needs changing.
             
             if($pageData->name != $_POST["name"])
             {
                 Site::$Logger->writeError("Page got renamed (" . $pageData->name . "=>" . $_POST["name"] . ")",\Bread\Logger::SEVERITY_MESSAGE,$this->name);
                 $pageData->name = $_POST["name"];
                 \rename($pageData->jsonurl,$this->settings->postdir . "/" . $pageData->name . ".json");
                 \rename($this->settings->postdir . "/" . $pageData->url,$this->settings->postdir . "/" . $pageData->name . ".md");
                 $pageData->jsonurl =  $this->settings->postdir . "/" . $pageData->name . ".json";
                 $pageData->url = $pageData->name . ".md";
             }
             
             try
             {
                Site::$settingsManager->SaveSetting($pageData,$this->settings->postindex->$id->jsonurl,True);
             }
             catch(Exception $e)
             {
                 Site::$Logger->writeError("Coudln't save bread page system settings. Gave an " . get_class($e),\Bread\Logger::SEVERITY_HIGH,$this->name);
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
    public $templatePath = "template.json";
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

class BreadPageSystemPost
{
   public $url= "";
   public $name= "";
   public $title= "";
   public $subtitle= "";
   public $categorys= array();
   public $liveedit= true;
   public $author= "Unknown";
   public $thumb= "";
   public $jsonurl= "";
   public $id= "";
}