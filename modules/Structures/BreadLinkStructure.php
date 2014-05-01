<?php
namespace Bread\Structures;
use Bread\Site as Site;
/**
 * A Link structure used for urls, navbar links and more.
 */
class BreadLinkStructure
{
    /**
     * The bread request to run. Leave blank for none.
     * @var string 
     */
    public $request = ""; 
    /**
     * An extra arguments key => value
     * @var array 
     */
    public $args;
    /**
     * Text for visual links.
     * @var string 
     */
    public $text = "Link";
    /**
     * Is the link hidden.
     * @var bool 
     */
    public $hidden = false;
    /**
     * If not a bread request, the url to link to.
     * @var string 
     */
    public $url;
    /**
     * The path of the icon to use.
     * @var string 
     */
    public $icon;
    /**
     * Target window of the link
     * @var string 
     */
    public $targetWindow = "_self";
    /**
     * An array of sublinks in a dropdown menu or something.
     * @var array 
     */
    public $sublinks;
    
    function __construct()
    {
        $this->args = new \stdClass();
    }
    
    /**
     * Create a URL from the structure.
     * @return type
     */
    public function createURL()
    {
        if(!isset($this->url))
        {
             if(isset($this->args)){
                 $params = get_object_vars($this->args); //Fixes JSON not supporting arrays with key=>values.
             }
             else{
                 $params = array();
             }

             $URL = Site::CondenseURLParams(false, array_merge(array("request" => $this->request),$params));
        }
        else
        {
             $URL = $this->url;
        }
        return $URL;
    }
}