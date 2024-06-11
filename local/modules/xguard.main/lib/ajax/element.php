<?
/**
 * xGuard Framework
 * @package xGuard
 * @subpackage main
 * @copyright 2014 xGuard
 */

namespace xGuard\Main\Ajax;

use \xGuard\Main;

/**
 * Base entity
 */

IncludeModuleLangFile(__FILE__);

class Element extends \xGuard\Main
{
    public function GetForm($options=array())
    {
        return $this->_GetForm($options);
    }

    public function GetBasket($options=array())
    {
        return $this->_GetBasket($options);
    }

    public function RemoveBasket($options=array())
    {
        return $this->_RemoveBasket($options);
    }

    public function AddBasket($options=array())
    {
        return $this->_AddBasket($options);
    }

    public function AddSubscribe($options=array())
    {
        return $this->_AddSubscribe($options);
    }

    public function RemoveSubscribe($options=array())
    {
        return $this->_RemoveSubscribe($options);
    }

    public function UpdateBasket($options=array())
    {
        return $this->_UpdateBasket($options);
    }

    public function SetGoDantistBasket($options=array())
    {
        return $this->_SetGoDantistBasket($options);
    }

    private function _GetForm($options=array())
    {
        $this->arParams['REQUEST']['t'] = isset($this->arParams['REQUEST']['t'])?$this->arParams['REQUEST']['t']:'';
        ob_start();
        $this->application->IncludeComponent("bitrix:form.result.new",$this->arParams['REQUEST']['t'],Array("SEF_MODE" => "N","WEB_FORM_ID" => $this->arParams['REQUEST']['WEB_FORM_ID'],	"LIST_URL" => "","EDIT_URL" => "","SUCCESS_URL" => "","CHAIN_ITEM_TEXT" => "","CHAIN_ITEM_LINK" => "","IGNORE_CUSTOM_TEMPLATE" => "N","USE_EXTENDED_ERRORS" => "Y","CACHE_TYPE" => "N","CACHE_TIME" => "360000","VARIABLE_ALIASES" => Array("WEB_FORM_ID" => "WEB_FORM_ID","RESULT_ID" => "RESULT_ID"),),false);
        $this->arResult['RESULT']['HTML'] = ob_get_contents();
        ob_end_clean();
    }

    private function _GetBasket($options=array())
    {
        $options = !is_array($options)?array():$options;

        if(isset($options['INTERNAL'])):
            $options['arResult']=&$this->arResult;
        endif;

        $vars = \xGuard\Main\Basket\Init::GetInstance(
            $options
        )->GetBasket(
            array_merge_recursive(
                $options,
                array(
                    'BASKET'=> array(
                        'ADDITIONAL' => array(
                            'Item',
                            'Section',
                            'Store',
                            'User',
                            'Catalog'
                        ),
                        'OPTIONS' => array(
                            'QUANTITY' => 'int'
                        ),
                    ),
                    'ITEMS' => array(
                        'GETLIST'   => array(
                            'SELECT'    => array(
                                'PREVIEW_PICTURE',
                            ),
                        ),
                    ),
                )
            )
        )->GetVars(array('arResult'));

        if(isset($options['INTERNAL'])):

        else:
            $this->arResult['RESULT'] = $vars['arResult'];
        endif;
    }

    private function _RemoveBasket($options=array())
    {
        $vars = \xGuard\Main\Basket\Init::GetInstance(
            array()
        )->Remove(
            array(
                'ID'    => $this->arParams['REQUEST']['id'],
            )
        )->GetBasket(
            array(
                'BASKET'=> array(
                    'ADDITIONAL' => array(
                        'Item',
                        'Section',
                        'Store',
                        'User'
                    ),
                    'OPTIONS' => array(
                        'QUANTITY' => 'int'
                    ),
                ),
                'ITEMS' => array(
                    'GETLIST'   => array(
                        'SELECT'    => array(
                            'PREVIEW_PICTURE',
                        ),
                    ),
                ),
            )
        )->GetVars(array('arResult'));

        $this->arResult['RESULT'] = $vars['arResult'];
    }

    private function _AddSubscribe($options=array())
    {
        try
        {
            $vars = \xGuard\Main\Basket\Init::GetInstance(
                array()
            )->AddSubscribe(
                array(
                    'ID'        => $this->arParams['REQUEST']['id'],
                    'QUANTITY'  => $this->arParams['REQUEST']['quantity'],
                    'URL'       => $this->arParams['REQUEST']['PRODUCT_URL'],
                    'NAME'      => $this->arParams['REQUEST']['PRODUCT_NAME'],
                )
            )->GetVars(array('arResult'));

            $this->arResult['RESULT'] = $vars['arResult'];
            $this->arResult['RESULT']['CLEAR_CONTENT'] = true;
        }
        catch(\xGuard\Main\Exception $e)
        {
            $this->arResult['RESULT']['ERRORS'][__LINE__] = $e->getMessage();
            $this->arResult['RESULT']['MESSAGE'] = $e->getMessage();
        }
    }

    private function _RemoveSubscribe($options=array())
    {
        try
        {
            $obBasket = \xGuard\Main\Basket\Init::GetInstance(
                array()
            );
            $bAnonymous = false;

            if(isset($this->arParams['REQUEST']['hash'])):
                list(
                    $this->arParams['REQUEST']['EMAIL'],
                    $this->arParams['REQUEST']['USER'],
                    $this->arParams['REQUEST']['id'],
                    $this->arParams['REQUEST']['quantity'],
                    $this->arParams['REQUEST']['PRODUCT_URL'],
                    $this->arParams['REQUEST']['PRODUCT_NAME']
                ) = explode('|',base64_decode($this->arParams['REQUEST']['hash']));
            
                $arUser = $this->user->GetById($this->arParams['REQUEST']['USER'])->Fetch();

                if($arUser['EMAIL']!==$this->arParams['REQUEST']['EMAIL']):
                    throw new \xGuard\Main\Exception(GetMessage('XGUARD_BASKET_DELETE_SUBSCRIBE_ERROR'),__LINE__);
                endif;

                $bAnonymous = true;
            else:
                $this->arParams['REQUEST']['USER'] = $this->user->GetId();
            endif;
                
            $vars = $obBasket->RemoveSubscribe(
                array(
                    'ID'        => $this->arParams['REQUEST']['id'],
                    'QUANTITY'  => $this->arParams['REQUEST']['quantity'],
                    'URL'       => $this->arParams['REQUEST']['PRODUCT_URL'],
                    'NAME'      => $this->arParams['REQUEST']['PRODUCT_NAME'],
                    'USER'      => $this->arParams['REQUEST']['USER'],
                )
            )->GetVars(array('arResult'));

            if($bAnonymous):
                echo GetMessage('XGUARD_BASKET_DELETE_SUBSCRIBE_SUCCESS');
                die;
            else:
                $this->arResult['RESULT'] = $vars['arResult'];
            endif;
        }
        catch(\xGuard\Main\Exception $e)
        {
            $this->arResult['RESULT']['ERRORS'][__LINE__] = $e->getMessage();
            $this->arResult['RESULT']['MESSAGE'] = $e->getMessage();
        }
    }

    private function _AddBasket($options=array())
    {
        if(!is_array($this->arParams['REQUEST']['id'])):
            $this->arParams['REQUEST']['id'] = array($this->arParams['REQUEST']['id']);
        endif;

        if(!is_array($this->arParams['REQUEST']['quantity'])):
            $this->arParams['REQUEST']['quantity'] = array_fill(0,count($this->arParams['REQUEST']['id']),$this->arParams['REQUEST']['quantity']);
        elseif(count($this->arParams['REQUEST']['quantity'])!=count($this->arParams['REQUEST']['id'])):
            $this->arParams['REQUEST']['quantity'] = array_fill(0,count($this->arParams['REQUEST']['id']),1);
        endif;

        $obBasket = \xGuard\Main\Basket\Init::GetInstance(
            array()
        );

        foreach($this->arParams['REQUEST']['id'] as $key=>$id):
            $obBasket->Add(
                array(
                    'ID'        => $id,
                    'QUANTITY'  => $this->arParams['REQUEST']['quantity'][$key],
                )
            );
        endforeach;

        $obBasket->GetBasket(
            array(
                'BASKET'=> array(
                    'ADDITIONAL' => array(
                        'Item',
                        'Section',
                        'Store',
                        'User'
                    ),
                    'OPTIONS' => array(
                        'QUANTITY' => 'int'
                    ),
                ),
                'ITEMS' => array(
                    'GETLIST'   => array(
                        'SELECT'    => array(
                            'PREVIEW_PICTURE',
                        ),
                    ),
                ),
            )
        );

        $vars = $obBasket->GetVars(array('arResult'));

        $this->arResult['RESULT'] = $vars['arResult'];;
    }

