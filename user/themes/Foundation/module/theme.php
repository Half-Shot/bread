<?php
use Bread\Site as Site;
use Bread\Themes\BreadXML as BreadXML;
class FoundationTheme extends Bread\Modules\Module
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
            $this->manager->RegisterHook($this->name,"Theme.Unload","Unload");     
            //Navigation
            $this->manager->RegisterHook($this->name,"Theme.VerticalNavbar","VerticalNavbar");
            $this->manager->RegisterHook($this->name,"Theme.DrawNavbar","Navbar");
            //Posts
            $this->manager->RegisterHook($this->name,"Theme.Post.Title","Title");
            $this->manager->RegisterHook($this->name,"Theme.Title","Title");
            $this->manager->RegisterHook($this->name,"Theme.Post.Information","Information");
            $this->manager->RegisterHook($this->name,"Theme.Information","ShowInformation");
            $this->manager->RegisterHook($this->name,"Theme.Panel","Panel");            //Forms
            $this->manager->RegisterHook($this->name,"Theme.Form","BuildForm");
            $this->manager->RegisterHook($this->name,"Theme.InputElement","BuildInput");
            $this->manager->RegisterHook($this->name,"Theme.Tabs","Tabs");
            $this->manager->RegisterHook($this->name,"Theme.Collapse","Collapse");
            //Layouts
            $this->manager->RegisterHook($this->name,"Theme.Layout.Article","Article");
            $this->manager->RegisterHook($this->name,"Theme.Layout.Block","LayoutBlock");
            $this->manager->RegisterHook($this->name,"Theme.Layout.Well","LayoutWell");
            $this->manager->RegisterHook($this->name,"Theme.Layout.ButtonGroup","ButtonGroup");
            $this->manager->RegisterHook($this->name,"Theme.Layout.ButtonGroupVertical","ButtonGroupVertical");
            $this->manager->RegisterHook($this->name,"Theme.Layout.ButtonToolbar","ButtonToolbar");
            $this->manager->RegisterHook($this->name,"Theme.Layout.Grid.HorizontalStack","GridHorizontalStack");
            $this->manager->RegisterHook($this->name,"Theme.DrawError","ShowErrorScreen");
            //Misc
            $this->manager->RegisterHook($this->name,"Theme.Icon","DrawIcon");
            $this->manager->RegisterHook($this->name,"Theme.Modal","SpawnModal");
            $this->manager->RegisterHook($this->name,"Theme.Button","Button");
            $this->manager->RegisterHook($this->name,"Theme.Badge","Badge");
            $this->manager->RegisterHook($this->name,"Theme.Label","Label");
            $this->manager->RegisterHook($this->name,"Theme.Breadcrumbs","Breadcrumbs");
            $this->manager->RegisterHook($this->name,"Theme.Comment","Comment");
            $this->manager->RegisterHook($this->name,"Theme.Alert","Alert");
            $this->manager->RegisterHook($this->name,"Theme.Table","Table");
            $this->manager->RegisterHook($this->name,"Theme.Dropdown","Dropdown");
            
            $this->manager->RegisterHook($this->name,"Theme.GetClass","GetClass");
	}
    
        function GetClass($name){
            switch($name){
                //Buttons
                case "Button.Success":
                    return "button-success";
                case "Button.Warning":
                    return "button-warning";
                case "Button.Info":
                    return "button-info";
                case "Button.Danger":
                    return "button-danger";
                case "Button.Primary":
                    return "button-primary";
                case "Button.Large":
                    return "button-lg";
                case "Button.Small":
                    return "button-sm";
                case "Button.ExtraSmall":
                    return "button-xs";
                //Alerts
                case "Alert.Success":
                    return "alert-success";
                case "Alert.Warning":
                    return "alert-warning";
                case "Alert.Info":
                    return "alert-info";
                case "Alert.Danger":
                    return "alert-danger";
                //Label
                case "Label.Default":
                    return "label-default";
                case "Label.Primary":
                    return "label-primary";
                case "Label.Success":
                    return "label-success";
                case "Label.Info":
                    return "label-info";
                case "Label.Warning":
                    return "label-warning";
                case "Label.Danger":
                    return "label-danger";
            }
        }
        
	function Load()
	{
            $this->breadXML = new BreadXML(Site::FindFile("Foundation/theme.xsl"));
            Site::AddRawScriptCode("$(document).foundation();",true);
	}
        
	function Navbar($args)
	{
                //Hooks should be filled with arrays of BreadLinkStructure.
                $Vars = array();
                $Hooks = $this->manager->FireEvent("Bread.GetNavbarIndex",$args,false);
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
                        $link->active = (Site::getRequest()->requestName == $link->request);
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
                $Vars["inner"] = array();
                foreach($args["_inner"] as $html){
                    if($html["guts"])
                        $Vars["inner"][] = $html["guts"];
                }
                return $this->breadXML->GetHTMLOfElement("Navbar",$Vars);
	}
        
        function VerticalNavbar($args)
        {
            unset($args["_inner"]);//Useful practise for theme elements that do not do layout processing.
            return $this->breadXML->GetHTMLOfElement("VerticalNavbar",$args);
        }
        
        function Dropdown($args)
        {
            return $this->breadXML->GetHTMLOfElement("Dropdown",$args);
        }

        function Title($args)
        {
            unset($args["_inner"]);//Useful practise for theme elements that do not do layout processing.
            if(count($args) < 2)
                $args[1] = "";
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
        
        function BuildForm(Bread\Structures\BreadForm $form)
        {
            $formHTML = $this->breadXML->GetHTMLOfElement("Form",$form);
            if($form->isinline == true){
                //Wrap it in a grid.
                $formDOM = new DOMDocument();
                $formHTML=str_replace('id=""', '', $formHTML);
                $formDOM->loadHTML($formHTML);
                //Convert rows to columns.
                $xpath = new DOMXPath($formDOM);
                $results = $xpath->query("//*[@class='row']//*[@type!='hidden']");
                $elements = array();
                foreach($results as $element){
                    $classValue = $element->getAttribute('class');
                    $elements[] = \Bread\Utilitys::DOMElementToString($element);
                }
                $formHTML = $this->GridHorizontalStack($elements);
            }
            return $formHTML;
            
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
            if(array_key_exists("_inner", $args)){
                $args["body"] = $this->LayoutBlock($args);
            }
            return $this->breadXML->GetHTMLOfElement("Panel",$args);
        }
        
        function ButtonGroupVertical($args){
           return $this->ButtonGroup($args, "stack button-group");
        }
        
        function ButtonGroup($args,$class = "button-group")
        {
            $HTML = "";
            if(is_array($args)){
                if(isset($args["_inner"])){
                    foreach($args["_inner"] as $element)
                    {
                        $HTML .= $element["guts"];
                    }
                }
                else
                {
                    foreach($args as $button)
                    {
                        if(!is_string($button)){
                            $button = $this->manager->FireEvent("Theme.Button",$button);
                        }
                        $HTML .= $button;
                    }
                }
            }
            else {
                $HTML = $args;
            }
            
                        
            return '<div class="'.$class.'">' . $HTML . '</div>';
        }
        
        function ButtonToolbar($args)
        {
            return '<div class="button-bar" role="toolbar">' . $args . '</div>';
        }
        
        function DrawIcon($args)
        {
            switch($args)
            {
                case "audio":
                    return '<span class="fi-volume"></span>';
                case "video":
                    return '<span class="fi-video"></span>';
                case "close":
                    return '<span class="fi-x-circle"></span>';
                case "megaphone":
                    return '<span class="fi-bullhorn"></span>';
                default:
                    return '<span class="fi-' . $args . '"></span>';
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
            if(array_key_exists("_inner", $args)){
                $args["body"] = $this->LayoutBlock($args);
            }
            //Foundation has no well class.
            return $this->breadXML->GetHTMLOfElement("Panel",$args);
        }
        
        function Tabs($args)
        {
            return $this->breadXML->GetHTMLOfElement("Tabs",$args);
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
        
        function GridHorizontalStack($args){
            if(count($args) < 1)
                return "";
            
            if(!is_array($args)){
                return "";
            }
            
            if(is_string(array_values($args)[0])){
                    $listOfCells = array();
                    foreach($args as $string){
                        $cell = new stdClass();
                        $cell->body = $string;
                        $listOfCells[] = $cell;
                    }
            }
            
            if(array_key_exists(0, $args) && !isset($listOfCells))
            {
                $listOfCells = $args[0];
                if(is_object($listOfCells)){
                    $listOfCells = $args;
                }
                else if(is_array($listOfCells))
                {
                    $listOfCells = $args[0];
                }
                else
                {
                    Site::$Logger->writeError("Theme was passed a parameter, but it was not an object!" . "<pre>" . var_export($listOfCells,true) . "</pre>" , \Bread\Logger::SEVERITY_LOW, "theme");
                    return;
                }
            }
            else if(array_key_exists("_inner", $args)){
                $listOfCells = array();
                foreach($args["_inner"] as $body){
                    $cell =  new stdClass();
                    $cell->body = $body["guts"];
                    if(array_key_exists("arguments", $body)){
                        if(isset($body["arguments"][0]->cell_offset)){
                            $cell->offset = $body["arguments"][0]->cell_offset;
                        }
                        if(isset($body["arguments"][0]->cell_size)){
                            $cell->size = $body["arguments"][0]->cell_size;
                        }
                    }
                    $listOfCells[] = $cell;
                }
            }
            $HTML = "<div class='row'>";
            $spaceLeft = 12;
            foreach($listOfCells as $i => $cell)
            {
                $offset = "";
                if(isset($cell->offset)){
                    $offset = " large-offset-" . $cell->offset;
                }
                else
                {
                    $cell->offset = 0;
                }
                $spaceLeft -= $cell->offset;
                $cellsLeft = count($listOfCells) - ($i);
                if(!isset($cell->size)){
                    if($cellsLeft > 1)
                    {
                        $cell->size = $spaceLeft / $cellsLeft;
                    }
                    else
                    {
                        $cell->size = $spaceLeft;
                    }
                }
                $HTML .= '<div class="columns large-'. $cell->size . $offset . ' ">'. $cell->body .'</div>';
                $spaceLeft -= $cell->size;
            }
            return $HTML . "</div>";
        }
        
        function Comment($args)
        {
            return $this->breadXML->GetHTMLOfElement("Comment",$args);
        }
        
        function Alert($args)
        {
            $class = "alert " . $args["class"];
            $closeButton = false;
            if(isset($args["canClose"])){
                if($args["canClose"]){
                    $closeButton = '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">' . $this->manager->FireEvent("Theme.Icon","close") . '</button>';
                    $class .= " alert-dismissable";
                }
            }
            $body = $args["body"];
            if(array_key_exists("id",$args)){
                $args["id"] = 'id="'.$args["id"].'"';
            }
            else{
                $args["id"] = "";
            }
            return '<div '.$args["id"].' class="'. $class .'">' . $closeButton .  $body . "</div>";
        }
        function Collapse($args)
        {
            return $this->breadXML->GetHTMLOfElement("Collapse",$args);
        }
        
        function Table(Bread\Structures\BreadTableElement $args)
        {
            return $this->breadXML->GetHTMLOfElement("Table",$args);
        }
}
?>
