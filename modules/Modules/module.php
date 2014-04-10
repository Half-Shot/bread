<?php
namespace Bread\Modules;
class Module
{
        /**
         * The parent module manager. Should be the same as Site::$moduleManager
         * unless your platform is rolling its own exotic manager.
         * Unless your a hipster, use the $manager variable.
         * @var type \Bread\Modules\ModuleManager
         */
        protected $manager;
        /**
         * The name of the module to be referred to.
         * @var type 
         */
	protected $name;
        protected $path;
	function __construct($manager,$name,$path = "")
	{
		$this->manager = $manager;
		$this->name = $name;
                $this->path = $path;
	}
        /**
         * Every module should use this function to set its hooks.
         * DO NOT USE THIS AS A SETUP FUNCTION, ITS NOT GOING TO WORK.
         */
	function RegisterEvents()
	{
            
	}
}
?>
