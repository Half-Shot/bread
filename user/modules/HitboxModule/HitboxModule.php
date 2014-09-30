<?php
namespace Bread\Modules;
use Bread\Site as Site; //Site Functions 
use Bread\Utilitys as Util; //Utilitys
class HitboxModule extends Module
{
        const BASEURL = "http://api.hitbox.tv/";
        const LIVEPATH = "media/live/";
        private $settings;
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}
        
        function Setup(){
           $this->settings = Site::$settingsManager->RetriveSettings("hitbox#settings",true,new HitboxSettings);
        }
        
        function GetHTMLObject($args,$ischat = false){
            if(array_key_exists(0, $args)){
                if(is_string($args[0])){
                    $streamName = $args[0];
                }
            }
            
            if(!isset($streamName)){
                $streamName = $this->settings->streamName;
            }
            
            if($ischat){
                $url = "http://hitbox.tv/#!/embedchat/".$streamName;
            }
            else{
                $url = "http://hitbox.tv/#!/embed/".$streamName;
            }
            return $this->manager->FireEvent("Theme.Layout.Well",array("value"=>"<iframe width='100%' height=100% src='".$url."' frameborder=0 allowfullscreen></iframe>","small"=>true));
        }
        
        function Text_GetGameTitle(){
           $StreamInfo = $this->GetStreamInformation();
           return "Currently Playing <b>" . $StreamInfo->category_name . "</b>";
        }
        
        function Text_IsLive(){
           $StreamInfo = $this->GetStreamInformation();
           if($StreamInfo->media_is_live){return "Stream is live";}else{ return "Stream is offline";};
            
        }
        
        function GetStreamInformation(){
            $url = HitboxModule::BASEURL . HitboxModule::LIVEPATH;
            $url .= $this->settings->streamName;
            $request = \Unirest::get($url);
            if($request->code == 200){
               return $request->body->livestream[0];
            }
            return false;
        }
        
        function GetPlayer($args){
            return $this->GetHTMLObject($args,false);
        }
        
        function GetChat($args){
            return $this->GetHTMLObject($args,true);
        }
}

class HitboxSettings{
    public $streamName = "";
}