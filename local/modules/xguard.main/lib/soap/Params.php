<?php
/**
 * xGuard Framework
 *
 * @package    xGuard
 * @subpackage main
 * @copyright  2014 xGuard
 */

namespace xGuard\Main\Soap;

use \xGuard\Main;
use \xGuard\Main\Basket\Base as BasketBase;

/**
 * Base entity
 */
IncludeModuleLangFile(__FILE__);

/**
 * Class Params
 *
 * @package xGuard\Main\Soap
 */
class Params extends Main
{

    /**
     * @var
     */
    private static $soap;

    /**
     * @return mixed
     * @throws Main\Exception
     */
    public static function getSoapInstance()
    {
        ini_set('soap.wsdl_cache_enabled', '0');

        try {
            ini_set('default_socket_timeout', SOAP_NTLM_TIMEOUT);

            static::$soap = static::$soap
                ?: new SoapClientNTLM(
                //?: new \SoapClient(
                    SOAP_NTLM_URL_FORWARDING,
                    //SOAP_NTLM_URL_WSDL,
                    array(
                        'soap_version'       => SOAP_1_2,
                        'trace'              => 1,
                        'exceptions'         => 1,
                        'login'              => SOAP_NTLM_LOGIN,
                        'password'           => SOAP_NTLM_PASSWORD,
                        'compression'        => SOAP_COMPRESSION_ACCEPT
                            | SOAP_COMPRESSION_GZIP,
                        'connection_timeout' => SOAP_NTLM_TIMEOUT,
                    )
                );

            /** @noinspection PhpUndefinedMethodInspection */
            static::$soap->Hello(array());

            return static::$soap;
        } catch (\Throwable $e) {
            throw new Main\Exception($e->getMessage(), __LINE__);
        }
    }

