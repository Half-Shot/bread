<?php
namespace Bread\Structures;
/**
 * This is filled out to describe the request from the browser to Bread.
 * 
 */
class BreadRequestData
{
    public function __construct() {
        $this->header = new \stdClass();
    }
       
        public $requestName = "";
    
        /**
         * @var integer From the list of compatible layouts with the request, select this index. 
         */
        public $layout = -1;
        
        /**
         * @var integer From the list of compatible themes with the request, select this index. 
         */
        public $theme = -1;
        
        /**
         *
         * @var array The list of additional arguments given by the browser, e.g. page number.
         */
        public $arguments = array();
        
        /**
         * @var array Events to run before starting the request.
         */
        public $events = array();
        
        /**
         * @var array(string) Parent requests to use as template.
         */
        public $include = array();
        
        /**
         * @var stdObject Header to write (technically overwrite)
         * e.g ContentType => text/html
         */
        public $header;
}
?>
