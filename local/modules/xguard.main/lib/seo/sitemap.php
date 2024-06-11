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
class SiteMap extends \xGuard\Main
{
    public $elements    = false;
    public $sections    = false;
    protected $saveParams = array();
    protected $moduleId = 'xguard.sm';
    protected $xguardSmSiteIdKey        = 'xguard_sm_site_id';
    protected $xguardSmSiteKey          = 'xguard_sm_site';
    protected $xguardSmUrlKey           = 'xguard_sm_url';
    protected $xguardSmUrlProtocolKey   = 'xguard_sm_url_protocol';
    protected $xguardSmUrlHttpsKey      = 'xguard_sm_use_https';
    protected $xguardSmExcludeMaskKey   = 'xguard_sm_exclude_mask';
    protected $xguardSmIblockKey        = 'xguard_sm_iblock';
    protected $xguardSmSectionKey       = 'xguard_sm_iblock_section';
    protected $xguardSmElementKey       = 'xguard_sm_iblock_element';

    public function __call($name='',$arguments=array())
    {
        return $this;
    }

    public function CreateSiteMap($options=array())
    {
        if(isset($this->arParams['REQUEST']['dev'])):
            error_reporting(E_ALL&&~E_WARNING);
            ini_set('display_errors','on');
        endif;

        $this->IncludeModule('iblock');
        $this->CreateSiteMapPrepareParams();
        $this->GetSections();
        $this->GetElements();
        $this->GetDirs();
        $this->SaveData();
    }

    protected function CreateSiteMapPrepareParams($options=array())
    {
        $this->arParams[$this->xguardSmSiteIdKey]         = \COption::GetOptionString($this->moduleId, $this->xguardSmSiteIdKey);
        $this->arParams[$this->xguardSmExcludeMaskKey]    = \COption::GetOptionString($this->moduleId, $this->xguardSmExcludeMaskKey);
        $this->arParams[$this->xguardSmUrlHttpsKey]       = \COption::GetOptionString($this->moduleId, $this->xguardSmUrlHttpsKey);
        $this->arParams[$this->xguardSmIblockKey]         = \COption::GetOptionString($this->moduleId, $this->xguardSmIblockKey);
        $this->arParams[$this->xguardSmIblockKey]         = !empty($this->arParams[$this->xguardSmIblockKey])?unserialize($this->arParams[$this->xguardSmIblockKey]):array();

        $arGroup = array('SECTION'=>$this->xguardSmSectionKey,'ELEMENT'=>$this->xguardSmElementKey);

        foreach($arGroup as $keyGroupName=>$groupName):
            $this->arParams[$keyGroupName]  = array();
            $this->arParams[$groupName]     = unserialize(\COption::GetOptionString($this->moduleId, $groupName));
            $this->arParams[$groupName]     = $this->arParams[$groupName]?$this->arParams[$groupName]:array();

            foreach($this->arParams[$groupName] as $id):
                $name = $groupName.'_'.$id;

                $this->arParams[$name] = unserialize(\COption::GetOptionString($this->moduleId, $name));

                if(is_array($this->arParams[$name])):
                    foreach($this->arParams[$name] as &$value):
                        if(!is_array($value)&&empty($value)):
                            $value=false;

                            if(count($this->arParams[$name])==1):
                                $this->arParams[$name]=false;
                                break;
                            endif;
                        endif;
                    endforeach;
                endif;

                $this->arParams[$keyGroupName][] = $this->arParams[$name];
            endforeach;
        endforeach;

        $this->arParams[$this->xguardSmSiteKey]         = \CSite::GetByID($this->arParams[$this->xguardSmSiteIdKey])->Fetch();
        $this->arParams[$this->xguardSmUrlProtocolKey]  = 'http'.(isset($this->arParams[$this->xguardSmUrlHttpsKey])&&!empty($this->arParams[$this->xguardSmUrlHttpsKey])?'s':'').'://';
        $this->arParams[$this->xguardSmUrlKey]          = $this->arParams[$this->xguardSmUrlProtocolKey].$this->arParams[$this->xguardSmSiteKey]['SERVER_NAME'];
    }

