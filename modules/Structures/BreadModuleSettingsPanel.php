<?php
/**
 * Description of BreadConfigurationPanel
 *
 * @author will
 */
namespace Bread\Structures;
/**
 * A structure for displaying your modules settings in a configuration panel.
 */
class BreadModuleSettings {
    //put your code here
    /**
     * The name of your module. HTML can be used.
     * @var string 
     */
    public $Name;
    /**
     * The array of tabs (sections) in your settings.
     * @var array of BreadModuleSettingsTab
     */
    public $SettingsGroups = array();
    /**
     * Override the index of your settings placement on the tab.
     * This does not always apply if another module requires it.
     * @var integer 
     */
    public $OverrideIndex = -1;
}
/**
 * A section of your BreadModuleSettings.
 */
class BreadModuleSettingsTab {
    public $Name;
    public $HumanTitle;
    public $Panels = array();
}

class BreadModuleSettingsPanel {
    public $Name = "unnamedPanel";
    public $HumanTitle = "Unnamed Panel";
    public $Body = "";
    /**
     *
     * @var Bread\Structures\BreadForm
     */
    public $ApplyButtons = false;
}