<?
/**
 * xGuard Framework
 * @package xGuard
 * @subpackage main
 * @copyright 2014 xGuard
 */

namespace xGuard\Main\Section;

use xGuard\Main;

/**
 * Base entity
 */
class Button extends \xGuard\Main
{
    protected $cache = false;
    protected $cacheTime = 31536000;
    protected $cacheId = false;
    protected $cachePath = '/';
    protected static $saveParams = array();
    protected static $elements = false;
    protected static $sections = false;
}