    protected function GetSections($options=array())
    {
        $this->sections = new \CIBlockSection;

        foreach($this->arParams[$this->xguardSmIblockKey] as $iblockId):
            $this->GetSectionsPrepareParams(array('IBLOCK_ID'=>$iblockId));

            $obSection = $this->sections->GetList(
                $this->arParams['GET_SECTIONS']['GET_LIST']['ORDER'],
                $this->arParams['GET_SECTIONS']['GET_LIST']['FILTER'],
                $this->arParams['GET_SECTIONS']['GET_LIST']['GROUP'],
                $this->arParams['GET_SECTIONS']['GET_LIST']['SELECT'],
                $this->arParams['GET_SECTIONS']['GET_LIST']['LIMIT']
            );

            while($arSection = $obSection->GetNext()):
                $this->SaveDataPrepareUrl(
                    array(
                        'arItem'    => &$arSection,
                    )
                );

                $this->arResult['SECTIONS'][$arSection['SECTION_PAGE_URL']] = $arSection;
                $this->arResult['RESULT'][$arSection['SECTION_PAGE_URL']] = &$this->arResult['SECTIONS'][$arSection['SECTION_PAGE_URL']];
                $this->arParams['GET_ELEMENTS']['GET_LIST']['FILTER']['SECTION_ID'][$arSection['ID']] = $arSection['ID'];
            endwhile;
        endforeach;
    }

    protected function GetSectionsPrepareParams($options=array())
    {
        $this->arParams['GET_SECTIONS']['GET_LIST'] = array(
            'ORDER'     => array(
                'SORT'  => 'ASC',
            ),
            'FILTER'    => array(
                'IBLOCK_ID'     => $options['IBLOCK_ID'],
                'ACTIVE'        => 'Y',
            ),
            'GROUP'    => false,
            'LIMIT'    => false,
            'SELECT'    => array(
                'TIMESTAMP_X',
                'SECTION_PAGE_URL',
                'NAME',
            ),
        );

        $this->GetSectionsFilterParams($options);
    }

    protected function GetSectionsFilterParams($options=array())
    {
        if(!isset($this->arParams['SECTION'])||empty($this->arParams['SECTION'])):
            return $this;
        endif;

        $this->GetSectionsFilterPrepareParams($options);

        $obEntity = \CUserTypeEntity::GetList(
            $this->arParams['IBLOCK_SECTION']['GET_LIST']['ORDER'],
            $this->arParams['IBLOCK_SECTION']['GET_LIST']['FILTER']
        );

        while($arItem = $obEntity->Fetch()):
            $key = $this->xguardSmSectionKey.'_'.$arItem['ID'];

            if(isset($this->arParams[$key])):
                $this->arParams['GET_SECTIONS']['GET_LIST']['FILTER'][$arItem['FIELD_NAME']] = $this->arParams[$key];
            endif;
        endwhile;
    }

    protected function GetSectionsFilterPrepareParams($options=array())
    {
        $this->arParams['IBLOCK_SECTION']['GET_LIST'] = array(
            'ORDER' => array(
                'ID'    => 'ASC',
            ),
            'FILTER' => array(
                'ID'        => $this->arParams['SECTION'],
                'ACTIVE'    => 'Y',
            ),
        );
    }

