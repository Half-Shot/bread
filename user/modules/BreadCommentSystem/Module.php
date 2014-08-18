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
    function __construct($manager,$name)
    {
        parent::__construct($manager,$name);
    }
    
    function Setup()
    {
        //Get hash.
        $this->uniqueID = $this->manager->FireEvent("Bread.PageUniqueID");
        if($this->uniqueID === false){
            $this->manager->UnregisterModule("BreadCommentSystem");
        }
        else{
            $path = Util::ResolvePath("%user-content/comments/" . $this->uniqueID . ".json");
            Site::$settingsManager->CreateSettingsFiles($path,new BreadCommentsStack());
            $this->comments = Site::$settingsManager->RetriveSettings($path);
        }
        $rootSettings = Site::$settingsManager->FindModuleDir("breadcommentsystem");
        Site::$settingsManager->CreateSettingsFiles($rootSettings . "settings.json",new BreadCommentSystemSettings());
        $this->settings = Site::$settingsManager->RetriveSettings($rootSettings . "settings.json");
    }
    
    function ShowComments(){
        $HTML = "<hr>";
        if($this->comments->locked){
            $HTML .= $this->manager->FireEvent("Theme.Alert",array("class"=>"alert-warning","body"=>"Comment section is locked."));
        }
        else{
            //Editable comment.
            $CurrentUser = $this->manager->FireEvent("Bread.Security.GetCurrentUser");
            $Avatar = $this->manager->FireEvent("Bread.GetAvatar",$CurrentUser);
            $Name = Util::EmptySub($CurrentUser->information->Name, "Unknown");
            $Avatar = Util::EmptySub($Avatar, "");
            $HTML .= '<div id="newcomment">' . $this->ConstructComment($Name, $Avatar, "Click here to edit...", true) . '</div>';
        }
        foreach($this->comments->comments as $comment){
            $commentObj = Util::CastStdObjectToStruct($comment, "Bread\Structures\BreadComment");
            $User = $this->manager->FireEvent("Bread.Security.GetUser",$commentObj->user);
            $Avatar = $this->manager->FireEvent("Bread.GetAvatar",$User);
            $HTML .= $this->ConstructComment($User->information->Name, $Avatar, $commentObj->body, true);
        }
        return $HTML;
    }
    
    private function ConstructComment($Name,$Thumb,$Text,$Editable){
        $CommentStruct = new \Bread\Structures\BreadThemeCommentStructure();
        $CommentStruct->thumbnail = $Thumb;
        $CommentStruct->header = $Name;
        $CommentStruct->body = '<div class="commentbody">' .  $Text . "</div>";
        $HTML = $this->manager->FireEvent("Theme.Comment",$CommentStruct);
        return $HTML;
    }
    
}

class BreadCommentSystemSettings {
    
}


class BreadCommentsStack{
    public $comments = array();
    public $locked = false;
    public $id = "";
}
