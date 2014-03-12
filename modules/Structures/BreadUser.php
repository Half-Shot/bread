<?php
/**
 * Description of BreadUser
 *
 * @author will
 */
namespace Bread\Structures;
class BreadUser
{
    public $username = "Unknown";
    public $uid = -1;
    public $infomation = array();
    public $sessionVars = array();
    public $rights = array();
}