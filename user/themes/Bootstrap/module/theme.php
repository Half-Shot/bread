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
            $this->manager->RegisterHook($this->name,"Theme.Post.Infomation","Infomation");
            $this->manager->RegisterHook($this->name,"Theme.Infomation","ShowInfomation");
            $this->manager->RegisterHook($this->name,"Theme.Panel","Panel");            //Forms
            $this->manager->RegisterHook($this->name,"Theme.Form","BuildForm");
            $this->manager->RegisterHook($this->name,"Theme.InputElement","BuildInput");
            //Layouts
            $this->manager->RegisterHook($this->name,"Theme.Layout.Article","Article");
            $this->manager->RegisterHook($this->name,"Theme.Layout.Block","LayoutBlock");
            $this->manager->RegisterHook($this->name,"Theme.Layout.Well","LayoutWell");
            $this->manager->RegisterHook($this->name,"Theme.Layout.ButtonGroup","ButtonGroup");
            $this->manager->RegisterHook($this->name,"Theme.Layout.ButtonToolbar","ButtonToolbar");
            $this->manager->RegisterHook($this->name,"Theme.DrawError","ShowErrorScreen");
            //Misc
            $this->manager->RegisterHook($this->name,"Theme.Icon","DrawIcon");
            $this->manager->RegisterHook($this->name,"Theme.Modal","SpawnModal");
            $this->manager->RegisterHook($this->name,"Theme.Button","Button");
            $this->manager->RegisterHook($this->name,"Theme.Badge","Badge");
            $this->manager->RegisterHook($this->name,"Theme.Label","Label");
            $this->manager->RegisterHook($this->name,"Theme.Breadcrumbs","Breadcrumbs");
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
                        if(isset($link->hidden))
                            if($link->hidden)
                                continue;
                        $link->active = (Site::getRequest()->requestType == $link->request);
                        if(isset($link->args) && $link->active )
                            $link->active = (Site::getRequest()->arguments == $link->args);
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
            return $this->breadXML->GetHTMLOfElement("InputElement",$element);
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
        
        function ShowErrorScreen($message){
            $HTML = $this->breadXML->GetHTMLOfElement("ErrorScreen",$message);
            return $HTML;
        }
        
        function Panel($args)
        {
            return $this->breadXML->GetHTMLOfElement("Panel",$args);
        }
        
        function ButtonGroup($args)
        {
            return '<div class="btn-group">' . $args . '</div>';
        }
        
        function ButtonToolbar($args)
        {
            return '<div class="btn-toolbar" role="toolbar">' . $args . '</div>';
        }
        
        function DrawIcon($args)
        {
            switch($args)
            {
                case "bold":
                    return '<span class="glyphicon glyphicon-bold"></span>';
                    break;
                case "italic":
                    return '<span class="glyphicon glyphicon-italic"></span>';
                    break;
                case "list":
                    return '<span class="glyphicon glyphicon-list"></span>';
                    break;
                case "audio":
                    return '<span class="glyphicon glyphicon-volume-up"></span>';
                    break;
                case "video":
                    return '<span class="glyphicon glyphicon-film"></span>';
                    break;
                default :
                    return '<small>' . $args . '</small>';
                    break;
            }
        }
        
        function Button($args)
        {
            return $this->breadXML->GetHTMLOfElement("Button",$args);
        }
        
        function SpawnModal($args)
        {
            return $this->breadXML->GetHTMLOfElement("Modal",$args);
        }
        
        function LayoutWell($args)
        {
            return $this->breadXML->GetHTMLOfElement("Well",$args);
        }
        
        function Breadcrumbs($args)
        {
            return $this->breadXML->GetHTMLOfElement("Breadcrumbs",$args);
        }
        function Badge($args)
        {
            if(is_string($args))
            {
                $object = new stdClass;
                $object->value = (string)$args;
                $args = $object;
            }
            return $this->breadXML->GetHTMLOfElement("Badge",$args);
        }
        function Label($args)
        {
            if(is_string($args))
            {
                $object = new stdClass;
                $object->value = (string)$args;
                $object->type = "default";
                $args = $object;
            }
            return $this->breadXML->GetHTMLOfElement("Label",$args);
        }
}
?>
