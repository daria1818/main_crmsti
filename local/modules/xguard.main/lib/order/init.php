<?
/**
 * xGuard Framework
 * @package xGuard * @subpackage main
 * @copyright 2014 xGuard
 */

namespace xGuard\Main\Order;

use \xGuard\Main;

/**
 * Base entity
 */
class Init extends \xGuard\Main
{
	public $obBasket = false;
	public $user     = false;

    public function __construct($options)
    {
        parent::__construct($options);
        $this->IncludeModule('sale');
        $this->IncludeModule('catalog');
    }

    public function Add($options=array())
    {
        //$this->DoCalculateOrder($options)->CalculateOrderPrices($options)->PrepareData($options);

        $this->arResult['ORDER']["VAT_SUM"] = empty($this->arResult['ORDER']["VAT_SUM"]) ? 0 : $this->arResult['ORDER']["VAT_SUM"];
        $this->arResult['ORDER']["TAX_PRICE"] = empty($this->arResult['ORDER']["TAX_PRICE"]) ? 0 : $this->arResult['ORDER']["TAX_PRICE"];

        $arFields = array(
            "ID_1C"                 => $this->arResult['ORDER']['ID_1C'],
            "XML_ID"                => $this->arResult['ORDER']['XML_ID'],
            "LID"                   => SITE_ID,
            "PERSON_TYPE_ID"        => $this->arResult['USER']["PERSON_TYPE_ID"],
            "PAYED"                 => isset($this->arResult['ORDER']['PAYED'])?$this->arResult['ORDER']['PAYED']:"N",
            "CANCELED"              => isset($this->arResult['ORDER']['CANCELED'])?$this->arResult['ORDER']['CANCELED']:"N",
            "STATUS_ID"             => isset($this->arResult['ORDER']['STATUS_ID'])?$this->arResult['ORDER']['STATUS_ID']:"A",
            "PRICE"                 => $this->arResult['ORDER']['TOTAL_PRICE'],
            "CURRENCY"              => $this->arResult['ORDER']["CURRENCY"],
            "USER_ID"               => (int)$this->user->GetID(),
            "PAY_SYSTEM_ID"         => $this->arResult['USER']["PAY_SYSTEM_ID"],
            "PRICE_DELIVERY"        => empty($this->arResult['ORDER']["PRICE_DELIVERY"])?0:$this->arResult['ORDER']["PRICE_DELIVERY"],
            "DELIVERY_PRICE"        => empty($this->arResult['ORDER']["DELIVERY_PRICE"])?0:$this->arResult['ORDER']["DELIVERY_PRICE"],
            "DELIVERY_ID"           => (strlen($this->arResult['USER']["DELIVERY_ID"]) > 0 ? $this->arResult['USER']["DELIVERY_ID"] : false),
            "DISCOUNT_VALUE"        => empty($this->arResult['ORDER']["DISCOUNT_VALUE"])?0:$this->arResult['ORDER']["DISCOUNT_VALUE"],
            "TAX_VALUE"             => $this->arResult['ORDER']["USE_VAT"] == "Y" ? $this->arResult['ORDER']["VAT_SUM"] : $this->arResult['ORDER']["TAX_PRICE"],
           // "USER_DESCRIPTION"      => $this->arResult['USER']["~ORDER_DESCRIPTION"]
            "ALLOW_DELIVERY"        => 'Y',
        );

        debugfile(array($this->arResult['ORDER'],$arFields),'order.log');
        $this->arResult['ORDER']['ORDER_ID']=\CSaleOrder::Add($arFields);

        \CSaleBasket::OrderBasket($this->arResult['ORDER']['ORDER_ID'], \CSaleBasket::GetBasketUserID(), SITE_ID, false);
        $this->arResult['ORDER'] = \CSaleOrder::GetById($this->arResult['ORDER']['ORDER_ID']);
        $this->arResult['ORDER']['ORDER_ID'] = $this->arResult['ORDER']['ID'];
        //$this->arResult['ORDER']["ORDER_ID"] = (int)\CSaleOrder::DoSaveOrder($this->arResult['ORDER_DATA'], $this->arResult['ORDER_FIELDS'], 0, $this->arResult["ERRORS"]);

        $this->db->Query('update b_sale_order_payment set ID_1C="'.$this->arResult['ORDER']['ID_1C'].'",SUM="'.($this->arResult['ORDER']['PRICE']-$this->arResult['ORDER']['DISCOUNT_VALUE']).'" where ORDER_ID="'.$this->arResult['ORDER']['ID'].'"');
    }
    protected function DoCalculateOrder($options=array())
    {
        $this->arResult['ORDER_DATA'] = \CSaleOrder::DoCalculateOrder(
            SITE_ID,
            $this->user->GetID(),
            $this->arResult["BASKET"],
            $this->arResult['USER']['PERSON_TYPE_ID'],
            $this->arResult['USER']["ORDER_PROP"],
            $this->arResult['USER']["DELIVERY_ID"],
            $this->arResult['USER']["PAY_SYSTEM_ID"],
            array(),
            $this->arResult['ERRORS'],
            $this->arResult['WARNINGS']
        );

        return $this;
    }
    protected function CalculateOrderPrices($options=array())
    {
        $this->arResult['OLD_ORDER_DATA'] = \CSaleOrder::CalculateOrderPrices($this->arResult["BASKET"]);

        if (!empty($this->arResult['OLD_ORDER_DATA'])):
            $this->arResult['ORDER']['ORDER_PRICE'] = $this->arResult['OLD_ORDER_DATA']['ORDER_PRICE'];
            $this->arResult['ORDER']["ORDER_PRICE_FORMATED"] = SaleFormatCurrency($this->arResult['ORDER']["ORDER_PRICE"], $this->arResult['ORDER']["BASE_LANG_CURRENCY"]);
            $this->arResult['ORDER']["ORDER_WEIGHT"] = $this->arResult['ORDER']["ORDER_WEIGHT"];
            $this->arResult['ORDER']['VAT_SUM'] = $this->arResult['ORDER']['VAT_SUM'];
            $this->arResult['ORDER']["USE_VAT"] = ($this->arResult['ORDER']['USE_VAT'] == "Y");
            $this->arResult['ORDER']["VAT_SUM_FORMATED"] = SaleFormatCurrency($this->arResult['ORDER']["VAT_SUM"], $this->arResult['ORDER']["BASE_LANG_CURRENCY"]);
        endif;

        return $this;
    }
    protected function PrepareData($options=array())
    {
        $this->arResult['ORDER_FIELDS'] = array(
            "LID"                   => SITE_ID,
            "PERSON_TYPE_ID"        => $this->arResult['USER']["PERSON_TYPE_ID"],
            "PAYED"                 => "N",
            "CANCELED"              => "N",
            "STATUS_ID"             => "A",
            "PRICE"                 => $this->arResult['ORDER_DATA']['ORDER_PRICE'],
            "CURRENCY"              => $this->arResult['ORDER_DATA']["CURRENCY"],
            "USER_ID"               => (int)$this->user->GetID(),
            "PAY_SYSTEM_ID"         => $this->arResult['USER']["PAY_SYSTEM_ID"],
            "PRICE_DELIVERY"        => $this->arResult['ORDER']["DELIVERY_PRICE"],
            "DELIVERY_ID"           => (strlen($this->arResult['USER']["DELIVERY_ID"]) > 0 ? $this->arResult['USER']["DELIVERY_ID"] : false),
            "DISCOUNT_VALUE"        => $this->arResult['ORDER_DATA']["DISCOUNT_PRICE"],
            "TAX_VALUE"             => $this->arResult['ORDER_DATA']["USE_VAT"] == "Y" ? $this->arResult['ORDER_DATA']["VAT_SUM"] : $this->arResult['ORDER_DATA']["TAX_PRICE"],
           // "USER_DESCRIPTION"      => $this->arResult['USER']["~ORDER_DESCRIPTION"]
        );

        $arOrderDat['USER_ID'] = $this->arResult['ORDER_FIELDS']['USER_ID'];

        if ($this->arResult['STORE_ID']):
            $this->arResult['ORDER_FIELDS']["STORE_ID"] = $this->arResult['STORE_ID'];
        endif;

        return $this;
    }

}
?>