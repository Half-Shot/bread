<?php
namespace Bread\Modules;
use Bread\Site as Site; //Site Functions 
use Bread\Utilitys as Util; //Utilitys
class JQuery extends Module
{
    private $cdnList;
    private $loaded;
    function __construct($manager,$name)
    {
            parent::__construct($manager,$name);
            $this->loaded = array("JQ"=>false,"JQUI"=>false,"JQM"=>false);
    }

    function Setup()
    {
       $this->cdnList = Site::$settingsManager->RetriveSettings("jquery#cdnlist",true,new JQueryCDNList());
    }

    function LoadJQuery(){
        if(!$this->loaded["JQ"])
        {
            Site::AddScript($this->cdnList->jquery_js, "JQuery", false);
            $this->loaded["JQ"] = true;
        }
        return true;
    }
    
    function LoadJQueryUI(){
        if(!$this->loaded["JQUI"])
        {
            Site::AddScript(Util::FindFile($this->cdnList->jqueryui_js), "JQueryUI", false);
            Site::AddCSS(Util::FindFile($this->cdnList->jqueryui_css));
            $this->loaded["JQUI"] = true;
        }
        return true;
    }
    
    function LoadJQueryMobile(){
        if(!$this->loaded["JQM"])
        {
            Site::AddScript(Util::FindFile($this->cdnList->jquerymobile_js), "JQueryMobile", false);
            Site::AddCSS(Util::FindFile($this->cdnList->jquerymobile_css));
            $this->loaded["JQM"] = true;
        }
        return true;
    }
}

class JQueryCDNList{
   public $jquery_js = "JQuery/js/jquery.min.js";
   
   public $jqueryui_js = "JQuery/js/jquery-ui.min.js";
   public $jqueryui_css = "JQuery/css/jquery-ui.min.css";
   
   public $jquerymobile_js = "JQuery/js/jquery.mobile.min.js";
   public $jquerymobile_css = "JQuery/css/jquery.mobile.min.css";
}
