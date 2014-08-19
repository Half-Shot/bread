<?php

/*
 * The MIT License
 *
 * Copyright 2014 will.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Description of BreadCommentSystem
 *
 * @author will
 */
namespace Bread\Modules;
use Bread\Site as Site;
use Bread\Utilitys as Util;
class BreadCommentSystem extends Module{
    private $settings;
    private $comments;
    private $uniqueID;
    private $completedPageSetup = false;
    private $buttons = array();
    function __construct($manager,$name)
    {
        parent::__construct($manager,$name);
    }
    
    function MakeButtons(){
        $Btn_Comment_Edit = new \Bread\Structures\BreadFormElement;
        $Btn_Comment_Edit->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $Btn_Comment_Edit->value = $this->manager->FireEvent("Theme.Icon","pencil");
        $Btn_Comment_Edit->class = $this->manager->FireEvent("Theme.GetClass","Button.Warning"). " " . $this->manager->FireEvent("Theme.GetClass","Button.Small");
        $Btn_Comment_Edit->id = "editcomment-button";
        $this->buttons["Edit"] = $this->manager->FireEvent("Theme.Button",$Btn_Comment_Edit);
        
        $Btn_Comment_Delete = new \Bread\Structures\BreadFormElement;
        $Btn_Comment_Delete->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $Btn_Comment_Delete->value = $this->manager->FireEvent("Theme.Icon","trash");
        $Btn_Comment_Delete->class = $this->manager->FireEvent("Theme.GetClass","Button.Danger"). " " . $this->manager->FireEvent("Theme.GetClass","Button.Small");
        $Btn_Comment_Delete->id = "deletecomment-button";
        $this->buttons["Delete"] = $this->manager->FireEvent("Theme.Button",$Btn_Comment_Delete);

        $Btn_SaveChanges = new \Bread\Structures\BreadFormElement;
        $Btn_SaveChanges->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $Btn_SaveChanges->value = $this->manager->FireEvent("Theme.Icon","ok");
        $Btn_SaveChanges->id = "savecomment-button";
        $Btn_SaveChanges->class = $this->manager->FireEvent("Theme.GetClass","Button.Success"). " " . $this->manager->FireEvent("Theme.GetClass","Button.Small");
        $this->buttons["Save"] = $this->manager->FireEvent("Theme.Button",$Btn_SaveChanges);
        
        
        $Btn_Comment_Upvote = new \Bread\Structures\BreadFormElement;
        $Btn_Comment_Upvote->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $Btn_Comment_Upvote->value = $this->manager->FireEvent("Theme.Icon","thumbs-up");
        $Btn_Comment_Upvote->class = $this->manager->FireEvent("Theme.GetClass","Button.Success"). " " . $this->manager->FireEvent("Theme.GetClass","Button.Small") . " upvotecomment-button";
        $this->buttons["Upvote"] = $this->manager->FireEvent("Theme.Button",$Btn_Comment_Upvote);
        
        $Btn_Comment_Downvote = new \Bread\Structures\BreadFormElement;
        $Btn_Comment_Downvote->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $Btn_Comment_Downvote->value = $this->manager->FireEvent("Theme.Icon","thumbs-down");
        $Btn_Comment_Downvote->class = $this->manager->FireEvent("Theme.GetClass","Button.Danger"). " " . $this->manager->FireEvent("Theme.GetClass","Button.Small");
        $Btn_Comment_Downvote->id = "downvotecomment-button";
        $this->buttons["Downvote"] = $this->manager->FireEvent("Theme.Button",$Btn_Comment_Downvote);
    }
    
    function Setup()
    {
        //Get hash.
        $rootSettings = Site::$settingsManager->FindModuleDir("breadcommentsystem");
        Site::$settingsManager->CreateSettingsFiles($rootSettings . "settings.json",new BreadCommentSystemSettings());
        $this->settings = Site::$settingsManager->RetriveSettings($rootSettings . "settings.json");
        $this->settings = Util::CastStdObjectToStruct($this->settings,"Bread\Modules\BreadCommentSystemSettings");
    }
    
    function PageSetup(){
        if(!$this->completedPageSetup){
             $this->uniqueID = $this->manager->FireEvent("Bread.PageUniqueID");
            if($this->uniqueID === false){
                $this->manager->UnregisterModule("BreadCommentSystem");
            }
            else{
                $path = Util::ResolvePath("%user-content/comments/" . $this->uniqueID . ".json");
                Site::$settingsManager->CreateSettingsFiles($path,new BreadCommentsStack());
                $this->comments = Site::$settingsManager->RetriveSettings($path);
            }
            $this->completedPageSetup = true;
            Site::AddScript(Site::ResolvePath("%user-modules/BreadCommentSystem/js/commentsystem.js"), true);
            Site::AddRawScriptCode('window.pageuniqueid="' . $this->uniqueID . '"');
            $this->MakeButtons();
        }
    }
    
