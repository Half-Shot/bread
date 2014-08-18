<?php
namespace Bread\Modules;
use Bread\Site as Site;
class GravatarModule extends Module
{
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}
    
	function GetAvatar($args)
	{
            if(!is_object($args)){
                $user = $this->manager->FireEvent("Bread.Security.GetUser",$args);
            }
            else if(is_array($args)){
                if(is_object($args["user"]))
                {
                    $user = $args["user"];
                }
                elseif(is_int($args["user"]))
                {
                    $user = $this->manager->FireEvent("Bread.Security.GetUser",$args["user"]);
                }
                if(array_key_exists("size", $args)){
                    $size = $args["size"];
                }
            }
            else if(is_object($args)){
                $user = $args;
            }
            if(isset($user->information->EMail)){
                $Email = $user->information->EMail;
            }
            else{
                return false;
            }
            $url = 'http://www.gravatar.com/avatar/';
            $url .= md5( strtolower( trim( $Email ) ) );
            if(isset($size)){
                $url .= "?s=".$size . "";
            }
            return $url;
        }

}
