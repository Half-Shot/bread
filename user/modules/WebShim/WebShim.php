<?php
namespace Bread\Modules;
use Bread\Site as Site;
class WebShimLoader extends Module
{
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}
    
	function LoadJS()
	{
            Site::AddScript(Site::FindFile("WebShim/modernizr.js"),"Modernizr");
            Site::AddScript(Site::FindFile("WebShim/js-webshim/polyfiller.js"),"WebShim");
            Site::AddRawScriptCode("webshims.setOptions('forms-ext', {replaceUI: 'auto'});webshims.polyfill('forms forms-ext');");
        }

}
