<?php
use Bread\Site as Site;
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
                        $this->manager->RegisterEvent($this->name,"Theme.Title","Title");
                        $this->manager->RegisterEvent($this->name,"Theme.Subtitle","SubTitle");
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

	function Navbar($args)
	{
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
                        if($link->hidden)
                           continue;
                        
                        if(!isset($link->url))
                        {
                            $params = array();
                            foreach($link->args as $args)
                            {
                                $params[$args->key] = $args->value;
                            }
                            $link->url = Site::CondenseURLParams(false, array_merge(array("request" => $link->request),$params));
                        }
                        $HTMLCode .= "<li><a href='" . $link->url . "' target ='" . $link->targetWindow ."'>" . $link->text . "</a></li>";

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
            $HTMLCode .= "</ui>";
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
        

}
?>