    protected function GetElements($options=array())
    {
        $this->elements = new \CIBlockElement;

        foreach($this->arParams[$this->xguardSmIblockKey] as $iblockId):
            $this->GetElementsPrepareParams(array("IBLOCK_ID"=>$iblockId));

            $obElement = $this->elements->GetList(
                $this->arParams['GET_ELEMENTS']['GET_LIST']['ORDER'],
                $this->arParams['GET_ELEMENTS']['GET_LIST']['FILTER'],
                $this->arParams['GET_ELEMENTS']['GET_LIST']['GROUP'],
                $this->arParams['GET_ELEMENTS']['GET_LIST']['LIMIT'],
                $this->arParams['GET_ELEMENTS']['GET_LIST']['SELECT']
            );

            while($arElement = $obElement->GetNext()):
                $this->SaveDataPrepareUrl(
                    array(
                        'arItem'    => &$arElement,
                    )
                );
                $this->arResult['ELEMENTS'][$arElement['DETAIL_PAGE_URL']] = $arElement;
                $this->arResult['RESULT'][$arElement['DETAIL_PAGE_URL']] = &$this->arResult['ELEMENTS'][$arElement['DETAIL_PAGE_URL']];
            endwhile;
        endforeach;
    }

    protected function GetElementsPrepareParams($options=array())
    {
        $this->arParams['GET_ELEMENTS']['GET_LIST'] = is_array($this->arParams['GET_ELEMENTS']['GET_LIST'])?$this->arParams['GET_ELEMENTS']['GET_LIST']:array();

        $this->arParams['GET_ELEMENTS']['GET_LIST']['ORDER']    = is_array($this->arParams['GET_ELEMENTS']['GET_LIST']['ORDER'])?$this->arParams['GET_ELEMENTS']['GET_LIST']['ORDER']:array();
        $this->arParams['GET_ELEMENTS']['GET_LIST']['FILTER']   = is_array($this->arParams['GET_ELEMENTS']['GET_LIST']['FILTER'])?$this->arParams['GET_ELEMENTS']['GET_LIST']['FILTER']:array();
        $this->arParams['GET_ELEMENTS']['GET_LIST']['GROUP']    = is_array($this->arParams['GET_ELEMENTS']['GET_LIST']['GROUP'])?$this->arParams['GET_ELEMENTS']['GET_LIST']['GROUP']:false;
        $this->arParams['GET_ELEMENTS']['GET_LIST']['LIMIT']    = is_array($this->arParams['GET_ELEMENTS']['GET_LIST']['LIMIT'])?$this->arParams['GET_ELEMENTS']['GET_LIST']['LIMIT']:false;
        $this->arParams['GET_ELEMENTS']['GET_LIST']['SELECT']   = is_array($this->arParams['GET_ELEMENTS']['GET_LIST']['SELECT'])?$this->arParams['GET_ELEMENTS']['GET_LIST']['SELECT']:array();
        $this->arParams['GET_ELEMENTS']['GET_LIST']['ORDER']    = array_replace_recursive(
            array(
                'SORT'  => 'ASC',
            ),
            $this->arParams['GET_ELEMENTS']['GET_LIST']['ORDER']
        );
        $this->arParams['GET_ELEMENTS']['GET_LIST']['FILTER'] = array_replace_recursive(
            array(
                'IBLOCK_ID'    => $options['IBLOCK_ID'],
                'ACTIVE'       => 'Y',
            ),
            $this->arParams['GET_ELEMENTS']['GET_LIST']['FILTER']
        );
        $this->arParams['GET_ELEMENTS']['GET_LIST']['SELECT'] = array_replace_recursive(
            array(
                'DETAIL_PAGE_URL',
                'ID',
                'NAME',
                'TIMESTAMP_X',
            ),
            $this->arParams['GET_ELEMENTS']['GET_LIST']['SELECT']
        );
    }