    public function GetSearch($options)
    {
        header("Content-Type: application/json");
        $this->arParams['REQUEST']['t'] = isset($this->arParams['REQUEST']['t'])?$this->arParams['REQUEST']['t']:'';
        $this->application->IncludeComponent("bitrix:search.page",$this->arParams['REQUEST']['t'], Array("COMPONENT_TEMPLATE" => ".default", "RESTART" => "N", "NO_WORD_LOGIC" => "N", "CHECK_DATES" => "Y", "USE_TITLE_RANK" => "Y", "DEFAULT_SORT" => "rank", "FILTER_NAME" => "", "arrFILTER" => array("no"), "SHOW_WHERE" => "Y", "arrWHERE" => array("iblock_CATALOG"), "SHOW_WHEN" => "N", "PAGE_RESULT_COUNT" => "50", "AJAX_MODE" => "N", "AJAX_OPTION_JUMP" => "N", "AJAX_OPTION_STYLE" => "Y", "AJAX_OPTION_HISTORY" => "N", "AJAX_OPTION_ADDITIONAL" => "", "CACHE_TYPE" => "A", "CACHE_TIME" => "3600", "USE_LANGUAGE_GUESS" => "Y", "USE_SUGGEST" => "N", "DISPLAY_TOP_PAGER" => "Y", "DISPLAY_BOTTOM_PAGER" => "Y", "PAGER_TITLE" => "", "PAGER_SHOW_ALWAYS" => "Y", "PAGER_TEMPLATE" => "",),false);
        $this->__parent->modeResult='externalJSON';
        $this->arResult['RESULT']='';
        return '';
    }

    public function _UpdateBasket($options=array())
    {
        $this->arResult['RESULT'] = \xGuard\Main\Basket\Init::GetInstance()->UpdateBasket(
            array(
                'BASKET'=>array(
                    'QUANTITY'=>$this->arParams['REQUEST']['quantity'],
                    'ID'=>$this->arParams['REQUEST']['id'],
                    'ADDITIONAL'=> array('Catalog'),
                    'GETLIST'   => array(
                        'SELECT'    => array(
                            'ID',
                        ),
                    ),
                ),
            )
        );
    }

    public function GetAccountsProperties($options=array())
    {
        if($this->arParams['REQUEST']['TYPE']=='USER_ID'):
            $user = new \CUser;
            $this->arParams['USER']['GET_LIST'] = array(
                'ORDER' => array(
                    'id',
                    'asc',
                ),
                'FILTER'    => array(
                    'ACTIVE'=>'Y',
                ),
            );

            $obItem = $user->GetList(
                $this->arParams['USER']['GET_LIST']['ORDER'][0],
                $this->arParams['USER']['GET_LIST']['ORDER'][1],
                $this->arParams['USER']['GET_LIST']['FILTER']
            );

            $this->arResult['RESULT']['DATA'] = array();
            $this->arResult['RESULT']['DATA']['TYPE']=$this->arParams['REQUEST']['TYPE'];
            $this->arResult['RESULT']['DATA']['ID']=$this->arParams['REQUEST']['ID'];
            $this->arResult['RESULT']['DATA']['PERSON_TYPE_ID']=$this->arParams['REQUEST']['PERSON_TYPE_ID'];
            $this->arResult['RESULT']['DATA'][$this->arParams['REQUEST']['TYPE']] = array();

            while($arItem = $obItem->Fetch()):

                $this->arResult['RESULT']['DATA'][$this->arParams['REQUEST']['TYPE']][] = array(
                    'FULL_NAME' => $arItem['LAST_NAME'].' '.$arItem['NAME'].' '.$arItem['SECOND_NAME'].' ['.$arItem['ID'].']',
                    'ID'        => $arItem['ID'],
                    'SELECT'    => $arItem['ID']==$this->arParams['REQUEST']['USER_ID'],
                );
            endwhile;
        else:

        endif;
    }

    public function GetProfile($options=array())
    {
        $options['type'] = isset($this->arParams['REQUEST']['type'])?$this->arParams['REQUEST']['type']:$options['type'];
        $options['type'] = empty($options['type'])?1:$options['type'];
        $options['id'] = isset($this->arParams['REQUEST']['id'])?$this->arParams['REQUEST']['id']:$options['id'];

        if(empty($options['id'])):
            return array();
        endif;

        $this->IncludeModule('sale');

        $this->arParams['SALE_ORDER_PROPERTIES']['GETLIST'] = array(
            'ORDER'  => array('SORT'=>'ASC'),
            'FILTER'  => array('ACTIVE'=>'Y','USER_PROPS'=>'Y'),
        );

        $nsItem = \CSaleOrderProps::GetList(
            $this->arParams['SALE_ORDER_PROPERTIES']['GETLIST']['ORDER'],
            $this->arParams['SALE_ORDER_PROPERTIES']['GETLIST']['FILTER']
        );

        while($arItem = $nsItem->Fetch()):
            $this->arResult['ORDER_PROPS'][] = $arItem['ID'];
            $this->arResult['ORDER_PROPS_ID'][$arItem['ID']] = $arItem['CODE'];
            $this->arResult['ORDER_PROPS_CODE'][$arItem['CODE']] = $arItem['ID'];
        endwhile;

        $this->arParams['SALE_ORDER_USER_PROPERTIES']['GETLIST'] = array(
            'ORDER'  => array('SORT'=>'ASC'),
            'FILTER'  => array("ORDER_PROPS_ID"=>$this->arResult['ORDER_PROPS'],"USER_PROPS_ID" => $options['id'],),
        );

        $nsItem = \CSaleOrderUserPropsValue::GetList(
            $this->arParams['SALE_ORDER_USER_PROPERTIES']['GETLIST']['ORDER'],
            $this->arParams['SALE_ORDER_USER_PROPERTIES']['GETLIST']['FILTER']
        );

        while($arItem = $nsItem->Fetch()):
            $ignoreGroup = constant('PERSON_GROUP_TYPE_HIDE_'.$arItem['PROP_PERSON_TYPE_ID']);

            if($ignoreGroup==$arItem['PROP_PROPS_GROUP_ID']):
                continue;
            endif;

            switch($options['type'])
            {
                case '1':
                default:
                    if(empty($this->arResult['RESULT']['DATA'])):
                        $this->arResult['RESULT']['DATA'][] = array(
                            'NAME'          => '',
                            'FIELD_NAME'    => 'PERSON_TYPE_ID',
                            'VALUE'         => $arItem['PROP_PERSON_TYPE_ID'],
                        );
                    endif;

                    $this->arResult['RESULT']['DATA'][] = array(
                        'NAME'          => $arItem['PROP_NAME'],
                        'FIELD_NAME'    => $arItem['PROP_CODE'],
                        'VALUE'         => $arItem['VALUE'],
                    );
                break;
                case '2':
                    $this->arResult['RESULT'][$arItem['PROP_CODE']] = $arItem['VALUE'];
                break;
                case '3':
                    $this->arResult['PROFILES'][$arItem['PROP_CODE']] = $arItem;
                    $this->arResult['PROFILE_CODE'][$arItem['PROP_CODE']] = $arItem['VALUE'];
                    $this->arResult['PROFILE_ID'][$arItem['ORDER_PROPS_ID']] = $arItem['VALUE'];
                break;
            }
        endwhile;

        if(isset($options['id'])):
            $this->arResult['RESULT']['USER_PROFILE_ID'] = $options['id'];
            $this->arResult['USER_PROFILE_ID'] = $options['id'];
        endif;

        switch($options['type'])
        {
            case '3':
                if(isset($this->arParams['REQUEST']['PERSON_TYPE_ID'])):
                    $this->arParams['SALE_ORDER_PROPERTIES_ADDITIONAL']['GETLIST'] = array(
                        'ORDER'  => array('SORT'=>'ASC'),
                        'FILTER'  => array('ACTIVE'=>'Y','USER_PROPS'=>'N','PERSON_TYPE_ID'=>$this->arParams['REQUEST']['PERSON_TYPE_ID']),
                    );

                    $nsItem = \CSaleOrderProps::GetList(
                        $this->arParams['SALE_ORDER_PROPERTIES_ADDITIONAL']['GETLIST']['ORDER'],
                        $this->arParams['SALE_ORDER_PROPERTIES_ADDITIONAL']['GETLIST']['FILTER']
                    );

                    while($arItem = $nsItem->Fetch()):
                        $this->arResult['PROFILES'][$arItem['CODE']] = array(
                            "PROP_NAME"         => $arItem['NAME'],
                            "PROP_CODE"         => $arItem["CODE"],
                            "ORDER_PROPS_ID"    => $arItem["ID"],
                            "VALUE"             => $this->arParams['REQUEST'][$arItem['CODE']],
                        );
                        $this->arResult['ORDER_PROPS_ID'][$arItem['ID']]        = $arItem['CODE'];
                        $this->arResult['ORDER_PROPS_CODE'][$arItem['CODE']]    = $arItem['ID'];
                        $this->arResult['PROFILE_CODE'][$arItem['CODE']]        = $this->arParams['REQUEST'][$arItem['CODE']];
                        $this->arResult['PROFILE_ID'][$arItem['ID']]            = $this->arParams['REQUEST'][$arItem['CODE']];
                    endwhile;
                endif;
            break;
        }
    }

