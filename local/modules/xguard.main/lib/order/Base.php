<?php
/**
 * xGuard Framework
 * @package xGuard * @subpackage main
 * @copyright 2014 xGuard
 */

namespace xGuard\Main\Order;

use \xGuard\Main;
use \xGuard\Main\Basket\Base as BasketBase;

IncludeModuleLangFile(__FILE__);
/**
 * Base entity
 */
class Base extends Main
{

    /**
     * @var bool
     */
    public $obBasket = false;

    /**
     * @var
     */
    public $user;

    /**
     * Base constructor.
     *
     * @param $options
     */
    public function __construct(array $options=[])
    {
        parent::__construct($options);
        $this->IncludeModule('sale');
        $this->IncludeModule('catalog');
    }

    /**
     *
     */
    public function add()
    {
        //$this->DoCalculateOrder($options)->CalculateOrderPrices($options)->PrepareData($options);

        $this->arResult['ORDER']['VAT_SUM'] = empty($this->arResult['ORDER']['VAT_SUM']) ? 0 : $this->arResult['ORDER']['VAT_SUM'];
        $this->arResult['ORDER']['TAX_PRICE'] = empty($this->arResult['ORDER']['TAX_PRICE']) ? 0 : $this->arResult['ORDER']['TAX_PRICE'];

        /** @noinspection PhpUndefinedMethodInspection */
        $arFields = array(
            'ID_1C'          => $this->arResult['ORDER']['ID_1C'],
            'XML_ID'         => $this->arResult['ORDER']['XML_ID'],
            'LID'            => SITE_ID,
            'PERSON_TYPE_ID' => $this->arResult['USER']['PERSON_TYPE_ID'],
            'PAYED'          => $this->arResult['ORDER']['PAYED'] ?? 'N',
            'CANCELED'       => $this->arResult['ORDER']['CANCELED'] ?? 'N',
            'STATUS_ID'      => $this->arResult['ORDER']['STATUS_ID'] ?? 'A',
            'PRICE'          => $this->arResult['ORDER']['TOTAL_PRICE'],
            'CURRENCY'       => $this->arResult['ORDER']['CURRENCY'],
            'USER_ID'        => (int)$this->user->GetID(),
            'PAY_SYSTEM_ID'  => $this->arResult['USER']['PAY_SYSTEM_ID'],
            'PRICE_DELIVERY' => empty($this->arResult['ORDER']['PRICE_DELIVERY'])?0: $this->arResult['ORDER']['PRICE_DELIVERY'],
            'DELIVERY_PRICE' => empty($this->arResult['ORDER']['DELIVERY_PRICE'])?0: $this->arResult['ORDER']['DELIVERY_PRICE'],
            'DELIVERY_ID'    => '' !== $this->arResult['USER']['DELIVERY_ID']
                ? $this->arResult['USER']['DELIVERY_ID'] : false,
            'DISCOUNT_VALUE' => empty($this->arResult['ORDER']['DISCOUNT_VALUE'])?0: $this->arResult['ORDER']['DISCOUNT_VALUE'],
            'TAX_VALUE'      => $this->arResult['ORDER']['USE_VAT'] === 'Y'
                ? $this->arResult['ORDER']['VAT_SUM'] : $this->arResult['ORDER']['TAX_PRICE'],
            // "USER_DESCRIPTION"      => $this->arResult['USER']["~ORDER_DESCRIPTION"]
            'ALLOW_DELIVERY' => 'Y',
        );


        /** @noinspection PhpUndefinedClassInspection */
        $this->arResult['ORDER']['ORDER_ID']=\CSaleOrder::Add($arFields);
        debugfile(array($this->arResult['ORDER'],$arFields),'order.log');
        $this->arResult['DEBUG'][] = $this->application->GetException();
        if(!empty($this->arResult['ORDER']['ORDER_ID'])) {

            /** @noinspection PhpUndefinedClassInspection */
            \CSaleBasket::OrderBasket($this->arResult['ORDER']['ORDER_ID'], \CSaleBasket::GetBasketUserID(), SITE_ID, false);
            /** @noinspection PhpUndefinedClassInspection */
            $this->arResult['ORDER'] = \CSaleOrder::GetById($this->arResult['ORDER']['ORDER_ID']);
            $this->arResult['ORDER']['ORDER_ID'] = $this->arResult['ORDER']['ID'];

            /** @noinspection PhpUndefinedMethodInspection */
            $this->db->Query('update b_sale_order_payment set ID_1C="'.$this->arResult['ORDER']['ID_1C'].'",SUM="'.($this->arResult['ORDER']['PRICE']- $this->arResult['ORDER']['DISCOUNT_VALUE']).'" where ORDER_ID="'.$this->arResult['ORDER']['ID'].'"');
        }
    }

