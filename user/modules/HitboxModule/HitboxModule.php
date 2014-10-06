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

        const TeamNameElement = "TeamName";
        const TeamListElement = "OnlineMembers";
        const GameNameElement = 'GameName';
        const PrefixStreamerNameElement = 'streamerBox_';
        const StreamPlayerLocation = "StreamPlayer";
        const ChatLocation = "ChatBox";

        
	function Setup(){
		$this->settings = Site::$settingsManager->RetriveSettings("hitbox#settings",true,new HitboxSettings);
                
                //Javascript
                Site::AddScript(Util::FindFile(Util::GetDirectorySubsection(__DIR__,0,1) . "js/hitbox.js"),"HitboxModule",true);
                
                Site::AddRawScriptCode("var hitboxStreamName = '".$this->settings->streamName."';");
                Site::AddRawScriptCode("var TeamNameElement = '#".HitboxModule::TeamNameElement."';");
                Site::AddRawScriptCode("var TeamListElement = '#".HitboxModule::TeamListElement."';");
                Site::AddRawScriptCode("var GameNameElement = '#".HitboxModule::GameNameElement."';");
                Site::AddRawScriptCode("var PrefixStreamerNameElement = '#".HitboxModule::PrefixStreamerNameElement."';");
                Site::AddRawScriptCode("var StreamPlayerLocation = '#".HitboxModule::StreamPlayerLocation."';");
                Site::AddRawScriptCode("var ChatLocation = '#".HitboxModule::ChatLocation."';");
                
                //CSS
                Site::AddCSS(Util::FindFile(Util::GetDirectorySubsection(__DIR__,0,1) . "css/hitbox.css"));
                
	}

	function Text_GetGameTitle(){
		return "<div id='".HitboxModule::GameNameElement."'></div>";
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
        
        function GetMembers(){
            return "<div id='".HitboxModule::TeamListElement."'></div>";
        }
        
        function GetTeamName(){
            return "<div id='".HitboxModule::TeamNameElement."'></div>";
        }
        
	function GetPlayer($args){
		return $this->manager->FireEvent("Theme.Layout.Well",array("value"=>"<div id='".HitboxModule::StreamPlayerLocation."'><img src='http://edge.sf.hitbox.tv/static/img/nocontent'/></div>","small"=>true));
	}

	function GetChat($args){
		return $this->manager->FireEvent("Theme.Layout.Well",array("value"=>"<div id='".HitboxModule::ChatLocation."'></div>","small"=>true));
	}
}

class HitboxSettings{
	public $streamName = "";
}
