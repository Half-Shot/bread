<?php
use Bread\Site as Site;
use Bread\Structures\BreadFormElement as BreadFormElement;
class VanillaBreadTheme extends Bread\Modules\Module
{
	
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}

	function RegisterEvents()
	{
            $this->manager->RegisterEvent($this->name,"Theme.Load","Load"); //For each event you want to allow, specify: Name of theme, EventName and function name
            $this->manager->RegisterEvent($this->name,"Theme.HeaderInfo","HeaderInfomation");
            $this->manager->RegisterEvent($this->name,"Theme.DrawSystemMenu","SystemMenu");
            $this->manager->RegisterEvent($this->name,"Theme.DrawNavbar","Navbar");
            $this->manager->RegisterEvent($this->name,"Theme.DrawFooter","Footer");
            $this->manager->RegisterEvent($this->name,"Theme.Unload","Unload");
            $this->manager->RegisterEvent($this->name,"Theme.VerticalNavbar","VerticalNavbar");
            $this->manager->RegisterEvent($this->name,"Theme.Post.Title","Title");
            $this->manager->RegisterEvent($this->name,"Theme.Post.Subtitle","SubTitle");
            $this->manager->RegisterEvent($this->name,"Theme.Post.Breadcrumbs","Breadcrumbs");
            $this->manager->RegisterEvent($this->name,"Theme.Post.Infomation","Infomation");
            $this->manager->RegisterEvent($this->name,"Theme.Infomation","ShowInfomation");
            $this->manager->RegisterEvent($this->name,"Theme.Form","BuildForm");
	}
    
	function Load()
	{
		$HTMLCode = "<p>VanillaBread Load Function</p>";
		return $HTMLCode;
	}

	function HeaderInfomation()
	{
		$HTMLCode = "<!-- Header Stuff from Vanilla -->\n";
		return $HTMLCode;
	}

	function SystemMenu($args)
	{
		$HTMLCode = "<p>VanillaBread DrawSystemMenu Function</p>";
		return $HTMLCode;
	}
        
        function ShowInfomation($args)
        {
            return "<div id='infobox' style='background:red;'>" . $args . "</div>";
        }
        
        function ProcessLink($link)
        {
           $HTMLCode = "";
           if($link->hidden)
            return "";
           
           //Vanilla doesn't support mutli level navbars as it doesn't use javascript.
           //The code is simple though.
           //$HTMLCode .= "<ul id='sublevel'>";
           //foreach($link->sublinks as $link)
           //{
           //    $HTMLCode .= ProcessLink($link);
           //}
           //$HTMLCode .= "</ul>";
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
           $HTMLCode .= "<li><a href='" . $URL . "' target ='" . $link->targetWindow ."'>" . $link->text . "</a></li>";
           return $HTMLCode;
        }
        
	function Navbar($args)
	{
                //Hooks should be filled with arrays of BreadLinkStructure.
                $Hooks = $this->manager->HookEvent("Bread.GetNavbarIndex",$args);
                if(!$Hooks)
                {
                   $Hooks = array();
                }
                $HTMLCode = "<ul>";
                foreach ($Hooks as $links)
                {
                    foreach ($links as $link)
                    {
                        $HTMLCode .= $this->ProcessLink($link);
                    }
                }
                $HTMLCode .= "</ul>";
		return $HTMLCode;
	}

	function Footer()
	{
		$HTMLCode = "<p>VanillaBread DrawFooter Function</p>";
		return $HTMLCode;
	}
        function VerticalNavbar($args)
        {
            $HTMLCode = "<ul>";
            foreach ($args as $text => $url)
            {
                $HTMLCode .= "<li><a href='" . $url . "'>" . $text . "</a></li>";
            }
            $HTMLCode .= "</ul>";
            return $HTMLCode;
        }

        function Title($args)
        {
            return "<h1>" . $args . "</h1>";
        }
        
        function SubTitle($args)
        {
            return "<h3>" . $args . "</h3>";
        }
        
	function Unload()
	{
		$HTMLCode = "<p>VanillaBread Unload Function</p>";
		return $HTMLCode;
	}
        
        function Breadcrumbs($args)
        {
                $HTMLCode = "<pre>";
                foreach($args as $arg)
                {
                    $HTMLCode .= $arg . " ";
                }
            	$HTMLCode .= "</pre>";
		return $HTMLCode;
        }
        
        function Infomation($args)
        {
                $HTMLCode = "<div>";
                foreach($args as $key => $arg)
                {
                    $HTMLCode .= $key . ":" . $arg . "</br>";
                }
            	$HTMLCode .= "</div>";
		return $HTMLCode;
        }
        
        function BuildForm(Bread\Structures\BreadForm $form)
        {
            $html = "<form name='" . $form->name ."' onsubmit='".$form->onsubmit."' action='" . $form->action . "' method='" . $form->method . "'";
            if(isset($element->attributes))
            {
                $attributes = get_object_vars($element->attributes);
                foreach($attributes as $key => $value)
                {
                    $html .= " " . $key . "='" . $value . "'";
                }
            }
            $html .= ">";
            
            foreach($form->elements as $element)
            {
                $html .= $this->BuildInput($element);
            }
            $html .= "\n</form>";
            return $html;
        }
        
        function BuildInput($element)
        {
                switch ($element->type)
                {
                    case BreadFormElement::TYPE_TEXTBOX:
                        $html = "\n\t<input name='".$element->name."' type='text'";
                        break;
                    case BreadFormElement::TYPE_PASSWORD:
                        $html = "\n\t<input name='".$element->name."' type='password'";
                        break; 
                    default:
                        $html = "\n\t<input name='".$element->name."' type='" . $element->type ."'";

                }
                if(isset($element->value))
                    $html .= " value='" . $element->value . "'";
                if(isset($element->placeholder))
                    $html .= " placeholder='" . $element->placeholder . "'";
                if(isset($element->attributes))
                {
                    $attributes = get_object_vars($element->attributes);
                    foreach($attributes as $key => $value)
                    {
                        $html .= " " . $key . "='" . $value . "'";
                    }
                }
                $html .= ">";
                return $html;
        }
}
?>
