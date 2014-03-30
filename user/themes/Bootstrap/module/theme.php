<?php
use Bread\Site as Site;
use Bread\Structures\BreadFormElement as BreadFormElement;
use Bread\Themes\BreadXML as BreadXML;
class BootstrapTheme extends Bread\Modules\Module
{
	
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}

	function RegisterEvents()
	{
            //Base
            $this->manager->RegisterHook($this->name,"Theme.Load","Load"); //For each event you want to allow, specify: Name of theme, EventName and function name
            $this->manager->RegisterHook($this->name,"Theme.HeaderInfo","HeaderInfomation");
            $this->manager->RegisterHook($this->name,"Theme.Unload","Unload");      
            
            $this->manager->RegisterHook($this->name,"Theme.DrawSystemMenu","SystemMenu");
            $this->manager->RegisterHook($this->name,"Theme.DrawFooter","Footer");
            //Navigation
            $this->manager->RegisterHook($this->name,"Theme.VerticalNavbar","VerticalNavbar");
            $this->manager->RegisterHook($this->name,"Theme.DrawNavbar","Navbar");
            //Posts
            $this->manager->RegisterHook($this->name,"Theme.Post.Title","Title");
            $this->manager->RegisterHook($this->name,"Theme.Post.Breadcrumbs","Breadcrumbs");
            $this->manager->RegisterHook($this->name,"Theme.Post.Infomation","Infomation");
            $this->manager->RegisterHook($this->name,"Theme.Infomation","ShowInfomation");
            //Forms
            $this->manager->RegisterHook($this->name,"Theme.Form","BuildForm");
            //Layouts
            $this->manager->RegisterHook($this->name,"Theme.Layout.Article","Article");
            $this->manager->RegisterHook($this->name,"Theme.Layout.Block","LayoutBlock");
	}
    
	function Load()
	{
		
	}

	function HeaderInfomation()
	{
            
	}

	function SystemMenu($args)
	{
	}
        
        function ShowInfomation($args)
        {
        }
        
        function ProcessLink($link,$isactive = false,$tag = "li",$linkclass = "",$istitle = false)
        {
           $HTMLCode = "";
           if($link->hidden)
            return "";
           if(!isset($link->url))
           {
                if(isset($link->args)){
                    $params = get_object_vars($link->args); //Fixes JSON not supporting arrays with key=>values.
                }
                else{
                    $params = array();
                }
                $URL = Site::CondenseURLParams(false, array_merge(array("request" => $link->request),$params));
           }
           else
           {
                $URL = $link->url;
           }
           if($istitle)
               return '<a class="navbar-brand" href="'. $URL .'">' . $link->text . '</a>';
           $class = "";
           if($isactive)
            $class = "class='active'";
           if($linkclass != ""){
               $linkclass = "class=" . $linkclass;
               if($isactive)
                   $linkclass .= " active";
           }
           $HTMLCode .= "<" . $tag ." " . $class ."><a ".$linkclass." href='" . $URL . "' target ='" . $link->targetWindow ."'>" . $link->text . "</a></" . $tag .">";
           return $HTMLCode;
        }
        
	function Navbar($args)
	{
                //Hooks should be filled with arrays of BreadLinkStructure.
                $Hooks = $this->manager->FireEvent("Bread.GetNavbarIndex",$args);
                if(!$Hooks)
                {
                   $Hooks = array();
                }
                $MainBlock = '<nav class="navbar navbar-default navbar-fixed-top" role="navigation"><div class="container-fluid"><div class="navbar-header">';
                $LinkBlock = "</div><ul class='nav navbar-nav'>";
                foreach ($Hooks as $links)
                {
                    foreach ($links as $link)
                    {
                        $isactive = (Site::getRequest()->requestType == $link->request);
                        if(isset($link->title)){
                            if($link->title){
                                $MainBlock .= $this->ProcessLink($link,$isactive,"","",true);
                                continue;
                                }
                        }
                        $LinkBlock .= $this->ProcessLink($link,$isactive);
                    }
                }
                $HTMLCode = $MainBlock . $LinkBlock . "</ul></div></nav>";
		return $HTMLCode;
	}

	function Footer($args)
	{
		return var_export($args,true);
	}
        function VerticalNavbar($args)
        {
            unset($args["_inner"]);//Useful practise for theme elements that do not do layout processing.
            $breadXML = new BreadXML(Site::FindFile("Bootstrap/theme.xsl"));

            $HTML = '<div class="list-group">';
            foreach($args as $arg)
            {
                $HTML .= $this->ProcessLink($arg,false,"div","list-group-item");
            }
            return $breadXML->GetHTMLOfElement("VerticalNavbar",$args);
            return $HTML . '</div>';

        }

        function Title($args)
        {
            return '<div class="page-header"><h1>'. $args[0] .'</h1><small>' . $args[1] . '</small></div>';
        }
        
        function SubTitle($args)
        {
            return '<div class="page-header"><h3>'. $args .'</h3></div>';
        }
        
	function Unload()
	{
            
	}
        
        function Article($args)
        {
            if($args["_inner"] === false)
                return false;
        }
        
        function Breadcrumbs($args)
        {
            $HTML = '<ol class="breadcrumb">';
            foreach ($args as $breadcrumb)
            {
                if($breadcrumb == $args[count($args) - 1])
                    break;
                $HTML .= '<li><a>' . $breadcrumb . '</a></li>';
            }
            $HTML .= '<li><a class="active">' . $args[count($args) - 1] . '</a></li></ol>';
            return $HTML;
        }
        
        function Infomation($args)
        {
            $HTML = "";
            foreach ($args as $label => $data)
            {
                $HTML .=  $label . ': <span class="label label-info">' .  $data . '</span></h3><br>';
            }
            return $HTML;
        }
        
        function BuildForm(Bread\Structures\BreadForm $form)
        {
           return var_export($form,true);
        }
        
        function BuildInput($element)
        {
            return var_export($element,true);
        }
        
        function LayoutBlock($args)
        {
            if($args["_inner"] === false)
                return false;
            $HTML = "";
            foreach($args["_inner"] as $element){
                $HTML .= $element["guts"];
            }
            return $HTML;
        }
}
?>