    public function GetStores($options=array())
    {
        $options['ID'] = isset($options['ID']) ? $options['ID'] : false;

        $this->arParams['STORES']['ORDER'] = array();
        $this->arParams['STORES']['FILTER'] = array(
            "LID"       => SITE_ID,
            "ACTIVE"    => "Y",
            'ID'        => $options['ID']
        );

        $dbDelivery = \CCatalogStore::GetList(
            $this->arParams['STORES']['ORDER'],
            $this->arParams['STORES']['FILTER']
        );

        while ($arDelivery = $dbDelivery->Fetch()):
            $this->arResult['STORES'][$arDelivery['ID']] = $arDelivery;
            $this->arResult['STORES'][$arDelivery['XML_ID']] = $arDelivery;
        endwhile;
    }

    public function GetDelivery($options=array())
    {
        $options['ID'] = isset($options['ID']) ? $options['ID'] : false;

        $this->arParams['DELIVERY']['ORDER'] = array();
        $this->arParams['DELIVERY']['FILTER'] = array(
            "LID"       => SITE_ID,
            "ACTIVE"    => "Y",
            'ID'        => $options['ID']
        );

        $dbDelivery = \CSaleDelivery::GetList(
            $this->arParams['DELIVERY']['ORDER'],
            $this->arParams['DELIVERY']['FILTER']
        );

        while ($arDelivery = $dbDelivery->Fetch()):
            $this->arResult['DELIVERY'][$arDelivery['ID']] = $arDelivery;

            if(!empty($arDelivery['DESCRIPTION'])):
                $this->arResult['DELIVERY'][$arDelivery['DESCRIPTION']] = $arDelivery;
            endif;
        endwhile;
    }

    public function GetPaySystem($options=array())
    {
        $options['PROFILE_ID'] = $options['PROFILE_ID']?$options['PROFILE_ID']:$this->arParams['REQUEST']['PROFILE_ID'];
        $options['DELIVERY_TO_PAYSYSTEM'] = $options['DELIVERY_TO_PAYSYSTEM']?$options['DELIVERY_TO_PAYSYSTEM']:$this->arParams['REQUEST']['DELIVERY_TO_PAYSYSTEM'];
        $options['PAY_SYSTEM_ID'] = $options['PAY_SYSTEM_ID']?$options['PAY_SYSTEM_ID']:$this->arParams['REQUEST']['PAY_SYSTEM_ID'];
        $options['DELIVERY_ID'] = $options['DELIVERY_ID']?$options['DELIVERY_ID']:$this->arParams['REQUEST']['DELIVERY_ID'];

        $this->IncludeModule('sale');

        $this->arResult['RESULT']['ERRORS'] = array();

        $this->arParams['ORDER_USER_PROPS']['ORDER']     = array(
            "DATE_UPDATE" => "ASC",
        );
        $this->arParams['ORDER_USER_PROPS']['FILTER']    = array(
            "USER_ID"   => (int) $this->user->GetID(),
            "ID"        => $options['PROFILE_ID'],
        );

        $arUserProfiles = \CSaleOrderUserProps::GetList(
            $this->arParams['ORDER_USER_PROPS']['ORDER'],
            $this->arParams['ORDER_USER_PROPS']['FILTER']
        )->Fetch($this->arParams['ORDER_USER_PROPS']);

        if(empty($arUserProfiles)&&$this->user->IsAuthorized())
        {
            //return ($this->arResult['RESULT']['ERRORS'][__LINE__] = '#'.__LINE__.': '.GetMessage('USER_PROPS_NOT_FOUND'));
        }

        $dbRes = \CSaleDelivery::GetDelivery2PaySystem(array());

        while ($arRes = $dbRes->Fetch()):
            $this->arResult['RESULT']['DELIVERY']['D2P'][$arRes["DELIVERY_ID"]][$arRes["PAYSYSTEM_ID"]] = $arRes["PAYSYSTEM_ID"];
            $this->arResult['RESULT']['DELIVERY']['P2D'][$arRes["PAYSYSTEM_ID"]][$arRes["DELIVERY_ID"]] = $arRes["DELIVERY_ID"];
        endwhile;

        if(isset($options['DELIVERY_TO_PAYSYSTEM'])):
            if($options['DELIVERY_TO_PAYSYSTEM']=='p2d'):
                unset($this->arResult['RESULT']['DELIVERY']['D2P']);
                $this->arResult['RESULT']['DELIVERY'] = $this->arResult['RESULT']['DELIVERY']['P2D'][$options['PAY_SYSTEM_ID']];
            else:
                unset($this->arResult['RESULT']['DELIVERY']['P2D']);
                $this->arResult['RESULT']['DELIVERY'] = $this->arResult['RESULT']['DELIVERY']['D2P'][$options['DELIVERY_ID']];
            endif;
        endif;

        $this->arParams['PAY_SYSTEM']['ORDER']     = array(
            "SORT"      => "ASC",
            "PSA_NAME"  => "ASC",
        );

        $this->arParams['PAY_SYSTEM']['FILTER']    = array(
            "ACTIVE"            => "Y",
            "PERSON_TYPE_ID"    => $arUserProfiles["PERSON_TYPE_ID"],
            "PSA_HAVE_PAYMENT"  => "Y",
        );

        $dbPaySystem = \CSalePaySystem::GetList(
            $this->arParams['PAY_SYSTEM']['ORDER'],
            $this->arParams['PAY_SYSTEM']['FILTER']
        );

        $bFirst=true;

        $this->arResult['RESULT']['DATA'] = array();

        while ($arPaySystem = $dbPaySystem->Fetch()):
            if(isset($options['DELIVERY_TO_PAYSYSTEM'])&&!isset($this->arResult['RESULT']['DELIVERY'][$arPaySystem['ID']]))
            {
                continue;
            }

            if (IntVal($this->arResult['USER']["PAY_SYSTEM_ID"]) <= 0 && $bFirst):
                $arPaySystem["CHECKED"] = "Y";
            endif;

            $arPaySystem['PSA_PARAMS'] = unserialize($arPaySystem['PSA_PARAMS']);

            if(
                !\xGuard\Main\Soap\Params::CheckPaySystem(
                    array(
                        'arPaySystem'     => &$arPaySystem,
                    )
                )
            ):
                unset($arPaySystem);
            endif;
            
            if(
                CheckPaymentAvailable()
                &&
                !stristr($arPaySystem['DESCRIPTION'],PAY_SYSTEM_NON_CASH_CODE)
                &&
                !stristr($arPaySystem['DESCRIPTION'],PAY_SYSTEM_CASH_CODE)
            ):
                unset($arPaySystem);
            endif;

            if($arPaySystem):
                $this->arResult['RESULT']['DATA'][] = array('ID'=>$arPaySystem['ID'],'NAME'=>$arPaySystem['NAME']);
                $this->arResult['PAYMENT'][$arPaySystem['ID']] = $arPaySystem;
                $bFirst = false;
            endif;

        endwhile;

        unset($this->arResult['RESULT']['DELIVERY']);
    }