    protected function GetDirs($options=array())
    {
        $path = rtrim($this->arParams[$this->xguardSmSiteKey]["DIR"], "/");
        $this->arParams[$this->xguardSmSiteKey]["DOC_ROOT"] = $this->arParams[$this->xguardSmSiteKey]["ABS_DOC_ROOT"].(empty($path)?'':$path);

        $io = \CBXVirtualIo::GetInstance();

        if(!$io->DirectoryExists($this->arParams[$this->xguardSmSiteKey]["DOC_ROOT"])):
            return 0;
        endif;

        $f = $io->GetFile($this->arParams[$this->xguardSmSiteKey]["DOC_ROOT"]);

        if(!$f->IsReadable()):
            return 0;
        endif;

        $path = '/';

        if($this->CheckPath($path)):
            $dirTemp = $io->GetFile($this->arParams[$this->xguardSmSiteKey]["DOC_ROOT"].'/index.php');

            $sSectionName='';

            @include($this->arParams[$this->xguardSmSiteKey]["DOC_ROOT"].'/.section.php');

            $this->arResult['DIRS'][$path] = array(
                'PAGE_URL'      => $path,
                'TIMESTAMP_X'   => $dirTemp->GetModificationTime(),
                'PRIORITY'      => '1',
                'NAME'          => $sSectionName,
            );

            $this->SaveDataPrepareUrl(
                array(
                    'arItem'    => &$this->arResult['DIRS'][$path],
                )
            );

            $this->arResult['RESULT'][$path] = &$this->arResult['DIRS'][$path];
        endif;

        $d = $io->GetDirectory($this->arParams[$this->xguardSmSiteKey]["DOC_ROOT"]);

        foreach($d->GetChildren() as $dir):
            if($dir->IsDirectory()):
                $path = str_replace($this->arParams[$this->xguardSmSiteKey]["DOC_ROOT"],'',$dir->GetPathWithName());
                $path = rtrim($path, "/").'/';

                if($this->CheckPath($path)):
                    $dirTemp = $io->GetFile($dir->GetPathWithName().'/index.php');

                    $sSectionName='';

                    @include($dir->GetPathWithName().'/.section.php');

                    $this->arResult['DIRS'][$path] = array(
                        'PAGE_URL'      => $path,
                        'TIMESTAMP_X'   => $dirTemp->GetModificationTime(),
                        '~TIMESTAMP_X'   => $this->GetTime(array('TIME'=>$dirTemp->GetModificationTime())),
                        'PRIORITY'      => '1',
                        'NAME'          => $sSectionName,
                    );

                    $this->SaveDataPrepareUrl(
                        array(
                            'arItem'    => &$this->arResult['DIRS'][$path],
                        )
                    );

                    $this->arResult['RESULT'][$path] = &$this->arResult['DIRS'][$path];
                endif;
            endif;
        endforeach;
    }

    protected function SaveData($options=array())
    {
        $fileName = 'sitemap_new.xml';

        $f = fopen($this->arParams[$this->xguardSmSiteKey]['ABS_DOC_ROOT'].'/'.$fileName,'w');

        if(!$f):
            return;
        endif;

        fwrite(
            $f,
            '<?xml version="1.0" encoding="UTF-8"?>'.
            "\n".
            '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.
            "\n"
        );

        $arItems = array('SECTIONS','ELEMENTS','DIRS');

        foreach($arItems as $key):
            if(isset($this->arResult[$key])):
                foreach($this->arResult[$key] as $arItem):
                    $this->SaveDataPrepareNode(
                        array(
                            'arItem'=>&$arItem,
                        )
                    );
                    fwrite($f,$arItem['NODE']);
                endforeach;
            endif;
        endforeach;

        fwrite(
            $f,
            '</urlset>'
        );

        fclose($f);
    }


    protected function SaveDataPrepareNode($options=array())
    {
        if(!isset($options['arItem']['URL'])||empty($options['arItem']['URL'])):
            $options['arItem']['NODE'] = '';

            return;
        endif;

        $options['arItem']['NODE']="\t".'<url>'."\n";
        $options['arItem']['NODE'].="\t\t".'<loc>'.$options['arItem']['URL'].'</loc>'."\n";

        if(isset($options['arItem']['TIMESTAMP_X'])):
            $options['arItem']['NODE'].="\t\t".'<lastmod>'.($this->GetTime(array('TIME'=>$options['arItem']['TIMESTAMP_X']))).'</lastmod>'."\n";
        endif;

        if(isset($options['arItem']['PRIORITY'])):
            $options['arItem']['NODE'].="\t\t".'<priority>'.$options['arItem']['PRIORITY'].'</priority>'."\n";
        endif;

        $options['arItem']['NODE'].="\t".'</url>'."\n";
    }

