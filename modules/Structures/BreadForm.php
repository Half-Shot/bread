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
    public $id = "";
    public $method = "";
    public $formtarget = "";
    public $onsubmit = "return false;";
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
    public $class = "";
    public $toggle = false;
    public $onclick = "";
    public $value = "";
    public $placeholder = "";
    public $id = "";
    public $readonly = false;
    public $options = array();
    public $dataset = array();
    public $attributes = array();
    public $required = false;
    public $hidden = false;
    const TYPE_TEXTBOX = "text";
    const TYPE_PASSWORD = "password";
    const TYPE_HTMLFIVEBUTTON = "button";
    const TYPE_RAWHTML = "rawhtml";
}