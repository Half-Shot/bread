<?php
/**
 * Description of BreadConfigurationPanel
 *
 * @author will
 */
namespace Bread\Structures;
class BreadConfigurationPanel {
    //put your code here
    public $Name;
    public $SettingsGroups = array();
}

class BreadCPSetting {
    public $Name;
    public $HumanTitle;
    public $Panels = array();
}

class BreadCPPanel {
    public $Name = "unnamedPanel";
    public $HumanTitle = "Unnamed Panel";
    public $Body = "";
    /**
     *
     * @var Bread\Structures\BreadForm
     */
    public $ApplyButtons = false;
}