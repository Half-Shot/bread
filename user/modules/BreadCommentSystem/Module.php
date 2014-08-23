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
        $Btn_Comment_Edit->value = $this->manager->FireEvent("Theme.Icon","pencil") . " Edit";
        $Btn_Comment_Edit->class = $this->manager->FireEvent("Theme.GetClass","Button.Warning"). " editcomment-button " . $this->manager->FireEvent("Theme.GetClass","Button.Small");
        $this->buttons["Edit"] = $this->manager->FireEvent("Theme.Button",$Btn_Comment_Edit);
        
        $Btn_Comment_Delete = new \Bread\Structures\BreadFormElement;
        $Btn_Comment_Delete->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $Btn_Comment_Delete->value = $this->manager->FireEvent("Theme.Icon","trash") . " Delete";
        $Btn_Comment_Delete->class = $this->manager->FireEvent("Theme.GetClass","Button.Danger"). " deletecomment-button " . $this->manager->FireEvent("Theme.GetClass","Button.Small");
        $this->buttons["Delete"] = $this->manager->FireEvent("Theme.Button",$Btn_Comment_Delete);

        $Btn_SaveChanges = new \Bread\Structures\BreadFormElement;
        $Btn_SaveChanges->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
        $Btn_SaveChanges->value = $this->manager->FireEvent("Theme.Icon","ok") . " Save";
        $Btn_SaveChanges->class = $this->manager->FireEvent("Theme.GetClass","Button.Success"). " savecomment-button " . $this->manager->FireEvent("Theme.GetClass","Button.Small");
        $this->buttons["Save"] = $this->manager->FireEvent("Theme.Button",$Btn_SaveChanges);
        
        if($this->settings->EnableUpvoting){
            $Btn_Comment_Upvote = new \Bread\Structures\BreadFormElement;
            $Btn_Comment_Upvote->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $Btn_Comment_Upvote->value = $this->manager->FireEvent("Theme.Icon","thumbs-up");
            $Btn_Comment_Upvote->class = $this->manager->FireEvent("Theme.GetClass","Button.Success"). " " . $this->manager->FireEvent("Theme.GetClass","Button.Small") . " upvotecomment-button";
            $this->buttons["Upvote"] = $this->manager->FireEvent("Theme.Button",$Btn_Comment_Upvote);
        }
        else{
            $this->buttons["Upvote"] = "";
        }
        
        if($this->settings->EnableDownvoting){
            $Btn_Comment_Downvote = new \Bread\Structures\BreadFormElement;
            $Btn_Comment_Downvote->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $Btn_Comment_Downvote->value = $this->manager->FireEvent("Theme.Icon","thumbs-down");
            $Btn_Comment_Downvote->class = $this->manager->FireEvent("Theme.GetClass","Button.Danger"). " " . $this->manager->FireEvent("Theme.GetClass","Button.Small") . " downvotecomment-button";
            $this->buttons["Downvote"] = $this->manager->FireEvent("Theme.Button",$Btn_Comment_Downvote);
        }
        else{
            $this->buttons["Downvote"] = "";
        }
    }
    
    function Setup()
    {
        $this->settings = Site::$settingsManager->RetriveSettings("breadcommentsystem#settings",true,new BreadCommentSystemSettings());
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
                $this->comments = Site::$settingsManager->RetriveSettings($path,true,new BreadCommentsStack());
            }
            $this->completedPageSetup = true;
            
            Site::AddScript(Site::ResolvePath("%user-modules/BreadCommentSystem/js/Markdown.Converter.js"),"MarkdownConverter",true);
            Site::AddScript(Site::ResolvePath("%user-modules/BreadCommentSystem/js/Markdown.Extra.js"),"MarkdownExtra",true);
            Site::AddScript(Site::ResolvePath("%user-modules/BreadCommentSystem/js/epiceditor.min.js"),"EpicEditor", true);
            Site::AddScript(Site::ResolvePath("%user-modules/BreadCommentSystem/js/commentsystem.js"),"BreadCommentSystemScript", true);
            
            Site::AddRawScriptCode("var epiceditor_basepath ='" . Site::ResolvePath("%user-modules/BreadCommentSystem/css/") . "';");//Dirty Hack
            Site::AddRawScriptCode('window.pageuniqueid="' . $this->uniqueID . '"');
            Site::AddRawScriptCode('window.commenttimeout="' . $this->settings->CommentTimeout . '"');
            Site::AddRawScriptCode('RegenerateAllCommentHTML();',true);
            
            $this->MakeButtons();
        }
    }
    
    function ConstructEditableComment(){
        //Editable comment.
        $CurrentUser = $this->manager->FireEvent("Bread.Security.GetCurrentUser");
        if($this->manager->FireEvent("Bread.Security.GetPermission","BreadCommentSystem.WriteComment")){
            $Avatar = $this->manager->FireEvent("Bread.GetAvatar",$CurrentUser);
            $Name = Util::EmptySub($CurrentUser->information->Name, "Unknown");
            $HTML = '<div id="newcomment">' . $this->ConstructComment(-1,$Name, $Avatar, "Click here to edit...", true,false,false,"",$this->buttons["Save"]) . '</div>';
        }
        else if($this->settings->AllowAnonComments && $CurrentUser === null){
            $Avatar = $this->manager->FireEvent("Bread.GetAvatar",false);
            $HTML = '<div id="newcomment">' . $this->ConstructComment(-1,"Anonymous",$Avatar ,"Click here to edit...", true,false,false,"",$this->buttons["Save"]) . '</div>';
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
        
        foreach($this->comments->comments as $comment){
            $commentObj = Util::CastStdObjectToStruct($comment, "Bread\Structures\BreadComment");
            $User = $this->manager->FireEvent("Bread.Security.GetUser",$commentObj->user);
            $Avatar = $this->manager->FireEvent("Bread.GetAvatar",$User);
            $EditorButtons = "";
            $ButtonsHTML = "";
            if($CurrentUser !== null){
                $ButtonsHTML = $this->buttons["Upvote"] . $this->buttons["Downvote"];
                if($comment->user === $CurrentUser->uid){
                    if($this->settings->AllowEditing){
                        $EditorButtons .= $this->buttons["Edit"] . $this->buttons["Save"];
                    }
                }
                if($this->manager->FireEvent("Bread.Security.GetPermission","BreadCommentSystem.DeleteOthersComment") || $comment->user === $CurrentUser->uid && $this->settings->AllowDeleting){
                    $EditorButtons .= $this->buttons["Delete"];
                }
            }
            $Name = "Anonymous";
            if($commentObj->user !== -1){
                $Name = $User->information->Name;
            }
            $HTML .= $this->ConstructComment($commentObj->index,$Name, $Avatar, $commentObj->body, false,$commentObj->time, count($commentObj->karmaupvotees) - count($commentObj->karmadownvotees),$ButtonsHTML,$EditorButtons);
        }
        return $HTML;
    }
    
    function DeleteComment(){
        //Not allowed to delete own comments and moderator rights are not found.
        $CurrentUser = $this->manager->FireEvent("Bread.Security.GetCurrentUser");
        $IsModerator = $this->manager->FireEvent("Bread.Security.GetPermission","BreadCommentSystem.DeleteOthersComment");
        if($IsModerator || $CurrentUser !== null && $this->settings->AllowDeleting)
        { 
            $this->uniqueID = $_REQUEST["uniqueid"];
            $commentIndex = intval($_REQUEST["index"]);

            $this->uniqueID = basename($this->uniqueID);
            $path = Util::ResolvePath("%user-content/comments/" . $this->uniqueID . ".json");
            if(!file_exists($path)){
                return 0;
            }
            $this->comments = Site::$settingsManager->RetriveSettings($path);
            $Comment = false;
            $realindex = 0;
            foreach($this->comments->comments as $realindex => $comment){
                if($comment->index === $commentIndex){
                    $Comment = $comment;
                    break;
                }
            }
            if($Comment === false){
                return 0;
            }
            if($Comment->user === $CurrentUser->uid || $IsModerator){
                unset($this->comments->comments[$realindex]);
                $this->comments->comments = array_values($this->comments->comments);
            }
            return 1;
        }
        return 0;
    }
    
    
    private function ConstructComment($Index,$Name,$Thumb,$Text,$Editable,$Time,$Karma,$Buttons,$EditorButtons){
        $CommentStruct = new \Bread\Structures\BreadThemeCommentStructure();
        $CommentStruct->thumbnail = $Thumb;
        $CommentStruct->header = $Name;
        $MarkdownArea = '<div id="bcs-editor"></div>';
        if($Editable){
            $CommentStruct->body =  '<span class="commentcharsleft">'.$this->settings->CharacterLimit.'</span>'.$MarkdownArea;
        }
        else{
            $CommentStruct->body =  '<index hidden=true>'.$Index.'</index>'.$MarkdownArea.'<div hidden=true class="bcs-markdown">' .  $Text . '</div><div class="bcs-html"></div>';
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
        $EditorButtons = $this->manager->FireEvent("Theme.Layout.ButtonGroup",$EditorButtons);
        $CommentStruct->body .= "<div class='standardbuttons'>".$Buttons."</div><div class='editorbuttons'>".$EditorButtons."</div>";
        $HTML = $this->manager->FireEvent("Theme.Comment",$CommentStruct);
        return $HTML;
    }
    
    function FindLastCommentTimeByUser($user){
        $lastTime = -1;
        foreach($this->comments->comments as $comment){
            if($comment->user == $user && $lastTime < $comment->time){
                $lastTime = $comment->time;
            }
        }
        return $lastTime;
    }
    
    function AddComment(){
        $text = $_REQUEST["text"];
        $this->uniqueID = $_REQUEST["uniqueid"];
        $index = $_REQUEST["index"];
        if(array_key_exists("index", $_REQUEST)){
            //Edit mode.
            return 1;
        }
        
        if(strlen($text) > $this->settings->CharacterLimit || strlen($text) == 0){
            return 0; //Too long
        }
        
        $CurrentUser = $this->manager->FireEvent("Bread.Security.GetCurrentUser");
        if($CurrentUser === null && !$this->settings->AllowAnonComments){
            return 0;
        }
        elseif($CurrentUser !== null){
            if(!$this->manager->FireEvent("Bread.Security.GetPermission","WriteComment")){
                return 0;
            }
        }
        
        $this->uniqueID = basename($this->uniqueID);
        $path = Util::ResolvePath("%user-content/comments/" . $this->uniqueID . ".json");
        if(!file_exists($path)){
            return 0;
        }
        $this->comments = Site::$settingsManager->RetriveSettings($path);
        $Comment = new \Bread\Structures\BreadComment();
        $Comment->body = $text;
        $Comment->time = time();
        $Comment->index = $this->comments->totalcommentsmade;
        if($CurrentUser === null){
            $Comment->user = -1;
            $Comment->karmaupvotees[] = -1;
        }
        else{
            $lastCommentTime = $Comment->time - $this->FindLastCommentTimeByUser($CurrentUser->uid);
            if($lastCommentTime != -1 && $lastCommentTime < $this->settings->CommentTimeout / 1000){
                return 1;
            }
            $Comment->user = $CurrentUser->uid;
            $Comment->karmaupvotees[] = $CurrentUser->uid;
        }
        $this->comments->comments[] = $Comment;
        $Avatar = $this->manager->FireEvent("Bread.GetAvatar",$CurrentUser);
        $this->MakeButtons();
        $ButtonsHTML = "";
        $EditorButtons = "";
        if($CurrentUser !== null){
            $ButtonsHTML = $this->buttons["Upvote"] . $this->buttons["Downvote"];
            if($this->settings->AllowEditing){
                $EditorButtons .=  $this->buttons["Save"];
                //$EditorButtons .= $this->buttons["Edit"];
            }
            if($this->settings->AllowDeleting){
                $EditorButtons .= $this->buttons["Delete"];
            }
       }
       else{
           $ButtonsHTML = "";
       }
        $Name = "Anonymous";
        if($CurrentUser->information->Name){
            $Name = $CurrentUser->information->Name;
        }
        $this->comments->totalcommentsmade++;
        $HTML = $this->ConstructComment($Comment->index,$Name,$Avatar,$Comment->body,false,$Comment->time,1,$ButtonsHTML,$EditorButtons);
        return $HTML;
    }
    
    function Upvote(){
        return $this->KarmaVote(true);
    }
    
    function KarmaVote($upvote){
        $CurrentUser = $this->manager->FireEvent("Bread.Security.GetCurrentUser");
        if($CurrentUser === null){
            return "Fail";
        }
        
        $this->uniqueID = basename($this->uniqueID);
        $this->uniqueID = $_REQUEST["uniqueid"];
        $commentID = intval($_REQUEST["commentid"]);
        $path = Util::ResolvePath("%user-content/comments/" . $this->uniqueID . ".json");
        if(!file_exists($path)){
            return "Fail";
        }
        $this->comments = Site::$settingsManager->RetriveSettings($path);  
        $comment = false;
        $realindex = 0;
        foreach($this->comments->comments as $realindex => $Comment){
            if($Comment->index === $commentID){
                $comment = $Comment;
                break;
            }
        }
        if($comment === false){
            return 0;
        }
        
        if($upvote){//Upvote
            if(in_array($CurrentUser->uid,$comment->karmadownvotees)){//Did the user also downvote
                $index = array_search($CurrentUser->uid, $comment->karmadownvotees);
                unset($comment->karmadownvotees[$index]);
                $comment->karmaupvotees[] = $CurrentUser->uid;
            }
            elseif(in_array($CurrentUser->uid,$comment->karmaupvotees)){
                //Take it off then.
                $index = array_search($CurrentUser->uid, $comment->karmaupvotees);
                unset($comment->karmaupvotees[$index]);
            }
            else{
                $comment->karmaupvotees[] = $CurrentUser->uid;
            }
        }
        else{//Downvote
            if(in_array($CurrentUser->uid,$comment->karmaupvotees)){//Did the user also upvote
                $index = array_search($CurrentUser->uid, $comment->karmaupvotees);
                unset($comment->karmaupvotees[$index]);
                $comment->karmadownvotees[] = $CurrentUser->uid;
            }
            elseif(in_array($CurrentUser->uid,$comment->karmadownvotees)){
                //Take it off then.
                $index = array_search($CurrentUser->uid, $comment->karmadownvotees);
                unset($comment->karmadownvotees[$index]);
            }
            else{
                $comment->karmadownvotees[] = $CurrentUser->uid;
            }
        }
        $comment->karmaupvotees = array_values($comment->karmaupvotees);
        $comment->karmadownvotees = array_values($comment->karmadownvotees);
        return count($comment->karmaupvotees) - count($comment->karmadownvotees);
    }
    
    function Downvote(){
        return $this->KarmaVote(false);
    }
    
}

class BreadCommentSystemSettings {
    public $AllowAnonComments = true;
    public $AllowEditing = true;
    public $AllowDeleting = true;
    public $EnableDownvoting = true;
    public $EnableUpvoting = true;
    public $CommentTimeout = 10000;
    public $CharacterLimit = 400;
}


class BreadCommentsStack{
    public $totalcommentsmade = 0;
    public $comments = array();
    public $locked = false;
    public $id = "";
}
