<?php
namespace Bread\Structures;

/**
 * A Common Structure used with FormBuilding Modules for themes
 * to parse.
 */
class BreadForm
{
    public $name = "";
    public $action = "";
    public $method = "";
    public $formtarget = "";
    public $onsubmit = "return true;";
    public $elements = array();
    public $attributes = array();
}
/**
 * A Form Element.
 */
class BreadFormElement
{
    public $name = "";
    public $type = "";
    public $onclick = "";
    public $value = "";
    public $placeholder = "";
    public $options = array();
    public $dataset = array();
    public $attributes = array();
    
    const TYPE_TEXTBOX = "text";
    const TYPE_PASSWORD = "password";
    const TYPE_HTMLFIVEBUTTON = "button";
}