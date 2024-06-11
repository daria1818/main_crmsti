<?
/**
 * xGuard Framework
 * @package xGuard
 * @subpackage main
 * @copyright 2014 xGuard
 */

namespace xGuard\Main\Seo;

use \xGuard\Main;

/**
 * Base entity
 */
class CDN extends \xGuard\Main
{
    public $elements    = false;
    public $sections    = false;
    protected $saveParams = array();

    public function __call($name='',$arguments=array())
    {
        return $this;
    }

    public function GetServerName($options=array())
    {
        static $host=false;

        $host = empty($host)?$_SERVER['SERVER_NAME']:$host;

        if(defined('CDN_HOST'))
        {
            return CDN_HOST;
        }

        if(stristr($host,'test'))
        {
            return 'https://test.stionline.ru';
        }

        switch($options['TYPE'])
        {
            case 'manufacturer':
                $url = 'https://cdn10.dentlman.ru';
            break;
            case 'js':
                $url = 'https://cdn2.dentlman.ru';
            break;
            case 'css':
            default:
                $url = 'https://cdn1.dentlman.ru';
            break;
        }

        return  ($this->arResult[$options['TYPE']] = isset($this->arResult[$options['TYPE']])?$this->arResult[$options['TYPE']]:(gethostbyname($url)?$url:''));
    }
}
?>
