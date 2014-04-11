<?php
namespace Bread\Modules;
use Bread\Site as Site;
class WebShimLoader extends Module
{
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}

	function RegisterEvents()
	{
            $this->manager->RegisterHook($this->name,"Bread.ProcessRequest","LoadJS");

	}
    
	function LoadJS()
	{
            Site::AddScript(Site::FindFile("WebShim/modernizr.js"));
            Site::AddScript(Site::FindFile("WebShim/js-webshim/polyfiller.js"));
            Site::AddRawScriptCode("webshims.setOptions('forms-ext', {replaceUI: 'auto'});webshims.polyfill('forms forms-ext');");
        }

}