    /**
     * @param array $options
     *
     * @return \SoapClient
     */
    public static function getInstance(array $options = []): \SoapClient
    {
        try {
            static::getSoapInstance();
        } catch (\Throwable $e) {

        }

        return static::$soap;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public static function prepare($options = array()): array
    {
        $options = !\is_array($options) ? array() : $options;
        $options['PARAMS'] = !\is_array($options['PARAMS']) ? array()
            : $options['PARAMS'];
        $options['PARAMS']['NOT_EMPTY'] = isset($options['PARAMS']['NOT_EMPTY'])
        && \is_bool($options['PARAMS']['NOT_EMPTY'])
            ? $options['PARAMS']['NOT_EMPTY'] : false;
        $options['PARAMS']['ACTION'] = isset($options['PARAMS']['ACTION'])
        && \is_string($options['PARAMS']['ACTION'])
        && !empty($options['PARAMS']['ACTION']) ? $options['PARAMS']['ACTION']
            : 'Test';
        $method = __FUNCTION__.$options['PARAMS']['ACTION'];
        $arResult = array();

        if (isset($options['SCHEME']) && \is_array($options['SCHEME'])
            && !empty($options['SCHEME'])
        ) {
            $arScheme = $options['SCHEME'];
        } else {
            $arScheme = method_exists(
                __CLASS__,
                $method
            ) ? self::$method($options) : array();
        }

        isset($options['VALUE']['PASSPORT_ISSUE_DATE'])
        && !empty($options['VALUE']['PASSPORT_ISSUE_DATE']) ? (
        $options['VALUE']['PASSPORT_ISSUE_DATE'] = date(
            'Y-m-d',
            strtotime($options['VALUE']['PASSPORT_ISSUE_DATE'])
        )) : false;

        foreach ($options['VALUE'] as $key => $value):
            if (
                isset($arScheme[$key])
                && (
                    (
                        $options['PARAMS']['NOT_EMPTY']
                        && (
                            \is_bool($value)
                            || is_numeric($value)
                            || !empty($value)
                        )
                    )
                    || (!$options['PARAMS']['NOT_EMPTY'])
                )
            ):
                if (\is_array($value)):
                    $currentKey = key($arScheme[$key]);
                    $curKey = key($value);

                    if (is_numeric($curKey)):
                        foreach ($value as $curKey => $curValue):
                            $temp = static::prepare(
                                array(
                                    'VALUE'  => $curValue,
                                    'SCHEME' => $arScheme[$key][$currentKey],
                                    'PARAMS' => array(
                                        'NOT_EMPTY' => $options['PARAMS']['NOT_EMPTY'],
                                        'ACTION'    => $options['PARAMS']['ACTION'],
                                        'KEY'       => $key,
                                        'RKEY'      => $options['PARAMS']['RKEY'],
                                    ),
                                )
                            );

                            if (!empty($temp)):
                                $arResult[$currentKey][] = $temp;
                            endif;
                        endforeach;
                    else:
                        $temp = static::prepare(
                            array(
                                'VALUE'  => $value,
                                'SCHEME' => $arScheme[$key][$currentKey],
                                'PARAMS' => array(
                                    'NOT_EMPTY' => $options['PARAMS']['NOT_EMPTY'],
                                    'ACTION'    => $options['PARAMS']['ACTION'],
                                    'KEY'       => $key,
                                    'RKEY'      => $options['PARAMS']['RKEY'],
                                ),
                            )
                        );

                        if (!empty($temp)):
                            $arResult[$currentKey] = $temp;
                        endif;
                    endif;
                else:
                    $arResult[$arScheme[$key]] = $value;
                endif;
            endif;
        endforeach;

        return $arResult;
    }

    /**
     * @return array
     */
    protected static function prepareCreateClient(): array
    {
        return array(
            'TypeClient'             => 'PERSON_TYPE_ID',
            'PERSON_TYPE_ID'         => 'TypeClient',
            'ParentINN'              => 'INN_PARENT',
            'INN_PARENT'             => 'ParentINN',
            'ParentKPP'              => 'KPP_PARENT',
            'KPP_PARENT'             => 'ParentKPP',
            'Invoice'                => 'BILL',
            'BILL'                   => 'Invoice',
            'AccNum'                 => 'RA',
            'RA'                     => 'AccNum',
            'AccNumCorr'             => 'KA',
            'KA'                     => 'AccNumCorr',
            'AccBIC'                 => 'BIK',
            'BIK'                    => 'AccBIC',
            'AccBank'                => 'BANK_NAME',
            'BANK_NAME'              => 'AccBank',
            'INN'                    => 'INN',
            'KPP'                    => 'KPP',
            'Login'                  => 'EMAIL',
            'EMAIL'                  => 'Login',
            'Name'                   => 'FULL_NAME',
            'FULL_NAME'              => 'Name',
            'NameFull'               => 'BRAND',
            'BRAND'                  => 'NameFull',
            'AddrrLegal'             => 'ADDRESS_REGISTER',
            'ADDRESS_REGISTER'       => 'AddrrLegal',
            'AddrrFact'              => 'ADDRESS_FACT',
            'ADDRESS_FACT'           => 'AddrrFact',
            'AddrrDelivery'          => 'ADDRESS_DELIVERY_INDEX',
            'ADDRESS_DELIVERY_INDEX' => 'AddrrDelivery',
            'Phone'                  => 'PHONE',
            'PHONE'                  => 'Phone',
            'Email'                  => 'USER_EMAIL',
            'USER_EMAIL'             => 'Email',
            'ContactPerson'          => array(
                'CONTACT_PERSON' => array(
                    'CONTACT_NAME'     => 'Name',
                    'Name'             => 'CONTACT_NAME',
                    'Phone'            => 'CONTACT_PHONE',
                    'CONTACT_PHONE'    => 'Phone',
                    'Email'            => 'CONTACT_EMAIL',
                    'CONTACT_EMAIL'    => 'Email',
                    'JobTitle'         => 'CONTACT_POSITION',
                    'CONTACT_POSITION' => 'JobTitle',
                ),
            ),
            'CONTACT_PERSON'         => array(
                'ContactPerson' => array(
                    'CONTACT_NAME'     => 'Name',
                    'Name'             => 'CONTACT_NAME',
                    'Phone'            => 'CONTACT_PHONE',
                    'CONTACT_PHONE'    => 'Phone',
                    'Email'            => 'CONTACT_EMAIL',
                    'CONTACT_EMAIL'    => 'Email',
                    'JobTitle'         => 'CONTACT_POSITION',
                    'CONTACT_POSITION' => 'JobTitle',
                ),
            ),
            'PassSeries'             => 'PASSPORT_SERIES',
            'PASSPORT_SERIES'        => 'PassSeries',
            'PassNum'                => 'PASSPORT_NUMBER',
            'PASSPORT_NUMBER'        => 'PassNum',
            'PassIssued'             => 'ISSUE_NAME',
            'ISSUE_NAME'             => 'PassIssued',
            'PassDateIssued'         => 'PASSPORT_ISSUE_DATE',
            'PASSPORT_ISSUE_DATE'    => 'PassDateIssued',
            'AddrDeliveryClient'     => 'ADDRESS_DELIVERY',
            'ADDRESS_DELIVERY'       => 'AddrDeliveryClient',
            'TimeDeliveryClient'     => 'TIME_DELIVERY',
            'TIME_DELIVERY'          => 'TimeDeliveryClient',
            'ContDelivery'           => 'CONTACT_DELIVERY',
            'CONTACT_DELIVERY'       => 'ContDelivery',
            'Comment'                => 'COMMENTS',
            'COMMENTS'               => 'Comment',
            'OGRN'                   => 'OGRN',
            'GUID'                   => 'guid',
            'guid'                   => 'GUID',
        );
    }

    /**
     * @return array
     */
    protected static function prepareCreateOrder(): array
    {
        return array(
            'DateClient'         => 'DATE_INSERT',
            'DATE_INSERT'        => 'DateClient',
            'INN'                => 'INN',
            'KPP'                => 'KPP',
            'ContactPerson'      => array(
                'CONTACT_PERSON' => array(
                    'CONTACT_NAME'     => 'Name',
                    'Name'             => 'CONTACT_NAME',
                    'Phone'            => 'CONTACT_PHONE',
                    'CONTACT_PHONE'    => 'Phone',
                    'Email'            => 'CONTACT_EMAIL',
                    'CONTACT_EMAIL'    => 'Email',
                    'JobTitle'         => 'CONTACT_POSITION',
                    'CONTACT_POSITION' => 'JobTitle',
                ),
            ),
            'CONTACT_PERSON'     => array(
                'ContactPerson' => array(
                    'CONTACT_NAME'     => 'Name',
                    'Name'             => 'CONTACT_NAME',
                    'Phone'            => 'CONTACT_PHONE',
                    'CONTACT_PHONE'    => 'Phone',
                    'Email'            => 'CONTACT_EMAIL',
                    'CONTACT_EMAIL'    => 'Email',
                    'JobTitle'         => 'CONTACT_POSITION',
                    'CONTACT_POSITION' => 'JobTitle',
                ),
            ),
            'Commodity'          => array(
                'BASKET' => array(
                    'Article'             => ELEMENT_PROP_ARTICLE,
                    ELEMENT_PROP_ARTICLE  => 'Article',
                    'Quantity'            => ELEMENT_PROP_QUANTITY,
                    ELEMENT_PROP_QUANTITY => 'Quantity',
                    'GUID_SOGL'           => 'GUIDSogl',
                    'GUIDSogl'            => 'GUID_SOGL',
                ),
            ),
            'BASKET'             => array(
                'Commodity' => array(
                    'Article'             => ELEMENT_PROP_ARTICLE,
                    ELEMENT_PROP_ARTICLE  => 'Article',
                    'Quantity'            => ELEMENT_PROP_QUANTITY,
                    ELEMENT_PROP_QUANTITY => 'Quantity',
                    'GUID_SOGL'           => 'GUIDSogl',
                    'GUIDSogl'            => 'GUID_SOGL',
                ),
            ),
            'AddrDeliveryClient' => 'ADDRESS_DELIVERY',
            'ADDRESS_DELIVERY'   => 'AddrDeliveryClient',
            'TimeDeliveryClient' => 'TIME_DELIVERY',
            'TIME_DELIVERY'      => 'TimeDeliveryClient',
            'ContDelivery'       => 'CONTACT_DELIVERY',
            'CONTACT_DELIVERY'   => 'ContDelivery',
            'PhoneDelivery'      => 'DELIVERY_PHONE',
            'DELIVERY_PHONE'     => 'PhoneDelivery',
            'Cart'               => 'EDIT',
            'EDIT'               => 'Cart',
            'PointsCount'        => 'POINTS',
            'POINTS'             => 'PointsCount',
            'Email'              => 'USER_EMAIL',
            'USER_EMAIL'         => 'Email',
            'Anesthesia'         => 'AID_IN_BASKET',
            'AID_IN_BASKET'      => 'Anesthesia',
            'SitePayFormID'      => 'PAY_SYSTEM_ID',
            'PAY_SYSTEM_ID'      => 'SitePayFormID',
            'Login'              => 'EMAIL',
            'EMAIL'              => 'Login',
            'CustomerGUID'       => 'GUID',
            'CUSTOMER_GUID'      => 'CustomerGUID',
            'GUID'               => 'GUID',
            'GUID_SOGL'          => 'GUIDSogl',
            'GUIDSogl'           => 'GUID_SOGL',
        );
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public static function prepareIdToCode($options = array()): array
    {
        $b = array();

        foreach ($options['CODE'] as $key => $value):
            if (isset($options['ID'][$value])):
                $b[$key] = $options['ID'][$value];
            endif;
        endforeach;

        return $b;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public static function prepareCodeToId($options = array()): array
    {
        $b = array();

        foreach ($options['CODE'] as $key => $value):
            if (isset($options['ID'][$value])):
                $b[$key] = $options['ID'][$value];
            endif;
        endforeach;

        return $b;
    }

    /**
     * @param array $options
     *
     * @throws Main\Exception
     */
    public static function checkFields($options = array())
    {
        foreach ($options['SCHEME'] as $arItem):
            if (
                $arItem['REQUIED'] === 'Y'
                && (
                    (
                        !isset($options['VALUE'][$arItem['ID']])
                        || (
                            isset($options['VALUE'][$arItem['ID']])
                            && empty($options['VALUE'][$arItem['ID']])
                        )
                    )
                    && (
                        !isset($options['VALUE'][$arItem['CODE']])
                        || (
                            isset($options['VALUE'][$arItem['CODE']])
                            && empty($options['VALUE'][$arItem['CODE']])
                        )
                    )
                )
            ):
                throw new Main\Exception(
                    GetMessage(
                        'XGUARD_MAIN_SOAP_PARAMS_CHECK_FIELDS_PROPERTY_REQUIRED',
                        $arItem
                    ), __LINE__
                );
            endif;
        endforeach;
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    public static function checkPaySystem($options = array()): bool
    {
        $canBy = (
            (
                (
                    isset($_SESSION['AID_IN_BASKET'])
                    && $_SESSION['AID_IN_BASKET'] > 0
                )
                && (
                    $options ['arPaySystem']['DESCRIPTION']
                    === PAY_SYSTEM_NON_CASH_CODE
                )
            )
            || (
                !isset($_SESSION['AID_IN_BASKET'])
                || $_SESSION['AID_IN_BASKET'] <= 0
            )
        );

        return $canBy;
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    public static function checkBuyers($options = array()): bool
    {
        if (BasketBase::$PRECURSOR_IN_BASKET > 0
            && empty($options['arOrderProperties'])
            && $options['arUserProfile']['PERSON_TYPE_ID'] === PERSON_TYPE_ID_PP
        ):
            return false;
        endif;

        $arProperty
            = $options['arOrderProperties'][ORDER_USER_PROPERTY_APPROVE];

        $isPP = $options['arUserProfile']['PERSON_TYPE_ID'] === PERSON_TYPE_ID_PP;
        $canBuyPrecursor = $arProperty['VALUE'] === 'Y' && BasketBase::$PRECURSOR_IN_BASKET > 0;
        $canNotBuyPrecursor = BasketBase::$PRECURSOR_IN_BASKET === 0
            && BasketBase::$AID_IN_BASKET === 0;
        $isLpIpOp = $options['arUserProfile']['PERSON_TYPE_ID'] === PERSON_TYPE_ID_LP
            || $options['arUserProfile']['PERSON_TYPE_ID'] === PERSON_TYPE_ID_IP
            || $options['arUserProfile']['PERSON_TYPE_ID'] === PERSON_TYPE_ID_OP;
        $canBuyAid = BasketBase::$AID_IN_BASKET > 0 && $arProperty['VALUE'] === 'Y';
        $canBy = (
            (
                $isPP
                && (
                    $canBuyPrecursor
                    || $canNotBuyPrecursor
                )
            )
            || (
                $isLpIpOp
                && (
                    $canBuyAid
                    || BasketBase::$AID_IN_BASKET === 0
                )
            )
        );
        /*debugmessage(
            [
                '$isPP'=>$isPP,
                '$canBuyPrecursor'=>$canBuyPrecursor,
                '$canNotBuyPrecursor'=>$canNotBuyPrecursor,
                '$isLpIpOp'=>$isLpIpOp,
                '$canBuyAid'=>$canBuyAid,
                '$canBy'=>$canBy,
                '$arProperty'=>$arProperty,
                $options['arUserProfile'],
                PERSON_TYPE_ID_PP,
                PERSON_TYPE_ID_LP,
                PERSON_TYPE_ID_IP,
                PERSON_TYPE_ID_OP,
                BasketBase::$PRECURSOR_IN_BASKET,
                BasketBase::$AID_IN_BASKET,
            ]
        );*/

        return $canBy;
    }
}