    /**
     * @return $this
     */
    protected function doCalculateOrder(): self
    {
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedMethodInspection */
        $this->arResult['ORDER_DATA'] = \CSaleOrder::DoCalculateOrder(
            SITE_ID,
            $this->user->GetID(),
            $this->arResult['BASKET'],
            $this->arResult['USER']['PERSON_TYPE_ID'],
            $this->arResult['USER']['ORDER_PROP'],
            $this->arResult['USER']['DELIVERY_ID'],
            $this->arResult['USER']['PAY_SYSTEM_ID'],
            array(),
            $this->arResult['ERRORS'],
            $this->arResult['WARNINGS']
        );

        return $this;
    }

    /**
     * @return $this
     */
    protected function calculateOrderPrices(): self
    {
        /** @noinspection PhpUndefinedClassInspection */
        $this->arResult['OLD_ORDER_DATA'] = \CSaleOrder::CalculateOrderPrices($this->arResult['BASKET']);

        if (!empty($this->arResult['OLD_ORDER_DATA'])):
            $this->arResult['ORDER']['ORDER_PRICE'] = $this->arResult['OLD_ORDER_DATA']['ORDER_PRICE'];
            /** @noinspection PhpUndefinedFunctionInspection */
            $this->arResult['ORDER']['ORDER_PRICE_FORMATED'] = SaleFormatCurrency($this->arResult['ORDER']['ORDER_PRICE'], $this->arResult['ORDER']['BASE_LANG_CURRENCY']);
            //$this->arResult['ORDER']['ORDER_WEIGHT'] = $this->arResult['ORDER']['ORDER_WEIGHT'];
            //$this->arResult['ORDER']['VAT_SUM'] = $this->arResult['ORDER']['VAT_SUM'];
            $this->arResult['ORDER']['USE_VAT'] = ($this->arResult['ORDER']['USE_VAT']
                === 'Y');
            /** @noinspection PhpUndefinedFunctionInspection */
            $this->arResult['ORDER']['VAT_SUM_FORMATED'] = SaleFormatCurrency($this->arResult['ORDER']['VAT_SUM'], $this->arResult['ORDER']['BASE_LANG_CURRENCY']);
        endif;

        return $this;
    }

    /**
     * @return $this
     */
    protected function prepareData(): self
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->arResult['ORDER_FIELDS'] = array(
            'LID'            => SITE_ID,
            'PERSON_TYPE_ID' => $this->arResult['USER']['PERSON_TYPE_ID'],
            'PAYED'          => 'N',
            'CANCELED'       => 'N',
            'STATUS_ID'      => 'A',
            'PRICE'          => $this->arResult['ORDER_DATA']['ORDER_PRICE'],
            'CURRENCY'       => $this->arResult['ORDER_DATA']['CURRENCY'],
            'USER_ID'        => (int)$this->user->GetID(),
            'PAY_SYSTEM_ID'  => $this->arResult['USER']['PAY_SYSTEM_ID'],
            'PRICE_DELIVERY' => $this->arResult['ORDER']['DELIVERY_PRICE'],
            'DELIVERY_ID'    => '' !== $this->arResult['USER']['DELIVERY_ID']
                ? $this->arResult['USER']['DELIVERY_ID'] : false,
            'DISCOUNT_VALUE' => $this->arResult['ORDER_DATA']['DISCOUNT_PRICE'],
            'TAX_VALUE'      => $this->arResult['ORDER_DATA']['USE_VAT'] === 'Y'
                ? $this->arResult['ORDER_DATA']['VAT_SUM'] : $this->arResult['ORDER_DATA']['TAX_PRICE'],
            // "USER_DESCRIPTION"      => $this->arResult['USER']["~ORDER_DESCRIPTION"]
        );

        $arOrderDat['USER_ID'] = $this->arResult['ORDER_FIELDS']['USER_ID'];

        if ($this->arResult['STORE_ID']):
            $this->arResult['ORDER_FIELDS']['STORE_ID'] = $this->arResult['STORE_ID'];
        endif;

