<?php
namespace Bread\Modules;
use Bread\Site as Site;
use Bread\Utilitys as Util;
use Bread\Structures\BreadForm as BreadForm;
use Bread\Structures\BreadFormElement as BreadFormElement;

class BreadFormBuilder extends Module
{
    public $includedScript = false;
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
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
        if(!is_array($args) && !is_int($args) && !is_string($args))
        {
            Site::$Logger->writeError("Request to draw form, but no data supplied!", \Bread\Logger::SEVERITY_HIGH,"breadforms");
            return "";
        }
        elseif(is_array($args)){
            //Primary Method, use existing form file.
            if(is_int($args[0]))
            {
                $form = $this->forms[$args[0]];
            }
            elseif(is_string($args[0])){
                $form = $this->FindByName($args[0]);
            }
            //3 parameters to a form.
            elseif(array_count_values($args) != 3){
                Site::$Logger->writeError("Request to draw form, but parameters are not usable for BreadFormBuilder!", \Bread\Logger::SEVERITY_HIGH,"breadforms");
                return "";
            }
            else {
                //Secondary method, retrive from args.
                $form = (object)$args;
            }
            //Cast the form.
        }
        elseif(is_int($args)){
            $form = $this->forms[$args];
        }
        elseif(is_string($args)){
            $form = $this->FindByName($args);
        }

        if($form == false){
            return "";
        }

        $form = Site::CastStdObjectToStruct($form, "Bread\Structures\BreadForm");
        //Throw any javascript into a function.
        return Site::$moduleManager->FireEvent("Theme.Form",$form);
    }
    
    function FindByName($string){
        foreach ($this->forms as $TestedForm) {
            if($TestedForm->name == $string){
                return $TestedForm;
            }
        }
        if(!isset($form)){
            Site::$Logger->writeError("Request to draw form, but named form (" . $string . ") did not match!", \Bread\Logger::SEVERITY_MEDIUM,"breadforms");
            return false;
        }
    }
    
    function CreateFormHTML($args){
        $HTML = Site::$moduleManager->FireEvent("Theme.Form",$args);
        if(empty($HTML)){
            return "";
        }
        
        if(!$this->includedScript){
            Site::AddScript(Util::FindFile("BreadFormBuilder/js/BreadForm.js"),"BreadFormScript" ,true);
            $this->includedScript = true;
        }
        Site::AddRawScriptCode('$("#' . $args->id . '").submit(function() { BreadFormSend(this,"' . $args->breadReturnEvent . '", "' . $args->breadReturnModule . '","' . $args->method . '");});', true);
        return $HTML;
    }
}