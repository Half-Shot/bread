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
        const TOKEN_SPLIT_STR = "[%]";
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
            $this->manager->RegisterHook($this->name,"BreadPageSystem.DeletePost","DeletePost");
            $this->manager->RegisterHook($this->name,"Bread.Security.LoggedIn","CheckEditorRights");
            $this->manager->RegisterHook($this->name,"BreadPageSystem.EditorInfomation","PostEditorInfomationPanel");
            $this->manager->RegisterHook($this->name,"Bread.PageTitle","SetSiteTitle");
            $this->manager->RegisterHook($this->name,"Bread.TokenizeText","BasicTokens");
            $this->manager->RegisterHook($this->name,"Bread.TokenizePost","TokenizeArticle");
            $this->manager->RegisterHook($this->name,"Bread.GetAllPages","ReturnBreadPages");
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
        
        static function USortDate($a,$b)
        {
            if($a->time_released > $b->time_released)
            {
                return 1;
            }
            else if ($a->time_released < $b->time_released)
            {
                return -1;
            }
            else {
                return 0;
            }
        }
        
        function GenerateNavbar()
        {
            $pages = array();
            $index = get_object_vars($this->settings->postindex);
            usort($index,"\Bread\Modules\BreadPageSystem::USortDate");
            $index = array_slice($index, 0, $this->settings->maxRecentPosts, true);
            foreach($index as $page)
            {
                if($page->time_released > time() && !$this->CheckEditorRights())
                    continue;
                if(isset($page->hidden))
                    if($page->hidden)
                        continue;
                $parts = array();
                $parts["request"] = $this->settings->RequestToLinkTo;
                $parts["post"] = $page->id;
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
            Site::AddScript(Site::ResolvePath("%user-modules/BreadPageSystem/js/Markdown.Converter.js"));
            Site::AddScript(Site::ResolvePath("%user-modules/BreadPageSystem/js/Markdown.Extra.js"));
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
        
        function SetSiteTitle()
        {
            $Post = $this->GetActivePost();
            return $Post->title;
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
            $form->onsubmit = "saveMarkdown();return false;";
            
            $E_Header_Basic = new \Bread\Structures\BreadFormElement;
            $E_Header_Basic->type = \Bread\Structures\BreadFormElement::TYPE_RAWHTML;
            $E_Header_Basic->value = "<h4>Basic Settings</h4>";
            $form->elements[] = $E_Header_Basic;
            
            $E_PostName = new \Bread\Structures\BreadFormElement;
            $E_PostName->id = "e_postname";
            $E_PostName->class = "bps-editorinfo-input";
            $E_PostName->type = \Bread\Structures\BreadFormElement::TYPE_TEXTBOX;
            $E_PostName->label = "Post Name";
            if(!$this->isnewpost)
                $E_PostName->value = $this->GetActivePost ()->name;
            $E_PostName->readonly = true;
            $E_PostName->required = true;
            $form->elements[] = $E_PostName;
            
            $E_TimeReleased = new \Bread\Structures\BreadFormElement;
            $E_TimeReleased->id = "e_timereleased";
            $E_TimeReleased->class = "bps-editorinfo-input";
            $E_TimeReleased->type = "datetime-local";
            $E_TimeReleased->label = "Release On";
            $E_TimeReleased->required = true;
            $E_TimeReleased->readonly = true;
            if(!$this->isnewpost)
                $E_TimeReleased->value = date ("m/d/Y h:i A",  $this->GetActivePost()->time_released);
            $form->elements[] = $E_TimeReleased;
            
            $E_Author = new \Bread\Structures\BreadFormElement;
            $E_Author->id = "e_author";
            $E_Author->type = \Bread\Structures\BreadFormElement::TYPE_TEXTBOX;
            $E_Author->label = "Author";
            $E_Author->readonly = true;
            $E_Author->class = "";
            $E_Author->required = true;
            if(!$this->isnewpost)
                $E_Author->value = $this->GetActivePost ()->author;
            else
                $E_Author->value = $this->GetActivePost ()->name = $this->manager->FireEvent("Bread.Security.GetCurrentUser")[0]->username;
            $form->elements[] = $E_Author;
            
            $E_Submit = new \Bread\Structures\BreadFormElement;
            $E_Submit->class = "bps-editorinfo-input btn-success";
            $E_Submit->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $E_Submit->value = "Save Post";
            if($this->isnewpost)
                $E_Submit->value = "Save New Post";
            $E_Submit->readonly = true;
            $form->elements[] = $E_Submit;
            if(!$this->isnewpost)
            {
                $E_Delete = new \Bread\Structures\BreadFormElement;
                $E_Delete->class = "bps-editorinfo-input btn-danger";
                $E_Delete->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
                $E_Delete->value = "Delete Post";
                $E_Delete->readonly = true;
                $E_Delete->onclick = "$('#warnDeletePost').modal('show');return false;";
                $form->elements[] = $E_Delete;
            }
            $HTML_Categorys = new \Bread\Structures\BreadFormElement;
            $HTML_Categorys->type = \Bread\Structures\BreadFormElement::TYPE_RAWHTML;
            $HTML_Categorys->value = "<h4>Categorys</h4>";
            $HTML_Categorys->id ="e_categorys";
            $HTML_Categorys->hidden = true;
            $E_New_Category = new \Bread\Structures\BreadFormElement;
            $E_New_Category->id = "e_newcategory";
            $E_New_Category->type = \Bread\Structures\BreadFormElement::TYPE_TEXTBOX;
            $E_New_Category->label = "Add a new category";
            $HTML_Categorys->value .= $this->manager->FireEvent("Theme.InputElement",$E_New_Category)[0];
            
            $E_New_Category_Button = new \Bread\Structures\BreadFormElement;
            $E_New_Category_Button->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $E_New_Category_Button->value = "Add Category";
            $E_New_Category_Button->onclick = "addNewCategory();";
            $E_New_Category_Button->class = "btn-default";
            $HTML_Categorys->value .= "<br>" . $this->manager->FireEvent("Theme.Button",$E_New_Category_Button)[0];
            
            $E_Categorys_List = new \stdClass();
            $E_Categorys_List->id = "bps-listcategories";
            $E_Categorys_List->small = true;
            $E_Categorys_List->value = $this->manager->FireEvent("Theme.Badge","Yay")[0];
            $HTML_Categorys->value .= "<h5>Available Categorys</h5>" . $this->manager->FireEvent("Theme.Layout.Well",$E_Categorys_List)[0];
            
            $E_Categorys_Selected = new \stdClass();
            $E_Categorys_Selected->id = "bps-selectcategories";
            $E_Categorys_Selected->small = true;
            $E_Categorys_Selected->value = "";
            foreach($this->GetActivePost ()->categorys as $category)
                $E_Categorys_Selected->value .= $this->manager->FireEvent("Theme.Badge",$category)[0];
            $HTML_Categorys->value .= "<h5>Selected Categorys</h5>" . $this->manager->FireEvent("Theme.Layout.Well",$E_Categorys_Selected)[0];
            
            $form->elements[] = $HTML_Categorys;
                    
            $E_Modal_Confirm = new \Bread\Structures\BreadFormElement;
            $E_Modal_Confirm->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $E_Modal_Confirm->value = "Yeah, burn it!";
            $E_Modal_Confirm->onclick = "deletePost();";
            $E_Modal_Confirm->class = "btn-danger";
            
            $E_Modal_Cancel = new \Bread\Structures\BreadFormElement;
            $E_Modal_Cancel->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $E_Modal_Cancel->value = "Acutally...";
            $E_Modal_Cancel->onclick = "$('#warnDeletePost').modal('hide');";
            $E_Modal_Cancel->class = "btn-info";
            $Buttons = $this->manager->FireEvent("Theme.Button",$E_Modal_Confirm)[0] . $this->manager->FireEvent("Theme.Button",$E_Modal_Cancel)[0];
            $Buttons = $this->manager->FireEvent("Theme.Layout.ButtonGroup",$Buttons)[0];
            
            //Modal for deleting posts.
            $ModalHTML = $this->manager->FireEvent("Theme.Modal",array("id"=>"warnDeletePost","label"=>"modalDeletePost","title"=>"Are You Sure?","body"=>"Are you sure you want to delete <strong>" . $this->GetActivePost ()->name . "</strong>?","footer"=>$Buttons))[0];
            
            $E_OpenEditor = new \Bread\Structures\BreadFormElement;
            $E_OpenEditor->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $E_OpenEditor->value = "Toggle Editor";
            $E_OpenEditor->onclick = "toggleMarkdown();";
            $E_OpenEditor->class = "btn-primary";
            $E_OpenEditor->toggle = true;
            $E_OpenEditor->readonly = !$this->EnableEditor;
            $OpenEditorHTML = $this->manager->FireEvent("Theme.Button",$E_OpenEditor)[0];
            $panel->body = $OpenEditorHTML . "<hr>" . $this->manager->FireEvent("Theme.Form",$form)[0] . $ModalHTML;
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
                    $pageData = Site::CastStdObjectToStruct($pageData, "\Bread\Modules\BreadPageSystemPost");
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
        
        function TokenizeArticle($article)
        {
            if(is_null($article))
                $article = $_POST["markdown"];
            $articleArray = \explode(self::TOKEN_SPLIT_STR,$article);
            $result = Site::$moduleManager->FireEvent("Bread.TokenizeText",$articleArray);
            if(!is_array($result))
                return $article;
            foreach($result as $res){
                    $articleArray = $res + $articleArray;
            }
            $article = implode("", $articleArray);
            return $article;
        }
        
        function BasicTokens($article)
        {
            foreach($article as $i => $string)
            {
                if($string == "breadver"){
                    $article[$i] = Site::Configuration ()["core"]["version"];
                    continue;
                }
                if(
                        (strpos($string, "audio(") === 0) &&
                        (strpos($string, ")") == strlen($string) - 1) &&
                        (strlen($string) > 7) 
                  )
                {
                    $URL = substr($string, 6,  strlen($string) - 7);
                    $type = substr($URL,strlen($URL) - 3,3);
                    if($type == "mp3")
                        $type = "mpeg";
                    $article[$i] = "<audio style='width:100%' controls><source src='" . $URL . "' type='audio/" . $type . "'></audio>";
                    continue;
                }
                
                if(
                        (strpos($string, "video(") === 0) &&
                        (strpos($string, ")") == strlen($string) - 1) &&
                        (strlen($string) > 7) 
                  )
                {
                    $URL = substr($string, 6,  strlen($string) - 7);
                    $type = substr($URL,strlen($URL) - 3,3);
                    $article[$i] = "<video controls><source src='" . $URL . "' type='video/" . $type . "'></video>";
                    continue;
                }
                if(
                        (strpos($string, "github(") === 0) &&
                        (strpos($string, ")") == strlen($string) - 1) &&
                        (strlen($string) > 8) 
                  )
                {
                    $URLSTR = substr($string, 7,  strlen($string) - 8);
                    $URL = explode('/',$URLSTR);
                    if(count($URL) == 5){
                        $article[$i] = '<iframe allowtransparency="true" frameborder="0" scrolling="no" seamless="seamless" src="http://colmdoyle.github.io/gh-activity/gh-activity.html?user=' . $URL[3] . '&repo=' . $URL[4] . '&type=repo" allowtransparency="true" frameborder="0" scrolling="0" width="292" height="290"></iframe>';
                    }
                    elseif(count($URL) == 4){
                        $article[$i] = '<iframe allowtransparency="true" frameborder="0" scrolling="no" seamless="seamless" src="http://colmdoyle.github.io/gh-activity/gh-activity.html?user=' . $URL[3] . '&type=user" allowtransparency="true" frameborder="0" scrolling="0" width="292" height="290"></iframe>';
                    }
                }
            }
            return $article;
        }
        
        function DrawPage()
        {
           $page = $this->GetActivePost();
           if($page == False)
            return False;
           if($page->time_released > time() && !$this->CheckEditorRights())
            return False;
           $markdown = "";
           $markdown = file_get_contents($this->GetPostPath($page->url));
           if(empty($markdown)){
               Site::$Logger->writeError ("Couldn't retrive markdown for post!",  \Bread\Logger::SEVERITY_MEDIUM, $this->name);
           }
           if(isset($page->liveedit) && $this->EnableEditor){
                $this->EnableEditor = $page->liveedit;
           }
           $ToolbarHTML = "";
           $editor = "";
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
            $ButtonAHTML = $this->manager->FireEvent("Theme.Button",$E_ButtonA)[0];
            
            $E_ButtonB = new \Bread\Structures\BreadFormElement;
            $E_ButtonB->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $E_ButtonB->value = $this->manager->FireEvent("Theme.Icon","italic")[0];
            $E_ButtonB->onclick = "wrap('*','*');";
            $ButtonBHTML = $this->manager->FireEvent("Theme.Button",$E_ButtonB)[0];
            
            $Group = $this->manager->FireEvent("Theme.Layout.ButtonGroup",$ButtonAHTML . $ButtonBHTML)[0];
            
            $E_List = new \Bread\Structures\BreadFormElement;
            $E_List->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $E_List->value = $this->manager->FireEvent("Theme.Icon","list")[0];
            $E_List->onclick = "wrap('*  ','');";
            $GroupTwo = $this->manager->FireEvent("Theme.Button",$E_List)[0];
            
            $E_Audio = new \Bread\Structures\BreadFormElement;
            $E_Audio->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $E_Audio->value = $this->manager->FireEvent("Theme.Icon","audio")[0];
            $E_Audio->onclick = "wrap('[%]audio(',')[%]');";
            
            $E_Video = new \Bread\Structures\BreadFormElement;
            $E_Video->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $E_Video->value = $this->manager->FireEvent("Theme.Icon","video")[0];
            $E_Video->onclick = "wrap('[%]video(',')[%]');";
            $GroupMedia = $this->manager->FireEvent("Theme.Layout.ButtonGroup",$this->manager->FireEvent("Theme.Button",$E_Audio)[0] . $this->manager->FireEvent("Theme.Button",$E_Video)[0])[0];
            
            $E_Github = new \Bread\Structures\BreadFormElement;
            $E_Github->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $E_Github->value = $this->manager->FireEvent("Theme.Icon","github")[0];
            $E_Github->onclick = "wrap('[%]github(',')[%]');";
            $GithubHTML = $this->manager->FireEvent("Theme.Button",$E_Github)[0];
            
            $Toolbar = $this->manager->FireEvent("Theme.Layout.ButtonToolbar",$Group . $GroupTwo . $GroupMedia . $GithubHTML)[0];
            
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
           return Site::CastStdObjectToStruct( $this->activePost, "Bread\Modules\BreadPageSystemPost");
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
           $links = array();
           foreach($breadcrumbs as $i => $text)
           {
               $link = new \stdClass();
               $link->active = ($i == 0);
               $link->value = $text;
               $links[] = $link;
           }
           $HTML = Site::$moduleManager->FireEvent("Theme.Breadcrumbs",$links)[0];
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
            $info["Last Modified"] = \date("F d Y H:i:s", $page->time_modified);
            $info["Created On"] = \date("F d Y H:i:s", $page->time_created);
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
                 $post->time_created = time();
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
             $this->settings->BuildTime = 0; //Reset Index.
             $pageData->title = $title;
             $pageData->subtitle = $subtitle; //Needs changing.
             $pageData->time_modified = time();
             $pageData->time_released = strtotime($_POST["timereleased"]);
             $pageData->categorys = $_POST["categorys"];
             if(is_null($pageData->categorys))
                 $pageData->categorys = array();
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
             return Site::getBaseURL() . "?request=" . $this->settings->RequestToLinkTo . "&post=" . $id;
        }
        
        function DeletePost()
        {   
             $canModify = $this->manager->FireEvent("Bread.Security.GetPermission","DeletePost")[0];
             if(!$canModify){
                 Site::$Logger->writeError("User tried to delete a post without permission!",\Bread\Logger::SEVERITY_MEDIUM,"breadpagesystem");
                 return "0";
             }
             $url = $_POST["url"];
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
                 Site::$Logger->writeError("User tried to delete a new post, ignoring.",\Bread\Logger::SEVERITY_LOW,"breadpagesystem");
                 return "2";
             }
             else
             {
                 Site::$Logger->writeError("Couldn't find the post for deleting file. Nonstandard URL!'",\Bread\Logger::SEVERITY_MEDIUM,"breadpagesystem");
                 return "0";
             }
             //Delete the files
             $post = $this->settings->postindex->$id;
             unlink($post->url);
             unlink($post->jsonurl);
             if(!file_exists($post->url) && !file_exists($post->jsonurl))
             {
                 return "1";
             }
             else
             {
                 Site::$Logger->writeError("Deleting the files failed for some reason",\Bread\Logger::SEVERITY_HIGH,"breadpagesystem");
                 return "3";
             }
             $this->settings->BuildTime = 0;
        }
        
        function ReturnBreadPages()
        {
            $Pages = array();
            $parts = array();
            $parts["request"] = $this->settings->RequestToLinkTo;
            foreach($this->settings->postindex as $post)
            {
                if($post->hidden)
                    continue;
                $Page = new \Bread\Structures\BreadSearchItem;
                $Page->Name = $post->title;
                $Page->Categorys = $post->categorys;
                $Page->Content = file_get_contents($this->GetPostPath($post->url));
                //URL
                $parts["post"] = $post->id;
                $Page->Url = Site::CondenseURLParams(false,$parts);
                $Pages[] = $Page;
            }
            return $Pages;
        }
}

class BreadPageSystemSettings
{
    public $postindex = array();
    public $postdir;
    public $BuildTime = 0;
    public $CheckIndexEvery = 4;
    public $RequestToLinkTo = "post";
    public $maxRecentPosts = 5;
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
   public $author = "Unknown";
   public $hidden = false;
   public $thumb= "";
   public $jsonurl= "";
   public $time_created = 0;
   public $time_released = 0;
   public $time_modified = 0;
   public $id= "";
}