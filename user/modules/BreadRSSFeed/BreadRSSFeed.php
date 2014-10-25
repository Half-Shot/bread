<?php
namespace Bread\Modules;
use Bread\Site as Site; //Site Functions 
use Bread\Utilitys as Util; //Utilitys
class BreadRSSFeed extends Module
{
    /* @var $settings BreadRSSFeedSettings */
    private $settings;
    function __construct($manager,$name)
    {
            parent::__construct($manager,$name);
    }

    function Setup(){
        $this->settings = Site::$settingsManager->RetriveSettings("breadrss#settings", true,new BreadRSSFeedSettings);
        $this->settings = Util::CastStdObjectToStruct($this->settings , "Bread\Modules\BreadRSSFeedSettings");
    }

    function ShowFeed()
    {
        $feed = new \DOMDocument("1.0","UTF-8");
        $feed->preserveWhiteSpace = false;
        $feed->formatOutput = true;
        $SearchRequest = new \Bread\Structures\BreadSearchRequest();
        $SearchRequest->IgnoreCase = true;

        //RSS
        $RSSNode = $feed->createElement("rss");
        $RSSNode->setAttribute("version", "2.0");
        $feed->appendChild($RSSNode);

        $channel = $feed->createElement("channel");
        $RSSNode->appendChild($channel);
        $URL = "http://" . $_SERVER['HTTP_HOST'] . Site::getBaseURL();
        $channel->appendChild($feed->createElement("title",Site::Configuration()->strings->sitename)); //Title
        $channel->appendChild($feed->createElement("link",$URL . "index.php")); //Link
        if(property_exists(Site::Configuration()->strings,"description"))
        {
            $channel->appendChild($feed->createElement("description",Site::Configuration()->strings->description)); //Description
        }
        $channel->appendChild($feed->createElement("language","en-gb")); //Description
        $channel->appendChild($feed->createElement("copyright","Copyleft")); //Description
        $channel->appendChild($feed->createElement("pubdate",date("D, d M Y H:i:s T",  time()))); //Title
        
        $posts = $this->manager->FireEvent("Bread.RunSearch",$SearchRequest);
        foreach($posts as $post){
            $feedItem = $feed->createElement("item");
            $channel->appendChild($feedItem);
            $feedItem->appendChild($feed->createElement("title",$post->Name)); //Title
            $feedItem->appendChild($feed->createElement("pubdate",date("D, d M Y H:i:s T",$post->PublishTime))); //Title
            if($this->settings->filtermediatags){
                $Content = strip_tags($post->Content);
            }
            else{
                $Content = $post->Content;
            }
            $feedItem->appendChild($feed->createElement("description",$Content)); //Title

            $link = $feed->createElement("link","");
            $link->appendChild($feed->createTextNode("http://" .$_SERVER['HTTP_HOST'] . $post->Url));
            $feedItem->appendChild($link); //Title
            foreach($post->Categories as $category){
                $feedItem->appendChild($feed->createElement("category",$category)); //Title
            }
        }
        //Force the site to use non-html.
        Site::AddToBodyCode($feed->saveXML());
        Site::SetContentType("text/xml");
    }

}

class BreadRSSFeedSettings{
    public $filtermediatags = false;
}

class BreadRSSStream{
    
}