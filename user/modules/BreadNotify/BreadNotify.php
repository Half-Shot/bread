<?php
namespace Bread\Modules;
use Bread\Site as Site; //Site Functions 
use Bread\Utilitys as Util; //Utilitys
class BreadNotify extends Module
{
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}
        
        function Setup(){
            $this->notifications = Site::$settingsManager->RetriveSettings("breadnotify#notifications",false, array());
            foreach($this->notifications as $i => $note){
                if($note->timesent + $note->duration < time()){
                    unset($this->notifications[$i]);
                }
            }
            $this->notifications = array_values($this->notifications); //Reset index;
        }
        
        function WriteNotification($notification)
        {
            if(is_object($notification))
            {
                Util::CastStdObjectToStruct($notification, "Bread\Structures\BreadNotification");
                $notification->timesent = time();
                $this->notifications[] = $notification;
                Site::$settingsManager->SaveSetting($this->notifications, "breadnotify#notifications");
            }
        }
        function GetNotificationNavbarWidget($args){
            
            $Dropdown = new \Bread\Structures\BreadDropdown();
            $NotifyCount = 0;
            $reversed = array_reverse($this->notifications);
            foreach($reversed as $notification){
                $notification = Util::CastStdObjectToStruct($notification, "Bread\Structures\BreadNotification");
                $shouldShow = false;
                if(empty($this->manager->FireEvent("Bread.Security.GetCurrentUser")) && !empty($notification->rights)){
                    break;
                }
                if(!in_array($this->manager->FireEvent("Bread.Security.GetCurrentUser")->uid, $notification->ignoreUsers)){
                    if(!empty($notification->rights)){
                        foreach($notification->rights as $right){
                            if($this->manager->FireEvent("Bread.Security.GetPermission",$right)){
                                $shouldShow = true;
                                $NotifyCount++;
                                break;
                            }
                        }
                    }
                    else{
                        $shouldShow = true;
                        $NotifyCount++;
                    }
                }
                
                if($shouldShow){
                    $Dropdown->items[] = "seperator";
                    $Item = new \Bread\Structures\BreadThemeCommentStructure();
                    $Item->header = $notification->title;
                    $Item->headerurl = $notification->URL;
                    $Item->body = "<small>$notification->description</small>";
                    
                    //$Button = new \Bread\Structures\BreadFormElement();
                    //$Button->type = \Bread\Structures\BreadFormElement::TYPE_HTMLFIVEBUTTON;
                    //$Button->value = "Remove";
                    //$Button->class = $this->manager->FireEvent("Theme.GetClass","Button.ExtraSmall") . " " . $this->manager->FireEvent("Theme.GetClass","Button.Warning");
                    //$Item->body .= "<a href='$URL'>".$this->manager->FireEvent("Theme.Button",$Button) . "</a>";
                    
                    
                    if($notification->thumbnail){
                        $Item->thumbnail = $notification->thumbnail;
                        $Item->thumbnailurl = $notification->URL;
                    }
                    $Dropdown->items[] = $this->manager->FireEvent("Theme.Comment",$Item);
                }
                
            }
            
            $BadgeHTML = $this->manager->FireEvent("Theme.Icon","exclamation-sign") . " " . $NotifyCount;
            if(isset($args[0]->Class)){
                $HTML = $this->manager->FireEvent("Theme.Badge",$BadgeHTML);
            }
            else{
                $HTML = $this->manager->FireEvent("Theme.Badge",$BadgeHTML);
            }
            $Dropdown->value = $HTML;
            
            if(!empty($Dropdown->items)){
                unset($Dropdown->items[0]);
            }
            else{
                $Dropdown->items[] = "No new notifications.";
            }
            
            $Dropdown->id = "dropdown";
            return "<div class='".$args[0]->Class."'>" .  $this->manager->FireEvent("Theme.Dropdown",$Dropdown) . "</div>";
        }

}