    public function GetStatus($options=array())
    {
        $options['ID'] = isset($options['ID']) ? $options['ID'] : false;

        $this->arParams['STATUS']['ORDER'] = array();
        $this->arParams['STATUS']['FILTER'] = array(
           // "LID"       => SITE_ID,
            "ACTIVE"    => "Y",
            //'ID'        => $options['ID']
        );

        //$this->arParams['STATUS']['FILTER']

        $dbItem = \CSaleStatus::GetList(
            $this->arParams['STATUS']['ORDER'],
            $this->arParams['STATUS']['FILTER']
        );

        while ($arItem = $dbItem->Fetch()):
            $this->arResult['STATUS'][$arItem['ID']] = $arItem;
        endwhile;
    }

    public function GetForwarding($options=array())
    {
        $ch = curl_init($this->arParams['REQUEST']['url']);

        $headers = array(
            //"Cache-Control: no-cache",
            //"Pragma: no-cache",
            'Connection: Keep-Alive',
            'Content-Type: text/xml;charset="utf-8"'
        );

        $arParams = array(
            CURLOPT_RETURNTRANSFER      => true,
            CURLOPT_POST                => true,
            CURLOPT_POSTFIELDS          => $_POST,
            CURLOPT_HTTP_VERSION        => CURL_HTTP_VERSION_1_1,
            CURLOPT_FAILONERROR         => true,
            CURLOPT_HTTPAUTH            => CURLAUTH_NTLM,
            CURLOPT_USERPWD             => SOAP_NTLM_LOGIN.':'.SOAP_NTLM_PASSWORD,
            CURLOPT_SSLVERSION          => 3,
            CURLOPT_SSL_VERIFYPEER      => false,
            CURLOPT_SSL_VERIFYHOST      => 2,
            CURLOPT_VERBOSE             => true,
            CURLOPT_FRESH_CONNECT       => true,
            CURLOPT_HTTPHEADER          => $headers,
            CURLOPT_CERTINFO            => true,
            CURLOPT_CONNECTTIMEOUT      => SOAP_NTLM_CONNECTION_TIMEOUT,
            CURLOPT_TIMEOUT             => SOAP_NTLM_TIMEOUT,
        );
        foreach($arParams as $keyParam=>$valueParam):
            curl_setopt($ch,$keyParam,$valueParam);
        endforeach;
        //curl_setopt_array($ch,$arParams);

        $response = curl_exec($ch);

        if (curl_errno($ch)):
            print "Error: " . curl_error($ch);
        else:
            header("Content-Type: text/xml");
            echo $response;
        endif;
        die;
    }