        return $this;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function getStores($options=array()): array
    {
        $options['ID'] = $options['ID'] ?? false;

        $this->arParams['STORES']['ORDER'] = array();
        $this->arParams['STORES']['FILTER'] = array(
            'LID'    => SITE_ID,
            'ACTIVE' => 'Y',
            'ID'     => $options['ID']
        );

        /** @noinspection PhpUndefinedClassInspection */
        $dbDelivery = \CCatalogStore::GetList(
            $this->arParams['STORES']['ORDER'],
            $this->arParams['STORES']['FILTER'],
            false,
            false,
            [
                '*',
                'UF_XML_ID',
            ]
        );

        /** @noinspection PhpUndefinedMethodInspection */
        while ($arDelivery = $dbDelivery->Fetch()):
            $this->arResult['STORES'][$arDelivery['ID']] = $arDelivery;
            $this->arResult['STORES'][$arDelivery['UF_XML_ID']??$arDelivery['XML_ID']] = $arDelivery;
        endwhile;

        return $this->arResult['STORES'];
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function getDelivery($options=array()): array
    {
        $options['ID'] = $options['ID'] ?? false;

        $this->arParams['DELIVERY']['ORDER'] = array();
        $this->arParams['DELIVERY']['FILTER'] = array(
            'LID'    => SITE_ID,
            'ACTIVE' => 'Y',
            'ID'     => $options['ID']
        );

        /** @noinspection PhpUndefinedClassInspection */
        $dbDelivery = \CSaleDelivery::GetList(
            $this->arParams['DELIVERY']['ORDER'],
            $this->arParams['DELIVERY']['FILTER']
        );

        /** @noinspection PhpUndefinedMethodInspection */
        while ($arDelivery = $dbDelivery->Fetch()):
            $this->arResult['DELIVERY'][$arDelivery['ID']] = $arDelivery;

            if(!empty($arDelivery['DESCRIPTION'])):
                $this->arResult['DELIVERY'][$arDelivery['DESCRIPTION']] = $arDelivery;
            endif;
        endwhile;

        return $this->arResult['DELIVERY'];
    }

    /**
     * @param array $options
     */
    public function getPaySystem($options=array())
    {
        $options['PROFILE_ID'] = $options['PROFILE_ID']?: $this->arParams['REQUEST']['PROFILE_ID'];
        $options['DELIVERY_TO_PAYSYSTEM'] = $options['DELIVERY_TO_PAYSYSTEM']?: $this->arParams['REQUEST']['DELIVERY_TO_PAYSYSTEM'];
        $options['PAY_SYSTEM_ID'] = $options['PAY_SYSTEM_ID']?: $this->arParams['REQUEST']['PAY_SYSTEM_ID'];
        $options['DELIVERY_ID'] = $options['DELIVERY_ID']?: $this->arParams['REQUEST']['DELIVERY_ID'];

        $this->IncludeModule('sale');

        $this->arResult['RESULT']['ERRORS'] = array();

        $this->arParams['ORDER_USER_PROPS']['ORDER']     = array(
            'DATE_UPDATE' => 'ASC',
        );
        /** @noinspection PhpUndefinedMethodInspection */
        $this->arParams['ORDER_USER_PROPS']['FILTER']    = array(
            'USER_ID' => (int) $this->user->GetID(),
            'ID'      => $options['PROFILE_ID'],
        );

        /** @noinspection PhpUndefinedClassInspection */
        $arUserProfiles = \CSaleOrderUserProps::GetList(
            $this->arParams['ORDER_USER_PROPS']['ORDER'],
            $this->arParams['ORDER_USER_PROPS']['FILTER']
        )->Fetch($this->arParams['ORDER_USER_PROPS']);

        /** @noinspection PhpUndefinedClassInspection */
        $dbRes = \CSaleDelivery::GetDelivery2PaySystem(array());

        /** @noinspection PhpUndefinedMethodInspection */
        while ($arRes = $dbRes->Fetch()):
            $this->arResult['RESULT']['DELIVERY']['D2P'][$arRes['DELIVERY_ID']][$arRes['PAYSYSTEM_ID']] = $arRes['PAYSYSTEM_ID'];
            $this->arResult['RESULT']['DELIVERY']['P2D'][$arRes['PAYSYSTEM_ID']][$arRes['DELIVERY_ID']] = $arRes['DELIVERY_ID'];
        endwhile;

        if(isset($options['DELIVERY_TO_PAYSYSTEM'])):
            if($options['DELIVERY_TO_PAYSYSTEM'] === 'p2d'):
                unset($this->arResult['RESULT']['DELIVERY']['D2P']);
                $this->arResult['RESULT']['DELIVERY'] = $this->arResult['RESULT']['DELIVERY']['P2D'][$options['PAY_SYSTEM_ID']];
            else:
                unset($this->arResult['RESULT']['DELIVERY']['P2D']);
                $this->arResult['RESULT']['DELIVERY'] = $this->arResult['RESULT']['DELIVERY']['D2P'][$options['DELIVERY_ID']];
            endif;
        endif;

        $this->arParams['PAY_SYSTEM']['ORDER']     = array(
            'SORT'     => 'ASC',
            'PSA_NAME' => 'ASC',
        );

        $this->arParams['PAY_SYSTEM']['FILTER']    = array(
            'ACTIVE'           => 'Y',
            'PERSON_TYPE_ID'   => $arUserProfiles['PERSON_TYPE_ID'],
            'PSA_HAVE_PAYMENT' => 'Y',
        );

        if(BasketBase::$PROM_ART_IN_BASKET===0) {
            $this->arParams['PAY_SYSTEM']['FILTER']['<SORT']=10000;
        } else {
            $this->arParams['PAY_SYSTEM']['FILTER']['>SORT']=10000;
        }

        /** @noinspection PhpUndefinedClassInspection */
        $dbPaySystem = \CSalePaySystem::GetList(
            $this->arParams['PAY_SYSTEM']['ORDER'],
            $this->arParams['PAY_SYSTEM']['FILTER']
        );

        $bFirst=true;

        $this->arResult['RESULT']['DATA'] = array();

        /** @noinspection PhpUndefinedMethodInspection */
        while ($arPaySystem = $dbPaySystem->Fetch()):
            if(BasketBase::$AID_IN_BASKET > 0 && $arPaySystem['DESCRIPTION']!==PAY_SYSTEM_NON_CASH_CODE) {
                continue;
            }

            if(isset($options['DELIVERY_TO_PAYSYSTEM'])&&!isset($this->arResult['RESULT']['DELIVERY'][$arPaySystem['ID']]))
            {
                continue;
            }

            if ($bFirst && (int) $this->arResult['USER']['PAY_SYSTEM_ID'] <= 0):
                $arPaySystem['CHECKED'] = 'Y';
            endif;

            $arPaySystem['PSA_PARAMS'] = \unserialize($arPaySystem['PSA_PARAMS'],['allowed_classes' => true]);

            if(
            !Main\Soap\Params::checkPaySystem(
                array(
                    'arPaySystem'     => &$arPaySystem,
                )
            )
            ):
                unset($arPaySystem);
            endif;

            if(
                !empty($arPaySystem)
                &&
                CheckPaymentAvailable()
                && false === stripos(
                    $arPaySystem['DESCRIPTION'],
                    PAY_SYSTEM_NON_CASH_CODE
                )
                && false === stripos(
                    $arPaySystem['DESCRIPTION'],
                    PAY_SYSTEM_CASH_CODE
                )
            ):
                unset($arPaySystem);
            endif;

            /** @noinspection PhpUndefinedVariableInspection */
            if($arPaySystem):
                $this->arResult['RESULT']['DATA'][] = array('ID'=>$arPaySystem['ID'],'NAME'=>$arPaySystem['NAME']);
                $this->arResult['PAYMENT'][$arPaySystem['ID']] = $arPaySystem;
                $bFirst = false;
            endif;

        endwhile;

        unset($this->arResult['RESULT']['DELIVERY']);

        return $this->arResult['PAYMENT'];
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function getStatus($options=array()): array
    {
        $options['ID'] = $options['ID'] ?? false;

        $this->arParams['STATUS']['ORDER'] = array();
        $this->arParams['STATUS']['FILTER'] = array(
            // "LID"       => SITE_ID,
            'ACTIVE' => 'Y',
            //'ID'        => $options['ID']
        );

        //$this->arParams['STATUS']['FILTER']

        /** @noinspection PhpUndefinedClassInspection */
        $dbItem = \CSaleStatus::GetList(
            $this->arParams['STATUS']['ORDER'],
            $this->arParams['STATUS']['FILTER']
        );

        /** @noinspection PhpUndefinedMethodInspection */
        while ($arItem = $dbItem->Fetch()):
            $this->arResult['STATUS'][$arItem['ID']] = $arItem;
        endwhile;

        return $this->arResult['STATUS'];
    }
}
