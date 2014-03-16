<?php
namespace Bread\Structures;
/**
 * This is filled out to describe the request from the browser to Bread.
 * @todo Change name to BreadRequest.
 */
class BreadRequestData
{
        /**
         * @var string The request from the browser. Avaliable requests are defined in the requests.json file.
         */
	public $requestType = False;
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
         * The specifed module names to load.
         * @var array Module name list
         */
        public $modules = array();
}
?>