    function ConstructEditableComment(){
        //Editable comment.
        $CurrentUser = $this->manager->FireEvent("Bread.Security.GetCurrentUser");
        
        if($this->manager->FireEvent("Bread.Security.GetPermission","WriteComment")){
            $Avatar = $this->manager->FireEvent("Bread.GetAvatar",$CurrentUser);
            $Name = Util::EmptySub($CurrentUser->information->Name, "Unknown");
            $HTML = '<div id="newcomment">' . $this->ConstructComment(-1,$Name, $Avatar, "Click here to edit...", true,false,false,array($this->buttons["Save"])) . '</div>';
        }
        else if($this->settings->AllowAnonComments && $CurrentUser === null){
            $Avatar = $this->manager->FireEvent("Bread.GetAvatar",false);
            $HTML = '<div id="newcomment">' . $this->ConstructComment(-1,"Anonymous",$Avatar ,"Click here to edit...", true,false,false,array($this->buttons["Save"])) . '</div>';
        }
        else{
            $HTML = "";
        }
        return $HTML;
    }
    
    function ShowComments(){
        $this->PageSetup();
        $HTML = "<hr>";
        $CurrentUser = $this->manager->FireEvent("Bread.Security.GetCurrentUser"); 

        if($this->comments->locked){
            $HTML .= $this->manager->FireEvent("Theme.Alert",array("class"=>"alert-warning","body"=>"Comment section is locked."));
        }
        else{
            $HTML .= $this->ConstructEditableComment();
        }
        $CommentIndex = 0;
        
        foreach($this->comments->comments as $comment){
            $commentObj = Util::CastStdObjectToStruct($comment, "Bread\Structures\BreadComment");
            $User = $this->manager->FireEvent("Bread.Security.GetUser",$commentObj->user);
            $Avatar = $this->manager->FireEvent("Bread.GetAvatar",$User);
            $ButtonsHTML = "";
            if($CurrentUser !== null){
                $ButtonsHTML = $this->buttons["Upvote"] . $this->buttons["Downvote"];
                if($comment->user === $CurrentUser->uid){
                    if($this->settings->AllowEditing){
                        $ButtonsHTML .= $this->buttons["Edit"] . $this->buttons["Save"];
                    }
                    if($this->settings->AllowDeleting){
                        $ButtonsHTML .= $this->buttons["Delete"];
                    }
                }
            }
            $HTML .= $this->ConstructComment($CommentIndex,$User->information->Name, $Avatar, $commentObj->body, false,$commentObj->time,$commentObj->karma,$ButtonsHTML);
            $CommentIndex++;
        }
        return $HTML;
    }
    
    private function ConstructComment($Index,$Name,$Thumb,$Text,$Editable,$Time,$Karma,$Buttons){
        $CommentStruct = new \Bread\Structures\BreadThemeCommentStructure();
        $CommentStruct->thumbnail = $Thumb;
        $CommentStruct->header = $Name;
        if($Editable){
            $CommentStruct->body =  '<span class="commentcharsleft">'.$this->settings->CharacterLimit.'</span><div class="commentbody editable" contenteditable="true"> ' .  $Text . "</div>";
        }
        else{
            $CommentStruct->body =  '<index hidden=true>'.$Index.'</index><div class="commentbody">' .  $Text . "</div>";
        }
        $CommentStruct->body .= '<small class="stats">';
        if($Time){
            $CommentStruct->body .= '<span class="time">Last edited on '.$this->manager->FireEvent("Theme.Badge",date("F j, Y, g:i a",$Time)).'</span>';
        }
        if($Karma !== false && ($this->settings->EnableUpvoting || $this->settings->EnableDownvoting)){
            $CommentStruct->body .= '<span class="score"> Score: '.$this->manager->FireEvent("Theme.Badge",  strval($Karma)) . '</span>';
        }
        $CommentStruct->body .= '</small>';
        $Buttons = $this->manager->FireEvent("Theme.Layout.ButtonGroup",$Buttons);
        $CommentStruct->body .= "<div>".$Buttons."</div>";
        $HTML = $this->manager->FireEvent("Theme.Comment",$CommentStruct);
        return $HTML;
    }
    
    function Upvote(){
        $CurrentUser = $this->manager->FireEvent("Bread.Security.GetCurrentUser");
        if($CurrentUser === null){
            return 0;
        }
        
        $this->uniqueID = $_REQUEST["uniqueid"];
        $commentID = $_REQUEST["commentid"];
        $path = Util::ResolvePath("%user-content/comments/" . $this->uniqueID . ".json");
        if(!file_exists($path)){
            return 0;
        }
        $this->comments = Site::$settingsManager->RetriveSettings($path);  
        $comment = $this->comments->comments[$commentID];
        if(in_array($CurrentUser->uid,$comment->karmavotees)){
            //Take it off then.
            $index = array_search($CurrentUser->uid, $comment->karmavotees);
            unset($comment->karmavotees[$index]);
            $comment->karma--;
            return 2;
        }
        $comment->karmavotees[] = $CurrentUser->uid;
        $comment->karma++;
        
        return 1;
    }
    
    function Downvote(){
        
    }
    
}

class BreadCommentSystemSettings {
    public $AllowAnonComments = true;
    public $AllowEditing = true;
    public $AllowDeleting = true;
    public $EnableDownvoting = true;
    public $EnableUpvoting = true;
    public $CharacterLimit = 400;
}


class BreadCommentsStack{
    public $comments = array();
    public $locked = false;
    public $id = "";
}