    protected function SaveDataPrepareUrl($options=array())
    {
        if(isset($options['arItem']['SECTION_PAGE_URL'])):
            $options['arItem']['URL']   = $this->arParams[$this->xguardSmUrlKey].$options['arItem']['SECTION_PAGE_URL'];
            $options['arItem']['~URL']  = $options['arItem']['SECTION_PAGE_URL'];
        elseif(isset($options['arItem']['DETAIL_PAGE_URL'])):
            $options['arItem']['URL']   = $this->arParams[$this->xguardSmUrlKey].$options['arItem']['DETAIL_PAGE_URL'];
            $options['arItem']['~URL']  = $options['arItem']['DETAIL_PAGE_URL'];
        elseif(isset($options['arItem']['PAGE_URL'])):
            $options['arItem']['URL']   = $this->arParams[$this->xguardSmUrlKey].$options['arItem']['PAGE_URL'];
            $options['arItem']['~URL']  = $options['arItem']['PAGE_URL'];
        else:
            $options['arItem']['URL']   = '';
            $options['arItem']['U~RL']  = '';
        endif;
    }

    protected function CheckPath($path)
    {
        static $SEARCH_MASKS_CACHE = false;

        if(!is_array($SEARCH_MASKS_CACHE))
        {
            $arSearch   = array("\\", ".",  "?", "*",   "'");
            $arReplace  = array("/",  "\\.", ".", ".*?", "\\'");
            $arFullExc  = array();
            $arExc      = array();
            $exc        = str_replace(
                $arSearch,
                $arReplace,
                \COption::GetOptionString($this->moduleId, $this->xguardSmExcludeMaskKey)
            );
            $arExcTmp   = explode(";", $exc);

            foreach($arExcTmp as $mask):
                $mask = trim($mask);

                if(strlen($mask)):
                    if(preg_match("#^/[a-z0-9_.\\\\]+/#i", $mask)):
                        $arFullExc[] = "'^".$mask."$'".BX_UTF_PCRE_MODIFIER;
                    else:
                        $arExc[] = "'^".$mask."$'".BX_UTF_PCRE_MODIFIER;
                    endif;
                endif;
            endforeach;

            $SEARCH_MASKS_CACHE = Array(
                "full_exc" => $arFullExc,
                "exc"=>$arExc,
            );
        }

        $file = end(explode('/', $path));

        if(strncmp($file, ".", 1)==0):
            return;
        endif;

        foreach($SEARCH_MASKS_CACHE["full_exc"] as $mask):
            if(preg_match($mask, $path)):
                return;
            endif;
        endforeach;

        foreach($SEARCH_MASKS_CACHE["exc"] as $mask):
            if(preg_match($mask, $path)):
                return;
            endif;
        endforeach;

        return true;
    }

    protected function GetTime($options=array())
    {
        $options['TIME'] = +$options['TIME']!==$options['TIME']?strtotime($options['TIME']):$options['TIME'];
        //$options['TIME'] = !$options['~TIME'] ? $options['TIME'] : $options['~TIME'];
        /*$iTZ = date("Z", $options['TIME']);
        $iTZHour = intval(abs($iTZ)/3600);
        $iTZMinutes = intval((abs($iTZ)-$iTZHour*3600)/60);
        $strTZ = ($iTZ<0? "-": "+").sprintf("%02d:%02d", $iTZHour, $iTZMinutes);

        return date("Y-m-d",$options['TIME'])."T".date("H:i:s",$options['TIME']).$strTZ;*/

        return date("c",$options['TIME']);
    }
}
?>
