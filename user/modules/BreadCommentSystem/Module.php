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
    /**
     * Maximum threshold for a string to be determined the same.
     */
    const SIMILAR_THRESHOLD = 1; 
    const SIMILAR_SCALEUP = 50;
    const MINGRACEPERIOD = 5;
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
            $Btn_Comment_Upvote->class = $this->manager->FireEvent("Theme.GetClass","Button.Success"). " upvotecomment-button " . $this->manager->FireEvent("Theme.GetClass","Button.Small");
            $this->buttons["Upvote"] = $this->manager->FireEvent("Theme.Button",$Btn_Comment_Upvote);
        }
        else{
            $this->buttons["Upvote"] = "";
        }
        
        if($this->settings->EnableDownvoting){
            $Btn_Comment_Downvote = new \Bread\Structures\BreadFormElement;
            $Btn_Comment_Downvote->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
            $Btn_Comment_Downvote->value = $this->manager->FireEvent("Theme.Icon","thumbs-down");
            $Btn_Comment_Downvote->class = $this->manager->FireEvent("Theme.GetClass","Button.Danger"). " downvotecomment-button " . $this->manager->FireEvent("Theme.GetClass","Button.Small");
            $this->buttons["Downvote"] = $this->manager->FireEvent("Theme.Button",$Btn_Comment_Downvote);
        }
        else{
            $this->buttons["Downvote"] = "";
        }
    }
    
    function Setup()
    {
        $this->settings = Site::$settingsManager->RetriveSettings("breadcommentsystem#settings.json",false,new BreadCommentSystemSettings());
        $this->settings = Util::CastStdObjectToStruct($this->settings,"Bread\Modules\BreadCommentSystemSettings");
    }
    
    function PageSetup(){
        if(!$this->completedPageSetup){
            $this->uniqueID = $this->manager->FireEvent("Bread.PageUniqueID");
            if($this->uniqueID === false){
                $this->manager->UnregisterModule("BreadCommentSystem");
                return false;
            }
            else{
                $path = Util::ResolvePath("%user-content/comments/" . $this->uniqueID . ".json");
                $this->comments = Site::$settingsManager->RetriveSettings($path,false,new BreadCommentsStack());
            }
            $this->completedPageSetup = true;
            
            Site::AddScript(Site::ResolvePath("%user-modules/BreadCommentSystem/js/Markdown.Converter.js"),"MarkdownConverter",true);
            Site::AddScript(Site::ResolvePath("%user-modules/BreadCommentSystem/js/Markdown.Extra.js"),"MarkdownExtra",true);
            Site::AddScript(Site::ResolvePath("%user-modules/BreadCommentSystem/js/epiceditor.min.js"),"EpicEditor", true);
            Site::AddScript(Site::ResolvePath("%user-modules/BreadCommentSystem/js/commentsystem.js"),"BreadCommentSystemScript", true);
            
            Site::AddRawScriptCode("var epiceditor_basepath ='" . Site::ResolvePath("%user-modules/BreadCommentSystem/css/") . "';");//Dirty Hack
            Site::AddRawScriptCode("ApplyJavascriptToAllComments();",true);
            Site::AddRawScriptCode('window.pageuniqueid="' . $this->uniqueID . '"');
            
            $this->MakeButtons();
        }
        return true;
    }
    
    function ConstructEditableComment(){
        //Editable comment.
        $CurrentUser = $this->manager->FireEvent("Bread.Security.GetCurrentUser");
        if($this->manager->FireEvent("Bread.Security.GetPermission","Bread.WriteComment")){
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
        if(!$this->PageSetup()){
            return "";
        }
        $HTML = "<hr>";
        $CurrentUser = $this->manager->FireEvent("Bread.Security.GetCurrentUser"); 

        if($this->comments->locked){
            $HTML .= $this->manager->FireEvent("Theme.Alert",array("class"=>"alert-warning","body"=>"Comment section is locked."));
        }
        else{
            $HTML .= $this->ConstructEditableComment();
        }
        $HTML .= "<div id='breadcomment-section'>";
        $Moderator = $this->manager->FireEvent("Bread.Security.GetPermission","BreadCommentSystem.Moderator");
        foreach($this->comments->comments as $Str_CommentIndex => $comment){
            $CommentIndex = intval($Str_CommentIndex);
            $ExtraClasses = "";
            $commentObj = Util::CastStdObjectToStruct($comment, "Bread\Structures\BreadComment");
            $User = $this->manager->FireEvent("Bread.Security.GetUser",$commentObj->user);
            $Avatar = $this->manager->FireEvent("Bread.GetAvatar",$User);
            $EditorButtons = "";
            $ButtonsHTML = "";
            if($CurrentUser !== null && !$this->comments->locked || $Moderator){
                if($this->manager->FireEvent("Bread.Security.GetPermission","BreadCommentSystem.CanVote")){
                    $ButtonsHTML = $this->buttons["Upvote"] . $this->buttons["Downvote"];
                }
                if($comment->user === $CurrentUser->uid || $Moderator){
                    if($this->settings->AllowEditing){
                        //$EditorButtons .= $this->buttons["Edit"] . $this->buttons["Save"];
                    }
                    if($this->settings->AllowDeleting || $Moderator){
                        $EditorButtons .= $this->buttons["Delete"];
                    }
                }
            }
            $Name = "Anonymous";
            if($User !== false){
                $Name = $User->information->Name;
                if(!empty($CurrentUser)){
                    if($User->uid === $CurrentUser->uid){
                        $ExtraClasses .= "currentusercomment";
                    }
                }
            }
            
            $HTML .= $this->ConstructComment($CommentIndex,$Name, $Avatar, $commentObj->body, false,$commentObj->time, count($commentObj->karmaupvotees) - count($commentObj->karmadownvotees),$ButtonsHTML,$EditorButtons,$ExtraClasses);
        }
        $HTML .= "</div>";
        return $HTML;
    }
    
    
    private function ConstructComment($Index,$Name,$Thumb,$Text,$Editable,$Time,$Karma,$Buttons,$EditorButtons,$ExtraClasses = ""){
        $CommentStruct = new \Bread\Structures\BreadThemeCommentStructure();
        $CommentStruct->thumbnail = $Thumb;
        $CommentStruct->header = $Name;
        if($ExtraClasses != ""){
            $CommentStruct->class .= " " . $ExtraClasses;
        }
        if($Editable){
            $CommentStruct->body =  '<span class="commentcharsleft">'.$this->settings->CharacterLimit.'</span><div id="bcs-editor"></div>';
        }
        else{
            $CommentStruct->body =  '<index hidden=true>'.$Index.'</index><div class="bcs-markdown">' .  $Text . '</div><div class="bcs-html"></div>';
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
    
    function AddComment(){
        $text = $_REQUEST["text"];
        $this->uniqueID = $_REQUEST["uniqueid"];
        
        //1. Check Length
        if(strlen($text) > $this->settings->CharacterLimit || strlen($text) == 0){
            return 0; //Too long
        }
        
        //2. Check User and rights.
        $CurrentUser = $this->manager->FireEvent("Bread.Security.GetCurrentUser");
        if($CurrentUser === null){
            if($this->settings->AllowAnonComments){
                //Dummy object
                $CurrentUser = new \stdClass();
                $CurrentUser->uid = -1;
                $CurrentUser->information = new \stdClass();
                $CurrentUser->information->Name = "Anonymous";
            }
            else{
                return 0;
            }
        }
        elseif($CurrentUser !== null){
            if(!$this->manager->FireEvent("Bread.Security.GetPermission","Bread.WriteComment")){
                return 0;
            }
        }
        
        //3. Check comments exists.
        $path = Util::ResolvePath("%user-content/comments/" . $this->uniqueID . ".json");
        if(!file_exists($path)){
            return 0;
        }
        
        $this->comments = Site::$settingsManager->RetriveSettings($path);
        
        //4. Check comments not locked.
        if($this->comments->locked){
            return 0;
        }
        
        //5. Check not a recent comment or a similar comment.
        $similarity = 0;
        $textlength = strlen($text);
        $maxsimularity = 100 - ceil(self::SIMILAR_THRESHOLD * ($textlength / self::SIMILAR_SCALEUP));
        if($maxsimularity > 100 - self::SIMILAR_THRESHOLD){
            $maxsimularity = 100 - self::SIMILAR_THRESHOLD;
        }
        
        foreach($this->comments->comments as $comment){
            if($comment->user == $CurrentUser->uid){
                //Check time
                if(time() - $comment->time < self::MINGRACEPERIOD)
                {
                    return 2;
                }
                //Check similarity
                similar_text($comment->body, $text,$similarity);
                if(round($similarity) > $maxsimularity){
                    //It's too similar.
                    return 3;
                }
            }
        }
        
        $this->uniqueID = basename($this->uniqueID);
        
        $Comment = new \Bread\Structures\BreadComment();
        $Comment->body = $text;
        $Comment->time = time();
        $Comment->user = $CurrentUser->uid;
        $Comment->karmaupvotees[] = $CurrentUser->uid;
        
        $CArray = array_keys((array)$this->comments->comments);
        $Index = $CArray[count($CArray) - 1] + 1;
         
        
        $this->comments->comments->$Index = $Comment;
        
        $Avatar = $this->manager->FireEvent("Bread.GetAvatar",$CurrentUser);
        $this->MakeButtons();
        $ButtonsHTML = "";
        $EditorButtons = "";
        if($CurrentUser->uid !== -1){
            $ButtonsHTML = $this->buttons["Upvote"] . $this->buttons["Downvote"];
            if($this->settings->AllowEditing){
                //$EditorButtons .= $this->buttons["Edit"] . $this->buttons["Save"];
            }
            if($this->settings->AllowDeleting){
                $EditorButtons .= $this->buttons["Delete"];
            }
       }
       else{
           $ButtonsHTML = "";
       }
        //Fallback
        if(isset($CurrentUser->information->Name)){
             $Name = $CurrentUser->information->Name;
        }
        else
        {
            $Name = $CurrentUser->username;
        }
        $HTML = $this->ConstructComment($Index,$Name,$Avatar,$text,false,$Comment->time,1,$ButtonsHTML,$EditorButtons);
        return $HTML;
    }
    
    function DeleteComment(){
        $Moderator = $this->manager->FireEvent("Bread.Security.GetPermission","BreadCommentSystem.Moderator");
        if(!$this->settings->AllowDeleting || !$Moderator){
            return 0;
        }
        $this->uniqueID = $_REQUEST["uniqueid"];
        $this->uniqueID = basename($this->uniqueID);
        $commentID = intval($_REQUEST["commentid"]);
        $path = Util::ResolvePath("%user-content/comments/" . $this->uniqueID . ".json");
        if(!file_exists($path)){
            return 0;
        }
        $this->comments = Site::$settingsManager->RetriveSettings($path);  
        if($this->comments->locked){
            return 0;
        }
        if(!isset($this->comments->comments->$commentID)){
            //Probably already deleted! Force a reload
            return 2;
        }
        $comment = $this->comments->comments->$commentID;
        $CurrentUser = $this->manager->FireEvent("Bread.Security.GetCurrentUser");
        if($comment->user === $CurrentUser->uid && $comment->canedit || $Moderator){
            unset($this->comments->comments->$commentID);
            return 1;
        }
        return 0;
    }
    
    function Upvote(){
        return $this->KarmaVote(true);
    }
    
    function KarmaVote($upvote){
        
        if(!$this->manager->FireEvent("Bread.Security.GetPermission","BreadCommentSystem.CanVote")){
            return "Fail";
        }
        $CurrentUser = $this->manager->FireEvent("Bread.Security.GetCurrentUser");
        if($CurrentUser === null){
            return "Fail";
        }
        
        $this->uniqueID = $_REQUEST["uniqueid"];
        $this->uniqueID = basename($this->uniqueID);
        $commentID = intval($_REQUEST["commentid"]);
        $path = Util::ResolvePath("%user-content/comments/" . $this->uniqueID . ".json");
        if(!file_exists($path)){
            return "Fail";
        }
        $this->comments = Site::$settingsManager->RetriveSettings($path);
        if($this->comments->locked){
            return 0;
        }
        $comment = $this->comments->comments->$commentID;
        
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
    public $CharacterLimit = 400;
}


class BreadCommentsStack{
    public $comments;
    public $locked = false;
    public $id = "";
    
    public function BreadCommentsStack()
    {
        $this->comments = new \stdClass();
    }
}
