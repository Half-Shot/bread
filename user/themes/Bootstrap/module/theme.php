<?php
use Bread\Site as Site;
use Bread\Structures\BreadFormElement as BreadFormElement;
use Bread\Themes\BreadXML as BreadXML;
class BootstrapTheme extends Bread\Modules\Module
{
	public $breadXML;
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
            $this->breadXML = new BreadXML(Site::FindFile("Bootstrap/theme.xsl"));
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
        
	function Navbar($args)
	{
                //Hooks should be filled with arrays of BreadLinkStructure.
                $Vars = array();
                $Hooks = $this->manager->FireEvent("Bread.GetNavbarIndex",$args);
                if(!$Hooks)
                {
                   $Hooks = array();
                }
                
                foreach ($Hooks as $links)
                {
                    foreach ($links as $srclink)
                    {
                        $link = clone $srclink;
                        if($link->hidden)
                            continue;
                        $link->active = (Site::getRequest()->requestType == $link->request);
                        if(!isset($link->url))
                        {
                             if(isset($link->args)){
                                 $params = get_object_vars($link->args); //Fixes JSON not supporting arrays with key=>values.
                             }
                             else{
                                 $params = array();
                             }
                             $link->url = Site::CondenseURLParams(false, array_merge(array("request" => $link->request),$params));
                        }
                        $Vars[] = $link;
                    }
                }
                unset($args["_inner"]);//Useful practise for theme elements that do not do layout processing.
                return $this->breadXML->GetHTMLOfElement("Navbar",$Vars);
	}

	function Footer($args)
	{
		return var_export($args,true);
	}
        function VerticalNavbar($args)
        {
            unset($args["_inner"]);//Useful practise for theme elements that do not do layout processing.
            return $this->breadXML->GetHTMLOfElement("VerticalNavbar",$args);
        }

        function Title($args)
        {
            unset($args["_inner"]);//Useful practise for theme elements that do not do layout processing.
            return $this->breadXML->GetHTMLOfElement("Title",array("title"=>$args[0],"subtitle"=>$args[1]));
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
            unset($args["_inner"]);//Useful practise for theme elements that do not do layout processing.
            return $this->breadXML->GetHTMLOfElement("Title",$args);
        }
        
        function Infomation($args)
        {
            $Vars = array();
            foreach ($args as $label => $data)
            {
                $Vars[] = array("label" => $label,"data" => $data);
            }
            return $this->breadXML->GetHTMLOfElement("LabelValuePairs",$Vars);
        }
        
        function BuildForm(Bread\Structures\BreadForm $form)
        {
            return $this->breadXML->GetHTMLOfElement("Form",$form);
        }
        
        function BuildInput($element)
        {
            return $this->breadXML->GetHTMLOfElement("FormElement",$element);
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