    public function AddOrder($options=array())
    {
        try
        {
            //\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('sale', 'onOrderNewSendEmail');

            $options['INTERNAL']=true;

            $this->GetBasket($options);
            $this->GetDelivery(
                array(
                    'ID'    => $this->arParams['REQUEST']['DELIVERY_ID'],
                )
            );
            $this->GetPaySystem(
                array(
                    'PROFILE_ID'    => $this->arParams['REQUEST']['PROFILE_ID'],
                    'DELIVERY_TO_PAYSYSTEM' => $this->arParams['REQUEST']['DELIVERY_TO_PAYSYSTEM'],
                    'PAY_SYSTEM_ID' => $this->arParams['REQUEST']['PAY_SYSTEM_ID'],
                    'DELIVERY_ID' => $this->arParams['REQUEST']['DELIVERY_ID'],
                )
            );
            $this->GetProfile(
                array(
                    'id'    => $this->arParams['REQUEST']['PROFILE_ID'],
                    'type'  => 3,
                )
            );

            $this->GetStatus();

            $this->arResult['STORE_ID'] = $this->arParams['REQUEST']['STORE_ID'];

            if(!empty($this->arResult['STORE_ID'])):
                $this->GetStores(
                    array(
                        'ID'=>$this->arResult['STORE_ID'],
                    )
                );
            endif;

            $arBasket = array();

            if(isset($this->arResult['STORE_ID'])):
                $arBasket[]=array(ELEMENT_PROP_ARTICLE=>$this->arResult['STORES'][$this->arResult['STORE_ID']]['XML_ID'],ELEMENT_PROP_QUANTITY=>1);
            else:
                $arDelivery = \CIBlockElement::GetList(
                    array(),
                    array('IBLOCK_ID'=>CATALOG_IBLOCK_ID,
                        '%NAME'=>$this->arResult['DELIVERY'][$this->arParams['REQUEST']['DELIVERY_ID']]['NAME'],
                        ),
                    false,
                    false,
                    array(
                        'PROPERTY_'.ELEMENT_PROP_ARTICLE,
                    )
                )->Fetch();
                $arBasket[]=array(ELEMENT_PROP_ARTICLE=>$arDelivery['PROPERTY_'.ELEMENT_PROP_ARTICLE.'_VALUE'],ELEMENT_PROP_QUANTITY=>1);
            endif;

            $GUIDSogl = '';

            foreach($this->arResult['BASKET'] as $arItem):
                $arBasket[] = array(
                    ELEMENT_PROP_ARTICLE => $arItem['PROPS'][ELEMENT_PROP_ARTICLE]['VALUE'],
                    ELEMENT_PROP_QUANTITY => $arItem['QUANTITY'],
                );

                $GUIDSogl = $arItem['NOTES'];
            endforeach;

            $this->arParams['REQUEST']['EDIT'] = isset($this->arParams['REQUEST']['cart']) ? $this->arParams['REQUEST']['cart'] : 1;
            //$this->arParams['REQUEST']['EDIT'] = true;

            $this->arResult['USER'] = array(
                'PERSON_TYPE_ID'=>$this->arParams['REQUEST']['PERSON_TYPE_ID'],
                "ORDER_PROP"=>$this->arResult['PROFILE_ID'],
                "DELIVERY_ID"=>$this->arParams['REQUEST']['DELIVERY_ID'],
                "PAY_SYSTEM_ID"=>$this->arParams['REQUEST']['PAY_SYSTEM_ID'],
            );

            $arFields = array(
                'DATE_INSERT'       => str_replace(date('P',time()),'',date('c',time())),
                "INN"               => $this->arResult['PROFILE_CODE']['INN'],
                "KPP"               => $this->arResult['PROFILE_CODE']['KPP'],
                'CONTACT_PERSON'    => array(
                    'CONTACT_NAME'      => $this->arResult['PROFILE_CODE']['CONTACT_NAME'],
                    'CONTACT_EMAIL'     => $this->arResult['PROFILE_CODE']['CONTACT_EMAIL'],
                    'CONTACT_PHONE'     => $this->arResult['PROFILE_CODE']['CONTACT_PHONE'],
                    'CONTACT_POSITION'  => $this->arResult['PROFILE_CODE']['CONTACT_POSITION'],
                ),
                "BASKET"            => $arBasket,
                //'DELIVERY_ID'     => $this->arParams['REQUEST']['DELIVERY_ID'],
                "ADDRESS_DELIVERY"  => isset($this->arParams['REQUEST']['DELIVERY_ADDRESS'])&&!empty($this->arParams['REQUEST']['DELIVERY_ADDRESS'])?$this->arParams['REQUEST']['DELIVERY_ADDRESS']:$this->arResult['PROFILE_CODE']['ADDRESS_DELIVERY'],
                "TIME_DELIVERY"     => $this->arResult['PROFILE_CODE']['TIME_DELIVERY'],
                "CONTACT_DELIVERY"  => isset($this->arParams['REQUEST']['DELIVERY_CONTACT'])&&!empty($this->arParams['REQUEST']['DELIVERY_CONTACT'])&&isset($this->arParams['REQUEST']['DELIVERY_PHONE'])&&!empty($this->arParams['REQUEST']['DELIVERY_PHONE'])?$this->arParams['REQUEST']['DELIVERY_CONTACT'].' '.$this->arParams['REQUEST']['DELIVERY_PHONE']:$this->arResult['PROFILE_CODE']['CONTACT_DELIVERY'],
                'EDIT'              => $this->arParams['REQUEST']['EDIT'],
                "POINTS"            => $this->arParams['REQUEST']['POINTS'],
                "USER_EMAIL"        => $this->user->GetEmail(),
                "AID_IN_BASKET"     => $this->arResult['AID_IN_BASKET']>0,
                "PAY_SYSTEM_ID"     => $this->arResult['PAYMENT'][$this->arParams['REQUEST']['PAY_SYSTEM_ID']]['DESCRIPTION'],
                "EMAIL"             => $this->arResult['PROFILE_CODE']['EMAIL'],
                "CUSTOMER_GUID"     => $this->arResult['PROFILE_CODE']['GUID'],
                'GUID_SOGL'         => $GUIDSogl,
            );

            if(isset($_SESSION['ORDER_ACCOUNT_NUMBER'])):
                $arFields['GUID'] = $_SESSION['ORDER_GUID'];
            endif;

            //
            //$this->arResult['RESULT']['DATA']['DEBUG'][__LINE__] = $arFields;
            $arFields = \xGuard\Main\Soap\Params::Prepare(
                array(
                    'VALUE' => $arFields,
                    'PARAMS'    => array(
                        'NOT_EMPTY' => true,
                        'ACTION'    => 'CreateOrder',
                    ),
                )
            );

            $statusDelivery = constant('STATUS_ID_FOR_CALC_PRICE_DELIVERY_'.$this->arResult['USER']['DELIVERY_ID']);

            $this->arResult['ORDER']['STATUS_ID'] = $this->arResult['USER']['STATUS_ID'] = !empty($statusDelivery)?$statusDelivery:STATUS_ID_START;

            $soap = \xGuard\Main\Soap\Params::GetSoapInstance();

            $result = $soap->CreateOrder(
                array(
                    'param1' => $arFields,
                )
            );
            //$this->arResult['RESULT']['DATA']['DEBUG'][__LINE__] = $arFields;

            if($result->return->Status):

                $this->arResult['RESULT']['DATA']['ORDER'] = (array)$result->return->ClientOrderOut;
                $this->arResult['RESULT']['DATA']['ITEMS'] = (array)$result->return->ClientOrderOut->Commodity;
                //$this->arResult['RESULT']['DATA']['DEBUG'][__LINE__] = $result->return;
                //$this->arResult['RESULT']['DATA']['DEBUG'][__LINE__] = $arFields;
                //$this->arResult['RESULT']['DATA']['DEBUG'][__LINE__] = $this->arResult['ITEMS_TO_BASKET_BY_ARTICLE'];
                //$this->arResult['RESULT']['DATA']['DEBUG'][__LINE__] = $this->arResult['DELIVERY'];

                unset($this->arResult['RESULT']['DATA']['ORDER']['Commodity']);

                //$this->arResult['RESULT']['DATA']['DEBUG'][__LINE__]=!is_array($this->arResult['RESULT']['DATA']['ITEMS']);

                if(!is_array($this->arResult['RESULT']['DATA']['ITEMS'])):
                    $this->arResult['RESULT']['DATA']['ITEMS'] = array(
                        array('SumTotal'=>0),
                        $this->arResult['RESULT']['DATA']['ITEMS']
                    );
                else:
                    reset($this->arResult['RESULT']['DATA']['ITEMS']);

                    $probablyDelivery=current($this->arResult['RESULT']['DATA']['ITEMS']);

                    if(!is_array($probablyDelivery)&&!is_object($probablyDelivery)):
                        $this->arResult['RESULT']['DATA']['ITEMS'] = array($this->arResult['RESULT']['DATA']['ITEMS']);
                        $probablyDelivery=current($this->arResult['RESULT']['DATA']['ITEMS']);
                    endif;

                   // $this->arResult['RESULT']['DATA']['DEBUG'][__LINE__]=$this->arResult['RESULT']['DATA']['ITEMS'];
                    //$this->arResult['RESULT']['DATA']['DEBUG'][__LINE__]=$probablyDelivery->Article;
                    //$this->arResult['RESULT']['DATA']['DEBUG'][__LINE__]=$probablyDelivery;
                    //$this->arResult['RESULT']['DATA']['DEBUG'][__LINE__]=!isset($this->arResult['DELIVERY'][$probablyDelivery->Article])
                    //    &&
                    //    !isset($this->arResult['STORES'][$probablyDelivery->Article]);

                    if(
                        !isset($this->arResult['DELIVERY'][$probablyDelivery->Article])
                        &&
                        !isset($this->arResult['STORES'][$probablyDelivery->Article])
                    ):
                        reset($this->arResult['BASKET']);
                        $basketDelivery = current($this->arResult['BASKET']);

                        $this->arResult['RESULT']['DATA']['ITEMS']=array_pad(
                            $this->arResult['RESULT']['DATA']['ITEMS'],
                            -(count($this->arResult['RESULT']['DATA']['ITEMS'])+1),
                            array(
                                'Article'       => $basketDelivery[ELEMENT_PROP_ARTICLE],
                                'SumTotal'      => 0,
                                'SumDiscount'   => 0
                            ));
                    endif;
                    //$this->arResult['RESULT']['DATA']['DEBUG'][__LINE__]=$basketDelivery;
                    //$this->arResult['RESULT']['DATA']['DEBUG'][__LINE__]=$probablyDelivery;
                    //$this->arResult['RESULT']['DATA']['DEBUG'][__LINE__]=$this->arResult['RESULT']['DATA']['ITEMS'];
                endif;

                $this->arResult['RESULT']['DATA']['BASKET'] = &$this->arResult['BASKET'];
                ///$this->arResult['RESULT']['DATA']['ORDER']=0;

                foreach($this->arResult['RESULT']['DATA']['ITEMS'] as &$arBasket):
                    $arBasket = (array)$arBasket;
                    $keyBasket = $this->arResult['ITEMS_TO_BASKET_BY_ARTICLE'][$arBasket['Article']];
                    if(!empty($keyBasket)):
                        $this->arResult['BASKET'][$keyBasket]['PRICE'] = $arBasket['PriceEnd'];
                        $this->arResult['BASKET'][$keyBasket]['DISCOUNT_PRICE'] = $arBasket['SumDiscount'];
                    endif;
                endforeach;

                $this->arResult['ORDER']['PRICE_DELIVERY'] = $this->arResult['RESULT']['DATA']['ITEMS'][0]['SumTotal'];
                $this->arResult['ORDER']['DELIVERY_PRICE'] = $this->arResult['ORDER']['PRICE_DELIVERY'];

                $this->arResult['ORDER'] = array_merge($this->arResult['RESULT']['DATA']['ORDER'],$this->arResult['ORDER']);

                $_SESSION['ORDER_GUID'] = $this->arResult['RESULT']['DATA']['ORDER']['GUID'];
                $_SESSION['ORDER_ACCOUNT_NUMBER'] = $this->arResult['RESULT']['DATA']['ORDER']['Number'];

                $this->arResult['ORDER']['TOTAL_PRICE']             = $this->arResult['ORDER']['SumTotal'];
                $this->arResult['ORDER']['SCORES']                  = $this->arResult['ORDER']['PointsDiscount'];
                $this->arResult['ORDER']['DISCOUNT_VALUE']          = $this->arResult['ORDER']['SumDiscount'];
                $this->arResult['ORDER']['SCORES_IN']               = $this->arResult['ORDER']['PointsCount'];

                \xGuard\Main\Basket\Init::GetInstance(array(
                    'arResult'=>&$this->arResult,
                ))->CalcBasket();

                if(+$this->arResult['ORDER']['PRICE']<+$this->arResult['ORDER']['DISCOUNT_VALUE']):
                    $this->arResult['ORDER']['TOTAL_PRICE']                 = $this->arResult['ORDER']['DELIVERY_PRICE'];
                    $this->arResult['ORDER']['FORMAT_TOTAL_PRICE']          = $this->arResult['ORDER']['DELIVERY_PRICE'];
                    $this->arResult['ORDER']['DISCOUNT_VALUE']              = $this->arResult['ORDER']['PRICE'];
                    $this->arResult['ORDER']['FORMAT_DISCOUNT_VALUE']       = $this->arResult['ORDER']['FORMAT_PRICE'];
                    $this->arResult['ORDER']['FULL_DISCOUNT_PRICE']         = 0;
                    $this->arResult['ORDER']['FORMAT_FULL_DISCOUNT_PRICE']  = 0;
                    $this->arResult['ORDER']['SCORES']                      = 0;
                    $this->arResult['ORDER']['SCORES_IN']                   = 0;
                endif;

                $this->arResult['RESULT']['DATA']['ORDER'] = $this->arResult['ORDER'];
                if(!+($this->arParams['REQUEST']['EDIT'])):
                    foreach($this->arResult['BASKET'] as $arBasket):
                        $arBasket['CUSTOM_PRICE'] = 'Y';

                        \CSaleBasket::Update($arBasket['ID'],$arBasket);
                    endforeach;

                    $this->arResult['RESULT']['DATA']['ACCOUNT_NUMBER']=$this->arResult['PROFILES']['ID_1C']['VALUE']=$this->arResult['ORDER']['ID_1C']=$_SESSION['ORDER_ACCOUNT_NUMBER'];
                    $this->arResult['RESULT']['DATA']['XML_ID']=$this->arResult['ORDER']['XML_ID']=$_SESSION['ORDER_GUID'];

                    unset($_SESSION['ORDER_GUID'],$_SESSION['ORDER_ACCOUNT_NUMBER']);

                    //$this->arResult['ORDER']['PRICE_DELIVERY'] = +$this->arResult['RESULT']['DATA']['ITEMS'][0]->SumTotal;

                    $obOrder = \xGuard\Main\Order\Init::GetInstance(
                        array(
                            'arResult'=>&$this->arResult,
                        )
                    );

                    $obOrder->Add(
                        array(

                        )
                    );

                    $fileName = '';

                    if(!empty($result->return->File)):
                        $fileName=$_SERVER['DOCUMENT_ROOT'].URL_INVOICE.$this->arResult['ORDER']['XML_ID'].'.pdf';

                        file_put_contents($fileName,$result->return->File);
                    endif;
                    //debugfile(array($this->arResult['PROFILES'],$this->arResult['BASKET'],$this->arResult['ORDER']),'order.log');
                    //\CSaleOrder::DeliverOrder($this->arResult['ORDER']['ID'], "Y");
                    foreach($this->arResult['PROFILES'] as $key=>$arProperty):
                        \CSaleOrderPropsValue::Add(
                            array(
                                "NAME"              => $arProperty["PROP_NAME"],
                                "CODE"              => $arProperty["PROP_CODE"],
                                "ORDER_PROPS_ID"    => $arProperty["ORDER_PROPS_ID"],
                                "ORDER_ID"          => $this->arResult['ORDER']['ORDER_ID'],
                                "VALUE"             => $arProperty['VALUE'],
                            )
                        );
                    endforeach;

                    $strOrderList   = '
                        <table border="1" cellspacing="0" cellpadding="5">
                            <tr>
                                <td bgcolor="#a0a0a0">'.GetMessage('XGUARD_MAIL_SALE_ORDER_NEW_ARTICLE').'</td>
                                <td bgcolor="#a0a0a0">'.GetMessage('XGUARD_MAIL_SALE_ORDER_NEW_NAME').'</td>
                                <td bgcolor="#a0a0a0">'.GetMessage('XGUARD_MAIL_SALE_ORDER_NEW_QUANTITY').'</td>
                                <td bgcolor="#a0a0a0">'.GetMessage('XGUARD_MAIL_SALE_ORDER_NEW_PRICE').'</td>
                                <td bgcolor="#a0a0a0">'.GetMessage('XGUARD_MAIL_SALE_ORDER_NEW_TOTAL_PRICE').'</td>
                                </tr>';
                    foreach ($this->arResult['BASKET'] as $arItem):
                        $measureText = (isset($arItem["MEASURE_TEXT"]) && strlen($arItem["MEASURE_TEXT"])) ? $arItem["MEASURE_TEXT"] : \GetMessage("XGUARD_BASKET_SHT");

                        $strOrderList .= '<tr><td>'.implode('</td><td>',array(
                            $arItem['PROPS'][ELEMENT_PROP_ARTICLE]['VALUE'],
                            $arItem["NAME"],
                            $arItem["QUANTITY"]." ".$measureText,
                            \SaleFormatCurrency($arItem["PRICE"]-($arItem["DISCOUNT_PRICE"]/$arItem["QUANTITY"]), $arItem["CURRENCY"]),
                            \SaleFormatCurrency($arItem["FULL_DISCOUNT_PRICE"], $arItem["CURRENCY"]),
                        )).'</td></tr>'."\n";
                    endforeach;
                    /*
                    $strOrderList .= '<td>'.implode('</td><td>',array(
                            '',//$this->arResult['RESULT']['DATA']['ITEMS'][0]['Article'],
                            $this->arResult['DELIVERY'][$this->arParams['REQUEST']['DELIVERY_ID']]['NAME'].(!empty($this->arResult['STORE_ID'])?$this->arResult['STORES'][$this->arResult['STORE_ID']]['NAME']:''),
                            '',
                            \SaleFormatCurrency($this->arResult['RESULT']['DATA']['ITEMS'][0]['SumTotal'], $arItem["CURRENCY"]),
                            \SaleFormatCurrency($this->arResult['RESULT']['DATA']['ITEMS'][0]['SumTotal'], $arItem["CURRENCY"]),
                        )).'</td>'."\n";*/

                    $strOrderList .= '</tr></table>';

                    $arFields = array(
                        "ORDER_ID"          => $this->arResult['ORDER']["ID_1C"],
                        "ORDER_DATE"        => \Date($this->db->DateFormatToPHP(\CLang::GetDateFormat("SHORT", SITE_ID))),
                        "ORDER_USER"        => ( (strlen($this->arResult['PROFILES']['FULL_NAME']) > 0) ? $this->arResult['PROFILES']['FULL_NAME'] : $this->user->GetFormattedName(false)),
                        //"USER_NAME"         => $this->user->GetFirstName(),
                        //"USER_LAST_NAME"    => $this->user->GetLastName(),
                        "PRICE"             => \SaleFormatCurrency($this->arResult['RESULT']['DATA']['ORDER']['TOTAL_PRICE'], $this->arResult['ORDER']["CURRENCY"]),
                        "BCC"               => \COption::GetOptionString("sale", "order_email", "order@".$_SERVER['SERVER_NAME']),
                        "EMAIL"             => (strlen($this->arResult['USER']["USER_EMAIL"])>0 ? $this->arResult['USER']["USER_EMAIL"] : $this->user->GetEmail()),
                        "ORDER_LIST"        => $strOrderList,
                        "SALE_EMAIL"        => \COption::GetOptionString("sale", "order_email", "order@".$_SERVER['SERVER_NAME']),
                        "DELIVERY_PRICE"    => empty($this->arResult['ORDER']["PRICE_DELIVERY"])||+$this->arResult['ORDER']["PRICE_DELIVERY"]<=0?GetMessage('XGUARD_MAIL_SALE_ORDER_NEW_EMPTY_DELIVERY_PRICE'):SaleFormatCurrency($this->arResult['ORDER']["PRICE_DELIVERY"], $this->arResult['ORDER']["CURRENCY"]),
                        "BONUS"             => $result->return->ClientOrderOut->PointsCount,
                        "DELIVERY_NAME"     => $this->arResult['DELIVERY'][$this->arParams['REQUEST']['DELIVERY_ID']]['NAME'].(!empty($this->arResult['STORE_ID'])?', '.$this->arResult['STORES'][$this->arResult['STORE_ID']]['NAME']:''),
                        "PAY_SYSTEM_NAME"   => $this->arResult['PAYMENT'][$this->arParams['REQUEST']['PAY_SYSTEM_ID']]['NAME'],
                    );

                    $eventName = "SALE_NEW_ORDER_2";

                    $bSend = true;

                    /*foreach(\GetModuleEvents("sale", "OnOrderNewSendEmail", true) as $arEvent):
                        if (\ExecuteModuleEventEx($arEvent, array($this->arResult['ORDER']["ID"], &$eventName, &$arFields))===false):
                            $bSend = false;
                        endif;
                    endforeach;*/

                    if($bSend):
                        \Bitrix\Main\Mail\Event::sendImmediate(
                            array(
                                'MESSAGE_ID'    => 122,
                                "EVENT_NAME"    => $eventName,
                                "LID"           => SITE_ID,
                                "C_FIELDS"      => $arFields,
                            )
                        );
                        if(
                            $this->arResult['ORDER']['STATUS_ID']!==STATUS_ID_FOR_CALC_PRICE_DELIVERY
                        ):
                            /*$arFields['SOAP']       = array(
                                'WSDL'      => 'https://www.dentlman.ru/private/webservices/mail/?wsdl',
                                'OPTIONS'   => array(),
                            );*/
                            //$arFields['FILES']      = $fileName;
                            //$arFields['NAME']       = basename($fileName);
                            //$arFields['RECIPIENT']  = $arFields['EMAIL'];
                            $arFields['BCC']        = 'support@dentlman.ru';
                            /*$arFields['MAIL']       = array(
                                'GETLIST'   => array(
                                    'FILTER'    => array(
                                        'ID'    => 95,
                                    ),
                                ),
                            );*/
                            //\xGuard\Main\Order\Mail::Send($arFields);
                            \Bitrix\Main\Mail\Event::send(
                                array(
                                    "EVENT_NAME"    => "SALE_STATUS_CHANGED_C_FILE",
                                    "LID"           => SITE_ID,
                                    'FILE'          => array(
                                        'Счет на оплату'    => $fileName,
                                    ),
                                    "C_FIELDS"      => $arFields,
                                )
                            );
                        endif;
                    endif;

                    $result = $soap->SetStatus(
                        array(
                            'GUID'      => $this->arResult['ORDER']['XML_ID'],
                            'ID_Status' => $this->arResult['STATUS'][$this->arResult['ORDER']['STATUS_ID']]['SORT'],
                        )
                    );
                    
                    if(!$result->return->Status):

                    endif;
                else:

                endif;

            else:
                $this->arResult['RESULT']['ERRORS'][__LINE__]   = $result->return->ErrorList->Error;
                    if(!empty($this->arResult['RESULT']['ERRORS'])):
                        $this->arResult['RESULT']['MESSAGE']=array();

                        foreach($this->arResult['RESULT']['ERRORS'] as $arErrors):
                            foreach($arErrors as $arError):
                                $arError = (array)$arError;
                                $this->arResult['RESULT']['MESSAGE'][] = implode('; ',$arError);
                            endforeach;
                        endforeach;

                        $this->arResult['RESULT']['MESSAGE'] = implode('; ',$this->arResult['RESULT']['MESSAGE']);
                    endif;
                $this->arResult['RESULT']['ERRORS']['HIDE'] = true;
                //$this->arResult['RESULT']['DEBUG'] = $this->arResult['ITEMS_TO_BASKET_BY_ARTICLE'];
            endif;
        }
        catch(\xGuard\Main\Exception $e)
        {
            $this->arResult['RESULT']['ERRORS'][__LINE__] = '#'.__LINE__.' '.$e->getMessage();
            $this->arResult['RESULT']['MESSAGE'] = $e->getMessage();
            //$this->arResult['RESULT']['DEBUG'] = $this->arResult['ITEMS_TO_BASKET_BY_ARTICLE'];
        }
        //debugfile($this->arResult['RESULT'],'order.log');
    }

