<?
/**
* xGuard Framework
* @package xGuard
* @subpackage main
* @copyright 2014 xGuard
*/

namespace xGuard\Main\Section;

use \xGuard\Main;

/**
* Base entity
*/
class Filter extends \xGuard\Main
{
    public $elements    = false;
    public $sections    = false;
    protected $saveParams = array();

    public function __call($name='',$arguments=array())
    {
        return $this;
    }
}
?>