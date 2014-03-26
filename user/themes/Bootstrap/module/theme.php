<?php
use Bread\Site as Site;
use Bread\Structures\BreadFormElement as BreadFormElement;
class BootstrapTheme extends Bread\Modules\Module
{
	
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}

	function RegisterEvents()
	{
            $this->manager->RegisterHook($this->name,"Theme.Load","Load"); //For each event you want to allow, specify: Name of theme, EventName and function name
            $this->manager->RegisterHook($this->name,"Theme.HeaderInfo","HeaderInfomation");
            $this->manager->RegisterHook($this->name,"Theme.DrawSystemMenu","SystemMenu");
            $this->manager->RegisterHook($this->name,"Theme.DrawNavbar","Navbar");
            $this->manager->RegisterHook($this->name,"Theme.DrawFooter","Footer");
            $this->manager->RegisterHook($this->name,"Theme.Unload","Unload");
            $this->manager->RegisterHook($this->name,"Theme.VerticalNavbar","VerticalNavbar");
            $this->manager->RegisterHook($this->name,"Theme.Post.Title","Title");
            $this->manager->RegisterHook($this->name,"Theme.Post.Breadcrumbs","Breadcrumbs");
            $this->manager->RegisterHook($this->name,"Theme.Post.Infomation","Infomation");
            $this->manager->RegisterHook($this->name,"Theme.Post.Article","Article");
            $this->manager->RegisterHook($this->name,"Theme.Infomation","ShowInfomation");
            $this->manager->RegisterHook($this->name,"Theme.Form","BuildForm");
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
        
        function ProcessLink($link,$isactive)
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
           $class = "";
           if($isactive)
            $class = "class='active'";
           $HTMLCode .= "<li " . $class ."><a href='" . $URL . "' target ='" . $link->targetWindow ."'>" . $link->text . "</a></li>";
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
                $HTMLCode = '<nav class="navbar navbar-default navbar-fixed-top" role="navigation"><div class="container-fluid"><div class="navbar-header">';
                
                if(isset($args[0]->title))
                    $HTMLCode .= '<a class="navbar-brand" href="'. $args[0]->title .'">' . $args[0]->title . '</a>';
                $HTMLCode .= "</div><ul class='nav navbar-nav'>";
                foreach ($Hooks as $links)
                {
                    foreach ($links as $link)
                    {
                        $isactive = (Site::getRequest()->requestType == $link->request);
                        $HTMLCode .= $this->ProcessLink($link,$isactive);
                    }
                }
                $HTMLCode .= "</ul></div></nav>";
		return $HTMLCode;
	}

	function Footer($args)
	{
		return var_export($args,true);
	}
        function VerticalNavbar($args)
        {
            $HTML = '<ul class="nav nav-pills nav-stacked">';
            foreach($args as $arg)
            {
                $HTML .= $this->ProcessLink($arg,false);
            }
            return $HTML . '</ul>';

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
            return var_export($args,true);
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
            return var_export($args,true);
        }
        
        function BuildForm(Bread\Structures\BreadForm $form)
        {
           return var_export($form,true);
        }
        
        function BuildInput($element)
        {
            return var_export($element,true);
        }
}
?>