    private function _SetGoDantistBasket($options=array())
    {
        if(isset($_GET['utm_source'])):
            foreach($_GET as $key=>$value):
                if(!is_array($value)):
                    $_SESSION[$key] = htmlspecialchars(urldecode($value));
                endif;
            endforeach;
        endif;
        
        \xGuard\Main\Basket\Init::GetInstance(array())->Remove();

        $this->AddBasket($options);
        
        LocalRedirect(URL_BASKET_PATH,false);
    }

    public function GetManufacturer($options=array())
    {
        $obCache = \Bitrix\Main\Data\Cache::createInstance();

        $id     = SITE_ID.__FUNCTION__.__LINE__.serialize($this->arParams['REQUEST']).serialize($options);
        $path   = '/'.SITE_ID.'/'.__FUNCTION__.'/';
        $time   = 36000;

        if($obCache->InitCache($time,$id,$path)):
            $this->arResult['RESULT'] = $obCache->GetVars();
            $this->arResult['RESULT']['CACHE']='Y';
        else:
            if($obCache->StartDataCache()):
                $this->IncludeModule('iblock');
                $this->arParams['MANUFACTURER']['GET_LIST'] = array(
                    'ORDER' => array('NAME'=>'ASC'),
                    'FILTER' => array(
                        'IBLOCK_ID' => MANUFACTURER_IBLOCK_ID,
                        'ACTIVE'    => 'Y',
                    ),
                    'GROUP' => false,
                    'LIMIT' => false,
                    'SELECT' => array(
                        'NAME',
                        'ID'=>'XML_ID',
                    )
                );

                if(!empty($this->arParams['REQUEST'])):
                    $this->arParams['MANUFACTURER']['GET_LIST']['FILTER'] = array_replace_recursive(
                        $this->arParams['MANUFACTURER']['GET_LIST']['FILTER'],
                        $this->arParams['REQUEST']
                    );
                endif;

                if(!empty($options)):
                    $this->arParams['MANUFACTURER']['GET_LIST'] = array_replace_recursive(
                        $this->arParams['MANUFACTURER']['GET_LIST'],
                        $options['MANUFACTURER']['GET_LIST']
                    );
                endif;

                $obElements = \CIBlockElement::GetList(
                    $this->arParams['MANUFACTURER']['GET_LIST']['ORDER'],
                    $this->arParams['MANUFACTURER']['GET_LIST']['FILTER'],
                    $this->arParams['MANUFACTURER']['GET_LIST']['GROUP'],
                    $this->arParams['MANUFACTURER']['GET_LIST']['LIMIT'],
                    $this->arParams['MANUFACTURER']['GET_LIST']['SELECT']
                );

                while($arItem = $obElements->Fetch()):
                    $arItem['ID'] = $arItem['NAME'];
                    $this->arResult['RESULT']['ITEMS'][] = $arItem;
                endwhile;

                $obCache->EndDataCache($this->arResult['RESULT']);

                $this->arResult['RESULT']['CACHE']='N';
            endif;
        endif;
    }

