<?php
namespace Bread\Structures;
use Bread\Site as Site;
class BreadLinkStructure
{
    public $request = "";
    public $args;
    public $text = "Link";
    public $hidden = false;
    public $url;
    public $icon;
    public $targetWindow = "_self";
    public $sublinks;
    
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