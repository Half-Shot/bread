<?php
namespace Bread\Structures;

/**
 * A Common Structure used with FormBuilding Modules for themes
 * to parse.
 */
class BreadForm
{
    public $name = "breadform";
    public $action = "";
    public $id = "breadform";
    public $class = "";
    public $standalone = true;
    public $method = "GET";
    public $formtarget = "";
    public $onsubmit = "return false;";
    public $isinline = false;
    public $elements = array();
    public $attributes = array();
    public $breadReturnEvent = "";
    public $breadReturnModule = "";
}
/**
 * A Form Element.
 */
class BreadFormElement
{
    public $name = "";
    public $type = "";
    public $class = "";
    public $toggle = false;
    public $onclick = "";
    public $value = "";
    public $placeholder = "";
    public $id = "";
    public $readonly = false;
    public $options = array();
    public $dataset = array();
    public $action = "";
    public $required = false;
    public $hidden = false;
    public $label = "";
    const TYPE_TEXTBOX = "text";
    const TYPE_PASSWORD = "password";
    const TYPE_HTMLFIVEBUTTON = "button";
    const TYPE_RAWHTML = "rawhtml";
}