    public function GetCatalogSections($options=array())
    {
        $this->IncludeModule('iblock');

        $obCache = \Bitrix\Main\Data\Cache::createInstance();

        $id     = SITE_ID.__FUNCTION__.__LINE__.serialize($options);
        $path   = '/'.SITE_ID.'/'.__FUNCTION__.'/';
        $time   = 360000;

        if($obCache->InitCache($time,$id,$path)):
            $this->arResult['RESULT'] = $obCache->GetVars();
            $this->arResult['RESULT']['CACHE']='Y';
        else:
            if($obCache->StartDataCache()):
                $this->arParams['SECTIONS_LIST']['GET_LIST'] = array(
                    'ORDER' => array('LEFT_MARGIN'=>'ASC'),
                    'FILTER' => array(
                        'IBLOCK_ID' => CATALOG_IBLOCK_ID,
                        'ACTIVE'    => 'Y',
                        'UF_SHOW'   => false,
                    ),
                    'GROUP' => false,
                    'LIMIT' => false,
                    'SELECT' => array(
                        'NAME',
                        'ID',
                        'DEPTH_LEVEL'
                    )
                );

                if(!empty($this->arParams['REQUEST'])):
                    $this->arParams['SECTIONS_LIST']['GET_LIST']['FILTER'] = array_replace_recursive(
                        $this->arParams['SECTIONS_LIST']['GET_LIST']['FILTER'],
                        $this->arParams['REQUEST']
                    );
                endif;

                if(!empty($options)):
                    $this->arParams['SECTIONS_LIST']['GET_LIST'] = array_replace_recursive(
                        $this->arParams['SECTIONS_LIST']['GET_LIST'],
                        $options['SECTIONS_LIST']['GET_LIST']
                    );
                endif;

                $obElements = \CIBlockSection::GetList(
                    $this->arParams['SECTIONS_LIST']['GET_LIST']['ORDER'],
                    $this->arParams['SECTIONS_LIST']['GET_LIST']['FILTER'],
                    $this->arParams['SECTIONS_LIST']['GET_LIST']['LIMIT'],
                    $this->arParams['SECTIONS_LIST']['GET_LIST']['SELECT']
                );

                while($arItem = $obElements->Fetch()):
                    $this->arResult['RESULT']['ITEMS'][] = $arItem;
                endwhile;

                $obCache->EndDataCache($this->arResult['RESULT']);

                $this->arResult['RESULT']['CACHE']='N';
            endif;
        endif;
    }

