<?php /** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUndefinedFieldInspection */

/** @noinspection PhpUndefinedConstantInspection */

/** @noinspection PhpUndefinedClassInspection */

/** @noinspection PhpUndefinedNamespaceInspection */

/**
 * Bork Framework
 *
 * @package    Bork
 * @subpackage main
 * @copyright  2014 Bork
 */

namespace xGuard\Main\Basket;

use CSaleUser;
use \xGuard\Main;

/**
 * Base entity
 */

IncludeModuleLangFile(__FILE__);

/**
 * Class Base
 *
 * @package xGuard\Main\Basket
 */
class Base extends Main
{

    /**
     * @var int
     */
    public static $AID_IN_BASKET;

    /**
     * @var int
     */
    public static $PRECURSOR_IN_BASKET;

    /**
     * @var int
     */
    public static $PROM_ART_IN_BASKET;

    /**
     * @var int
     */
    public static $REGULAR_ITEM;

    /**
     * Base constructor.
     *
     * @param array $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->IncludeModule('sale');
        $this->IncludeModule('catalog');
    }

    /**
     * @param array $item
     */
    public static function checkSpecialItemInBasket(array $item = [])
    {
        static::$AID_IN_BASKET = static::$AID_IN_BASKET ?? 0;
        static::$PRECURSOR_IN_BASKET = static::$PRECURSOR_IN_BASKET ?? 0;
        static::$PROM_ART_IN_BASKET = static::$PROM_ART_IN_BASKET ?? 0;
        static::$REGULAR_ITEM = static::$REGULAR_ITEM ?? 0;

        if (
            (
                isset($item['PROPERTIES'][ELEMENT_PROP_SPECIAL_ITEMS_TYPE]['VALUE_XML_ID'])
                && $item['PROPERTIES'][ELEMENT_PROP_SPECIAL_ITEMS_TYPE]['VALUE_XML_ID'] === AID_CODE
            )
            ||
            (
                isset($item['PROPS'][ELEMENT_PROP_SPECIAL_ITEMS_TYPE]['VALUE'])
                && $item['PROPS'][ELEMENT_PROP_SPECIAL_ITEMS_TYPE]['VALUE'] === AID_CODE
            )
        ) {
            static::$AID_IN_BASKET++;

            return;
        }

        if (
            (
                isset($item['PROPERTIES'][ELEMENT_PROP_SPECIAL_ITEMS_TYPE]['VALUE_XML_ID'])
                && $item['PROPERTIES'][ELEMENT_PROP_SPECIAL_ITEMS_TYPE]['VALUE_XML_ID'] === PROM_ART_CODE
            )
            ||
            (
                isset($item['PROPS'][ELEMENT_PROP_SPECIAL_ITEMS_TYPE]['VALUE'])
                && $item['PROPS'][ELEMENT_PROP_SPECIAL_ITEMS_TYPE]['VALUE'] === PROM_ART_CODE
            )
        ) {
            static::$PROM_ART_IN_BASKET++;

            return;
        }

        if (
            (
                isset($item['PROPERTIES'][ELEMENT_PROP_SPECIAL_ITEMS_TYPE]['VALUE_XML_ID'])
                &&
                (
                    $item['PROPERTIES'][ELEMENT_PROP_SPECIAL_ITEMS_TYPE]['VALUE_XML_ID'] === PRECURSOR_CODE
                    || $item['PROPERTIES'][ELEMENT_PROP_SPECIAL_ITEMS_TYPE]['VALUE_XML_ID'] === PRECURSOR_ADD_CODE
                )
            )
            ||
            (
                isset($item['PROPS'][ELEMENT_PROP_SPECIAL_ITEMS_TYPE]['VALUE'])
                &&
                (
                    $item['PROPS'][ELEMENT_PROP_SPECIAL_ITEMS_TYPE]['VALUE_XML_ID'] === PRECURSOR_CODE
                    || $item['PROPS'][ELEMENT_PROP_SPECIAL_ITEMS_TYPE]['VALUE_XML_ID'] === PRECURSOR_ADD_CODE
                )
            )
        ) {
            static::$PRECURSOR_IN_BASKET++;

            return;
        }

        static::$REGULAR_ITEM++;
    }

