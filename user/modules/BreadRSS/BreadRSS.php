<?php
namespace Bread\Modules;
use Bread\Site as Site; //Site Functions 
use Bread\Utilitys as Util; //Utilitys
class BreadRSS extends Module
{
    /* @var $feeds array of Bread\Modules\BreadRSSStream */
    private $feeds;
    function __construct($manager,$name)
    {
            parent::__construct($manager,$name);
    }

    function Setup(){
        $feeds = Site::$settingsManager->RetriveSettings("breadrss#settings", false,array(new BreadRSSStream("master")));
        foreach($feeds as $i => $feed){
           $feeds[$i] = Util::CastStdObjectToStruct($feed , "Bread\Modules\BreadRSSStream");
        }
        $this->feeds = Util::ArraySetKeyByProperty($feeds,"name");
    }

    function ShowFeed($args)
    {
        if(isset($_GET['page'])){
            $postPage = intval($_GET['page']);
        }
        else{
            $postPage = 0;
        }
        
        if(array_key_exists("streamName", $args)){
            $feedName = $args['streamName'];
        }
        else{
            $feedName = array_values($this->feeds)[0]->name;
        }
        
        if(array_key_exists($feedName, $this->feeds)){
            $XML = $this->BuildFeed($this->feeds[$feedName],$postPage);
            Site::AddToBodyCode($XML);
            //Force the site to use non-html.
            Site::SetContentType("text/xml");
        }
        else
        {
            Site::SetContentType("text/plain");
            Site::AddToBodyCode("No RSS Feed could be found matching the name '$feedName'");
            Site::$Logger->writeError("No RSS Feed could be found matching the name '$feedName'", \Bread\Logger::SEVERITY_HIGH, "BreadRSS", false);
        }

    }
    
    private function BuildFeed(BreadRSSStream $feedObj,$postPage){
        $feed = new \DOMDocument("1.0","UTF-8");$feed->preserveWhiteSpace = false;$feed->formatOutput = true;

        //RSS
        $RSSNode = $feed->createElement("rss");
        $RSSNode->setAttribute("version", "2.0");
        $feed->appendChild($RSSNode);

        $channel = $feed->createElement("channel");
        $RSSNode->appendChild($channel);
        $channel->appendChild($feed->createElement("title",$feedObj->title)); //Title
        $channel->appendChild($feed->createElement("link",$feedObj->link)); //Link
        $channel->appendChild($feed->createElement("description",$feedObj->description)); //Description
        $channel->appendChild($feed->createElement("language",$feedObj->language)); //Description
        $channel->appendChild($feed->createElement("copyright",$feedObj->copyright)); //Description
        $feedObj->terms = Util::CastStdObjectToStruct($feedObj->terms , "Bread\Structures\BreadSearchRequest");
        $posts = $this->manager->FireEvent("Bread.RunSearch",$feedObj->terms);
        //Paginate
        $posts = array_slice($posts, $postPage * $feedObj->maxposts, $feedObj->maxposts);
        foreach($posts as $post){
            $feedItem = $feed->createElement("item");
            $channel->appendChild($feedItem);
            $feedItem->appendChild($feed->createElement("title",$post->Name)); //Title
            $feedItem->appendChild($feed->createElement("pubdate",date("D, d M Y H:i:s T",$post->PublishTime))); //Title
            if($feedObj->filtermediatags){
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
        $channel->appendChild($feed->createElement("pubdate",date("D, d M Y H:i:s T", $posts[0]->PublishTime))); //Title
        return $feed->saveXML();
    }
    
    function RSSIcon($args){
        if(array_key_exists(0, $args)){
            $args = $args[0];
        }
        
        if(isset($args->streamName)){
            $streamName = $args->streamName;
        }
        else{
            $streamName = array_values($this->feeds)[0]->name;
        }
        
        if(array_key_exists($streamName, $this->feeds)){
            $link = $this->feeds[$streamName]->rssLink;
        }
        else{
            $link = "#";
        }
        
        $RSSIcon = file_get_contents(Site::ResolvePath("%user-modules/BreadRSS/Feed-icon.svg"));
        return "<a href='$link'>$RSSIcon</a>";
    }

}

class BreadRSSStream
{
    public function __construct($Name = "") {
        $this->name = $Name;
        $URL = "http://" . $_SERVER['HTTP_HOST'] . Site::getBaseURL();
        $this->link = $URL . "index.php";
        $this->title = Site::Configuration()->strings->sitename;
        $this->description = Site::Configuration()->strings->description;
        $this->terms = new \Bread\Structures\BreadSearchRequest();
        $this->rssLink = Site::getBaseURL();
        $this->rssLink .= "?request=rss&streamName=$this->name"; 
    }
    
    public $name = "";
    public $title = "";
    public $description = "";
    public $filtermediatags = true;
    public $maxposts = 25;
    public $copyright = false;
    public $language = "en-us";
    public $link = "";
    public $rssLink = "";
    public $terms;
}