    public function GetAddItemsPrefix($options=array())
    {
        $arUser = $this->user->GetById($this->user->GetId())->Fetch();

        $this->arResult['RESULT'] = $arUser;
    }

    public function SetAddItemsFile($options=array())
    {
        $arFiles = array();

        $obFile = new \CFile;

        if(!empty($_FILES)&&isset($this->arParams['REQUEST']['id'])&&+$this->arParams['REQUEST']['id']>0):
            $obFile->Delete($this->arParams['REQUEST']['id']);
        endif;

        foreach($_FILES as $arFile):
            $arFile['MODULE_ID']='web.forms';
            $arFile['id'] = $obFile->SaveFile(
                $arFile,
                'webforms'
            );

            $arFile = $obFile->GetFileArray($arFile['id']);

            if(empty($arFile)):
                $arFiles[] = array(
                    "name"=> "",
                    "size"=> 0,
                    "error"=>__LINE__,
                );
            else:
                $arFile['IS_IMAGE'] = \CFile::IsImage($arFile['ORIGINAL_NAME']);

                if($arFile['IS_IMAGE']):
                    $arFile['THUMB'] = $obFile->ResizeImageGet(
                        $arFile['ID'],
                        array(
                            'width'=>200,
                            'height'=>200,
                        ),
                        BX_RESIZE_IMAGE_PROPORTIONAL
                    );
                    $arFile['TYPE'] = 'image';
                else:
                    $arFile['THUMB'] = array_change_key_case($arFile,CASE_LOWER);
                    $arFile['TYPE'] = 'file';
                endif;

                $arFiles[] = array(
                    'id'            => $arFile['ID'],
                    "name"          => $arFile['ORIGINAL_NAME'],
                    "size"          => $arFile['FILE_SIZE'],
                    "url"           => $arFile['SRC'],
                    "thumbnailUrl"  => $arFile['THUMB']['src'],
                    "deleteUrl"     => $arFile['SRC'],
                    "deleteType"    => "DELETE",
                    "type"          => $arFile['TYPE'],
                );
            endif;
        endforeach;

        if(empty($arFiles)):
            $arFiles[] = array(
                "name"=> "error",
                "size"=> 0,
                "error"=>__LINE__,
            );
        endif;

        $this->arResult['RESULT'] = array(
            'files' => $arFiles,
        );//54400
    }

    public function GetAddItemsFile($options=array())
    {
        $obFile = new \CFile;

        $this->arParams['REQUEST']['id'] = isset($this->arParams['REQUEST']['id'])?$this->arParams['REQUEST']['id']:array();
        $this->arParams['REQUEST']['id'] = !is_array($this->arParams['REQUEST']['id'])?array($this->arParams['REQUEST']['id']):$this->arParams['REQUEST']['id'];

        foreach($this->arParams['REQUEST']['id'] as $id):
            $arFile = $obFile->GetFileArray($id);

            if(empty($arFile)):
                $arFiles[] = array(
                    "name"=> "",
                    "size"=> 0,
                    "error"=>__LINE__,
                );
            else:
                $arFile['IS_IMAGE'] = \CFile::IsImage($arFile['ORIGINAL_NAME']);

                if($arFile['IS_IMAGE']):
                    $arFile['THUMB'] = $obFile->ResizeImageGet(
                        $arFile['ID'],
                        array(
                            'width'=>200,
                            'height'=>200,
                        ),
                        BX_RESIZE_IMAGE_PROPORTIONAL
                    );
                    $arFile['TYPE'] = 'image';
                else:
                    $arFile['THUMB'] = array_change_key_case($arFile,CASE_LOWER);
                    $arFile['TYPE'] = 'file';
                endif;

                $arFiles[] = array(
                    'id'            => $arFile['ID'],
                    "name"          => $arFile['ORIGINAL_NAME'],
                    "size"          => $arFile['FILE_SIZE'],
                    "url"           => $arFile['SRC'],
                    "thumbnailUrl"  => $arFile['THUMB']['src'],
                    "deleteUrl"     => $arFile['SRC'],
                    "deleteType"    => "DELETE",
                    "type"          => $arFile['TYPE'],
                );
            endif;
        endforeach;

        if(empty($arFiles)):
            $arFiles[] = array(
                "name"=> "error",
                "size"=> 0,
                "error"=>__LINE__,
            );
        endif;

        $this->arResult['RESULT'] = array(
            'files' => $arFiles,
        );
    }

    public function getOrderPay()
    {
        \Bitrix\Main\Loader::includeModule('sale');

        $id1C = htmlspecialchars($_GET['ID_1C']);

        $order = \CSaleOrder::getList(array(),array('ID_1C'=>$id1C),false,false,array('ID','ID_1C','PAY_SYSTEM_ID','PRICE','DISCOUNT_VALUE'))->Fetch();

        if(!empty($order)&&!CheckPaymentAvailable()||IS_QS)
        {
            \CSaleOrder::Update($order['ID'],array('PAY_SYSTEM_ID'=>PAY_SYSTEM_CREDIT_CARD));

            $this->db->Query('update b_sale_order_payment set SUM="'.($order['PRICE']-$order['DISCOUNT_VALUE']).'" where ORDER_ID="'.$order['ID'].'"');

            LocalRedirect(URL_BASKET_PATH.'?ORDER_ID='.$order["ID_1C"].'#payment',false);
        }
    }
}
/*

{
    Anesthesia: false
    Cart: 1
    Commodity: [
    0: {
        Article: "D000001",
        Quantity: 1
    },
    1: {
        Article: "E002724",
        Quantity: 1
    },
    2: {
        Article: "E002725",
        Quantity: 1
    },
    3: {
        Article: "UL707",
        Quantity: 1
    },
    4: {
        Article: "UL5396-A",
        Quantity: 1
    },
    CustomerGUID: "856f86d1-4d06-11e5-a0fa-d850e6c45b03"
    DateClient: "2016-02-05T15:49:53"
    Email: "sergeygardner@yandex.ru"
    GUID: "c6257ff0-cbfe-11e5-820b-d850e6c45b03"
    INN: "123456789012"
    Login: "sergeygardner@yandex.ru"
    PointsCount: "100"
    SitePayFormID: "CA"
}*/