    /**
     *
     */
    public static function cleanSpecialItemInBasket()
    {
        static::$AID_IN_BASKET = null;
        static::$PRECURSOR_IN_BASKET = null;
        static::$PROM_ART_IN_BASKET = null;
        static::$REGULAR_ITEM = null;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function add(array $options = array()): self
    {
        $obSaleBasket = new \CSaleBasket;

        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'Before',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        $this->getModel(['BASKET' => ['ADDITIONAL' => ['item']]]);

        $options['arItem'] = $this->getItem(
            array(
                'ID'     => $options['ID'],
                'ACTION' => 'Add',
            )
        );

        if (empty($options['arItem'])) {
            return $this;
        }

        $options['PRICE_ID'] = $options['PRICE_ID'] ?? 0;
        $options['PRICE'] = $options['PRICE'] ?? 0;
        $options['CURRENCY'] = $options['CURRENCY'] ?? CURRENT_CURRENCY;
        $options['CURRENT_PRICE_XML_ID'] = $options['CURRENT_PRICE_XML_ID']
            ?? CURRENT_PRICE_XML_ID;
        $options['QUANTITY'] = $options['QUANTITY'] ?? 1;

        $this->getPriceType()->getPrice($options);

        $arFields = array(
            'PRODUCT_ID'           => $options['ID'],
            'PRODUCT_PRICE_ID'     => $options['PRICE_ID'],
            'PRICE'                => $options['PRICE'],
            'CUSTOM_PRICE'         => 'Y',
            'CURRENCY'             => $options['CURRENCY'],
            'QUANTITY'             => $options['QUANTITY'],
            'LID'                  => SITE_ID,
            'DELAY'                => 'N',
            'CAN_BUY'              => 'Y',
            'NAME'                 => $options['arItem']['NAME'],
            'DETAIL_PAGE_URL'      => strip_tags(htmlspecialchars($_GET['url'])),
            'IGNORE_CALLBACK_FUNC' => 'Y',
            'NOTES'                => $options['CURRENT_PRICE_XML_ID'],
        );

        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'BeforeAdd',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arFields'  => &$arFields,
                    'arguments' => \func_get_args(),
                ),
            )
        );
        $arFields['FUSER_ID'] = \CSaleBasket::GetBasketUserID();
        $result = $obSaleBasket->Add($arFields);

        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'AfteAdd',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        if ($result):
            $this->arResult['MESSAGE'] = $this->arResult['MESSAGE'] ??
                GetMessage(
                    'MODULE_XGUARD_MAIN_BASKET_INIT_MESSAGE_ADD_ITEM'
                );
        else:
            $this->arResult['ERRORS']['LINE'] = 'ERROR #'.__LINE__.': '
                .$obSaleBasket->LAST_ERRORS;
        endif;

        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'After',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        return $this;
    }

    /**
     * @param array $options
     */
    public function inc(array $options = array())
    {
        $this->getModel(
            array('BASKET' => array('GETLIST' => array('FILTER' => array('ID' => $options['ID']))))
        );

        //$this->Add(array('ID'=>$this->arResult['BASKET'][$options['ID']]['PRODUCT_ID']));

        \CSaleBasket::Update(
            $this->arResult['BASKET'][$options['ID']]['ID'],
            array(
                'QUANTITY' => ++$this->arResult['BASKET'][$options['ID']]['QUANTITY'],
            )
        );
    }

    /**
     * @param array $options
     */
    public function dec(array $options = array())
    {
        $this->getModel(
            array('BASKET' => array('GETLIST' => array('FILTER' => array('ID' => $options['ID']))))
        );

        \CSaleBasket::Update(
            $this->arResult['BASKET'][$options['ID']]['ID'],
            array(
                'QUANTITY' => --$this->arResult['BASKET'][$options['ID']]['QUANTITY'],
            )
        );
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function remove(array $options = array()): self
    {
        if (!empty($options['ID'])):
            \CSaleBasket::delete($options['ID']);
        else:
            \CSaleBasket::DeleteAll(\CSaleBasket::GetBasketUserID());
        endif;

        //$this->arResult['MESSAGE'] = isset($this->arResult['MESSAGE'])?$this->arResult['MESSAGE']:GetMessage('MODULE_XGUARD_MAIN_BASKET_INIT_MESSAGE_REMOVE_ITEM');

        return $this;
    }

    /**
     * @param array $options
     */
    public function getCurrency(array $options = array())
    {

    }

    /**
     * @return $this
     */
    public function getPriceType(): self
    {
        $this->arParams['PRICE_TYPE']['GETLIST']
            = $this->arParams['PRICE_TYPE']['GETLIST']
            ?? array();
        $this->arParams['PRICE_TYPE']['GETLIST'] = array_merge_recursive(
            $this->arParams['PRICE_TYPE']['GETLIST'],
            array(
                'ORDER'   => array(),
                'FILTER'  => array(
                    'GROUP_ID' => $this->user->GetUserGroupArray()
                    //"XML_ID"	=> isset($options['CURRENT_PRICE_XML_ID']) ? $options['CURRENT_PRICE_XML_ID'] : CURRENT_PRICE_XML_ID,
                ),
                'GROUPBY' => false,
                'LIMIT'   => false,
                'SELECT'  => array(
                    '*',
                ),
            )
        );

        $catalogGroups = \CCatalogGroup::GetList(
            $this->arParams['PRICE_TYPE']['GETLIST']['ORDER'],
            $this->arParams['PRICE_TYPE']['GETLIST']['FILTER'],
            $this->arParams['PRICE_TYPE']['GETLIST']['GROUPBY'],
            $this->arParams['PRICE_TYPE']['GETLIST']['LIMIT'],
            $this->arParams['PRICE_TYPE']['GETLIST']['SELECT']
        );

        while ($item = $catalogGroups->Fetch()) {
            $this->arResult['PRICE_TYPE'][$item['ID']] = $item;
        }

        return $this;
    }

    /**
     * @param $options
     *
     * @return $this
     */
    public function getPrice(&$options): self
    {
        if (!isset($options['ID']) || empty($options['ID'])) {
            //$this->Log($options,__LINE__);

            return $this;
        }

        $catalogGroups = \CCatalogGroup::GetGroupsList(
            array('GROUP_ID' => $this->user->GetUserGroupArray())
        );
        $catalogGroup = [];

        while ($item = $catalogGroups->Fetch()) {
            $catalogGroup[$item['CATALOG_GROUP_ID']]
                = $item['CATALOG_GROUP_ID'];
        }

        $this->arParams['PRICE']['GETLIST']
            = $this->arParams['PRICE']['GETLIST']
            ?? array();
        $this->arParams['PRICE']['GETLIST'] = array_replace_recursive(
            $this->arParams['PRICE']['GETLIST'],
            array(
                'ORDER'   => array(),
                'FILTER'  => array(
                    'PRODUCT_ID'       => $options['ID'],
                    'CAN_BUY'          => isset($options['CAN_BUY'])
                    && $options['CAN_BUY'] ? $options['CAN_BUY'] : 'Y',
                    'CURRENCY'         => isset($options['CURRENCY'])
                    && !empty($options['CURRENCY']) ? $options['CURRENCY']
                        : CURRENT_CURRENCY,
                    'CATALOG_GROUP_ID' => $catalogGroup,
                ),
                'GROUPBY' => false,
                'LIMIT'   => false,
                'SELECT'  => array(
                    '*',
                ),
            )
        );

        $nsPrices = \CPrice::GetList(
            $this->arParams['PRICE']['GETLIST']['ORDER'],
            $this->arParams['PRICE']['GETLIST']['FILTER'],
            $this->arParams['PRICE']['GETLIST']['GROUPBY'],
            $this->arParams['PRICE']['GETLIST']['LIMIT'],
            $this->arParams['PRICE']['GETLIST']['SELECT']
        );

        $price = time();

        while ($item = $nsPrices->Fetch()) {
            if ($price > $item['PRICE']) {
                $this->arResult['PRICE'][$options['ID']] = $item;
                $price = $item['PRICE'];
            }
        }

        if (isset($options['PRICE'])) {
            $options['PRICE']
                = $this->arResult['PRICE'][$options['ID']]['PRICE'];
        }

        if (isset($options['PRICE_ID'])) {
            $options['PRICE_ID']
                = $this->arResult['PRICE'][$options['ID']]['ID'];

        }

        if(static::$PROM_ART_IN_BASKET>0) {
            $options['CURRENT_PRICE_XML_ID'] = PROM_ART_PRICE_XML_ID;
        } else {
            $options['CURRENT_PRICE_XML_ID']
                = $this->arResult['PRICE_TYPE'][$this->arResult['PRICE'][$options['ID']]['CATALOG_GROUP_ID']]['XML_ID'];
        }

        return $this;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function getModel(array $options = array()): array
    {
        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'Before',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        $this->arResult['BASKET'] = array();
        $this->arResult['~BASKET'] = array(
            'ITEMS'    => array(),
            'SECTIONS' => array(),
        );

        $this->arParams['BASKET']['GETLIST']['DEFAULT_FILTER'] = array(
            'LID'      => SITE_ID,
            'ORDER_ID' => 'NULL',
            'CAN_BUY'  => 'Y',
        );
        $this->arParams['BASKET']['GETLIST']['DEFAULT_SELECT'] = array(
            'ID',
            'PRODUCT_ID',
            'QUANTITY',
            'PRICE',
            'NAME',
            'CURRENCY',
            'DISCOUNT_PRICE',
            'DETAIL_PAGE_URL',
            'NOTES',
        );

        $this->arParams['BASKET']['GETLIST']
            = $this->arParams['BASKET']['GETLIST']
            ?? array();
        $this->arParams['BASKET']['GETLIST']['ORDER']
            = isset($this->arParams['BASKET']['GETLIST']['ORDER'])
            ? array_merge(
                $this->arParams['BASKET']['GETLIST']['ORDER'],
                array()
            ) : array();
        $this->arParams['BASKET']['GETLIST']['FILTER']
            = isset($this->arParams['BASKET']['GETLIST']['FILTER'])
            ? array_merge(
                $this->arParams['BASKET']['GETLIST']['DEFAULT_FILTER'],
                $this->arParams['BASKET']['GETLIST']['FILTER']
            ) : $this->arParams['BASKET']['GETLIST']['DEFAULT_FILTER'];
        $this->arParams['BASKET']['GETLIST']['GROUPBY']
            = $this->arParams['BASKET']['GETLIST']['GROUPBY']
            ?? false;
        $this->arParams['BASKET']['GETLIST']['LIMIT']
            = $this->arParams['BASKET']['GETLIST']['LIMIT']
            ?? false;
        $this->arParams['BASKET']['GETLIST']['SELECT']
            = isset($this->arParams['BASKET']['GETLIST']['SELECT'])
            ? array_merge(
                $this->arParams['BASKET']['GETLIST']['DEFAULT_SELECT'],
                $this->arParams['BASKET']['GETLIST']['SELECT']
            ) : $this->arParams['BASKET']['GETLIST']['DEFAULT_SELECT'];
        $this->arParams['BASKET']['ADDITIONAL']
            = $this->arParams['BASKET']['ADDITIONAL']
            ?? array();

        $options['BASKET'] = !isset($options['BASKET'])
        || !\is_array(
            $options['BASKET']
        ) ? array() : $options['BASKET'];
        $options['BASKET']['GETLIST'] = !isset($options['BASKET']['GETLIST'])
        || !\is_array($options['BASKET']['GETLIST']) ? array()
            : $options['BASKET']['GETLIST'];
        $options['BASKET']['ADDITIONAL']
            = !isset($options['BASKET']['ADDITIONAL'])
        || !\is_array(
            $options['BASKET']['ADDITIONAL']
        ) ? array() : $options['BASKET']['ADDITIONAL'];

        if (isset($options['ORDER_ID'])) {
            $this->arParams['BASKET']['GETLIST']['FILTER']['ORDER_ID']
                = $options['ORDER_ID'];
            $this->arParams['BASKET']['GETLIST']['SELECT'][] = 'ORDER_ID';
        } else {
            $this->arParams['BASKET']['GETLIST']['FILTER']['FUSER_ID']
                = \CSaleBasket::GetBasketUserID();
            $this->arParams['BASKET']['GETLIST']['SELECT'][] = 'FUSER_ID';
        }

        $this->arParams['BASKET']['GETLIST'] = array_replace_recursive(
            $this->arParams['BASKET']['GETLIST'],
            $options['BASKET']['GETLIST']
        );
        $this->arParams['BASKET']['ADDITIONAL'] = array_replace_recursive(
            $this->arParams['BASKET']['ADDITIONAL'],
            $options['BASKET']['ADDITIONAL']
        );

        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'BeforeGetList',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        $nsBasket = \CSaleBasket::GetList(
            $this->arParams['BASKET']['GETLIST']['ORDER'],
            $this->arParams['BASKET']['GETLIST']['FILTER'],
            $this->arParams['BASKET']['GETLIST']['GROUPBY'],
            $this->arParams['BASKET']['GETLIST']['LIMIT'],
            $this->arParams['BASKET']['GETLIST']['SELECT']
        );

        $this->arResult['ORDER']['PRICE'] = 0;
        $this->arResult['ORDER']['FORMAT_PRICE'] = \priceFormat(0, false);
        $this->arResult['ORDER']['TOTAL_PRICE'] = 0;
        $this->arResult['ORDER']['FORMAT_TOTAL_PRICE'] = \priceFormat(0, false);
        $this->arResult['ORDER']['QUANTITY'] = 0;
        $this->arResult['ORDER']['DISCOUNT_VALUE'] = 0;
        $this->arResult['ORDER']['PRICE_DELIVERY'] = 0;
        $this->arResult['ORDER']['FORMAT_PRICE_DELIVERY'] = \priceFormat(0, false);
        $this->arResult['ORDER']['FULL_DISCOUNT_PRICE'] = 0;
        $this->arResult['ORDER']['FORMAT_FULL_DISCOUNT_PRICE'] = \priceFormat(0, false);

        if ($this->arParams['BASKET']['GETLIST']['GROUPBY']) {
            $this->GetEvents(
                array(
                    '__CLASS__'    => __CLASS__,
                    '__FUNCTION__' => __FUNCTION__,
                    'TYPE'         => 'GetListGroupBy',
                    'MODULE'       => 'xGuard',
                    'PARAMS'       => array(
                        'this'      => &$this,
                        'options'   => &$options,
                        'nsBasket'  => &$nsBasket,
                        'arguments' => \func_get_args(),
                    ),
                )
            );

            $this->arResult['BASKET'][$nsBasket['ID']] = $nsBasket;
        } else {
            while ($arBasket = $nsBasket->Fetch()) {
                $this->GetEvents(
                    array(
                        '__CLASS__'    => __CLASS__,
                        '__FUNCTION__' => __FUNCTION__,
                        'TYPE'         => 'BeforeProcess',
                        'MODULE'       => 'xGuard',
                        'PARAMS'       => array(
                            'this'      => &$this,
                            'options'   => &$options,
                            'arBasket'  => &$arBasket,
                            'arguments' => \func_get_args(),
                        ),
                    )
                );

                $this->calcBasket(
                    array(
                        'arBasket' => &$arBasket,
                    )
                );

                $this->GetEvents(
                    array(
                        '__CLASS__'    => __CLASS__,
                        '__FUNCTION__' => __FUNCTION__,
                        'TYPE'         => 'AfterProcess',
                        'MODULE'       => 'xGuard',
                        'PARAMS'       => array(
                            'this'      => &$this,
                            'options'   => &$options,
                            'arBasket'  => &$arBasket,
                            'arguments' => \func_get_args(),
                        ),
                    )
                );
            }

            $this->arResult['ORDER']['FORMAT_PRICE'] = \priceFormat(
                $this->arResult['ORDER']['PRICE'],
                false
            );
            $this->arResult['ORDER']['TOTAL_PRICE'] = $this->arResult['ORDER']['PRICE'] + $this->arResult['ORDER']['PRICE_DELIVERY'];
            $this->arResult['ORDER']['FORMAT_TOTAL_PRICE'] = \priceFormat($this->arResult['ORDER']['TOTAL_PRICE'], false);
        }

        if (!empty($this->arResult['BASKET'])
            && (
                (isset($this->arParams['BASKET']['GET_PROPS'])
                    && $this->arParams['BASKET']['GET_PROPS']
                )
                || (
                    isset($options['BASKET']['GET_PROPS'])
                    && $options['BASKET']['GET_PROPS']
                )
            )
        ):
            $nsItem = \CSaleBasket::GetPropsList(
                array(
                    'SORT' => 'ASC',
                    'NAME' => 'ASC',
                ),
                array('BASKET_ID' => array_keys($this->arResult['BASKET']))
            );

            while ($arItem = $nsItem->Fetch()):
                $basketId = $arItem['BASKET_ID'];

                unset($arItem['BASKET_ID']);

                $this->arResult['BASKET'][$basketId]['PROPS'][$arItem['CODE']]
                    = $arItem;

                static::checkSpecialItemInBasket($this->arResult['BASKET'][$basketId]);
            endwhile;
        endif;

        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'AfterGetList',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        if (isset($this->arParams['BASKET']['ADDITIONAL'])) {

            foreach ($this->arParams['BASKET']['ADDITIONAL'] as $do) {
                $do = 'Get'.$do;
                method_exists($this, $do) ? $this->$do() : false;
            }
        }

        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'After',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        return $this->arResult['BASKET'];
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function calcBasket(array $options = array()): array
    {
        if (empty($options['arBasket'])):
            $this->arResult['ORDER']['PRICE'] = 0;
            $this->arResult['ORDER']['TOTAL_PRICE'] = 0;
            $this->arResult['ORDER']['QUANTITY'] = 0;
            $this->arResult['ORDER']['DISCOUNT_VALUE'] = 0;
            $this->arResult['ORDER']['FULL_DISCOUNT_PRICE'] = 0;
            //$this->arResult['ORDER']['PRICE_DELIVERY']  = 0;
            $this->GetEvents(
                array(
                    '__CLASS__'    => __CLASS__,
                    '__FUNCTION__' => __FUNCTION__,
                    'TYPE'         => 'Before',
                    'MODULE'       => 'xGuard',
                    'PARAMS'       => array(
                        'this'      => &$this,
                        'options'   => &$options,
                        'arguments' => \func_get_args(),
                    ),
                )
            );
        endif;

        $arElements = array();

        if (!empty($options['arBasket'])):
            $arElements[] = &$options['arBasket'];
        elseif (!empty($this->arResult['BASKET'])):
            $arElements = &$this->arResult['BASKET'];
        endif;

        foreach ($arElements as $arBasket):
            $arBasket['QUANTITY']
                = $this->arParams['BASKET']['OPTIONS']['QUANTITY']
            === 'int' ? (int)$arBasket['QUANTITY']
                : (float)$arBasket['QUANTITY'];
            $arBasket['FORMAT_PRICE'] = \priceFormat($arBasket['PRICE'], false);
            $arBasket['FULL_PRICE'] = ($arBasket['PRICE']
                * $arBasket['QUANTITY']);
            $arBasket['FORMAT_FULL_PRICE'] = \priceFormat(
                $arBasket['FULL_PRICE'],
                false
            );
            $arBasket['FORMAT_DISCOUNT_PRICE'] = \priceFormat(
                $arBasket['DISCOUNT_PRICE'],
                false
            );
            $arBasket['FULL_DISCOUNT_PRICE'] = $arBasket['FULL_PRICE']
                - $arBasket['DISCOUNT_PRICE'];
            $arBasket['FORMAT_FULL_DISCOUNT_PRICE'] = \priceFormat(
                $arBasket['FULL_DISCOUNT_PRICE'],
                false
            );
            $this->arResult['BASKET'][$arBasket['ID']] = $arBasket;
            $this->arResult['~BASKET']['ITEMS'][$arBasket['PRODUCT_ID']]
                = $arBasket['ID'];
            $this->arResult['~BASKET']['CATALOG'][$arBasket['PRODUCT_ID']]
                = $arBasket['ID'];

            $this->arResult['ORDER']['FUSER_ID'] = $arBasket['FUSER_ID'];
            $this->arResult['ORDER']['PRICE'] += $arBasket['FULL_PRICE'];
            $this->arResult['ORDER']['FULL_DISCOUNT_PRICE']
                += $arBasket['FULL_DISCOUNT_PRICE'];
            $this->arResult['ORDER']['QUANTITY'] += $arBasket['QUANTITY'];
            $this->arResult['ORDER']['CURRENCY'] = $arBasket['CURRENCY'];
            $this->arResult['ORDER']['DISCOUNT_VALUE']
                += $arBasket['DISCOUNT_PRICE'];

        endforeach;

        //if (empty($options['arBasket'])):
        //$this->arResult['ORDER']['PRICE']           -= $this->arResult['ORDER']['DISCOUNT_VALUE'];
        $this->arResult['ORDER']['FORMAT_PRICE'] = \priceFormat(
            $this->arResult['ORDER']['PRICE'],
            false
        );
        $this->arResult['ORDER']['FORMAT_FULL_DISCOUNT_PRICE']
            = \priceFormat(
            $this->arResult['ORDER']['FULL_DISCOUNT_PRICE'],
            false
        );
        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'After',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        //endif;

        return $this->arResult['ORDER'];
    }

    /**
     * @param array $options
     *
     * @return array|bool
     */
    public function updateBasket(array $options = array())
    {
        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'Before',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        $this->getModel($options);

        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'BeforeUpdate',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        if ($result = \CSaleBasket::Update(
            $options['ID'],
            array(
                'QUANTITY' => $options['QUANTITY'],
            )
        )
        ) {
            $this->arResult['BASKET'][$options['ID']]['QUANTITY']
                = $options['QUANTITY'];
        }

        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'AfterUpdate',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        $this->calcBasket();

        $this->arResult['MESSAGE'] = $this->arResult['MESSAGE'] ??
            GetMessage('MODULE_XGUARD_MAIN_BASKET_INIT_MESSAGE_UPDATE_ITEM');

        unset($this->arResult['MESSAGE']);

        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'After',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        return $this->arResult;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function getItem(array $options = array()): array
    {
        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'Before',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        if (empty($options['ID'])
            && empty($this->arResult['~BASKET']['ITEMS'])
        ) {
            return [];
        }

        $this->arParams['ITEMS']['OPTIONS']
            = isset($this->arParams['ITEMS']['OPTIONS'])
        && \is_array(
            $this->arParams['ITEMS']['OPTIONS']
        ) ? $this->arParams['ITEMS']['OPTIONS'] : array();
        $this->arParams['ITEMS']['OPTIONS']['PROPERTY_CODE'] = !\is_array(
            $this->arParams['ITEMS']['OPTIONS']['PROPERTY_CODE']
        ) ? array($this->arParams['ITEMS']['OPTIONS']['PROPERTY_CODE'])
            : $this->arParams['ITEMS']['OPTIONS']['PROPERTY_CODE'];
        $this->arParams['ITEMS']['GETLIST']
            = $this->arParams['ITEMS']['GETLIST']
            ?? array();
        //$this->arParams['ITEMS']['GETLIST'] = array();
        $this->arParams['ITEMS']['~GETLIST'] = array_merge_recursive(
            $this->arParams['ITEMS']['GETLIST'],
            array(
                'ORDER'   => array(),
                'FILTER'  => array(
                    'LID' => SITE_ID,
                    'ID'  => !empty($options['ID']) ? $options['ID']
                        : array_keys($this->arResult['~BASKET']['ITEMS']),
                ),
                'GROUPBY' => false,
                'LIMIT'   => false,
                'SELECT'  => array(
                    'ID',
                    'XML_ID',
                    'CODE',
                    'IBLOCK_SECTION_ID',
                    'IBLOCK_ID',
                    'NAME',
                    'DETAIL_PICTURE',
                    'PREVIEW_PICTURE',
                    'DETAIL_PAGE_URL',
                ),
            )
        );

        $this->arParams['ITEMS_PROPERTIES']['GETLIST']
            = $this->arParams['ITEMS_PROPERTIES']['GETLIST']
            ?? array();
        $this->arParams['ITEMS_PROPERTIES']['GETLIST']['ORDER'] = array();
        $this->arParams['ITEMS_PROPERTIES']['GETLIST']['FILTER'] = array();

        $options['ITEMS'] = !isset($options['ITEMS'])
        || !\is_array(
            $options['ITEMS']
        ) ? array() : $options['ITEMS'];
        $options['ITEMS']['GETLIST'] = !isset($options['ITEMS']['GETLIST'])
        || !\is_array($options['ITEMS']['GETLIST']) ? array()
            : $options['ITEMS']['GETLIST'];
        $options['ITEMS_PROPERTIES'] = !isset($options['ITEMS_PROPERTIES'])
        || !\is_array($options['ITEMS_PROPERTIES']) ? array()
            : $options['ITEMS_PROPERTIES'];
        $options['ITEMS_PROPERTIES']['GETLIST']
            = !isset($options['ITEMS_PROPERTIES']['GETLIST'])
        || !\is_array(
            $options['ITEMS_PROPERTIES']['GETLIST']
        ) ? array() : $options['ITEMS_PROPERTIES']['GETLIST'];

        $this->arParams['ITEMS']['~GETLIST'] = array_merge_recursive(
            $this->arParams['ITEMS']['~GETLIST'],
            $options['ITEMS']['GETLIST']
        );
        $this->arParams['ITEMS_PROPERTIES']['GETLIST'] = array_merge_recursive(
            $this->arParams['ITEMS_PROPERTIES']['GETLIST'],
            $options['ITEMS_PROPERTIES']['GETLIST']
        );

        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'BeforeGetList',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        $nsItem = \CIBlockElement::GetList(
            $this->arParams['ITEMS']['~GETLIST']['ORDER'],
            $this->arParams['ITEMS']['~GETLIST']['FILTER'],
            $this->arParams['ITEMS']['~GETLIST']['GROUPBY'],
            $this->arParams['ITEMS']['~GETLIST']['LIMIT'],
            $this->arParams['ITEMS']['~GETLIST']['SELECT']
        );

        $arIblock = array();

        $arItem = $nsItem->fetch();

        while ($arItem) {
            $this->GetEvents(
                array(
                    '__CLASS__'    => __CLASS__,
                    '__FUNCTION__' => __FUNCTION__,
                    'TYPE'         => 'BeforeItem',
                    'MODULE'       => 'xGuard',
                    'PARAMS'       => array(
                        'this'      => &$this,
                        'options'   => &$options,
                        'arItem'    => &$arItem,
                        'arIblock'  => &$arIblock,
                        'arguments' => \func_get_args(),
                    ),
                )
            );

            $arItem['PARENT_IBLOCK_ID'] = $arItem['PARENT_IBLOCK_ID'] ??
                $arItem['IBLOCK_ID'];
            $arItem['PARENT_ID'] = $arItem['PARENT_ID'] ?? $arItem['ID'];
            $arItem['PARENT_ELEMENT'] = $arItem['PARENT_ELEMENT'] ?? array(
                    'ID'        => $arItem['ID'],
                    'IBLOCK_ID' => $arItem['IBLOCK_ID'],
                );

            if (isset($this->arParams['ITEMS']['OPTIONS']['PROPERTY_CODE'])):
                $arIblock[$arItem['IBLOCK_ID']]
                    = empty($arIblock[$arItem['IBLOCK_ID']])
                    ? \CCatalog::GetById($arItem['IBLOCK_ID'])
                    : $arIblock[$arItem['IBLOCK_ID']];

                foreach (
                    $this->arParams['ITEMS']['OPTIONS']['PROPERTY_CODE'] as $key
                => $value
                ):
                    $this->arParams['ITEMS_PROPERTIES']['GETLIST']['FILTER']['CODE']
                        = $value;

                    $nsItemProperties = \CIBlockElement::GetProperty(
                        $arItem['IBLOCK_ID'],
                        $arItem['ID'],
                        $this->arParams['ITEMS_PROPERTIES']['GETLIST']['ORDER'],
                        $this->arParams['ITEMS_PROPERTIES']['GETLIST']['FILTER']
                    );

                    while ($arProperty = $nsItemProperties->Fetch()):
                        $this->GetEvents(
                            array(
                                '__CLASS__'    => __CLASS__,
                                '__FUNCTION__' => __FUNCTION__,
                                'TYPE'         => 'CheckProperty',
                                'MODULE'       => 'xGuard',
                                'PARAMS'       => array(
                                    'this'       => &$this,
                                    'options'    => &$options,
                                    'arItem'     => &$arItem,
                                    'arIblock'   => &$arIblock,
                                    'arProperty' => &$arProperty,
                                    'arguments'  => \func_get_args(),
                                ),
                            )
                        );

                        if (!isset($arItem['PROPERTIES'][$arProperty['CODE']])):
                            $arItem['PROPERTIES'][$arProperty['CODE']]
                                = $arProperty;
                        else:
                            $arItem['PROPERTIES'][$arProperty['CODE']]['VALUE']
                                = !\is_array(
                                $arItem['PROPERTIES'][$arProperty['CODE']]['VALUE']
                            )
                                ? array($arItem['PROPERTIES'][$arProperty['CODE']]['VALUE'])
                                : $arItem['PROPERTIES'][$arProperty['CODE']]['VALUE'];
                            $arItem['PROPERTIES'][$arProperty['CODE']]['DESCRIPTION']
                                = !\is_array(
                                $arItem['PROPERTIES'][$arProperty['CODE']]['DESCRIPTION']
                            )
                                ? array($arItem['PROPERTIES'][$arProperty['CODE']]['DESCRIPTION'])
                                : $arItem['PROPERTIES'][$arProperty['CODE']]['DESCRIPTION'];
                            $arItem['PROPERTIES'][$arProperty['CODE']]['VALUE'][]
                                = $arProperty['VALUE'];
                            $arItem['PROPERTIES'][$arProperty['CODE']]['DESCRIPTION'][]
                                = $arProperty['DESCRIPTION'];
                        endif;
                    endwhile;
                endforeach;
            endif;

            Main\Section\Filter::GetInstance()->SetPageUrl(
                array(
                    'URL'       => &$arItem['DETAIL_PAGE_URL'],
                    'TYPE'      => \constant(
                        $arItem['PROPERTIES']['ITEM_TYPE']['VALUE_XML_ID']
                    ),
                    'ID'        => $arItem['~ID'],
                    'CODE'      => $arItem['CODE'],
                    'IBLOCK_ID' => $arItem['~IBLOCK_ID'],
                )
            );

            $this->GetEvents(
                array(
                    '__CLASS__'    => __CLASS__,
                    '__FUNCTION__' => __FUNCTION__,
                    'TYPE'         => 'BeforeMergeBasketItem',
                    'MODULE'       => 'xGuard',
                    'PARAMS'       => array(
                        'this'      => &$this,
                        'options'   => &$options,
                        'arItem'    => &$arItem,
                        'arIblock'  => &$arIblock,
                        'arguments' => \func_get_args(),
                    ),
                )
            );

            static::checkSpecialItemInBasket($arItem);

            if (isset($this->arResult['~BASKET']['ITEMS'][$arItem['ID']])):
                $this->arResult['BASKET'][$this->arResult['~BASKET']['ITEMS'][$arItem['ID']]]['ELEMENT']
                    = $arItem;

                if (isset($arItem['IBLOCK_SECTION_ID'])):
                    $this->arResult['~BASKET']['SECTIONS'][$arItem['IBLOCK_SECTION_ID']]
                        = $this->arResult['~BASKET']['ITEMS'][$arItem['ID']];
                endif;
            elseif ($arItem['PARENT_ID'] === $arItem['ID']
                && $arItem['PARENT_IBLOCK_ID'] === $arItem['IBLOCK_ID']
            ):
                return $arItem;
            endif;

            $this->GetEvents(
                array(
                    '__CLASS__'    => __CLASS__,
                    '__FUNCTION__' => __FUNCTION__,
                    'TYPE'         => 'AfterMergeBasketItem',
                    'MODULE'       => 'xGuard',
                    'PARAMS'       => array(
                        'this'      => &$this,
                        'options'   => &$options,
                        'arItem'    => &$arItem,
                        'arIblock'  => &$arIblock,
                        'arguments' => \func_get_args(),
                    ),
                )
            );

            if ($arItem['PARENT_ID'] !== $arItem['ID']
                && $arItem['PARENT_IBLOCK_ID'] !== $arItem['IBLOCK_ID']
            ):
                $arItem['OFFER_PROPERTIES'] = $arItem['PROPERTIES'];
                $arItem['PROPERTIES'] = array();
                $arItem['ID'] = $arItem['PARENT_ID'];
                $arItem['IBLOCK_ID'] = $arItem['PARENT_IBLOCK_ID'];
            else:
                $arItem = $nsItem->Fetch();
            endif;

            $this->GetEvents(
                array(
                    '__CLASS__'    => __CLASS__,
                    '__FUNCTION__' => __FUNCTION__,
                    'TYPE'         => 'AfterItem',
                    'MODULE'       => 'xGuard',
                    'PARAMS'       => array(
                        'this'      => &$this,
                        'options'   => &$options,
                        'arItem'    => &$arItem,
                        'arIblock'  => &$arIblock,
                        'arguments' => \func_get_args(),
                    ),
                )
            );
        }

        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'AfterGetList',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'After',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        return isset($options['ID'], $this->arResult['~BASKET']['ITEMS'][$options['ID']])
            ? $this->arResult['BASKET'][$this->arResult['~BASKET']['ITEMS'][$options['ID']]]['ELEMENT']
            : array();
    }

    /**
     * @param array $options
     */
    public function getCatalog(array $options = array())
    {
        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'Before',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        if ((empty($this->arResult['~BASKET']['CATALOG'])
                && empty($options['ID']))
            || !$this->IncludeModule('catalog')
        ) {
            return;
        }

        $this->arParams['CATALOG']['GETLIST']
            = $this->arParams['CATALOG']['GETLIST']
            ?? array();
        $this->arParams['CATALOG']['~GETLIST'] = array_merge_recursive(
            $this->arParams['CATALOG']['GETLIST'],
            array(
                'ORDER'   => array(),
                'FILTER'  => array(
                    'LID'        => SITE_ID,
                    'PRODUCT_ID' => !empty($options['ID']) ? $options['ID']
                        : array_keys($this->arResult['~BASKET']['CATALOG']),
                ),
                'GROUPBY' => false,
                'LIMIT'   => false,
                'SELECT'  => array(
                    '*',
                ),
            )
        );

        $options['CATALOG'] = !isset($options['CATALOG'])
        || !\is_array(
            $options['CATALOG']
        ) ? array() : $options['CATALOG'];
        $options['CATALOG']['GETLIST'] = !isset($options['CATALOG']['GETLIST'])
        || !\is_array($options['CATALOG']['GETLIST']) ? array()
            : $options['CATALOG']['GETLIST'];

        $this->arParams['CATALOG']['~GETLIST'] = array_merge_recursive(
            $this->arParams['CATALOG']['~GETLIST'],
            $options['CATALOG']['GETLIST']
        );

        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'BeforeGetList',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        $nsItem = \CCatalogProduct::GetList(
            $this->arParams['CATALOG']['~GETLIST']['ORDER'],
            $this->arParams['CATALOG']['~GETLIST']['FILTER'],
            $this->arParams['CATALOG']['~GETLIST']['GROUPBY'],
            $this->arParams['CATALOG']['~GETLIST']['LIMIT'],
            $this->arParams['CATALOG']['~GETLIST']['SELECT']
        );

        while ($arItem = $nsItem->Fetch()):
            $this->GetEvents(
                array(
                    '__CLASS__'    => __CLASS__,
                    '__FUNCTION__' => __FUNCTION__,
                    'TYPE'         => 'BeforeItem',
                    'MODULE'       => 'xGuard',
                    'PARAMS'       => array(
                        'this'      => &$this,
                        'options'   => &$options,
                        'arItem'    => &$arItem,
                        'arguments' => \func_get_args(),
                    ),
                )
            );

            if (!isset($this->arResult['~BASKET']['CATALOG'][$arItem['ID']], $this->arResult['BASKET'][$this->arResult['~BASKET']['CATALOG'][$arItem['ID']]])):
                continue;
            endif;

            $this->arResult['BASKET'][$this->arResult['~BASKET']['CATALOG'][$arItem['ID']]]['CATALOG']
                = $arItem;
        endwhile;

        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'After',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );
    }

    /**
     *
     */
    public function getSection()
    {
        if (empty($this->arResult['~BASKET']['SECTIONS'])) {
            return;
        }

        $this->arParams['SECTIONS']['GETLIST']
            = $this->arParams['SECTIONS']['GETLIST']
            ?? array(
                'ORDER'   => array(),
                'FILTER'  => array(
                    'LID' => SITE_ID,
                    'ID'  => array_keys($this->arResult['~BASKET']['SECTIONS']),
                ),
                'GROUPBY' => false,
                'LIMIT'   => false,
                'SELECT'  => array(
                    'ID',
                    'NAME',
                    'CODE',
                    'PICTURE',
                ),
            );

        $nsSection = \CIBlockSection::GetList(
            $this->arParams['SECTIONS']['GETLIST']['ORDER'],
            $this->arParams['SECTIONS']['GETLIST']['FILTER'],
            $this->arParams['SECTIONS']['GETLIST']['GROUPBY'],
            $this->arParams['SECTIONS']['GETLIST']['SELECT'],
            $this->arParams['SECTIONS']['GETLIST']['LIMIT']
        );

        while ($arSection = $nsSection->Fetch()) {
            $this->arResult['BASKET'][$this->arResult['~BASKET']['SECTIONS'][$arSection['ID']]]['SECTION']
                = $arSection;
        }
    }

    /**
     *
     */
    public function getStore()
    {
        if (empty($this->arResult['~BASKET']['ITEMS'])) {
            return;
        }

        $this->getStores();

        $this->arParams['LOCATION'] = $this->arParams['LOCATION'] ??
            Main\Location::GetInstance()->Get();

        $this->arParams['STORE']['GETLIST']
            = $this->arParams['STORE']['GETLIST']
            ?? array(
                'ORDER'   => array(),
                'FILTER'  => array(
                    'LID'             => SITE_ID,
                    'ACTIVE'          => 'Y',
                    'PRODUCT_ID'      => array_keys(
                        $this->arResult['~BASKET']['ITEMS']
                    ),
                    '!PRODUCT_AMOUNT' => 0,
                    'STORE_ID'        => array_keys(
                        $this->arResult['BASKET']['STORES']
                    ),
                ),
                'GROUPBY' => false,
                'LIMIT'   => false,
                'SELECT'  => array(
                    'ID',
                    'PRODUCT_ID',
                    'AMOUNT',
                    'STORE_ID',
                    'STORE_NAME',
                ),
            );

        $nsStore = \CCatalogStoreProduct::GetList(
            $this->arParams['STORE']['GETLIST']['ORDER'],
            $this->arParams['STORE']['GETLIST']['FILTER'],
            $this->arParams['STORE']['GETLIST']['GROUPBY'],
            $this->arParams['STORE']['GETLIST']['LIMIT'],
            $this->arParams['STORE']['GETLIST']['SELECT']
        );

        while ($arStore = $nsStore->Fetch()) {
            $this->arResult['BASKET'][$this->arResult['~BASKET']['ITEMS'][$arStore['PRODUCT_ID']]]['STORES'][$arStore['ID']]
                = $arStore;
        }
    }

    /**
     * @return mixed
     */
    public function getStores()
    {
        $this->arParams['LOCATION'] = $this->arParams['LOCATION'] ??
            Main\Location::GetInstance()->Get();

        if (\is_object($this->arParams['LOCATION'])):
            $this->arResult['ERRORS']['STORES'][] = __LINE__;

            return $this->arParams['LOCATION'];
        endif;

        $this->arParams['STORES']['GETLIST']
            = $this->arParams['STORES']['GETLIST']
            ?? array(
                'ORDER'   => array(),
                'FILTER'  => array(
                    'LID'      => SITE_ID,
                    'ACTIVE'   => 'Y',
                    '%ADDRESS' => $this->arParams['LOCATION']['CITY_NAME'],
                ),
                'GROUPBY' => false,
                'LIMIT'   => false,
                'SELECT'  => array(
                    '*',
                ),
            );

        $nsStore = \CCatalogStore::GetList(
            $this->arParams['STORES']['GETLIST']['ORDER'],
            $this->arParams['STORES']['GETLIST']['FILTER'],
            $this->arParams['STORES']['GETLIST']['GROUPBY'],
            $this->arParams['STORES']['GETLIST']['LIMIT'],
            $this->arParams['STORES']['GETLIST']['SELECT']
        );

        while ($arStore = $nsStore->Fetch()) {
            if (
                !empty($arStore['IMAGE_ID'])
                && ((int)$this->arParams['STORES']['IMAGE']['WIDTH'])
                && ((int)$this->arParams['STORES']['IMAGE']['HEIGHT'])
                && isset($this->arParams['STORES']['IMAGE']['WIDTH'], $this->arParams['STORES']['IMAGE']['HEIGHT'])
            ) {
                $arStore['IMAGE'] = CFile::ResizeImageGet(
                    $arStore['IMAGE_ID'],
                    array(
                        'width'  => $this->arParams['STORES']['IMAGE']['WIDTH'],
                        'height' => $this->arParams['STORES']['IMAGE']['HEIGHT'],
                    ),
                    BX_RESIZE_IMAGE_PROPORTIONAL_ALT
                );
            }

            $arStore['ACTIVE'] = $this->checkStoreActive(
                array('arStore' => &$arStore)
            );

            //$this->arResult['BASKET']['~STORES'][$arStore['ID']] = $arStore;

            if (
                !empty($arStore['GPS_N'])
                && !empty($arStore['GPS_S'])
            ) {
                $this->arResult['STORES'][$arStore['ID']] = array(
                    'coords'      => array(
                        (float)$arStore['GPS_N'],
                        (float)$arStore['GPS_S'],
                    ),
                    'ID'          => $arStore['ID'],
                    'IMAGE'       => !empty($arStore['IMAGE'])
                        ? $arStore['IMAGE']['src'] : null,
                    'TITLE'       => $arStore['TITLE'],
                    'ADDRESS'     => $arStore['ADDRESS'],
                    'DESCRIPTION' => $arStore['DESCRIPTION'],
                    'PHONE'       => $arStore['PHONE'],
                    'EMAIL'       => $arStore['EMAIL'],
                    'SCHEDULE'    => $arStore['SCHEDULE'],
                    'ACTIVE'      => $arStore['ACTIVE'],
                    'XML_ID'      => $arStore['XML_ID'],
                );

                $this->arResult['~STORES'][$arStore['XML_ID']] = array();
            }
        }

        if (!$this->IncludeModule('highloadblock')
            || !$this->IncludeModule(
                'iblock'
            )
        ):
            $this->arResult['ERRORS']['STORES'][] = __LINE__;

            return $this->arParams['LOCATION'];
        endif;

        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::GetById(3)->Fetch();
        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity(
            $hlblock
        );
        $entityDataClass = $entity->getDataClass();

        $arFilter = array(
            'UF_TYPE'    => 'stores',
            'UF_COUNTRY' => CURRENT_LANG,
            'UF_EXT_ID'  => array_keys(
                $this->arResult['~STORES']
            ),
            'UF_ACTIVE'  => '1',
        );

        $nsObject = $entityDataClass::GetList(
            array(
                'filter' => $arFilter,
                'order'  => array('UF_NAME' => 'ASC'),
                'select' => array('*'),
            )
        );

        while ($arItem = $nsObject->Fetch()):
            $points = explode(',', $arItem['UF_COORDS']);
            $arItem['UF_COORDS_Y'] = trim($points['0']);
            $arItem['UF_COORDS_X'] = trim($points['1']);

            if ($arItem['UF_METRO']) {
                $arItem['UF_METRO_COLOR'] = substr(
                    $arItem['UF_METRO'],
                    strpos($arItem['UF_METRO'], '(') + 1,
                    7
                );
                $arItem['UF_METRO'] = substr(
                    $arItem['UF_METRO'],
                    0,
                    strpos($arItem['UF_METRO'], '(')
                );
            }

            $arItem['ICON']
                = $this->arResult['RESULT']['PARTNERS'][$arItem['UF_XML_NAME']]['PREVIEW_PICTURE'];

            $arItem['CURRENT'] = $arItem['UF_CODE']
            === $this->arParams['REQUEST']['town'] ? 'Y' : 'N';

            $this->arResult['~STORES'][$arItem['UF_EXT_ID']] = $arItem;
        endwhile;

        return $this->arParams['LOCATION'];
    }

    /**
     * @return bool
     */
    protected function getUser(): bool
    {
        if (!isset($this->arResult['ORDER']['USER_ID'])) {
            return false;
        }

        $this->arResult['USER'] = \CUser::GetByID(
            $this->arResult['ORDER']['USER_ID']
        )->Fetch();

        return true;
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    protected function checkStoreActive(array $options = array()): bool
    {
        return isset($_SESSION['PICKUP_POINT'])
            && $_SESSION['PICKUP_POINT'] === $options['arStore']['ID'];
    }

    /**
     * @param array $options
     *
     * @return $this
     * @throws Main\Exception
     */
    public function addSubscribe(array $options = array()): self
    {
        $options = array_merge(
            array(
                'CREATED_BY'         => $this->user->GetID(),
                'PRODUCT_ID'         => $options['ID'],
                'PRODUCT_URL'        => $options['URL'],
                'NAME'               => $options['NAME'],
                'QUANTITY'           => $options['QUANTITY'],
                'IBLOCK_ID'          => SUBSCRIBE_IBLOCK_ID,
                ELEMENT_PROP_ARTICLE => $options['ELEMENT_PROP_ARTICLE'],
            ),
            $options
        );

        $this->getSubscribe(
            array(
                'ID' => $options['ID'],
            )
        );
        $element = new \CIBlockElement;
        if (!isset($this->arResult['~SUBSCRIBE'][$options['ID']])):
            unset($options['ID']);

            $result = $element->Add(
                $options
            );
            if (!$result):
                throw new Main\Exception(
                    GetMessage('XGUARD_BASKET_ADD_SUBSCRIBE_ERROR'), __LINE__
                );
            else:
                \CIBlockElement::SetPropertyValuesEx(
                    $result,
                    $options['IBLOCK_ID'],
                    $options
                );
                $this->arResult['HTML'] = GetMessage(
                    'XGUARD_BASKET_ADD_SUBSCRIBE_SUCCESS'
                );
                //$this->arResult['MESSAGE'] = GetMessage('XGUARD_BASKET_ADD_SUBSCRIBE_SUCCESS');
            endif;
        else:
            $options['ACTIVE'] = 'Y';

            $result = $element->Update(
                key($this->arResult['~SUBSCRIBE'][$options['ID']]),
                $options
            );
            if (!$result):
                throw new Main\Exception(
                    GetMessage('XGUARD_BASKET_UPDATE_SUBSCRIBE_ERROR'), __LINE__
                );
            else:
                \CIBlockElement::SetPropertyValuesEx(
                    key($this->arResult['~SUBSCRIBE'][$options['ID']]),
                    $options['IBLOCK_ID'],
                    $options
                );
                $this->arResult['HTML'] = GetMessage(
                    'XGUARD_BASKET_UPDATE_SUBSCRIBE_SUCCESS'
                );
                //$this->arResult['MESSAGE'] = GetMessage('XGUARD_BASKET_UPDATE_SUBSCRIBE_SUCCESS');
            endif;
        endif;

        $this->getSubscribe(
            array(
                'ID' => $options['ID'],
            )
        );

        $this->sendSubscribeEmail();

        return $this;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function getSubscribe(array $options = array()): self
    {
        $this->arParams['SUBSCRIBE']['GETLIST']
            = $this->arParams['SUBSCRIBE']['GETLIST']
            ?? array();
        $this->arParams['SUBSCRIBE']['~GETLIST'] = array_merge_recursive(
            $this->arParams['SUBSCRIBE']['GETLIST'],
            array(
                'ORDER'   => array(),
                'FILTER'  => array(
                    'IBLOCK_ID'            => SUBSCRIBE_IBLOCK_ID,
                    '?PROPERTY_PRODUCT_ID' => !empty($options['ID'])
                        ? $options['ID']
                        : array_keys(
                            $this->arResult['~BASKET']['SUBSCRIBE']
                        ),
                ),
                'GROUPBY' => false,
                'LIMIT'   => false,
                'SELECT'  => array(
                    '*',
                ),
            )
        );

        $options['USER'] = isset($options['USER']) && !empty($options['USER'])
            ? $options['USER'] : $this->user->GetID();

        if (isset($options['ANONYMOUS'])):
            $this->arParams['SUBSCRIBE']['~GETLIST']['FILTER']['NAME']
                = $options['NAME'];
            $this->arParams['SUBSCRIBE']['~GETLIST']['FILTER']['PROPERTY_QUANTITY']
                = $options['QUANTITY'];
            $this->arParams['SUBSCRIBE']['~GETLIST']['FILTER']['PROPERTY_URL']
                = $options['URL'];
            $this->arParams['SUBSCRIBE']['~GETLIST']['FILTER']['CREATED_BY']
                = $options['USER'];
        else:
            $this->arParams['SUBSCRIBE']['~GETLIST']['FILTER']['CREATED_BY']
                = $options['USER'];
        endif;

        $options['SUBSCRIBE'] = !isset($options['SUBSCRIBE'])
        || !\is_array(
            $options['SUBSCRIBE']
        ) ? array() : $options['SUBSCRIBE'];
        $options['SUBSCRIBE']['GETLIST']
            = !isset($options['SUBSCRIBE']['GETLIST'])
        || !\is_array(
            $options['SUBSCRIBE']['GETLIST']
        ) ? array() : $options['SUBSCRIBE']['GETLIST'];

        $this->arParams['SUBSCRIBE']['~GETLIST'] = array_merge_recursive(
            $this->arParams['SUBSCRIBE']['~GETLIST'],
            $options['SUBSCRIBE']['GETLIST']
        );

        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'BeforeGetList',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        $nsItem = \CIBlockElement::GetList(
            $this->arParams['SUBSCRIBE']['~GETLIST']['ORDER'],
            $this->arParams['SUBSCRIBE']['~GETLIST']['FILTER'],
            $this->arParams['SUBSCRIBE']['~GETLIST']['GROUPBY'],
            $this->arParams['SUBSCRIBE']['~GETLIST']['LIMIT'],
            $this->arParams['SUBSCRIBE']['~GETLIST']['SELECT']
        );

        while ($arItem = $nsItem->Fetch()):
            $this->GetEvents(
                array(
                    '__CLASS__'    => __CLASS__,
                    '__FUNCTION__' => __FUNCTION__,
                    'TYPE'         => 'BeforeItem',
                    'MODULE'       => 'xGuard',
                    'PARAMS'       => array(
                        'this'      => &$this,
                        'options'   => &$options,
                        'arItem'    => &$arItem,
                        'arguments' => \func_get_args(),
                    ),
                )
            );
            $nsItemProperties = \CIBlockElement::GetProperty(
                $arItem['IBLOCK_ID'],
                $arItem['ID'],
                $this->arParams['ITEMS_PROPERTIES']['GETLIST']['ORDER'],
                $this->arParams['ITEMS_PROPERTIES']['GETLIST']['FILTER']
            );

            while ($arProperty = $nsItemProperties->Fetch()):
                $arItem['PROPERTIES'][$arProperty['CODE']] = $arProperty;
            endwhile;

            $this->arResult['SUBSCRIBE'][$arItem['ID']] = $arItem;
            $this->arResult['~SUBSCRIBE'][$arItem['PROPERTIES']['PRODUCT_ID']['VALUE']][$arItem['ID']]
                = $arItem['ID'];
        endwhile;

        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'After',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        return $this;
    }

    /**
     * @param array $options
     *
     * @return $this
     * @throws Main\Exception
     */
    public function removeSubscribe(array $options = array()): self
    {
        if (empty($options['ID'])):
            return $this;
        endif;

        $elements = new \CIBlockElement;

        $this->getSubscribe($options);

        if (isset($this->arResult['~SUBSCRIBE'][$options['ID']])):
            $result = $elements->Update(
                key($this->arResult['~SUBSCRIBE'][$options['ID']]),
                array('ACTIVE' => 'N')
            );
            /*$result = \CIBlockElement::Delete(
                key($this->arResult['~SUBSCRIBE'][$options['ID']])
            );*/
        endif;

        if (!empty($result)):
            throw new Main\Exception(
                GetMessage('XGUARD_BASKET_DELETE_SUBSCRIBE_ERROR'), __LINE__
            );
        else:
            unset(
                $this->arResult['~SUBSCRIBE'][$this->arResult['~SUBSCRIBE'][$options['ID']]],
                $this->arResult['~SUBSCRIBE'][$options['ID']]
            );
            //$this->arResult['HTML'] = GetMessage('XGUARD_BASKET_DELETE_SUBSCRIBE_SUCCESS');
            $this->arResult['MESSAGE'] = GetMessage(
                'XGUARD_BASKET_DELETE_SUBSCRIBE_SUCCESS'
            );
        endif;

        $this->getSubscribe(
            array(
                'ID' => $options['ID'],
            )
        );

        return $this;
    }

    /**
     * @param array $options
     *
     * @return $this
     * @throws Main\Exception
     */
    public function addCollection(array $options = array()): self
    {
        $file = \CFile::getFileArray($options['PICTURE']);

        $options = array_merge(
            array(
                'CREATED_BY'         => $this->user->GetID(),
                'PRODUCT_ID'         => $options['ID'],
                'PRODUCT_URL'        => $options['URL'],
                'PRODUCT_PICTURE'    => !empty($file) ? \CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT'].$file['SRC']) : false,
                'NAME'               => $options['NAME'],
                'IBLOCK_ID'          => COLLECTION_IBLOCK_ID,
                ELEMENT_PROP_ARTICLE => $options['ELEMENT_PROP_ARTICLE'],
            ),
            $options
        );

        $this->getCollection(
            array(
                'ID' => $options['ID'],
            )
        );
        $element = new \CIBlockElement;
        if (!isset($this->arResult['~COLLECTION'][$options['ID']])):
            unset($options['ID']);

            $result = $element->Add(
                $options
            );
            if (!$result):
                throw new Main\Exception(
                    GetMessage('XGUARD_BASKET_ADD_COLLECTION_ERROR'), __LINE__
                );
            else:
                \CIBlockElement::SetPropertyValuesEx(
                    $result,
                    $options['IBLOCK_ID'],
                    $options
                );
                $this->arResult['HTML'] = GetMessage(
                    'XGUARD_BASKET_ADD_COLLECTION_SUCCESS'
                );
                //$this->arResult['MESSAGE'] = GetMessage('XGUARD_BASKET_ADD_COLLECTION_SUCCESS');
            endif;
        else:
            $options['ACTIVE'] = 'Y';

            $result = $element->Update(
                key($this->arResult['~COLLECTION'][$options['ID']]),
                $options
            );
            if (!$result):
                throw new Main\Exception(
                    GetMessage('XGUARD_BASKET_UPDATE_COLLECTION_ERROR'), __LINE__
                );
            else:
                \CIBlockElement::SetPropertyValuesEx(
                    key($this->arResult['~COLLECTION'][$options['ID']]),
                    $options['IBLOCK_ID'],
                    $options
                );
                $this->arResult['HTML'] = GetMessage(
                    'XGUARD_BASKET_UPDATE_COLLECTION_SUCCESS'
                );
                //$this->arResult['MESSAGE'] = GetMessage('XGUARD_BASKET_UPDATE_COLLECTION_SUCCESS');
            endif;
        endif;

        $this->getCollection(
            array(
                'ID' => $options['ID'],
            )
        );

        return $this;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function getCollection(array $options = array()): self
    {
        $this->arParams['COLLECTION']['GETLIST']
            = $this->arParams['COLLECTION']['GETLIST']
            ?? array();
        $this->arParams['COLLECTION']['~GETLIST'] = array_merge_recursive(
            $this->arParams['COLLECTION']['GETLIST'],
            array(
                'ORDER'   => array(),
                'FILTER'  => array(
                    'IBLOCK_ID'            => COLLECTION_IBLOCK_ID,
                    '?PROPERTY_PRODUCT_ID' => !empty($options['ID'])
                        ? $options['ID']
                        : array_keys(
                            $this->arResult['~BASKET']['COLLECTION']
                        ),
                ),
                'GROUPBY' => false,
                'LIMIT'   => false,
                'SELECT'  => array(
                    '*',
                ),
            )
        );

        $options['USER'] = isset($options['USER']) && !empty($options['USER'])
            ? $options['USER'] : $this->user->GetID();

        if (isset($options['ANONYMOUS'])):
            $this->arParams['COLLECTION']['~GETLIST']['FILTER']['NAME']
                = $options['NAME'];
            $this->arParams['COLLECTION']['~GETLIST']['FILTER']['PROPERTY_QUANTITY']
                = $options['QUANTITY'];
            $this->arParams['COLLECTION']['~GETLIST']['FILTER']['PROPERTY_URL']
                = $options['URL'];
            $this->arParams['COLLECTION']['~GETLIST']['FILTER']['CREATED_BY']
                = $options['USER'];
        else:
            $this->arParams['COLLECTION']['~GETLIST']['FILTER']['CREATED_BY']
                = $options['USER'];
        endif;

        $options['COLLECTION'] = !isset($options['COLLECTION'])
        || !\is_array(
            $options['COLLECTION']
        ) ? array() : $options['COLLECTION'];
        $options['COLLECTION']['GETLIST']
            = !isset($options['COLLECTION']['GETLIST'])
        || !\is_array(
            $options['COLLECTION']['GETLIST']
        ) ? array() : $options['COLLECTION']['GETLIST'];

        $this->arParams['COLLECTION']['~GETLIST'] = array_merge_recursive(
            $this->arParams['COLLECTION']['~GETLIST'],
            $options['COLLECTION']['GETLIST']
        );

        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'BeforeGetList',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        $nsItem = \CIBlockElement::GetList(
            $this->arParams['COLLECTION']['~GETLIST']['ORDER'],
            $this->arParams['COLLECTION']['~GETLIST']['FILTER'],
            $this->arParams['COLLECTION']['~GETLIST']['GROUPBY'],
            $this->arParams['COLLECTION']['~GETLIST']['LIMIT'],
            $this->arParams['COLLECTION']['~GETLIST']['SELECT']
        );

        while ($arItem = $nsItem->Fetch()):
            $this->GetEvents(
                array(
                    '__CLASS__'    => __CLASS__,
                    '__FUNCTION__' => __FUNCTION__,
                    'TYPE'         => 'BeforeItem',
                    'MODULE'       => 'xGuard',
                    'PARAMS'       => array(
                        'this'      => &$this,
                        'options'   => &$options,
                        'arItem'    => &$arItem,
                        'arguments' => \func_get_args(),
                    ),
                )
            );
            $nsItemProperties = \CIBlockElement::GetProperty(
                $arItem['IBLOCK_ID'],
                $arItem['ID'],
                $this->arParams['ITEMS_PROPERTIES']['GETLIST']['ORDER'],
                $this->arParams['ITEMS_PROPERTIES']['GETLIST']['FILTER']
            );

            while ($arProperty = $nsItemProperties->Fetch()):
                $arItem['PROPERTIES'][$arProperty['CODE']] = $arProperty;
            endwhile;

            $this->arResult['COLLECTION'][$arItem['ID']] = $arItem;
            $this->arResult['~COLLECTION'][$arItem['PROPERTIES']['PRODUCT_ID']['VALUE']][$arItem['ID']]
                = $arItem['ID'];
        endwhile;

        $this->GetEvents(
            array(
                '__CLASS__'    => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                'TYPE'         => 'After',
                'MODULE'       => 'xGuard',
                'PARAMS'       => array(
                    'this'      => &$this,
                    'options'   => &$options,
                    'arguments' => \func_get_args(),
                ),
            )
        );

        return $this;
    }

    /**
     * @param array $options
     *
     * @return $this
     * @throws Main\Exception
     */
    public function removeCollection(array $options = array()): self
    {
        if (empty($options['ID'])):
            return $this;
        endif;

        $elements = new \CIBlockElement;

        $this->getCollection($options);

        if (isset($this->arResult['~COLLECTION'][$options['ID']])):
            $result = $elements->Update(
                key($this->arResult['~COLLECTION'][$options['ID']]),
                array('ACTIVE' => 'N')
            );
        endif;

        if (!empty($result)):
            throw new Main\Exception(
                GetMessage('XGUARD_BASKET_DELETE_COLLECTION_ERROR'), __LINE__
            );
        else:
            unset(
                $this->arResult['~COLLECTION'][$this->arResult['~COLLECTION'][$options['ID']]],
                $this->arResult['~COLLECTION'][$options['ID']]
            );
            $this->arResult['MESSAGE'] = GetMessage(
                'XGUARD_BASKET_DELETE_COLLECTION_SUCCESS'
            );
        endif;

        $this->getCollection(
            array(
                'ID' => $options['ID'],
            )
        );

        return $this;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function sendSubscribeEmail(array $options = array()): array
    {
        $options['USER_FULL_NAME'] = $this->user->GetFullName();

        $options['SALE_EMAIL'] = \COption::GetOptionString(
            'sale',
            'order_email',
            'order@'.SERVER_NAME
        );

        $options['ITEM_TABLE']
            = '
                        <table border="1" cellspacing="0" cellpadding="5">
                            <tr>
                                <td bgcolor="#a0a0a0">'.GetMessage(
                'XGUARD_MAIL_SALE_ORDER_NEW_ARTICLE'
            ).'</td>
                                <td bgcolor="#a0a0a0">'.GetMessage(
                'XGUARD_MAIL_SALE_ORDER_NEW_NAME'
            ).'</td>
                                <td bgcolor="#a0a0a0">'.GetMessage(
                'XGUARD_MAIL_SALE_ORDER_NEW_QUANTITY'
            ).'</td>
                                </tr>';
        foreach ($this->arResult['SUBSCRIBE'] as &$arItem):
            $arItem['PROPERTIES'][ELEMENT_PROP_ARTICLE]
                = !empty($arItem['PROPERTIES'][ELEMENT_PROP_ARTICLE])
                ? $arItem['PROPERTIES'][ELEMENT_PROP_ARTICLE]
                : \CIBlockElement::GetProperty(
                    OFFER_IBLOCK_ID,
                    $arItem['PROPERTIES']['PRODUCT_ID']['VALUE'],
                    array(),
                    array('CODE' => ELEMENT_PROP_ARTICLE)
                )->Fetch();
            $options['ITEM_TABLE'] .= '<tr><td>'.implode(
                    '</td><td>',
                    array(
                        $arItem['PROPERTIES'][ELEMENT_PROP_ARTICLE]['VALUE'],
                        $arItem['NAME'],
                        $arItem['PROPERTIES']['QUANTITY']['VALUE'],
                    )
                ).'</td></tr>'."\n";
        endforeach;

        unset($arItem);

        $options['ITEM_TABLE'] .= '</tr></table>';

        $obEvent = new \CEvent;

        $obEvent->SendImmediate('SENDER_SUBSCRIBE', SITE_ID, $options);

        return $options;
    }
}

