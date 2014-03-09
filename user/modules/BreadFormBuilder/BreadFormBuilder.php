<?php
namespace Bread\Modules;
use Bread\Site as Site;
use Bread\Structures\BreadForm as BreadForm;
use Bread\Structures\BreadFormElement as BreadFormElement;

class BreadFormBuilder extends Module
{
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}

	function RegisterEvents()
	{
            $this->manager->RegisterEvent($this->name,"Bread.ProcessRequest","Setup");
            $this->manager->RegisterEvent($this->name,"BreadFormBuilder.DrawForm","DrawForm");
	}
        
        function Setup()
        {
            //Get a settings file.
            $rootSettings = Site::$settingsManager->FindModuleDir("breadforms");
            Site::$settingsManager->CreateSettingsFiles($rootSettings . "forms.json", array());
            $this->forms = Site::$settingsManager->RetriveSettings($rootSettings . "forms.json");
        }
        
        function DrawForm($args)
        {
            if(!is_array($args) && !is_int($args))
            {
                Site::$Logger->writeError("Request to draw form, but no data supplied!");
                return Site::$moduleManager->HookEvent("Theme.Infomation","Couldn't draw form :(")[0];
            }
            //Primary Method, use existing form file.
            if(is_int($args))
            {
                $form = $this->forms[$args];
            }
            //3 parameters to a form.
            elseif(array_count_values($args) != 3){
                Site::$Logger->writeError("Request to draw form, but parameters are not usable for BreadFormBuilder!");
                return Site::$moduleManager->HookEvent("Theme.Infomation","Couldn't draw form :(")[0];
            }
            else {
                //Secondary method, retrive from args.
                $form = (object)$args;
            }
            //Cast the form.
            $form = Site::CastStdObjectToStruct($form, "Bread\Structures\BreadForm");
            //Throw any javascript into a function.
            return Site::$moduleManager->HookEvent("Theme.Form",$form)[0];
        }
        
}