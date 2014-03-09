<?php
namespace Bread\Structures;

class BreadForm
{
    public $name = "";
    public $action = "";
    public $method = "post";
    public $formtarget = "_self";
    public $onsubmit = "return true;";
    public $elements = array();
    public $attributes = array();
}

class BreadFormElement
{
    public $name = "";
    public $type = "";
    public $value = "";
    public $placeholder = "";
    public $options = array();
    public $dataset = array();
    public $attributes = array();
    
    const TYPE_TEXTBOX = "text";
    const TYPE_PASSWORD = "password";
}