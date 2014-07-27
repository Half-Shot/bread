<?php
/**
 * Description of BreadUser
 *
 * @author will
 */
namespace Bread\Structures;
/**
 * A Standard User Structure for User Management Modules.
 */
class BreadUser
{
    /**
     * The username associated with the user.
     * @var string 
     */
    public $username = "%BREADNOTSET%";
    /**
     * Any groups the user may be a memeber of.
     * @var array
     */
    public $groups = array();
    /**
     * A unique identifer for the user
     * NOTE: This is NOT the index or key of the user in a dataset.
     * @var int 
     */
    public $uid = -1;
    /**
     * An array of extra information e.g. e-mail address.
     * @var array
     */
    public $information = array();
    /**
     * @todo Figure out what this is for. 
     */
    public $sessionVars = array();
    /**
     * What rights does the user have?
     * An array of Strings for rights.
     * @var array 
     */
    public $rights = array();
}

/**
 * A Standard Group Structure for User Management Modules.
 */
class BreadGroup
{
    /**
     * The username associated with the user.
     * @var string 
     */
    public $name = "Unknown";
    /**
     * An array of extra information e.g. e-mail address.
     * @var array
     */
    public $information = array();
    /**
     * What rights does the user have?
     * An array of Strings for rights.
     * @var array 
     */
    public $rights = array();
}