<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Sale\DiscountCouponsManager;
use \xGuard\Main\Basket\Base as BasketBase;

/**
 * Class CXGuardSaleOrderAjaxAll
 */
class CXGuardSaleOrderAjaxAll extends \xGuard\Main
{

    /**
     * @var int
     */
    protected $DISPLAY_IMG_HEIGHT   = 90;

    /**
     * @var string
     */
    protected $PATH_TO_BASKET       = "/personal/basket/";

    /**
     * @var string
     */
    protected $PATH_TO_PERSONAL     = "/personal/";

    /**
     * @var string
     */
    protected $PATH_TO_PAYMENT      = "/personal/payment/";

    /**
     * @var string
     */
    protected $PATH_TO_AUTH         = "/auth/";

    /**
     * @var array
     */
    protected $arEvents             = array();

    /**
     * @var array
     */
    public $arError                 = array();

    /**
     * @var bool
     */
    public $bUseIblock              = false;

    /**
     * @var bool
     */
    public $bUseCatalog             = false;

    /**
     * @var bool
     */
    public $bIblockEnabled          = false;

    /**
     * @var bool
     */
    public $allCurrency             = false;

    /**
     * @var bool
     */
    public $psPreAction             = false;

    /**
     * @var array
     */
    public $arCustomSelectFields    = array();

    /**
     * @var array
     */
    public $arIblockProps           = array();

    /**
     * @var array
     */
    public $arSku2Parent            = array();

    /**
     * @var array
     */
    public $arElementId             = array();

    /**
     * CXGuardSaleOrderAjaxAll constructor.
     *
     * @param array $options
     */
    public function __construct($options=array())
    {
        try
        {
            parent::__construct($options);

            $this->_setDefine($options);
            $this->_load($options);
            $this->_setArParamsBefore($options);
            $this->_setArResult($options);
            $this->_setArParamsAfter($options);
            $this->_setProductsColumns($options);
            $this->_setProductsColumnsArray($options);
            $this->_authorize($options);

            $this->_otherSetDefine($options);
            $this->_otherLoad($options);
            $this->_otherSetArParamsBefore($options);
            $this->_otherSetArResult($options);
            $this->_otherSetArParamsAfter($options);
            $this->_otherSetArParamsBefore($options);
            $this->_otherSetProductsColumns($options);
            $this->_otherSetProductsColumnsArray($options);
            $this->_otherAuthorize($options);
        }
        catch(Exception $e)
        {
            $this->arError[__LINE__] = $e->message();

            return $this;
        }

        return $this;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function AddEventListener($options=array())
    {
        if(!empty($options['EVENT'])&&isset($options['FUNCTION'])&&is_callable($options['FUNCTION'])):
            $this->arEvents[$options['EVENT']] = is_array($options['EVENT'])?$options['EVENT']:array();
            $options['SORT'] = empty($options['SORT'])?(count($this->arEvents[$options['EVENT']])?max(array_keys($this->arEvents[$options['EVENT']])):0)+100:$options['SORT'];
            $this->arEvents[$options['EVENT']][$options['SORT']] = $options['FUNCTION'];
        endif;

        return $this;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function Trigger($options=array())
    {
        if(!empty($options['EVENT'])&&isset($this->arEvents[$options['EVENT']])):
            foreach($this->arEvents[$options['EVENT']] as $key=>$function):
                is_callable($function)?call_user_func_array($function,array(&$this)):false;
            endforeach;
        endif;

        return $this;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    protected function _ajaxDetected($options=array())
    {
        if($this->arParams['REQUEST']["AJAX_CALL"] == "Y" || $this->arParams['REQUEST']["is_ajax_post"] == "Y")
        {
            $this->application->RestartBuffer();
        }

        return $this;
    }

    /**
     * @param array $options
     */
    protected function _authorize($options=array())
    {
        if (!$this->user->IsAuthorized() && $this->arParams["ALLOW_AUTO_REGISTER"] == "N"):
            $this->arResult["AUTH"]["USER_LOGIN"]             = ((strlen($_POST["USER_LOGIN"]) > 0) ? htmlspecialcharsbx($_POST["USER_LOGIN"]) : htmlspecialcharsbx(${COption::GetOptionString("main", "cookie_name", "BITRIX_SM")."_LOGIN"}));
            $this->arResult["AUTH"]["captcha_registration"]   = ((COption::GetOptionString("main", "captcha_registration", "N") == "Y") ? "Y" : "N");

            if($this->arResult["AUTH"]["captcha_registration"] == "Y"):
                $this->arResult["AUTH"]["capCode"] = htmlspecialcharsbx($this->application->CaptchaGetCode());
            endif;

            $this->arResult["REQUEST"] = array();

            if ($_SERVER["REQUEST_METHOD"] == "POST" && ($this->arParams["DELIVERY_NO_SESSION"] == "N" || check_bitrix_sessid())):
                foreach ($this->arParams['REQUEST'] as $vName=>$vValue):
                    if (in_array($vName, array("USER_LOGIN", "USER_PASSWORD", "do_authorize", "NEW_NAME", "NEW_LAST_NAME", "NEW_EMAIL", "NEW_GENERATE", "NEW_LOGIN", "NEW_PASSWORD", "NEW_PASSWORD_CONFIRM", "captcha_sid", "captcha_word", "do_register", "AJAX_CALL", "is_ajax_post"))):
                        continue;
                    endif;

                    if(is_array($vValue)):
                        foreach($vValue as $k => $v)
                            $this->arResult["REQUEST"][htmlspecialcharsbx($vName."[".$k."]")] = !is_array($v)?htmlspecialcharsbx($v):$v;
                    else:
                        $this->arResult["POST"][htmlspecialcharsbx($vName)] = htmlspecialcharsbx($vValue);
                    endif;
                endforeach;

                if ($this->arParams['REQUEST']["do_authorize"] == "Y"):
                    if (strlen($this->arParams['REQUEST']["USER_LOGIN"]) <= 0):
                        $this->arResult["ERROR"][] = GetMessage("STOF_ERROR_AUTH_LOGIN");
                    endif;

                    if (empty($this->arResult["ERROR"])):
                        $arAuthResult = $this->user->Login($_POST["USER_LOGIN"], $_POST["USER_PASSWORD"], "N");

                        if ($this->arAuthResult != False && $arAuthResult["TYPE"] == "ERROR"):
                            $this->arResult["ERROR"][] = GetMessage("STOF_ERROR_AUTH").((strlen($arAuthResult["MESSAGE"]) > 0) ? ": ".$arAuthResult["MESSAGE"] : "" );
                        endif;
                    endif;
                elseif ($this->arParams['REQUEST']["do_register"] == "Y" && $this->arResult["AUTH"]["new_user_registration"] == "Y"):
                    if (strlen($this->arParams['REQUEST']["NEW_NAME"]) <= 0):
                        $this->arResult["ERROR"][] = GetMessage("STOF_ERROR_REG_NAME");
                    endif;

                    if (strlen($this->arParams['REQUEST']["NEW_LAST_NAME"]) <= 0):
                        $this->arResult["ERROR"][] = GetMessage("STOF_ERROR_REG_LASTNAME");
                    endif;

                    if (strlen($this->arParams['REQUEST']["NEW_EMAIL"]) <= 0):
                        $this->arResult["ERROR"][] = GetMessage("STOF_ERROR_REG_EMAIL");
                    elseif (!check_email($this->arParams['REQUEST']["NEW_EMAIL"])):
                        $this->arResult["ERROR"][] = GetMessage("STOF_ERROR_REG_BAD_EMAIL");
                    endif;

                    $this->arResult["AUTH"]["NEW_EMAIL"] = $this->arParams['REQUEST']["NEW_EMAIL"];

                    if (empty($this->arResult["ERROR"])):
                        if ($this->arParams['REQUEST']["NEW_GENERATE"] == "Y"):
                            $this->arResult["AUTH"]["NEW_EMAIL"] = $this->arParams['REQUEST']["NEW_EMAIL"];
                            $this->arResult["AUTH"]["NEW_LOGIN"] = $this->arParams['REQUEST']["NEW_EMAIL"];

                            $pos = strpos($this->arResult["AUTH"]["NEW_LOGIN"], "@");

                            if ($pos !== false):
                                $this->arParams['REQUEST']["NEW_LOGIN"] = substr($this->arResult["AUTH"]["NEW_LOGIN"], 0, $pos);
                            endif;

                            if (strlen($this->arResult["AUTH"]["NEW_LOGIN"]) > 47):
                                $this->arParams['REQUEST']["NEW_LOGIN"] = substr($this->arResult["AUTH"]["NEW_LOGIN"], 0, 47);
                            endif;

                            if (strlen($this->arResult["AUTH"]["NEW_LOGIN"]) < 3):
                                $this->arResult["AUTH"]["NEW_LOGIN"] .= "_";
                            endif;

                            if (strlen($this->arResult["AUTH"]["NEW_LOGIN"]) < 3):
                                $this->arResult["AUTH"]["NEW_LOGIN"] .= "_";
                            endif;

                            $dbUserLogin = \CUser::GetByLogin($this->arResult["AUTH"]["NEW_LOGIN"]);

                            if ($arUserLogin = $dbUserLogin->Fetch()):
                                $newLoginTmp    = $this->arResult["AUTH"]["NEW_LOGIN"];
                                $uind           = 0;

                                do
                                {
                                    $uind++;

                                    if ($uind == 10):
                                        $this->arResult["AUTH"]["NEW_LOGIN"] = $this->arResult["AUTH"]["NEW_EMAIL"];
                                        $newLoginTmp = $this->arResult["AUTH"]["NEW_LOGIN"];
                                    elseif ($uind > 10):
                                        $this->arResult["AUTH"]["NEW_LOGIN"] = "buyer".time().GetRandomCode(2);
                                        $newLoginTmp = $this->arResult["AUTH"]["NEW_LOGIN"];
                                        break;
                                    else:
                                        $newLoginTmp = $this->arResult["AUTH"]["NEW_LOGIN"].$uind;
                                    endif;

                                    $dbUserLogin = CUser::GetByLogin($newLoginTmp);
                                }
                                while ($arUserLogin = $dbUserLogin->Fetch());

                                $this->arResult["AUTH"]["NEW_LOGIN"] = $newLoginTmp;
                            endif;

                            $def_group = COption::GetOptionString("main", "new_user_registration_def_group", "");

                            if($def_group!=""):
                                $GROUP_ID = explode(",", $def_group);
                                $arPolicy = $this->user->GetGroupPolicy($GROUP_ID);
                            else:
                                $arPolicy = $this->user->GetGroupPolicy(array());
                            endif;

                            $password_min_length = intval($arPolicy["PASSWORD_LENGTH"]);

                            if($password_min_length <= 0):
                                $password_min_length = 6;
                            endif;

                            $password_chars = array(
                                "abcdefghijklnmopqrstuvwxyz",
                                "ABCDEFGHIJKLNMOPQRSTUVWXYZ",
                                "0123456789",
                            );

                            if($arPolicy["PASSWORD_PUNCTUATION"] === "Y"):
                                $password_chars[] = ",.<>/?;:'\"[]{}\|`~!@#\$%^&*()-_+=";
                            endif;

                            $this->arResult["AUTH"]["NEW_PASSWORD"] = $this->arResult["AUTH"]["NEW_PASSWORD_CONFIRM"] = randString($password_min_length+2, $password_chars);
                        else:
                            if (strlen($this->arParams['REQUEST']["NEW_LOGIN"]) <= 0):
                                $this->arResult["ERROR"][__LINE__] = GetMessage("STOF_ERROR_REG_FLAG");
                            endif;

                            if (strlen($this->arParams['REQUEST']["NEW_PASSWORD"]) <= 0):
                                $this->arResult["ERROR"][__LINE__] = GetMessage("STOF_ERROR_REG_FLAG1");
                            endif;

                            if (
                                strlen($this->arParams['REQUEST']["NEW_PASSWORD"]) > 0
                                    &&
                                strlen($this->arParams['REQUEST']["NEW_PASSWORD_CONFIRM"]) <= 0
                            ):
                                $this->arResult["ERROR"][__LINE__] = GetMessage("STOF_ERROR_REG_FLAG1");
                            endif;

                            if (
                                strlen($this->arParams['REQUEST']["NEW_PASSWORD"]) > 0
                                    &&
                                strlen($this->arParams['REQUEST']["NEW_PASSWORD_CONFIRM"]) > 0
                                    &&
                                $this->arParams['REQUEST']["NEW_PASSWORD"] != $this->arParams['REQUEST']["NEW_PASSWORD_CONFIRM"]
                            ):
                                $this->arResult["ERROR"][__LINE__] = GetMessage("STOF_ERROR_REG_PASS");
                            endif;

                            $this->arResult["AUTH"]["NEW_LOGIN"]            = $this->arParams['REQUEST']["NEW_LOGIN"];
                            $this->arResult["AUTH"]["NEW_NAME"]             = $this->arParams['REQUEST']["NEW_NAME"];
                            $this->arResult["AUTH"]["NEW_PASSWORD"]         = $this->arParams['REQUEST']["NEW_PASSWORD"];
                            $this->arResult["AUTH"]["NEW_PASSWORD_CONFIRM"] = $this->arParams['REQUEST']["NEW_PASSWORD_CONFIRM"];
                        endif;
                    endif;

                    if (empty($this->arResult["ERROR"])):
                        $arAuthResult = $this->USER->Register(
                            $this->arResult["AUTH"]["NEW_LOGIN"],
                            $this->arParams['REQUEST']["NEW_NAME"],
                            $this->arParams['REQUEST']["NEW_LAST_NAME"],
                            $this->arResult["AUTH"]["NEW_PASSWORD"],
                            $this->arResult["AUTH"]["NEW_PASSWORD_CONFIRM"],
                            $this->arResult["AUTH"]["NEW_EMAIL"],
                            LANG,
                            $this->arParams['REQUEST']["captcha_word"],
                            $this->arParams['REQUEST']["captcha_sid"]
                        );

                        if ($arAuthResult != False && $arAuthResult["TYPE"] == "ERROR"):
                            $this->arResult["ERROR"][__LINE__] = GetMessage("STOF_ERROR_REG").((strlen($arAuthResult["MESSAGE"]) > 0) ? ": ".$arAuthResult["MESSAGE"] : "" );
                        else:
                            if ($this->USER->IsAuthorized()):
                                if($this->arParams["SEND_NEW_USER_NOTIFY"] == "Y"):
                                    CUser::SendUserInfo($this->USER->GetID(), SITE_ID, GetMessage("INFO_REQ"), true);
                                endif;

                                LocalRedirect($this->application->GetCurPageParam());
                            else:
                                $this->arResult["OK_MESSAGE"][__LINE__] = GetMessage("STOF_ERROR_REG_CONFIRM");
                            endif;
                        endif;
                    endif;

                    $this->arResult["AUTH"]["~NEW_LOGIN"]     = $this->arResult["AUTH"]["NEW_LOGIN"];
                    $this->arResult["AUTH"]["NEW_LOGIN"]      = htmlspecialcharsEx($this->arResult["AUTH"]["NEW_LOGIN"]);
                    $this->arResult["AUTH"]["~NEW_NAME"]      = $this->arParams['REQUEST']["NEW_NAME"];
                    $this->arResult["AUTH"]["NEW_NAME"]       = htmlspecialcharsEx($this->arParams['REQUEST']["NEW_NAME"]);
                    $this->arResult["AUTH"]["~NEW_LAST_NAME"] = $this->arParams['REQUEST']["NEW_LAST_NAME"];
                    $this->arResult["AUTH"]["NEW_LAST_NAME"]  = htmlspecialcharsEx($this->arParams['REQUEST']["NEW_LAST_NAME"]);
                    $this->arResult["AUTH"]["~NEW_EMAIL"]     = $this->arResult["AUTH"]["NEW_EMAIL"];
                    $this->arResult["AUTH"]["NEW_EMAIL"]      = htmlspecialcharsEx($this->arResult["AUTH"]["NEW_EMAIL"]);
                endif;
            endif;
        endif;
    }

    /**
     * @param array $options
     *
     * @return bool
     * @throws Exception
     */
    protected function _load($options=array())
    {
        if (!$this->includeModule("sale")||!$this->includeModule("iblock"))
        {
            throw new \Exception(GetMessage("SOA_MODULE_NOT_INSTALL"));
        }

        if (!$this->includeModule("catalog"))
        {
            throw new \Exception(GetMessage("SOA_MODULE_NOT_INSTALL"));
        }

        return ($this->bUseIblock = true);
    }

    /**
     * @param array $options
     */
    protected function _setArParamsAfter($options=array())
    {
        $this->arParams["ALLOW_AUTO_REGISTER"]  = ($this->arParams["ALLOW_AUTO_REGISTER"] == "Y") ? "Y" : "N";
        $this->arParams["ALLOW_AUTO_REGISTER"]  = ($this->arParams["ALLOW_AUTO_REGISTER"] == "Y" && ($this->arResult["AUTH"]["new_user_registration_email_confirmation"] == "Y" || $this->arResult["AUTH"]["new_user_registration"] == "N")) ? "N" : $this->arParams["ALLOW_AUTO_REGISTER"];
    }

    /**
     * @param array $options
     */
    protected function _setArParamsBefore($options=array())
    {
        $this->arParams["PATH_TO_BASKET"]               = Trim($this->arParams["PATH_TO_BASKET"]);
        $this->arParams["PATH_TO_PERSONAL"]             = Trim($this->arParams["PATH_TO_PERSONAL"]);
        $this->arParams["PATH_TO_PAYMENT"]              = Trim($this->arParams["PATH_TO_PAYMENT"]);
        $this->arParams["PATH_TO_AUTH"]                 = Trim($this->arParams["PATH_TO_AUTH"]);
        $this->arParams["DISPLAY_IMG_HEIGHT"]           = (Intval($this->arParams["DISPLAY_IMG_HEIGHT"]) <= 0)  ? $this->DISPLAY_IMG_HEIGHT : Intval($this->arParams["DISPLAY_IMG_HEIGHT"]);
        $this->arParams["PATH_TO_BASKET"]               = (strlen($this->arParams["PATH_TO_BASKET"])     <= 0)  ? $this->PATH_TO_BASKET     : $this->arParams["PATH_TO_BASKET"];
        $this->arParams["PATH_TO_PERSONAL"]             = (strlen($this->arParams["PATH_TO_PERSONAL"])   <= 0)  ? $this->PATH_TO_PERSONAL   : $this->arParams["PATH_TO_PERSONAL"];
        $this->arParams["PATH_TO_PAYMENT"]              = (strlen($this->arParams["PATH_TO_PAYMENT"])    <= 0)  ? $this->PATH_TO_PAYMENT    : $this->arParams["PATH_TO_PAYMENT"];
        $this->arParams["PATH_TO_AUTH"]                 = (strlen($this->arParams["PATH_TO_AUTH"])       <= 0)  ? $this->PATH_TO_AUTH       : $this->arParams["PATH_TO_AUTH"];
        $this->arParams["PAY_FROM_ACCOUNT"]             = ($this->arParams["PAY_FROM_ACCOUNT"] == "N")          ? "N" : "Y";
        $this->arParams["COUNT_DELIVERY_TAX"]           = ($this->arParams["COUNT_DELIVERY_TAX"] == "Y")        ? "Y" : "N";
        $this->arParams["ONLY_FULL_PAY_FROM_ACCOUNT"]   = ($this->arParams["ONLY_FULL_PAY_FROM_ACCOUNT"] == "Y")? "Y" : "N";
        $this->arParams["DELIVERY_NO_AJAX"]             = ($this->arParams["DELIVERY_NO_AJAX"] == "Y")          ? "Y" : "N";
        $this->arParams["USE_PREPAYMENT"]               = ($this->arParams["USE_PREPAYMENT"] == 'Y')            ? 'Y' : 'N';
        $this->arParams["SEND_NEW_USER_NOTIFY"]         = ($this->arParams["SEND_NEW_USER_NOTIFY"] == "N")      ? "N" : "Y";
        $this->arParams["ALLOW_NEW_PROFILE"]            = ($this->arParams["ALLOW_NEW_PROFILE"] == "N")         ? "N" : "Y";
        $this->arParams["DELIVERY_NO_SESSION"]          = (!$this->arParams["DELIVERY_NO_SESSION"])             ? "N" : $this->arParams["DELIVERY_NO_SESSION"];
        $this->arParams["DELIVERY_TO_PAYSYSTEM"]        = (strlen($this->arParams["DELIVERY_TO_PAYSYSTEM"]) <= 0) ? "d2p" : trim($this->arParams["DELIVERY_TO_PAYSYSTEM"]);
        $this->arParams["DISABLE_BASKET_REDIRECT"]      = (!isset($this->arParams["DISABLE_BASKET_REDIRECT"]) || 'Y' !== $this->arParams["DISABLE_BASKET_REDIRECT"]) ? "N" : $this->arParams["DISABLE_BASKET_REDIRECT"];
        $this->arParams["USE_ACCOUNT_NUMBER"]           = (COption::GetOptionString("sale", "account_number_template", "") !== "");
        $this->arParams['IS_AUTHORIZED']                = $this->user->IsAuthorized() || $this->arParams["ALLOW_AUTO_REGISTER"] == "Y";
        $this->arParams['IS_ORDER_PLACED']              = ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["confirmorder"]) && ($this->arParams["DELIVERY_NO_SESSION"] == "N" || check_bitrix_sessid()));

        $this->allCurrency = $this->arResult['BASE_LANG_CURRENCY'];

        $this->arParams["SET_TITLE"] == "Y"?$this->application->SetTitle(GetMessage("SOA_TITLE")):false;
    }

    /**
     * @param array $options
     */
    protected function _setArResult($options=array())
    {
        $arResult = array(
            "GRID"                          => array(
                "HEADERS"                       => array(),
                "ROWS"                          => array(),
                "DEFAULT_COLUMNS"               => array(),
            ),
            "DELIVERY_EXTRA"                => isset($this->arParams['REQUEST']["DELIVERY_ID"]) && isset($this->arParams['REQUEST']["DELIVERY_EXTRA"][$this->arParams['REQUEST']["DELIVERY_ID"]]) ? $this->arParams['REQUEST']["DELIVERY_EXTRA"][$this->arParams['REQUEST']["DELIVERY_ID"]] : array(),
            "PERSON_TYPE"                   => array(),
            "PAY_SYSTEM"                    => array(),
            "ORDER_PROP"                    => array(),
            "DELIVERY"                      => array(),
            "TAX"                           => array(),
            "ERROR"                         => array(),
            "ORDER_PRICE"                   => 0,
            "ORDER_WEIGHT"                  => 0,
            "VATE_RATE"                     => 0,
            "VAT_SUM"                       => 0,
            "bUsingVat"                     => false,
            "BASKET_ITEMS"                  => array(),
            "BASE_LANG_CURRENCY"            => \CSaleLang::GetLangCurrency(SITE_ID),
            "BUYER_STORE"                   => isset($this->arParams['REQUEST']["BUYER_STORE"]) ? intval($this->arParams['REQUEST']["BUYER_STORE"]) : "",
            "WEIGHT_UNIT"                   => htmlspecialcharsbx(\COption::GetOptionString('sale', 'weight_unit', false, SITE_ID)),
            "WEIGHT_KOEF"                   => htmlspecialcharsbx(\COption::GetOptionString('sale', 'weight_koef', 1, SITE_ID)),
            "TaxExempt"                     => array(),
            "DISCOUNT_PRICE"                => 0,
            "DISCOUNT_PERCENT"              => 0,
            "DELIVERY_PRICE"                => 0,
            "TAX_PRICE"                     => 0,
            "PAYED_FROM_ACCOUNT_FORMATED"   => false,
            "ORDER_TOTAL_PRICE_FORMATED"    => false,
            "ORDER_WEIGHT_FORMATED"         => false,
            "ORDER_PRICE_FORMATED"          => false,
            "VAT_SUM_FORMATED"              => false,
            "DELIVERY_SUM"                  => false,
            "DELIVERY_PROFILE_SUM"          => false,
            "DELIVERY_PRICE_FORMATED"       => false,
            "DISCOUNT_PERCENT_FORMATED"     => false,
            "PAY_FROM_ACCOUNT"              => false,
            "CURRENT_BUDGET_FORMATED"       => false,
            "USER_ACCOUNT"                  => false,
            "DISCOUNTS"                     => array(),
            "AUTH"                          => array(
                "new_user_registration_email_confirmation"  => ((COption::GetOptionString("main", "new_user_registration_email_confirmation", "N") == "Y") ? "Y" : "N"),
                "new_user_registration"                     => ((COption::GetOptionString("main", "new_user_registration", "Y") == "Y") ? "Y" : "N"),
            ),
            "HAVE_PREPAYMENT"               => false,
            "PREPAY_PS"                     => array(),
            "PREPAY_ADIT_FIELDS"            => "",
            "PREPAY_ORDER_PROPS"            => array(),
            "USER"                          => array(
                "PERSON_TYPE_ID"                => false,
                "PAY_SYSTEM_ID"                 => false,
                "DELIVERY_ID"                   => false,
                "ORDER_PROP"                    => false,
                "DELIVERY_LOCATION"             => false,
                "TAX_LOCATION"                  => false,
                "PAYER_NAME"                    => false,
                "USER_EMAIL"                    => false,
                "PROFILE_NAME"                  => false,
                "PAY_CURRENT_ACCOUNT"           => false,
                "CONFIRM_ORDER"                 => false,
                "FINAL_STEP"                    => false,
                "ORDER_DESCRIPTION"             => false,
                "PROFILE_ID"                    => false,
                "PROFILE_CHANGE"                => false,
                "DELIVERY_LOCATION_ZIP"         => false,
            ),
        );

        $this->arResult = array_replace_recursive($arResult,$this->arResult);
    }

    /**
     * @param array $options
     */
    protected function _setDefine($options=array())
    {
        define("PROPERTY_COUNT_LIMIT", 24);
    }

    /**
     * @param array $options
     */
    protected function _setProductsColumns($options=array())
    {
        if (empty($this->arParams["PRODUCT_COLUMNS"])):
            $this->arParams["PRODUCT_COLUMNS"] = array(
                "NAME"                              => GetMessage("SOA_NAME_DEFAULT_COLUMN"),
                "PROPS"                             => GetMessage("SOA_PROPS_DEFAULT_COLUMN"),
                "DISCOUNT_PRICE_PERCENT_FORMATED"   => GetMessage("SOA_DISCOUNT_DEFAULT_COLUMN"),
                "PRICE_FORMATED"                    => GetMessage("SOA_PRICE_DEFAULT_COLUMN"),
                "QUANTITY"                          => GetMessage("SOA_QUANTITY_DEFAULT_COLUMN"),
                "SUM"                               => GetMessage("SOA_SUM_DEFAULT_COLUMN"),
            );

            $this->arResult["GRID"]["DEFAULT_COLUMNS"] = true;
        else:
            $this->bIblockEnabled = $this->bUseCatalog ? true : $this->bIblockEnabled;

            if (($key = array_search("PREVIEW_TEXT", $this->arParams["PRODUCT_COLUMNS"])) !== false):
                unset($this->arParams["PRODUCT_COLUMNS"][$key]);
                $this->arParams["PRODUCT_COLUMNS"]["PREVIEW_TEXT"] = GetMessage("SOA_NAME_COLUMN_PREVIEW_TEXT");
            endif;

            if (($key = array_search("PREVIEW_PICTURE", $this->arParams["PRODUCT_COLUMNS"])) !== false):
                unset($this->arParams["PRODUCT_COLUMNS"][$key]);
                $this->arParams["PRODUCT_COLUMNS"]["PREVIEW_PICTURE"] = GetMessage("SOA_NAME_COLUMN_PREVIEW_PICTURE");
            endif;

            if (($key = array_search("DETAIL_PICTURE", $this->arParams["PRODUCT_COLUMNS"])) !== false):
                unset($this->arParams["PRODUCT_COLUMNS"][$key]);
                $this->arParams["PRODUCT_COLUMNS"]["DETAIL_PICTURE"] = GetMessage("SOA_NAME_COLUMN_DETAIL_PICTURE");
            endif;

            if (($key = array_search("PROPS", $this->arParams["PRODUCT_COLUMNS"])) !== false):
                unset($this->arParams["PRODUCT_COLUMNS"][$key]);
                $this->arParams["PRODUCT_COLUMNS"]["PROPS"] = GetMessage("SOA_PROPS_DEFAULT_COLUMN");
            endif;

            if (($key = array_search("NOTES", $this->arParams["PRODUCT_COLUMNS"])) !== false):
                unset($this->arParams["PRODUCT_COLUMNS"][$key]);
                $this->arParams["PRODUCT_COLUMNS"]["NOTES"] = GetMessage("SOA_PRICE_TYPE_DEFAULT_COLUMN");
            endif;

            if (($key = array_search("DISCOUNT_PRICE_PERCENT_FORMATED", $this->arParams["PRODUCT_COLUMNS"])) !== false):
                unset($this->arParams["PRODUCT_COLUMNS"][$key]);
                $this->arParams["PRODUCT_COLUMNS"]["DISCOUNT_PRICE_PERCENT_FORMATED"] = GetMessage("SOA_DISCOUNT_DEFAULT_COLUMN");
            endif;

            if (($key = array_search("WEIGHT_FORMATED", $this->arParams["PRODUCT_COLUMNS"])) !== false):
                unset($this->arParams["PRODUCT_COLUMNS"][$key]);
                $this->arParams["PRODUCT_COLUMNS"]["WEIGHT_FORMATED"] = GetMessage("SOA_WEIGHT_DEFAULT_COLUMN");
            endif;
        endif;

        if (!array_key_exists("NAME", $this->arParams["PRODUCT_COLUMNS"])):
            $this->arParams["PRODUCT_COLUMNS"] = array("NAME" => GetMessage("SOA_NAME_DEFAULT_COLUMN")) + $this->arParams["PRODUCT_COLUMNS"];
        endif;

        if (!array_key_exists("PRICE_FORMATED", $this->arParams["PRODUCT_COLUMNS"])):
            $this->arParams["PRODUCT_COLUMNS"]["PRICE_FORMATED"] = GetMessage("SOA_PRICE_DEFAULT_COLUMN");
        endif;

        if (!array_key_exists("QUANTITY", $this->arParams["PRODUCT_COLUMNS"])):
            $this->arParams["PRODUCT_COLUMNS"]["QUANTITY"] = GetMessage("SOA_QUANTITY_DEFAULT_COLUMN");
        endif;

        if (!array_key_exists("SUM", $this->arParams["PRODUCT_COLUMNS"])):
            $this->arParams["PRODUCT_COLUMNS"]["SUM"] = GetMessage("SOA_SUM_DEFAULT_COLUMN");
        endif;
    }

    /**
     * @param array $options
     */
    protected function _setProductsColumnsArray($options=array())
    {
        $propertyCount=0;

        foreach ($this->arParams["PRODUCT_COLUMNS"] as $key => $value):
            if (strncmp($value, "PROPERTY_", 9) == 0):
                $propertyCount++;

                if ($propertyCount > PROPERTY_COUNT_LIMIT):
                    continue;
                endif;

                $propCode = substr($value, 9);

                if ($propCode == ''):
                    continue;
                endif;

                $this->arCustomSelectFields[] = $value;
                $id     = $value."_VALUE";
                $name   = $value;

                if ($this->bIblockEnabled):
                    $dbRes = CIBlockProperty::GetList(array(), array("CODE" => $propCode));

                    if ($arRes = $dbRes->GetNext()):
                        $name = $arRes["NAME"];
                        $this->arIblockProps[$propCode] = $arRes;
                    endif;
                else:

                endif;
            else:
                $id     = $key;
                $name   = $value;
            endif;

            $arColumn = array(
                "id"    => $id,
                "name"  => $name
            );

            if ($key == "PRICE_FORMATED"):
                $arColumn["align"] = "right";
            endif;

            $this->arResult["GRID"]["HEADERS"][] = $arColumn;

        endforeach;
    }

    /**
     *
     */
    protected function _getFormatedProperties()
    {
        $arDeleteFieldLocation = array();

        $this->arParams['FORMAT_PROPERTIES_ORDER_PROPS']['ORDER']  = array(
            "GROUP_SORT"        => "ASC",
            "PROPS_GROUP_ID"    => "ASC",
            "USER_PROPS"        => "ASC",
            "SORT"              => "ASC",
            "NAME"              => "ASC"
        );
        $this->arParams['FORMAT_PROPERTIES_ORDER_PROPS']['FILTER']  = array(
            "PERSON_TYPE_ID"    => $this->arResult['USER']["PERSON_TYPE_ID"],
            "ACTIVE"            => "Y",
            "UTIL"              => "N",
            "RELATED"           => false,
        );
        $this->arParams['FORMAT_PROPERTIES_ORDER_PROPS']['GROUP']   = false;
        $this->arParams['FORMAT_PROPERTIES_ORDER_PROPS']['LIMIT']   = false;
        $this->arParams['FORMAT_PROPERTIES_ORDER_PROPS']['SELECT']  = array(
            "ID",
            "NAME",
            "TYPE",
            "REQUIED",
            "DEFAULT_VALUE",
            "IS_LOCATION",
            "PROPS_GROUP_ID",
            "SIZE1",
            "SIZE2",
            "DESCRIPTION",
            "IS_EMAIL",
            "IS_PROFILE_NAME",
            "IS_PAYER",
            "IS_LOCATION4TAX",
            "DELIVERY_ID",
            "PAYSYSTEM_ID",
            "MULTIPLE",
            "CODE",
            "GROUP_NAME",
            "GROUP_SORT",
            "SORT",
            "USER_PROPS",
            "IS_ZIP",
            "INPUT_FIELD_LOCATION",
        );

        if(!empty($this->arParams["PROP_".$this->arResult['USER']["PERSON_TYPE_ID"]])):
            $this->arParams['FORMAT_PROPERTIES_ORDER_PROPS']['FILTER']["!ID"] = $this->arParams["PROP_".$this->arResult['USER']["PERSON_TYPE_ID"]];
        endif;

        $dbProperties = CSaleOrderProps::GetList(
            $this->arParams['FORMAT_PROPERTIES_ORDER_PROPS']['ORDER'],
            $this->arParams['FORMAT_PROPERTIES_ORDER_PROPS']['FILTER'],
            $this->arParams['FORMAT_PROPERTIES_ORDER_PROPS']['GROUP'],
            $this->arParams['FORMAT_PROPERTIES_ORDER_PROPS']['LIMIT'],
            $this->arParams['FORMAT_PROPERTIES_ORDER_PROPS']['SELECT']
        );

        $propIndex = array();

        if(is_array($_REQUEST['LOCATION_ALT_PROP_DISPLAY_MANUAL'])):
            foreach($_REQUEST['LOCATION_ALT_PROP_DISPLAY_MANUAL'] as $propId => $switch):
                if(intval($propId)):
                    $this->arResult['LOCATION_ALT_PROP_DISPLAY_MANUAL'][intval($propId)] = !!$switch;
                endif;
            endforeach;
        endif;

        while ($arProperties = $dbProperties->GetNext()):
            $arProperties = $this->_getOrderPropFormated(
                array(
                    'arProperties'             => &$arProperties,
                    'arDeleteFieldLocation'    => &$arDeleteFieldLocation,
                )
            );

            $flag = $arProperties["USER_PROPS"]=="Y" ? 'Y' : 'N';

            $this->arResult["ORDER_PROP"]["USER_PROPS_".$flag][$arProperties["ID"]] = $arProperties;
            $propIndex[$arProperties["ID"]] =&$this->arResult["ORDER_PROP"]["USER_PROPS_".$flag][$arProperties["ID"]];

            $this->arResult["ORDER_PROP"]["PRINT"][$arProperties["ID"]] = Array(
                "ID"                => $arProperties["ID"],
                "NAME"              => $arProperties["NAME"],
                "VALUE"             => $arProperties["VALUE_FORMATED"],
                "SHOW_GROUP_NAME"   => $arProperties["SHOW_GROUP_NAME"],
            );
        endwhile;

        foreach($propIndex as $propId => $propDesc):
            if(intval($propDesc['INPUT_FIELD_LOCATION']) && isset($propIndex[$propDesc['INPUT_FIELD_LOCATION']])):
                $propIndex[$propDesc['INPUT_FIELD_LOCATION']]['IS_ALTERNATE_LOCATION_FOR'] = $propId;
                $propIndex[$propId]['CAN_HAVE_ALTERNATE_LOCATION'] = $propDesc['INPUT_FIELD_LOCATION'];
            endif;
        endforeach;

        foreach(GetModuleEvents("sale", "OnSaleComponentOrderOneStepOrderProps", true) as $arEvent):
            ExecuteModuleEventEx($arEvent, Array(&$this->arResult, &$this->arResult['USER'], &$this->arParams));
        endforeach;

        if (count($arDeleteFieldLocation) > 0):
            foreach ($arDeleteFieldLocation as $fieldId):
                unset($this->arResult["ORDER_PROP"]["USER_PROPS_Y"][$fieldId]);
            endforeach;
        endif;
    }

    /**
     * @param array $options
     *
     * @return array|mixed
     */
    private function _getOrderPropFormated($options=array())
    {
        $options['arProperties']            = !isset($options['arProperties'])||(isset($options['arProperties'])&&!is_array($options['arProperties']))?array():$options['arProperties'];
        $options['arDeleteFieldLocation']   = !isset($options['arDeleteFieldLocation'])||(isset($options['arDeleteFieldLocation'])&&!is_array($options['arDeleteFieldLocation']))?array():$options['arDeleteFieldLocation'];

        $isProfileChanged = ($this->arResult['USER']["PROFILE_CHANGE"] == "Y");

        $isEmptyUserResult = (empty($this->arResult['USER']["ORDER_PROP"]));

        $curVal         = $this->arResult['USER']["ORDER_PROP"][$options['arProperties']["ID"]];
        $curLocation    = false;

        static $propertyGroupID = 0;
        static $propertyUSER_PROPS = "";

        if (
            $this->arResult['USER']["PROFILE_CHANGE"] == "Y"
                &&
            intval($this->arResult['USER']["PROFILE_ID"]) > 0
                &&
            !(
                $this->arResult["HAVE_PREPAYMENT"]
                    &&
                $this->arResult['USER']["PROFILE_DEFAULT"] == "Y"
                    &&
                !empty($this->arResult["PREPAY_ORDER_PROPS"][$options['arProperties']["CODE"]])
            )
        ):
            $dbUserPropsValues = CSaleOrderUserPropsValue::GetList(
                array("SORT" => "ASC"),
                array(
                    "USER_PROPS_ID" => $this->arResult['USER']["PROFILE_ID"],
                    "ORDER_PROPS_ID" => $options['arProperties']["ID"],
                    "USER_ID" => intval($this->user->GetID()),
                ),
                false,
                false,
                array("VALUE", "PROP_TYPE", "VARIANT_NAME", "SORT", "ORDER_PROPS_ID")
            );
            if ($arUserPropsValues = $dbUserPropsValues->Fetch()):
                $valueTmp = "";

                if ($arUserPropsValues["PROP_TYPE"] == "MULTISELECT"):
                    $arUserPropsValues["VALUE"] = explode(",", $arUserPropsValues["VALUE"]);
                endif;

                $curVal = $arUserPropsValues["VALUE"];
            endif;
        elseif($this->arResult['USER']["PROFILE_CHANGE"] == "Y" && intval($this->arResult['USER']["PROFILE_ID"]) <= 0):
            if (isset($curVal)):
                unset($curVal);
            endif;
        elseif(isset($this->arResult['USER']["ORDER_PROP"][$options['arProperties']["ID"]])):
            $curVal = $this->arResult['USER']["ORDER_PROP"][$options['arProperties']["ID"]];
        elseif($this->arResult["HAVE_PREPAYMENT"] && !empty($this->arResult["PREPAY_ORDER_PROPS"][$options['arProperties']["CODE"]])):
            $curVal = $this->arResult["PREPAY_ORDER_PROPS"][$options['arProperties']["CODE"]];

            if($options['arProperties']["TYPE"] == "LOCATION"):
                $curLocation = $curVal;
            endif;
        endif;

        if (intval($_REQUEST["NEW_LOCATION_".$options['arProperties']["ID"]]) > 0):
            $curVal = intval($_REQUEST["NEW_LOCATION_".$options['arProperties']["ID"]]);
        endif;

        $options['arProperties']["FIELD_NAME"] = "ORDER_PROP_".$options['arProperties']["ID"];

        if(strlen($options['arProperties']["CODE"]) > 0):
            $options['arProperties']["FIELD_ID"] = "ORDER_PROP_".$options['arProperties']["CODE"];
        else:
            $options['arProperties']["FIELD_ID"] = "ORDER_PROP_".$options['arProperties']["ID"];
        endif;

        if (
                intval($options['arProperties']["PROPS_GROUP_ID"]) != $propertyGroupID
                    ||
                $propertyUSER_PROPS != $options['arProperties']["USER_PROPS"]
        ):
            $options['arProperties']["SHOW_GROUP_NAME"] = "Y";
        endif;

        $propertyGroupID    = $options['arProperties']["PROPS_GROUP_ID"];
        $propertyUSER_PROPS = $options['arProperties']["USER_PROPS"];

        if(
            $options['arProperties']["REQUIED"]=="Y"
                ||
            $options['arProperties']["IS_EMAIL"]=="Y"
                ||
            $options['arProperties']["IS_PROFILE_NAME"]=="Y"
                ||
            $options['arProperties']["IS_LOCATION"]=="Y"
                ||
            $options['arProperties']["IS_LOCATION4TAX"]=="Y"
                ||
            $options['arProperties']["IS_PAYER"]=="Y"
                ||
            $options['arProperties']["IS_ZIP"]=="Y"
        ):
            $options['arProperties']["REQUIED_FORMATED"]="Y";
        endif;

        if ($options['arProperties']["TYPE"] == "CHECKBOX"):
            if ($curVal=="Y" || !isset($curVal) && $options['arProperties']["DEFAULT_VALUE"]=="Y"):
                $options['arProperties']["CHECKED"] = "Y";
                $options['arProperties']["VALUE_FORMATED"] = GetMessage("SOA_Y");
            else:
                $options['arProperties']["VALUE_FORMATED"] = GetMessage("SOA_N");
            endif;

            $options['arProperties']["SIZE1"] = ((intval($options['arProperties']["SIZE1"]) > 0) ? $options['arProperties']["SIZE1"] : 30);

            if ($isProfileChanged || $isEmptyUserResult):
                $this->arResult['USER']["ORDER_PROP"][$options['arProperties']["ID"]] = (isset($options['arProperties']["CHECKED"]) && $options['arProperties']["CHECKED"] == "Y" ? 'Y' : "N");
            endif;
        elseif ($options['arProperties']["TYPE"] == "TEXT"):
            if (strlen($curVal) <= 0):
                if(strlen($options['arProperties']["DEFAULT_VALUE"])>0 && !isset($curVal)):
                    $options['arProperties']["VALUE"] = $options['arProperties']["DEFAULT_VALUE"];
                elseif ($options['arProperties']["IS_EMAIL"] == "Y"):
                    $options['arProperties']["VALUE"] = $this->user->GetEmail();
                elseif ($options['arProperties']["IS_PAYER"] == "Y"):
                    $rsUser = CUser::GetByID($this->user->GetID());
                    $fio    = "";

                    if ($arUser = $rsUser->Fetch()):
                        $fio = CUser::FormatName(
                            CSite::GetNameFormat(false),
                            array(
                                "NAME"          => $arUser["NAME"],
                                "LAST_NAME"     => $arUser["LAST_NAME"],
                                "SECOND_NAME"   => $arUser["SECOND_NAME"],
                                ),
                            false,
                            false);
                    endif;

                    $options['arProperties']["VALUE"] = $fio;
                endif;

                $options['arProperties']["SOURCE"] = 'DEFAULT';
            else:
                $options['arProperties']["VALUE"] = $curVal;
                $options['arProperties']["SOURCE"] = 'FORM';
            endif;

            if ($options['arProperties']["IS_ZIP"] == "Y" && $this->arResult['USER']["PROFILE_CHANGE"] == "N"):
                $dbPropertiesLoc = CSaleOrderProps::GetList(
                    array("ID" => "DESC"),
                    array(
                        "PERSON_TYPE_ID" => $this->arResult['USER']["PERSON_TYPE_ID"],
                        "ACTIVE" => "Y",
                        "UTIL" => "N",
                        "IS_LOCATION" => "Y"
                    ),
                    false,
                    false,
                    array("ID")
                );
                $arPropertiesLoc = $dbPropertiesLoc->Fetch();

                if ($arPropertiesLoc["ID"] > 0):
                    $arZipLocation = array();

                    if(strlen($curVal) > 0):
                        $arZipLocation = CSaleLocation::GetByZIP($curVal);
                    endif;

                    $rsZipList = CSaleLocation::GetLocationZIP($this->arResult['USER']["ORDER_PROP"][$arPropertiesLoc["ID"]]);

                    if($arZip = $rsZipList->Fetch()):
                        if (
                            strlen($arZip["ZIP"]) > 0
                                &&
                            (
                                empty($arZipLocation)
                                    ||
                                $arZipLocation["ID"] != $this->arResult['USER']["ORDER_PROP"][$arPropertiesLoc["ID"]]
                            )
                        ):
                            $options['arProperties']["VALUE"] = $arZip["ZIP"];
                        endif;
                    endif;
                endif;
            endif;

            if ($options['arProperties']["IS_ZIP"]=="Y"):
                $this->arResult['USER']["DELIVERY_LOCATION_ZIP"] = $options['arProperties']["VALUE"];
            endif;

            $options['arProperties']["VALUE"]           = htmlspecialcharsEx($options['arProperties']["VALUE"]);
            $options['arProperties']["VALUE_FORMATED"]  = $options['arProperties']["VALUE"];

            if ($isProfileChanged || $isEmptyUserResult):
                $this->arResult['USER']["ORDER_PROP"][$options['arProperties']["ID"]] = $options['arProperties']["VALUE"];
            endif;
        elseif ($options['arProperties']["TYPE"] == "SELECT"):
            $options['arProperties']["SIZE1"] = ((intval($options['arProperties']["SIZE1"]) > 0) ? $options['arProperties']["SIZE1"] : 1);
            $dbVariants = CSaleOrderPropsVariant::GetList(
                array("SORT" => "ASC", "NAME" => "ASC"),
                array("ORDER_PROPS_ID" => $options['arProperties']["ID"]),
                false,
                false,
                array("*")

            );
            $flagDefault = "N";
            $nameProperty = "";

            while ($arVariants = $dbVariants->GetNext()):
                if ($flagDefault == "N" && $nameProperty == ""):
                    $nameProperty = $arVariants["NAME"];
                endif;

                if (
                    ($arVariants["VALUE"] == $curVal)
                        ||
                    (
                        (
                            !isset($curVal)
                                ||
                            $curVal == ""
                        )
                            &&
                        (
                            $arVariants["VALUE"] == $options['arProperties']["DEFAULT_VALUE"]
                        )
                    )
                ):
                    $arVariants["SELECTED"] = "Y";

                    $options['arProperties']["VALUE_FORMATED"] = $arVariants["NAME"];

                    $flagDefault = "Y";

                    if ($isProfileChanged || $isEmptyUserResult):
                        $this->arResult['USER']["ORDER_PROP"][$options['arProperties']["ID"]] = $arVariants["NAME"];
                    endif;
                endif;

                $options['arProperties']["VARIANTS"][] = $arVariants;
            endwhile;

            if ($flagDefault == "N"):
                $options['arProperties']["VARIANTS"][0]["SELECTED"]         = "Y";
                $options['arProperties']["VARIANTS"][0]["VALUE_FORMATED"]   = $nameProperty;

                if ($isProfileChanged || $isEmptyUserResult):
                    $this->arResult['USER']["ORDER_PROP"][$options['arProperties']["ID"]] = $nameProperty;
                endif;
            endif;
        elseif ($options['arProperties']["TYPE"] == "MULTISELECT"):
            $options['arProperties']["FIELD_NAME"]  = "ORDER_PROP_".$options['arProperties']["ID"].'[]';
            $options['arProperties']["SIZE1"]       = ((intval($options['arProperties']["SIZE1"]) > 0) ? $options['arProperties']["SIZE1"] : 5);

            $setValue       = array();
            $arDefVal       = explode(",", $options['arProperties']["DEFAULT_VALUE"]);
            $countDefVal    = count($arDefVal);

            for ($i = 0; $i < $countDefVal; $i++):
                $arDefVal[$i] = Trim($arDefVal[$i]);
            endfor;

            $dbVariants = CSaleOrderPropsVariant::GetList(
                array("SORT" => "ASC"),
                array("ORDER_PROPS_ID" => $options['arProperties']["ID"]),
                false,
                false,
                array("*")
            );

            $i = 0;

            while ($arVariants = $dbVariants->GetNext()):
                if (
                    (
                        is_array($curVal)
                            &&
                        in_array($arVariants["VALUE"], $curVal)
                    )
                        ||
                    (
                        !isset($curVal)
                            &&
                        in_array($arVariants["VALUE"], $arDefVal)
                    )
                ):
                    $arVariants["SELECTED"] = "Y";

                    if ($i > 0):
                        $options['arProperties']["VALUE_FORMATED"] .= ", ";
                    endif;

                    $options['arProperties']["VALUE_FORMATED"] .= $arVariants["NAME"];

                    $setValue[] = $arVariants["VALUE"];

                    $i++;
                endif;

                $options['arProperties']["VARIANTS"][] = $arVariants;
            endwhile;

            if ($isProfileChanged || $isEmptyUserResult):
                $this->arResult['USER']["ORDER_PROP"][$options['arProperties']["ID"]] = $setValue;
            endif;
        elseif ($options['arProperties']["TYPE"] == "TEXTAREA"):
            $options['arProperties']["SIZE2"]           = ((intval($options['arProperties']["SIZE2"]) > 0) ? $options['arProperties']["SIZE2"] : 4);
            $options['arProperties']["SIZE1"]           = ((intval($options['arProperties']["SIZE1"]) > 0) ? $options['arProperties']["SIZE1"] : 40);
            $options['arProperties']["VALUE"]           = htmlspecialcharsEx(isset($curVal) ? $curVal : $options['arProperties']["DEFAULT_VALUE"]);
            $options['arProperties']["VALUE_FORMATED"]  = $options['arProperties']["VALUE"];

            if ($isProfileChanged || $isEmptyUserResult):
                $this->arResult['USER']["ORDER_PROP"][$options['arProperties']["ID"]] = $options['arProperties']["VALUE"];
            endif;
        elseif ($options['arProperties']["TYPE"] == "LOCATION"):
            if(CSaleLocation::isLocationProEnabled()):
                $options['arProperties']["VALUE"] = $curVal;

                $locationFound  = false;
                $dbVariants     = CSaleLocation::GetList(
                    array("SORT" => "ASC", "COUNTRY_NAME_LANG" => "ASC", "CITY_NAME_LANG" => "ASC"),
                    array("LID" => LANGUAGE_ID),
                    false,
                    false,
                    array("ID", "COUNTRY_NAME", "CITY_NAME", "SORT", "COUNTRY_NAME_LANG", "CITY_NAME_LANG", "CITY_ID")
                );

                while ($arVariants = $dbVariants->GetNext()):
                    if (
                        intval($arVariants["ID"]) == intval($curVal)
                            ||
                        (
                            !isset($curVal)
                                &&
                            intval($arVariants["ID"]) == intval($options['arProperties']["DEFAULT_VALUE"])
                        )
                            ||
                        (
                            strlen($curLocation) > 0
                                &&
                            ToUpper($curLocation) == ToUpper($arVariants["CITY_NAME"])
                        )
                    ):
                        $options['arProperties']["VALUE_FORMATED"] = $arVariants["COUNTRY_NAME"].((strlen($arVariants["CITY_NAME"]) > 0) ? " - " : "").$arVariants["CITY_NAME"];

                        $this->arResult['USER']["DELIVERY_LOCATION"] = $arVariants['ID'];

                        if($options['arProperties']["IS_LOCATION4TAX"]=="Y"):
                            $this->arResult['USER']["TAX_LOCATION"] = $arVariants['ID'];
                        endif;

                        $locationFound = $arVariants;

                        $arVariants["SELECTED"] = "Y";

                        if ($isProfileChanged || $isEmptyUserResult):
                            $this->arResult['USER']["ORDER_PROP"][$options['arProperties']["ID"]] = $arVariants['ID'];
                        endif;
                    endif;

                    $arVariants["NAME"] = $arVariants["COUNTRY_NAME"].((strlen($arVariants["CITY_NAME"]) > 0) ? " - " : "").$arVariants["CITY_NAME"];

                    $options['arProperties']["VARIANTS"][] = $arVariants;
                endwhile;

                if(!$locationFound && IntVal($curVal)):
                    $item = CSaleLocation::GetById($curVal);

                    if($item):
                        $options['arProperties']["VALUE_FORMATED"] = $item["COUNTRY_NAME"].((strlen($item["CITY_NAME"]) > 0) ? " - " : "").$item["CITY_NAME"];

                        $this->arResult['USER']["DELIVERY_LOCATION"] = $options['arProperties']["VALUE"];

                        if($options['arProperties']["IS_LOCATION4TAX"]=="Y"):
                            $this->arResult['USER']["TAX_LOCATION"] = $options['arProperties']["VALUE"];
                        endif;

                        if ($isProfileChanged || $isEmptyUserResult):
                            $this->arResult['USER']["ORDER_PROP"][$options['arProperties']["ID"]] = $options['arProperties']["VALUE"];
                        endif;

                        $locationFound = $item;
                        $item['SELECTED']   = 'Y';
                        $item['NAME']       = $item["COUNTRY_NAME"].((strlen($item["CITY_NAME"]) > 0) ? " - " : "").$item["CITY_NAME"];

                        $options['arProperties']["VARIANTS"][] = $item;
                    endif;
                endif;

                if($locationFound):
                    if(isset($this->arResult['LOCATION_ALT_PROP_DISPLAY_MANUAL'])):
                        if(intval($this->arResult['LOCATION_ALT_PROP_DISPLAY_MANUAL'][$options['arProperties']["ID"]])):
                            unset($options['arDeleteFieldLocation'][$options['arProperties']["ID"]]);
                        else:
                            $options['arDeleteFieldLocation'][$options['arProperties']["ID"]] = $options['arProperties']["INPUT_FIELD_LOCATION"];
                        endif;
                    else:
                        $options['arDeleteFieldLocation'][$options['arProperties']["ID"]] = $options['arProperties']["INPUT_FIELD_LOCATION"];
                    endif;
                else:
                    $options['arDeleteFieldLocation'][$options['arProperties']["ID"]] = $options['arProperties']["INPUT_FIELD_LOCATION"];
                endif;
            else:
                if (
                    $_REQUEST["is_ajax_post"] == "Y"
                        &&
                    $options['arProperties']["IS_LOCATION"] == "Y"
                        &&
                    intval($options['arProperties']["INPUT_FIELD_LOCATION"]) > 0
                        &&
                    isset($_REQUEST["ORDER_PROP_".$options['arProperties']["ID"]])
                ):
                    $rsLocationsList = CSaleLocation::GetList(
                        array(),
                        array("ID" => $curVal),
                        false,
                        false,
                        array("ID", "CITY_ID")
                    );
                    $arCity = $rsLocationsList->GetNext();

                    if (intval($arCity["CITY_ID"]) <= 0):
                        unset($options['arDeleteFieldLocation'][$options['arProperties']["ID"]]);
                    else:
                        $options['arDeleteFieldLocation'][$options['arProperties']["ID"]] = $options['arProperties']["INPUT_FIELD_LOCATION"];
                    endif;
                elseif ($options['arProperties']["IS_LOCATION"] == "Y" && intval($options['arProperties']["INPUT_FIELD_LOCATION"]) > 0):
                    $options['arDeleteFieldLocation'][$options['arProperties']["ID"]] = $options['arProperties']["INPUT_FIELD_LOCATION"];
                endif;

                $options['arProperties']["SIZE1"] = ((intval($options['arProperties']["SIZE1"]) > 0) ? $options['arProperties']["SIZE1"] : 1);

                $dbVariants = CSaleLocation::GetList(
                    array("SORT" => "ASC", "COUNTRY_NAME_LANG" => "ASC", "CITY_NAME_LANG" => "ASC"),
                    array("LID" => LANGUAGE_ID),
                    false,
                    false,
                    array("ID", "COUNTRY_NAME", "CITY_NAME", "SORT", "COUNTRY_NAME_LANG", "CITY_NAME_LANG")
                );

                while ($arVariants = $dbVariants->GetNext()):
                    if (
                        intval($arVariants["ID"]) == intval($curVal)
                            ||
                        (
                            !isset($curVal)
                                &&
                            intval($arVariants["ID"]) == intval($options['arProperties']["DEFAULT_VALUE"])
                        )
                            ||
                        (
                            strlen($curLocation) > 0
                                &&
                            ToUpper($curLocation) == ToUpper($arVariants["CITY_NAME"])
                        )
                    ):
                        $arVariants["SELECTED"] = "Y";
                        $options['arProperties']["VALUE_FORMATED"] = $arVariants["COUNTRY_NAME"].((strlen($arVariants["CITY_NAME"]) > 0) ? " - " : "").$arVariants["CITY_NAME"];
                        $options['arProperties']["VALUE"] = $arVariants["ID"];

                        if ($options['arProperties']["IS_LOCATION"]=="Y"):
                            $this->arResult['USER']["DELIVERY_LOCATION"] = $options['arProperties']["VALUE"];
                            endif;

                        if ($options['arProperties']["IS_LOCATION4TAX"]=="Y"):
                            $this->arResult['USER']["TAX_LOCATION"] = $options['arProperties']["VALUE"];
                        endif;

                        if ($isProfileChanged || $isEmptyUserResult):
                            $this->arResult['USER']["ORDER_PROP"][$options['arProperties']["ID"]] = $options['arProperties']["VALUE"];
                        endif;

                    endif;

                    $arVariants["NAME"] = $arVariants["COUNTRY_NAME"].((strlen($arVariants["CITY_NAME"]) > 0) ? " - " : "").$arVariants["CITY_NAME"];

                    $options['arProperties']["VARIANTS"][] = $arVariants;
                endwhile;

                if(count($options['arProperties']["VARIANTS"]) == 1)
                {
                    $options['arProperties']["VALUE"] = $options['arProperties']["VARIANTS"][0]["ID"];
                    if($options['arProperties']["IS_LOCATION"]=="Y")
                        $this->arResult['USER']["DELIVERY_LOCATION"] = $options['arProperties']["VALUE"];
                    if($options['arProperties']["IS_LOCATION4TAX"]=="Y")
                        $this->arResult['USER']["TAX_LOCATION"] = $options['arProperties']["VALUE"];
                }
            endif;
        elseif ($options['arProperties']["TYPE"] == "RADIO"):
            $dbVariants = CSaleOrderPropsVariant::GetList(
                array("SORT" => "ASC"),
                array("ORDER_PROPS_ID" => $options['arProperties']["ID"]),
                false,
                false,
                array("*")
            );

            while ($arVariants = $dbVariants->GetNext()):
                if (
                    $arVariants["VALUE"] == $curVal
                        ||
                    (
                        !isset($curVal)
                            &&
                        $arVariants["VALUE"] == $options['arProperties']["DEFAULT_VALUE"]
                    )
                ):
                    $arVariants["CHECKED"]="Y";

                    $options['arProperties']["VALUE_FORMATED"] = $arVariants["NAME"];

                    if ($isProfileChanged || $isEmptyUserResult):
                        $this->arResult['USER']["ORDER_PROP"][$options['arProperties']["ID"]] = $arVariants["VALUE"];
                    endif;
                endif;

                $options['arProperties']["VARIANTS"][] = $arVariants;
            endwhile;
        elseif ($options['arProperties']["TYPE"] == "FILE"):
            $options['arProperties']["SIZE1"] = intval($options['arProperties']["SIZE1"]);
            $options['arProperties']["VALUE"] = isset($curVal) ? CSaleHelper::getFileInfo($curVal) : $options['arProperties']["DEFAULT_VALUE"];

            if ($isProfileChanged || $isEmptyUserResult):
                $this->arResult['USER']["ORDER_PROP"][$options['arProperties']["ID"]] = $options['arProperties']["VALUE"];
            endif;
        endif;

        return $options['arProperties'];
    }

    /**
     *
     */
    public function GetBasket()
    {
        $this->arResult['ELEMENT_ID']           = array();
        $this->arResult['SKU_2_PARENT']         = array();
        $this->arResult['SET_PARENT_WEIGHT']    = array();
        $this->arResult['DISCOUNT_PRICE_ALL']   = 0;
        $this->arResult["MAX_DIMENSIONS"]       = $this->arResult["ITEMS_DIMENSIONS"] = array();

        \CSaleBasket::UpdateBasketPrices(\CSaleBasket::GetBasketUserID(), SITE_ID);

        $this->arParams['BASKET']['ORDER']    = array(
            "ID"    => "ASC",
        );
        $this->arParams['BASKET']['FILTER']   = array(
            "FUSER_ID"  => \CSaleBasket::GetBasketUserID(),
            "LID"       => SITE_ID,
            "ORDER_ID"  => "NULL",
        );
        $this->arParams['BASKET']['SELECT'] = array(
            "ID",
            "CALLBACK_FUNC",
            "MODULE",
            "PRODUCT_ID",
            "QUANTITY",
            "DELAY",
            "CAN_BUY",
            "PRICE",
            "WEIGHT",
            "NAME",
            "CURRENCY",
            "CATALOG_XML_ID",
            "VAT_RATE",
            "NOTES",
            "DISCOUNT_PRICE",
            "PRODUCT_PROVIDER_CLASS",
            "DIMENSIONS",
            "TYPE",
            "SET_PARENT_ID",
            "DETAIL_PAGE_URL",
        );
        $this->arParams['BASKET']['GROUP'] = false;
        $this->arParams['BASKET']['LIMIT'] = false;

        $dbBasketItems = CSaleBasket::GetList(
            $this->arParams['BASKET']['ORDER'],
            $this->arParams['BASKET']['FILTER'],
            $this->arParams['BASKET']['GROUP'],
            $this->arParams['BASKET']['LIMIT'],
            $this->arParams['BASKET']['SELECT']
        );

        while ($arItem = $dbBasketItems->GetNext()):
            if ($arItem["DELAY"] == "N" && $arItem["CAN_BUY"] == "Y"):
                $arItem["PRICE"]    = roundEx($arItem["PRICE"], SALE_VALUE_PRECISION);
                $arItem["QUANTITY"] = DoubleVal($arItem["QUANTITY"]);
                $arItem["WEIGHT"]   = DoubleVal($arItem["WEIGHT"]);
                $arItem["VAT_RATE"] = DoubleVal($arItem["VAT_RATE"]);

                $arDim = unserialize($arItem["~DIMENSIONS"]);

                if(is_array($arDim)):
                    $arItem["DIMENSIONS"] = $arDim;

                    unset($arItem["~DIMENSIONS"]);

                    $this->arResult["MAX_DIMENSIONS"] = \CSaleDeliveryHelper::getMaxDimensions(
                        array(
                            $arDim["WIDTH"],
                            $arDim["HEIGHT"],
                            $arDim["LENGTH"],
                        ),
                        $this->arResult["MAX_DIMENSIONS"]
                    );

                    $this->arResult["ITEMS_DIMENSIONS"][] = $arDim;
                endif;

                if($arItem["VAT_RATE"] > 0 && !\CSaleBasketHelper::isSetItem($arItem)):
                    $this->arResult["bUsingVat"] = "Y";

                    if($arItem["VAT_RATE"] > $this->arResult["VAT_RATE"]):
                        $this->arResult["VAT_RATE"] = $arItem["VAT_RATE"];
                    endif;

                    $arItem["VAT_VALUE"]        = roundEx((($arItem["PRICE"] / ($arItem["VAT_RATE"] +1)) * $arItem["VAT_RATE"]), SALE_VALUE_PRECISION);

                    $this->arResult["VAT_SUM"] += roundEx($arItem["VAT_VALUE"] * $arItem["QUANTITY"], SALE_VALUE_PRECISION);
                endif;

                $arItem["PRICE_FORMATED"]   = SaleFormatCurrency($arItem["PRICE"], $arItem["CURRENCY"]);
                $arItem["WEIGHT_FORMATED"]  = roundEx(DoubleVal($arItem["WEIGHT"]/$this->arResult["WEIGHT_KOEF"]), SALE_WEIGHT_PRECISION)." ".$this->arResult["WEIGHT_UNIT"];

                if($arItem["DISCOUNT_PRICE"] > 0):
                    $arItem["DISCOUNT_PRICE_PERCENT"]           = $arItem["DISCOUNT_PRICE"]*100 / ($arItem["DISCOUNT_PRICE"] + $arItem["PRICE"]);
                    $arItem["DISCOUNT_PRICE_PERCENT_FORMATED"]  = roundEx($arItem["DISCOUNT_PRICE_PERCENT"], 0)."%";
                endif;

                $arItem["PROPS"] = array();

                $this->arParams['BASKET_PROPS']['ORDER'] = array(
                    "SORT" => "ASC",
                    "ID" => "ASC",
                );
                $this->arParams['BASKET_PROPS']['FILTER'] = array(
                    "BASKET_ID" => $arItem["ID"],
                    "!CODE" => array(
                        "CATALOG.XML_ID",
                        "PRODUCT.XML_ID",
                    ),
                );

                $dbProp = CSaleBasket::GetPropsList(
                    $this->arParams['BASKET_PROPS']['ORDER'],
                    $this->arParams['BASKET_PROPS']['FILTER']
                );

                while($arProp = $dbProp -> GetNext()):
                    if (array_key_exists('BASKET_ID', $arProp)):
                        unset($arProp['BASKET_ID']);
                    endif;

                    if (array_key_exists('~BASKET_ID', $arProp)):
                        unset($arProp['~BASKET_ID']);
                    endif;

                    $arProp = array_filter($arProp, array("CSaleBasketHelper", "filterFields"));

                    $arItem["PROPS"][$arProp['CODE']] = $arProp;
                endwhile;

                if (!\CSaleBasketHelper::isSetItem($arItem)):
                    $this->arResult['DISCOUNT_PRICE_ALL'] += $arItem["DISCOUNT_PRICE"] * $arItem["QUANTITY"];
                    $arItem["DISCOUNT_PRICE"] = roundEx($arItem["DISCOUNT_PRICE"], SALE_VALUE_PRECISION);
                    $this->arResult["ORDER_PRICE"] += $arItem["PRICE"] * $arItem["QUANTITY"];
                endif;

                if (!\CSaleBasketHelper::isSetItem($arItem)):
                    $this->arResult["ORDER_WEIGHT"] += $arItem["WEIGHT"] * $arItem["QUANTITY"];
                endif;

                if (\CSaleBasketHelper::isSetItem($arItem)):
                    $this->arResult['SET_PARENT_WEIGHT'][$arItem["SET_PARENT_ID"]] += $arItem["WEIGHT"] * $arItem['QUANTITY'];
                endif;

                $this->arResult["BASKET_ITEMS"][] = $arItem;
            endif;

            $this->arResult["PRICE_WITHOUT_DISCOUNT"] = SaleFormatCurrency($this->arResult["ORDER_PRICE"] + $this->arResult['DISCOUNT_PRICE_ALL'], $this->allCurrency);

            foreach ($this->arResult["BASKET_ITEMS"] as &$arItem):
                if (\CSaleBasketHelper::isSetParent($arItem)):
                    $arItem["WEIGHT"] = $this->arResult['SET_PARENT_WEIGHT'][$arItem["ID"]] / $arItem["QUANTITY"];
                    $arItem["WEIGHT_FORMATED"] = roundEx(doubleval($arItem["WEIGHT"] / $this->arResult["WEIGHT_KOEF"]), SALE_WEIGHT_PRECISION)." ".$this->arResult["WEIGHT_UNIT"];
                endif;
            endforeach;

            $this->arResult["ORDER_WEIGHT_FORMATED"]    = roundEx(DoubleVal($this->arResult["ORDER_WEIGHT"]/$this->arResult["WEIGHT_KOEF"]), SALE_WEIGHT_PRECISION)." ".$this->arResult["WEIGHT_UNIT"];
            $this->arResult["ORDER_PRICE_FORMATED"]     = SaleFormatCurrency($this->arResult["ORDER_PRICE"], $this->arResult["BASE_LANG_CURRENCY"]);
            $this->arResult["VAT_SUM_FORMATED"]         = SaleFormatCurrency($this->arResult["VAT_SUM"], $this->arResult["BASE_LANG_CURRENCY"]);

            $this->arElementId[] = $arItem["PRODUCT_ID"];

            if ($this->bUseCatalog):
                $arParent = \CCatalogSku::GetProductInfo($arItem["PRODUCT_ID"]);

                if ($arParent):
                    $this->arElementId[] = $arParent["ID"];
                    $this->arSku2Parent[$arItem["PRODUCT_ID"]] = $arParent["ID"];
                endif;
            endif;

            unset($arItem);
        endwhile;

        if (!empty($this->arResult["BASKET_ITEMS"]))
        {
            if ($this->bUseCatalog)
                $this->arResult["BASKET_ITEMS"] = getMeasures($this->arResult["BASKET_ITEMS"]); // get measures
        }
        if (empty($this->arResult["BASKET_ITEMS"]) || !is_array($this->arResult["BASKET_ITEMS"]))
        {
            if ($this->arParams["DISABLE_BASKET_REDIRECT"] == 'Y')
            {
                return;
            }
            else
            {
                if (isset($_REQUEST['json']) && $_REQUEST['json'] == "Y")
                {
                    $this->application->RestartBuffer();

                    echo json_encode(array("success" => "N", "redirect" => $this->arParams["PATH_TO_BASKET"]));

                    die();
                }

                LocalRedirect($this->arParams["PATH_TO_BASKET"]);

                die();
            }
        }
    }

    /**
     * @param array $opations
     */
    public function ReCheckBasket($opations=array())
    {
        $arSelect = array_merge(array("ID", "PREVIEW_PICTURE", "DETAIL_PICTURE", "PREVIEW_TEXT"), $this->arCustomSelectFields);

        $arProductData = getProductProps($this->arElementId, $arSelect);

        foreach ($this->arResult["BASKET_ITEMS"] as &$arResultItem):
            $productId  = $this->arResultItem["PRODUCT_ID"];
            $arParent   = CCatalogSku::GetProductInfo($productId);

            if (
                (int)$arProductData[$productId]["PREVIEW_PICTURE"] <= 0
                    &&
                (int)$arProductData[$productId]["DETAIL_PICTURE"] <= 0
                    &&
                $arParent
            ):
                $productId = $arParent["ID"];
            endif;

            if((int)$arProductData[$productId]["PREVIEW_PICTURE"] > 0):
                $this->arResultItem["PREVIEW_PICTURE"] = $arProductData[$productId]["PREVIEW_PICTURE"];
            endif;

            if((int)$arProductData[$productId]["DETAIL_PICTURE"] > 0):
                $arResultItem["DETAIL_PICTURE"] = $arProductData[$productId]["DETAIL_PICTURE"];
            endif;

            if($arProductData[$productId]["PREVIEW_TEXT"] != ''):
                $arResultItem["PREVIEW_TEXT"] = $arProductData[$productId]["PREVIEW_TEXT"];
            endif;

            foreach ($arProductData[$arResultItem["PRODUCT_ID"]] as $key => $value):
                if (strpos($key, "PROPERTY_") !== false):
                    $arResultItem[$key] = $value;
                endif;
            endforeach;

            if (array_key_exists($arResultItem["PRODUCT_ID"], $this->arSku2Parent)):
                foreach ($this->arCustomSelectFields as $field):
                    $fieldVal = $field."_VALUE";
                    $parentId = $this->arSku2Parent[$arResultItem["PRODUCT_ID"]];

                    if(
                        (
                            (!isset($arResultItem[$fieldVal]))
                                ||
                            (
                                (isset($arResultItem[$fieldVal]))
                                    &&
                                (strlen($arResultItem[$fieldVal]) == 0)
                            )
                        )
                            &&
                        (
                            (isset($arProductData[$parentId][$fieldVal]))
                                &&
                            (!empty($arProductData[$parentId][$fieldVal]))
                        )
                    ):
                        $arResultItem[$fieldVal] = $arProductData[$parentId][$fieldVal];
                    endif;
                endforeach;
            endif;

            $arResultItem["PREVIEW_PICTURE_SRC"] = "";

            if (isset($arResultItem["PREVIEW_PICTURE"]) && intval($arResultItem["PREVIEW_PICTURE"]) > 0):
                $arImage = CFile::GetFileArray($arResultItem["PREVIEW_PICTURE"]);

                $this->arParams['BASKET']['PARAMS']['PREVIEW_IMAGE'] = array(
                    'OPTIONS'       => array(
                        "width"         => "110",
                        "height"        =>"110",
                    ),
                    'RESIZE'        => BX_RESIZE_IMAGE_PROPORTIONAL,
                    'INIT_SIZES'    => true,
                    'FILTERS'       => false,
                    'IMMEDIATE'     => false,
                    'JPGQUALITY'    => 100,
                );

                if ($arImage):
                    $arFileTmp = CFile::ResizeImageGet(
                        $arImage,
                        $this->arParams['BASKET']['PARAMS']['PREVIEW_IMAGE']['OPTIONS'],
                        $this->arParams['BASKET']['PARAMS']['PREVIEW_IMAGE']['RESIZE'],
                        $this->arParams['BASKET']['PARAMS']['PREVIEW_IMAGE']['INIT_SIZES'],
                        $this->arParams['BASKET']['PARAMS']['PREVIEW_IMAGE']['FILTERS'],
                        $this->arParams['BASKET']['PARAMS']['PREVIEW_IMAGE']['IMMEDIATE'],
                        $this->arParams['BASKET']['PARAMS']['PREVIEW_IMAGE']['JPGQUALITY']
                    );

                    $arResultItem["PREVIEW_PICTURE_SRC"] = $arFileTmp["src"];
                endif;
            endif;

            $arResultItem["DETAIL_PICTURE_SRC"] = "";

            if (isset($arResultItem["DETAIL_PICTURE"]) && intval($arResultItem["DETAIL_PICTURE"]) > 0):
                $arImage = CFile::GetFileArray($arResultItem["DETAIL_PICTURE"]);

                $this->arParams['BASKET']['PARAMS']['DETAIL_PICTURE'] = array(
                    'OPTIONS'       => array(
                        "width"         => "110",
                        "height"        =>"110",
                    ),
                    'RESIZE'        => BX_RESIZE_IMAGE_PROPORTIONAL,
                    'INIT_SIZES'    => true,
                    'FILTERS'       => false,
                    'IMMEDIATE'     => false,
                    'JPGQUALITY'    => 100,
                );

                if ($arImage):
                    $arFileTmp = CFile::ResizeImageGet(
                        $arImage,
                        $this->arParams['BASKET']['PARAMS']['DETAIL_PICTURE']['OPTIONS'],
                        $this->arParams['BASKET']['PARAMS']['DETAIL_PICTURE']['RESIZE'],
                        $this->arParams['BASKET']['PARAMS']['DETAIL_PICTURE']['INIT_SIZES'],
                        $this->arParams['BASKET']['PARAMS']['DETAIL_PICTURE']['FILTERS'],
                        $this->arParams['BASKET']['PARAMS']['DETAIL_PICTURE']['IMMEDIATE'],
                        $this->arParams['BASKET']['PARAMS']['DETAIL_PICTURE']['JPGQUALITY']
                    );

                    $arResultItem["DETAIL_PICTURE_SRC"] = $arFileTmp["src"];
                endif;
            endif;
        endforeach;
    }

    /**
     * @param array $options
     *
     * @return $this|bool
     */
    public function SetPrePaid($options=array())
    {
        if($this->arParams["USE_PREPAYMENT"] != "Y"):
            return false;
        endif;

        $this->arParams['PERSON_TYPE']['ORDER'] = array(
            "SORT" => "ASC",
            "NAME" => "ASC",
        );
        $this->arParams['PERSON_TYPE']['FILTER'] = array(
            "LID"       => SITE_ID,
            "ACTIVE"    => "Y",
        );

        $PSpersonType = array();
        $dbPersonType = \CSalePersonType::GetList(
            $this->arParams['PERSON_TYPE']['ORDER'],
            $this->arParams['PERSON_TYPE']['FILTER']
        );

        while($arPersonType = $dbPersonType->GetNext()):
            $PSpersonType[] = $arPersonType["ID"];
        endwhile;

        if(!empty($PSpersonType)):
            $this->arParams['PERSON_SYSTEM_ACTION']['ORDER'] = array(
                "SORT" => "ASC",
                "NAME" => "ASC",
            );
            $this->arParams['PERSON_SYSTEM_ACTION']['FILTER'] = array(
                "PS_ACTIVE"         => "Y",
                "HAVE_PREPAY"       => "Y",
                "PERSON_TYPE_ID"    => $PSpersonType,
            );
            $this->arParams['PERSON_SYSTEM_ACTION']['GROUP']    = false;
            $this->arParams['PERSON_SYSTEM_ACTION']['LIMIT']    = false;
            $this->arParams['PERSON_SYSTEM_ACTION']['SELECT']   = array(
                "ID",
                "PAY_SYSTEM_ID",
                "PERSON_TYPE_ID",
                "NAME",
                "ACTION_FILE",
                "RESULT_FILE",
                "NEW_WINDOW",
                "PARAMS",
                "ENCODING",
                "LOGOTIP",
            );

            $dbPaySysAction = \CSalePaySystemAction::GetList(
                $this->arParams['PERSON_SYSTEM_ACTION']['ORDER'],
                $this->arParams['PERSON_SYSTEM_ACTION']['FILTER'],
                $this->arParams['PERSON_SYSTEM_ACTION']['GROUP'],
                $this->arParams['PERSON_SYSTEM_ACTION']['LIMIT'],
                $this->arParams['PERSON_SYSTEM_ACTION']['SELECT']
            );

            if ($arPaySysAction = $dbPaySysAction->Fetch()):
                $this->arResult["PREPAY_PS"]          = $arPaySysAction;
                $this->arResult["HAVE_PREPAYMENT"]    = true;

                \CSalePaySystemAction::InitParamArrays(false, false, $arPaySysAction["PARAMS"]);

                $pathToAction = $_SERVER["DOCUMENT_ROOT"].$arPaySysAction["ACTION_FILE"];

                $pathToAction = str_replace("\\", "/", $pathToAction);

                while (substr($pathToAction, strlen($pathToAction) - 1, 1) == "/"):
                    $pathToAction = substr($pathToAction, 0, strlen($pathToAction) - 1);
                endwhile;

                if (file_exists($pathToAction)):
                    if (is_dir($pathToAction) && file_exists($pathToAction."/pre_payment.php")):
                        $pathToAction .= "/pre_payment.php";
                    endif;

                    try
                    {
                        @include_once($pathToAction);
                    }
                    catch(\Bitrix\Main\SystemException $e)
                    {
                        if($e->getCode() == \CSalePaySystemAction::GET_PARAM_VALUE):
                            $this->arResult["ERROR"][] = GetMessage("SOA_TEMPL_ORDER_PS_ERROR");
                        else:
                            $this->arResult["ERROR"][] = $e->getMessage();
                        endif;
                    }

                    $this->psPreAction = new \CSalePaySystemPrePayment;

                    if($this->psPreAction->init()):
                        $this->psPreAction->encoding = $arPaySysAction["ENCODING"];

                        if($this->psPreAction->IsAction()):
                            $this->arResult["PREPAY_ORDER_PROPS"] = $this->psPreAction->getProps();

                            if(IntVal($this->arResult['USER']["PAY_SYSTEM_ID"]) <= 0):
                                $this->arResult['USER']["PERSON_TYPE_ID"] = $this->arResult["PREPAY_PS"]["PERSON_TYPE_ID"];
                            endif;

                            $this->arResult['USER']["PREPAYMENT_MODE"]    = true;
                            $this->arResult['USER']["PAY_SYSTEM_ID"]      = $this->arResult["PREPAY_PS"]["PAY_SYSTEM_ID"];
                        elseif($_POST["PAY_SYSTEM_ID"] == $this->arResult["PREPAY_PS"]["PAY_SYSTEM_ID"]):
                            $orderData = array(
                                "PATH_TO_ORDER" => $this->application->GetCurPage(),
                                "AMOUNT"        => $this->arResult["ORDER_PRICE"],
                                "ORDER_REQUEST" => "Y",
                                "BASKET_ITEMS"  => $this->arResult["BASKET_ITEMS"],
                            );
                            $this->arResult["REDIRECT_URL"] = $this->psPreAction->BasketButtonAction($orderData);

                            if(strlen($this->arResult["REDIRECT_URL"]) > 1):
                                $this->arResult["NEED_REDIRECT"] = "Y";
                            endif;
                        endif;

                        $this->arResult["PREPAY_ADIT_FIELDS"] = $this->psPreAction->getHiddenInputs();
                    endif;
                endif;
            endif;
        endif;

        return $this;
    }

    /**
     * @param array $options
     *
     * @return $this|bool
     */
    public function SetOrder($options=array())
    {
        if(!$this->arResult['IS_ORDER_PLACED']):return false;endif;

        if(IntVal($_POST["PERSON_TYPE"]) > 0):
            $this->arResult['USER']["PERSON_TYPE_ID"] = IntVal($_POST["PERSON_TYPE"]);
        endif;

        if(IntVal($_POST["PERSON_TYPE_OLD"]) == $this->arResult['USER']["PERSON_TYPE_ID"]):
            if(isset($_POST["PROFILE_ID"])):
                $this->arResult['USER']["PROFILE_ID"] = IntVal($_POST["PROFILE_ID"]);
            endif;

            if(isset($_POST["PAY_SYSTEM_ID"])):
                $this->arResult['USER']["PAY_SYSTEM_ID"] = IntVal($_POST["PAY_SYSTEM_ID"]);
            endif;

            if(isset($_POST["DELIVERY_ID"])):
                $this->arResult['USER']["DELIVERY_ID"] = $_POST["DELIVERY_ID"];
            endif;

            if(strlen($_POST["ORDER_DESCRIPTION"]) > 0):
                $this->arResult['USER']["~ORDER_DESCRIPTION"]   = $_POST["ORDER_DESCRIPTION"];
                $this->arResult['USER']["ORDER_DESCRIPTION"]    = htmlspecialcharsbx($this->arResult['USER']["~ORDER_DESCRIPTION"]);
            endif;

            if($_POST["PAY_CURRENT_ACCOUNT"] == "Y"):
                $this->arResult['USER']["PAY_CURRENT_ACCOUNT"] = "Y";
            endif;

            if($_POST["confirmorder"] == "Y"):
                $this->arResult['USER']["CONFIRM_ORDER"]    = "Y";
                $this->arResult['USER']["FINAL_STEP"]       = "Y";
            endif;

            if($_POST["profile_change"] == "Y"):
                $this->arResult['USER']["PROFILE_CHANGE"] = "Y";
            else:
                $this->arResult['USER']["PROFILE_CHANGE"] = "N";
            endif;
        endif;

        if(IntVal($this->arResult['USER']["PERSON_TYPE_ID"]) <= 0):
            $this->arResult["ERROR"][] = GetMessage("SOA_ERROR_PERSON_TYPE");
        endif;

        foreach($_POST as $k => $v):
            if(strpos($k, "ORDER_PROP_") !== false):
                if(strpos($k, "[]") !== false):
                    $orderPropId = IntVal(substr($k, strlen("ORDER_PROP_"), strlen($k)-2));
                else:
                    $orderPropId = IntVal(substr($k, strlen("ORDER_PROP_")));
                endif;

                if($orderPropId > 0):
                    $this->arResult['USER']["ORDER_PROP"][$orderPropId] = $v;
                elseif(strpos($k, "COUNTRY_ORDER_PROP_") !== false):
                    $this->arResult['USER']["ORDER_PROP"]["COUNTRY_".IntVal(substr($k, strlen("COUNTRY_ORDER_PROP_")))] = $v;
                elseif(strpos($k, "REGION_ORDER_PROP_") !== false):
                    $this->arResult['USER']["ORDER_PROP"]["REGION_".IntVal(substr($k, strlen("REGION_ORDER_PROP_")))] = $v;
                elseif(strpos($k, "COUNTRYORDER_PROP_") !== false):
                    $this->arResult['USER']["ORDER_PROP"]["COUNTRY_".IntVal(substr($k, strlen("COUNTRYORDER_PROP_")))] = $v;
                elseif(strpos($k, "REGIONORDER_PROP_") !== false):
                    $this->arResult['USER']["ORDER_PROP"]["REGION_".IntVal(substr($k, strlen("REGIONORDER_PROP_")))] = $v;
                endif;
            endif;

            if(strpos($k, "NEW_LOCATION_") !== false && intval($v) > 0):
                $orderPropId = IntVal(substr($k, strlen("NEW_LOCATION_")));
                $this->arResult['USER']["ORDER_PROP"][$orderPropId] = $v;
            endif;
        endforeach;

        foreach ($_FILES as $k => $arFileData):
            if(strpos($k, "ORDER_PROP_") !== false):
                $orderPropId = intval(substr($k, strlen("ORDER_PROP_")));

                $this->arResult['USER']["ORDER_PROP"][$orderPropId][0] = array();

                if (is_array($arFileData)):
                    foreach ($arFileData as $param_name => $arValues):
                        foreach ($arValues as $nIndex => $val):
                            if (strlen($arFileData["name"][$nIndex]) > 0):
                                $this->arResult['USER']["ORDER_PROP"][$orderPropId][$nIndex][$param_name] = $val;
                            endif;
                        endforeach;
                    endforeach;
                endif;
            endif;
        endforeach;

        $this->_getFormatedProperties($this->arResult['USER']["PERSON_TYPE_ID"], $this->arResult, $this->arResult['USER'], $this->arParams);

        $this->arParams['ORDER_PROPS']['ORDER']     = array(
            "SORT" => "ASC"
        );
        $this->arParams['ORDER_PROPS']['FILTER']    = array();

        if (isset($_POST["PAY_SYSTEM_ID"]) && strlen($_POST["PAY_SYSTEM_ID"]) > 0 && isset($_POST["PAY_CURRENT_ACCOUNT"]) && $_POST["PAY_CURRENT_ACCOUNT"] != "Y"):
            $this->arParams['ORDER_PROPS']['FILTER']["RELATED"]["PAYSYSTEM_ID"]    = $_POST["PAY_SYSTEM_ID"];
            $this->arParams['ORDER_PROPS']['FILTER']["RELATED"]["TYPE"]            = "WITH_NOT_RELATED";
        endif;

        if (isset($_POST["DELIVERY_ID"]) && strlen($_POST["DELIVERY_ID"]) > 0):
            $this->arParams['ORDER_PROPS']['FILTER']["RELATED"]["DELIVERY_ID"] = $_POST["DELIVERY_ID"];
            $this->arParams['ORDER_PROPS']['FILTER']["RELATED"]["TYPE"]        = "WITH_NOT_RELATED";
        endif;

        $this->arParams['ORDER_PROPS']['FILTER']["PERSON_TYPE_ID"] = $this->arResult['USER']["PERSON_TYPE_ID"];
        $this->arParams['ORDER_PROPS']['FILTER']["ACTIVE"] = "Y";
        $this->arParams['ORDER_PROPS']['FILTER']["UTIL"] = "N";

        if(!empty($this->arParams["PROP_".$this->arResult['USER']["PERSON_TYPE_ID"]])):
            $this->arParams['ORDER_PROPS']['FILTER']["!ID"] = $this->arParams["PROP_".$this->arResult['USER']["PERSON_TYPE_ID"]];
        endif;

        $this->arParams['ORDER_PROPS']['GROUP']     = false;
        $this->arParams['ORDER_PROPS']['LIMIT']     = false;
        $this->arParams['ORDER_PROPS']['SELECT']    = array(
            "ID",
            "NAME",
            "TYPE",
            "IS_LOCATION",
            "IS_LOCATION4TAX",
            "IS_PROFILE_NAME",
            "IS_PAYER",
            "IS_EMAIL",
            "REQUIED",
            "SORT",
            "IS_ZIP",
            "CODE",
            "MULTIPLE",
        );

        $dbOrderProps = CSaleOrderProps::GetList(
            $this->arParams['ORDER_PROPS']['ORDER'],
            $this->arParams['ORDER_PROPS']['FILTER'],
            $this->arParams['ORDER_PROPS']['GROUP'],
            $this->arParams['ORDER_PROPS']['LIMIT'],
            $this->arParams['ORDER_PROPS']['SELECT']
        );

        while ($arOrderProps = $dbOrderProps->GetNext()):
            $bErrorField = False;
            $curVal = $this->arResult['USER']["ORDER_PROP"][$arOrderProps["ID"]];

            if ($arOrderProps["TYPE"]=="LOCATION" && ($arOrderProps["IS_LOCATION"]=="Y" || $arOrderProps["IS_LOCATION4TAX"]=="Y")):
                if ($arOrderProps["IS_LOCATION"]=="Y"):
                    $this->arResult['USER']["DELIVERY_LOCATION"] = $curVal;
                endif;

                if ($arOrderProps["IS_LOCATION4TAX"]=="Y"):
                    $this->arResult['USER']["TAX_LOCATION"] = $curVal;
                endif;

                if (IntVal($curVal)<=0 && IntVal($this->arResult['USER']["ORDER_PROP"]["REGION_".$arOrderProps["ID"]]) > 0):
                    $dbLoc = CSaleLocation::GetList(array(), array("REGION_ID" => $this->arResult['USER']["ORDER_PROP"]["REGION_".$arOrderProps["ID"]], "CITY_ID" => false), false, false, array("ID", "REGION_ID", "CITY_ID"));

                    if($arLoc = $dbLoc->Fetch()):
                        $curVal = $arLoc["ID"];
                    endif;
                endif;

                if(IntVal($curVal)<=0 && IntVal($this->arResult['USER']["ORDER_PROP"]["COUNTRY_".$arOrderProps["ID"]]) > 0):
                    $dbLoc = CSaleLocation::GetList(array(), array("COUNTRY_ID" => $this->arResult['USER']["ORDER_PROP"]["COUNTRY_".$arOrderProps["ID"]], "REGION_ID" => false, "CITY_ID" => false), false, false, array("ID", "COUNTRY_ID", "REGION_ID", "CITY_ID"));

                    if($arLoc = $dbLoc->Fetch()):
                        $curVal = $arLoc["ID"];
                    endif;
                endif;

                if (IntVal($curVal)<=0):
                    $bErrorField = __LINE__;
                else:
                    $this->arResult['USER']["ORDER_PROP"][$arOrderProps["ID"]] = $curVal;
                endif;
            elseif (
                $arOrderProps["IS_PROFILE_NAME"]=="Y" ||
                $arOrderProps["IS_PAYER"]=="Y" ||
                $arOrderProps["IS_EMAIL"]=="Y" ||
                $arOrderProps["IS_ZIP"]=="Y"
            ):
                if ($arOrderProps["IS_PROFILE_NAME"]=="Y"):
                    $this->arResult['USER']["PROFILE_NAME"] = Trim($curVal);

                    if (strlen($this->arResult['USER']["PROFILE_NAME"])<=0):
                        $bErrorField = __LINE__;
                    endif;
                endif;

                if ($arOrderProps["IS_PAYER"]=="Y"):
                    $this->arResult['USER']["PAYER_NAME"] = Trim($curVal);

                    if (strlen($this->arResult['USER']["PAYER_NAME"])<=0):
                        $bErrorField = __LINE__;
                    endif;
                endif;

                if ($arOrderProps["IS_EMAIL"]=="Y"):
                    $this->arResult['USER']["USER_EMAIL"] = Trim($curVal);

                    if (strlen($this->arResult['USER']["USER_EMAIL"])<=0):
                        $bErrorField = __LINE__;
                    elseif(!check_email($this->arResult['USER']["USER_EMAIL"])):
                        $this->arResult["ERROR"][] = GetMessage("SOA_ERROR_EMAIL");
                    endif;
                endif;
                if ($arOrderProps["IS_ZIP"]=="Y"):
                    $this->arResult['USER']["DELIVERY_LOCATION_ZIP"] = Trim($curVal);

                    if (strlen($this->arResult['USER']["DELIVERY_LOCATION_ZIP"])<=0):
                        $bErrorField = __LINE__;
                    endif;
                endif;
            elseif ($arOrderProps["REQUIED"]=="Y"):
                if (
                    $arOrderProps["TYPE"]=="TEXT" ||
                    $arOrderProps["TYPE"]=="TEXTAREA" ||
                    $arOrderProps["TYPE"]=="RADIO" ||
                    $arOrderProps["TYPE"]=="SELECT" ||
                    $arOrderProps["TYPE"] == "CHECKBOX"
                ):
                    if (strlen($curVal)<=0):
                        $bErrorField = __LINE__;
                    endif;
                elseif ($arOrderProps["TYPE"]=="LOCATION"):
                    if (IntVal($curVal)<=0):
                        $bErrorField = __LINE__;
                    endif;
                elseif ($arOrderProps["TYPE"]=="MULTISELECT"):
                    if (!is_array($curVal) || count($curVal)<=0):
                        $bErrorField = __LINE__;
                    endif;
                elseif ($arOrderProps["TYPE"]=="FILE"):
                    if (is_array($curVal)):
                        foreach ($curVal as $index => $arFileData):
                            if (!array_key_exists("name", $arFileData) || strlen($arFileData["name"]) <= 0):
                                $bErrorField = __LINE__;
                            endif;
                        endforeach;
                    endif;
                endif;
            endif;

            if ($bErrorField):
                $this->arResult["ERROR"][$bErrorField] = GetMessage("SOA_ERROR_REQUIRE").' "'.$arOrderProps["NAME"].'"';
            endif;
        endwhile;

        return $this;
    }

    /**
     * @param array $options
     */
    public function GetPersonaTypes($options=array())
    {
        $this->arParams['PERSON_TYPE']['ORDER']     = array(
            "SORT" => "ASC",
            "NAME" => "ASC",
        );
        $this->arParams['PERSON_TYPE']['FILTER']    = array(
            "LID" => SITE_ID,
            "ACTIVE" => "Y",
        );

        $dbPersonType = \CSalePersonType::GetList(
            $this->arParams['PERSON_TYPE']['ORDER'],
            $this->arParams['PERSON_TYPE']['FILTER']
        );

        while($arPersonType = $dbPersonType->GetNext()):
            if($this->arResult['USER']["PERSON_TYPE_ID"] == $arPersonType["ID"] || IntVal($this->arResult['USER']["PERSON_TYPE_ID"]) <= 0):

                $this->arResult['USER']["PERSON_TYPE_ID"] = $arPersonType["ID"];
                $arPersonType["CHECKED"] = "Y";
            endif;

            $this->arResult["PERSON_TYPE"][$arPersonType["ID"]] = $arPersonType;
        endwhile;

        foreach(GetModuleEvents("sale", "OnSaleComponentOrderOneStepPersonType", true) as $arEvent)
            ExecuteModuleEventEx($arEvent, array(&$this->arResult, &$this->arResult['USER'], &$this->arParams));
    }

    /**
     * @param array $options
     */
    public function GetUserProfiles($options=array())
    {
        $this->arParams['ORDER_USER_PROPS']['ORDER']     = array(
            "DATE_UPDATE" => "ASC",
        );
        $this->arParams['ORDER_USER_PROPS']['FILTER']    = array(
            //"PERSON_TYPE_ID"    => $this->arResult['USER']["PERSON_TYPE_ID"],
            "USER_ID"           => IntVal($this->user->GetID())
        );

        $bFirst = false;

        $dbUserProfiles = \CSaleOrderUserProps::GetList(
            $this->arParams['ORDER_USER_PROPS']['ORDER'],
            $this->arParams['ORDER_USER_PROPS']['FILTER']
        );

        while($arUserProfiles = $dbUserProfiles->GetNext()):
            if(!$bFirst && empty($this->arResult['USER']["PROFILE_CHANGE"])):
                $bFirst = true;

                $this->arResult['USER']["PROFILE_ID"]       = IntVal($arUserProfiles["ID"]);
                $this->arResult['USER']["PROFILE_CHANGE"]   = "Y";
                $this->arResult['USER']["PROFILE_DEFAULT"]  = "Y";
            endif;

            if (IntVal($this->arResult['USER']["PROFILE_ID"])==IntVal($arUserProfiles["ID"])):
                $arUserProfiles["CHECKED"] = "Y";
            endif;

            $this->arResult["ORDER_PROP"]["USER_PROFILES"][$arUserProfiles["ID"]] = $arUserProfiles;
        endwhile;

        if (!$this->arParams['IS_ORDER_PLACED']):
            $this->_getFormatedProperties($this->arResult['USER']["PERSON_TYPE_ID"], $this->arResult, $this->arResult['USER'], $this->arParams);
        endif;
    }

    /**
     * @param array $options
     */
    public function GetDelivery($options=array())
    {
        if ((int)$this->arResult['USER']["DELIVERY_LOCATION"] <= 0):
            //return $this;
        endif;

        $locFrom = \COption::GetOptionString('sale', 'location', false, SITE_ID);

        $this->arParams['DELIVER_HANDLER']['ORDER']     = array(
            "SORT" => "ASC",
        );
        $this->arParams['DELIVER_HANDLER']['FILTER']    = array(
            "COMPABILITY" => array(
                "WEIGHT"            => $this->arResult["ORDER_WEIGHT"],
                "PRICE"             => $this->arResult["ORDER_PRICE"],
                "LOCATION_FROM"     => $locFrom,
                "LOCATION_TO"       => $this->arResult['USER']["DELIVERY_LOCATION"],
                "LOCATION_ZIP"      => $this->arResult['USER']["DELIVERY_LOCATION_ZIP"],
                "MAX_DIMENSIONS"    => $this->arResult["MAX_DIMENSIONS"],
                "ITEMS"             => $this->arResult["BASKET_ITEMS"],
            ),
        );

        $bFirst = true;
        $arDeliveryServiceAll = array();
        $bFound = false;

        $rsDeliveryServicesList = \CSaleDeliveryHandler::GetList(
            $this->arParams['DELIVER_HANDLER']['ORDER'],
            $this->arParams['DELIVER_HANDLER']['FILTER']
        );

        while ($arDeliveryService = $rsDeliveryServicesList->Fetch()):
            if (!is_array($arDeliveryService) || !is_array($arDeliveryService["PROFILES"])):
                continue;
            endif;

            if(
                !empty($this->arResult['USER']["DELIVERY_ID"]) &&
                strpos($this->arResult['USER']["DELIVERY_ID"], ":") !== false
            ):
                foreach ($arDeliveryService["PROFILES"] as $profile_id => $arDeliveryProfile):
                    if($arDeliveryProfile["ACTIVE"] == "Y"):
                        $delivery_id = $arDeliveryService["SID"];

                        if($this->arResult['USER']["DELIVERY_ID"] == $delivery_id.":".$profile_id):
                            $bFound = true;
                        endif;
                    endif;
                endforeach;
            endif;

            $arDeliveryServiceAll[] = $arDeliveryService;
        endwhile;

        if(
            !$bFound &&
            !empty($this->arResult['USER']["DELIVERY_ID"]) &&
            strpos($this->arResult['USER']["DELIVERY_ID"], ":") !== false
        ):
            $this->arResult['USER']["DELIVERY_ID"]      = "";
            $this->arResult["DELIVERY_PRICE"]           = 0;
            $this->arResult["DELIVERY_PRICE_FORMATED"]  = "";
        endif;

        $this->arResult['USER']["PAY_SYSTEM_ID"]    = IntVal($this->arResult['USER']["PAY_SYSTEM_ID"]);
        $this->arResult['USER']["DELIVERY_ID"]      = trim($this->arResult['USER']["DELIVERY_ID"]);

        $bShowDefaultSelected   = True;
        $arD2P                  = array();
        $arP2D                  = array();
        $delivery               = "";
        $bSelected              = false;

        $dbRes = \CSaleDelivery::GetDelivery2PaySystem(array());

        while ($arRes = $dbRes->Fetch()):
            $arD2P[$arRes["DELIVERY_ID"]][$arRes["PAYSYSTEM_ID"]] = $arRes["PAYSYSTEM_ID"];
            $arP2D[$arRes["PAYSYSTEM_ID"]][$arRes["DELIVERY_ID"]] = $arRes["DELIVERY_ID"];

            $bShowDefaultSelected = False;
        endwhile;

        if ($this->arParams["DELIVERY_TO_PAYSYSTEM"] == "d2p"):
            $arP2D = array();
        endif;

        if ($this->arParams["DELIVERY_TO_PAYSYSTEM"] == "p2d"):
            if(IntVal($this->arResult['USER']["PAY_SYSTEM_ID"]) <= 0):
                $bFirst     = True;

                $this->arParams['PAY_SYSTEM']['ORDER']     = array(
                    "SORT"      => "ASC",
                    "PSA_NAME"  => "ASC",
                );
                $this->arParams['PAY_SYSTEM']['FILTER']    = array(
                    "ACTIVE"            => "Y",
                    "PERSON_TYPE_ID"    => $this->arResult['USER']["PERSON_TYPE_ID"],
                    "PSA_HAVE_PAYMENT"  => "Y",
                );

                $dbPaySystem = \CSalePaySystem::GetList(
                    $this->arParams['PAY_SYSTEM']['ORDER'],
                    $this->arParams['PAY_SYSTEM']['FILTER']
                );

                while ($arPaySystem = $dbPaySystem->Fetch()):
                    if (IntVal($this->arResult['USER']["PAY_SYSTEM_ID"]) <= 0 && $bFirst):
                        $arPaySystem["CHECKED"] = "Y";

                        $this->arResult['USER']["PAY_SYSTEM_ID"] = $arPaySystem["ID"];
                    endif;

                    $bFirst = false;
                endwhile;
            endif;
        endif;

        $bFirst = True;
        $bFound = false;

        $_SESSION["SALE_DELIVERY_EXTRA_PARAMS"] = array();

        foreach($arDeliveryServiceAll as $arDeliveryService):
            foreach ($arDeliveryService["PROFILES"] as $profile_id => $arDeliveryProfile):
                if (
                    $arDeliveryProfile["ACTIVE"] == "Y"
                        &&
                    (
                        count($arP2D[$this->arResult['USER']["PAY_SYSTEM_ID"]]) <= 0
                            ||
                        in_array($arDeliveryService["SID"], $arP2D[$this->arResult['USER']["PAY_SYSTEM_ID"]])
                            ||
                        empty($arD2P[$arDeliveryService["SID"]])
                    )
                ):
                    $delivery_id = $arDeliveryService["SID"];

                    $arProfile = array(
                        "SID"           => $profile_id,
                        "TITLE"         => $arDeliveryProfile["TITLE"],
                        "DESCRIPTION"   => $arDeliveryProfile["DESCRIPTION"],
                        "FIELD_NAME"    => "DELIVERY_ID",
                    );


                    if(
                        (
                            strlen($this->arResult['USER']["DELIVERY_ID"]) > 0
                                &&
                            $this->arResult['USER']["DELIVERY_ID"] == $delivery_id.":".$profile_id
                        )
                    ):
                        $arProfile["CHECKED"] = "Y";

                        $this->arResult['USER']["DELIVERY_ID"] = $delivery_id.":".$profile_id;

                        $bSelected = true;

                        $arOrderTmpDel = array(
                            "PRICE"             => $this->arResult["ORDER_PRICE"],
                            "WEIGHT"            => $this->arResult["ORDER_WEIGHT"],
                            "DIMENSIONS"        => $this->arResult["ORDER_DIMENSIONS"],
                            "LOCATION_FROM"     => \COption::GetOptionString('sale', 'location'),
                            "LOCATION_TO"       => $this->arResult['USER']["DELIVERY_LOCATION"],
                            "LOCATION_ZIP"      => $this->arResult['USER']["DELIVERY_LOCATION_ZIP"],
                            "ITEMS"             => $this->arResult["BASKET_ITEMS"],
                            "EXTRA_PARAMS"      => $this->arResult["DELIVERY_EXTRA"]
                        );

                        $arDeliveryPrice = \CSaleDeliveryHandler::CalculateFull($delivery_id, $profile_id, $arOrderTmpDel, $this->arResult["BASE_LANG_CURRENCY"]);

                        if ($arDeliveryPrice["RESULT"] == "ERROR"):
                            $this->arResult["ERROR"][] = $arDeliveryPrice["TEXT"];
                        else:
                            $this->arResult["DELIVERY_PRICE"]   = roundEx($arDeliveryPrice["VALUE"], SALE_VALUE_PRECISION);
                            $this->arResult["PACKS_COUNT"]      = $arDeliveryPrice["PACKS_COUNT"];
                        endif;
                    endif;

                    if (empty($this->arResult["DELIVERY"][$delivery_id])):
                        $this->arResult["DELIVERY"][$delivery_id] = array(
                            "SID"           => $delivery_id,
                            "SORT"          => $arDeliveryService["SORT"],
                            "TITLE"         => $arDeliveryService["NAME"],
                            "DESCRIPTION"   => $arDeliveryService["DESCRIPTION"],
                            "PROFILES"      => array(),
                        );
                    endif;

                    $arDeliveryExtraParams = \CSaleDeliveryHandler::GetHandlerExtraParams($delivery_id, $profile_id, $arOrderTmpDel, SITE_ID);

                    if(!empty($arDeliveryExtraParams)):
                        $_SESSION["SALE_DELIVERY_EXTRA_PARAMS"][$delivery_id.":".$profile_id] = $arDeliveryExtraParams;

                        $this->arResult["DELIVERY"][$delivery_id]["ISNEEDEXTRAINFO"] = "Y";
                    else:
                        $this->arResult["DELIVERY"][$delivery_id]["ISNEEDEXTRAINFO"] = "N";
                    endif;

                    if(
                        !empty($this->arResult['USER']["DELIVERY_ID"])
                            &&
                        strpos($this->arResult['USER']["DELIVERY_ID"], ":") !== false
                    ):
                        if($this->arResult['USER']["DELIVERY_ID"] == $delivery_id.":".$profile_id):
                            $bFound = true;
                        endif;
                    endif;

                    $this->arResult["DELIVERY"][$delivery_id]["LOGOTIP"] = $arDeliveryService["LOGOTIP"];

                    $this->arResult["DELIVERY"][$delivery_id]["PROFILES"][$profile_id] = $arProfile;

                    $bFirst = false;
                endif;
            endforeach;
        endforeach;

        if(
            !$bFound
                &&
            !empty($this->arResult['USER']["DELIVERY_ID"])
                &&
            strpos($this->arResult['USER']["DELIVERY_ID"], ":") !== false
        ):
            $this->arResult['USER']["DELIVERY_ID"] = "";
        endif;

        /*Old Delivery*/
        $arStoreId      = array();
        $arDeliveryAll  = array();
        $bFound         = false;
        $bFirst         = true;

        $this->arParams['DELIVERY']['ORDER']    = array(
            "SORT"  => "ASC",
            "NAME"  => "ASC",
        );
        $this->arParams['DELIVERY']['FILTER'] = array(
            "LID"                   => SITE_ID,
            "+<=WEIGHT_FROM"        => $this->arResult["ORDER_WEIGHT"],
            "+>=WEIGHT_TO"          => $this->arResult["ORDER_WEIGHT"],
            "+<=ORDER_PRICE_FROM"   => $this->arResult["ORDER_PRICE"],
            "+>=ORDER_PRICE_TO"     => $this->arResult["ORDER_PRICE"],
            "ACTIVE"                => "Y",
            "LOCATION"              => $this->arResult['USER']["DELIVERY_LOCATION"],
        );

        $dbDelivery = \CSaleDelivery::GetList(
            $this->arParams['DELIVERY']['ORDER'],
            $this->arParams['DELIVERY']['FILTER']
        );

        while ($arDelivery = $dbDelivery->Fetch()):
            $arStore = array();

            if (strlen($arDelivery["STORE"]) > 0):
                $arStore = unserialize($arDelivery["STORE"]);
                foreach ($arStore as $val)
                    $arStoreId[$val] = $val;
            endif;
            ksort($arStore);
            $arDelivery["STORE"] = $arStore;

            if (isset($_POST["BUYER_STORE"]) && in_array($_POST["BUYER_STORE"], $arStore)):
                $this->arResult['USER']['DELIVERY_STORE'] = $arDelivery["ID"];
            endif;

            $arDeliveryDescription = \CSaleDelivery::GetByID($arDelivery["ID"]);

            $arDelivery["DESCRIPTION"] = htmlspecialcharsbx($arDeliveryDescription["DESCRIPTION"]);

            $arDeliveryAll[] = $arDelivery;

            if(
                !empty($this->arResult['USER']["DELIVERY_ID"])
                    &&
                strpos($this->arResult['USER']["DELIVERY_ID"], ":") === false
            ):
                if(IntVal($this->arResult['USER']["DELIVERY_ID"]) == IntVal($arDelivery["ID"])):
                    $bFound = true;
                endif;
            endif;

            if(IntVal($this->arResult['USER']["DELIVERY_ID"]) == IntVal($arDelivery["ID"])):
                $this->arResult["DELIVERY_PRICE"] = roundEx(\CCurrencyRates::ConvertCurrency($arDelivery["PRICE"], $arDelivery["CURRENCY"], $this->arResult["BASE_LANG_CURRENCY"]), SALE_VALUE_PRECISION);
            endif;
        endwhile;

        if(!$bFound && !empty($this->arResult['USER']["DELIVERY_ID"]) && strpos($this->arResult['USER']["DELIVERY_ID"], ":") === false)
        {
            $this->arResult['USER']["DELIVERY_ID"] = "";
        }

        $arStore = array();
        $this->arParams['STORE']['ORDER']   = array(
            "TITLE"  => "ASC",
        );
        $this->arParams['STORE']['FILTER']  = array(
            "ACTIVE"            => "Y",
            "ID"                => $arStoreId,
            "ISSUING_CENTER"    => "Y",
            "+SITE_ID"          => SITE_ID,
        );
        $this->arParams['STORE']['GROUP']   = false;
        $this->arParams['STORE']['LIMIT']   = false;
        $this->arParams['STORE']['SELECT']  = array(
            "ID",
            "TITLE",
            "ADDRESS",
            "DESCRIPTION",
            "IMAGE_ID",
            "PHONE",
            "SCHEDULE",
            "GPS_N",
            "GPS_S",
            "ISSUING_CENTER",
            "SITE_ID",
            "XML_ID",
        );

        $dbList = \CCatalogStore::GetList(
            $this->arParams['STORE']['ORDER'],
            $this->arParams['STORE']['FILTER'],
            $this->arParams['STORE']['GROUP'],
            $this->arParams['STORE']['LIMIT'],
            $this->arParams['STORE']['SELECT']
        );

        while ($arStoreTmp = $dbList->Fetch()):
            if ($arStoreTmp["IMAGE_ID"] > 0):
                $arStoreTmp["IMAGE_ID"] = \CFile::GetFileArray($arStoreTmp["IMAGE_ID"]);
            endif;

            $arStore[$arStoreTmp["ID"]] = $arStoreTmp;
            $this->arResult["~STORE_LIST"][$arStoreTmp['DESCRIPTION']][$arStoreTmp["ID"]] = $arStoreTmp;
        endwhile;

        $this->arResult["STORE_LIST"] = $arStore;

        if(
            !$bFound
                &&
            !empty($this->arResult['USER']["DELIVERY_ID"])
                &&
            strpos($this->arResult['USER']["DELIVERY_ID"], ":") === false
        ):
            $this->arResult['USER']["DELIVERY_ID"] = "";
        endif;

        foreach($arDeliveryAll as $arDelivery):
            if (
                count($arP2D[$this->arResult['USER']["PAY_SYSTEM_ID"]]) <= 0
                    ||
                in_array($arDelivery["ID"], $arP2D[$this->arResult['USER']["PAY_SYSTEM_ID"]])
            ):
                $arDelivery["FIELD_NAME"] = "DELIVERY_ID";

                if ((IntVal($this->arResult['USER']["DELIVERY_ID"]) == IntVal($arDelivery["ID"]))):
                    $arDelivery["CHECKED"] = "Y";

                    $this->arResult['USER']["DELIVERY_ID"]  = $arDelivery["ID"];
                    $this->arResult["DELIVERY_PRICE"]       = roundEx(CCurrencyRates::ConvertCurrency($arDelivery["PRICE"], $arDelivery["CURRENCY"], $this->arResult["BASE_LANG_CURRENCY"]), SALE_VALUE_PRECISION);

                    $bSelected = true;
                endif;

                if (IntVal($arDelivery["PERIOD_FROM"]) > 0 || IntVal($arDelivery["PERIOD_TO"]) > 0):
                    $arDelivery["PERIOD_TEXT"] = GetMessage("SALE_DELIV_PERIOD");

                    if (IntVal($arDelivery["PERIOD_FROM"]) > 0):
                        $arDelivery["PERIOD_TEXT"] .= " ".GetMessage("SOA_FROM")." ".IntVal($arDelivery["PERIOD_FROM"]);
                    endif;

                    if (IntVal($arDelivery["PERIOD_TO"]) > 0):
                        $arDelivery["PERIOD_TEXT"] .= " ".GetMessage("SOA_TO")." ".IntVal($arDelivery["PERIOD_TO"]);
                    endif;

                    if ($arDelivery["PERIOD_TYPE"] == "H"):
                        $arDelivery["PERIOD_TEXT"] .= " ".GetMessage("SOA_HOUR")." ";
                    elseif ($arDelivery["PERIOD_TYPE"]=="M"):
                        $arDelivery["PERIOD_TEXT"] .= " ".GetMessage("SOA_MONTH")." ";
                    else:
                        $arDelivery["PERIOD_TEXT"] .= " ".GetMessage("SOA_DAY")." ";
                    endif;
                endif;

                if (intval($arDelivery["LOGOTIP"]) > 0):
                    $arDelivery["LOGOTIP"] = \CFile::GetFileArray($arDelivery["LOGOTIP"]);
                endif;

                $arDelivery["PRICE_FORMATED"] = SaleFormatCurrency($arDelivery["PRICE"], $arDelivery["CURRENCY"]);

                $this->arResult["DELIVERY"][$arDelivery["ID"]] = $arDelivery;

                $bFirst = false;
            endif;
        endforeach;

        $this->arParams['DELIVERY']['UASORT_FUNCTION'] = array('CSaleBasketHelper', 'cmpBySort');

        uasort($this->arResult["DELIVERY"], $this->arParams['DELIVERY']['UASORT_FUNCTION']);

        if(!$bSelected && !empty($this->arResult["DELIVERY"])):
            $bf = true;

            foreach($this->arResult["DELIVERY"] as $k => $v):
                if($bf):
                    if(IntVal($k) > 0):
                        $this->arResult["DELIVERY"][$k]["CHECKED"] = "Y";
                        $this->arResult['USER']["DELIVERY_ID"] = $k;
                        $bf = false;

                        $this->arResult["DELIVERY_PRICE"] = roundEx(\CCurrencyRates::ConvertCurrency($this->arResult["DELIVERY"][$k]["PRICE"], $this->arResult["DELIVERY"][$k]["CURRENCY"], $this->arResult["BASE_LANG_CURRENCY"]), SALE_VALUE_PRECISION);
                    else:
                        foreach($v["PROFILES"] as $kk => $vv):
                            if($bf):
                                $this->arResult["DELIVERY"][$k]["PROFILES"][$kk]["CHECKED"] = "Y";

                                $this->arResult['USER']["DELIVERY_ID"] = $k.":".$kk;

                                $bf = false;

                                $arOrderTmpDel = array(
                                    "PRICE"         => $this->arResult["ORDER_PRICE"],
                                    "WEIGHT"        => $this->arResult["ORDER_WEIGHT"],
                                    "DIMENSIONS"    => $this->arResult["ORDER_DIMENSIONS"],
                                    "LOCATION_FROM" => \COption::GetOptionString('sale', 'location'),
                                    "LOCATION_TO"   => $this->arResult['USER']["DELIVERY_LOCATION"],
                                    "LOCATION_ZIP"  => $this->arResult['USER']["DELIVERY_LOCATION_ZIP"],
                                    "ITEMS"         => $this->arResult["BASKET_ITEMS"],
                                    "EXTRA_PARAMS"  => $this->arResult["DELIVERY_EXTRA"]
                                );

                                $arDeliveryPrice = \CSaleDeliveryHandler::CalculateFull($k, $kk, $arOrderTmpDel, $this->arResult["BASE_LANG_CURRENCY"]);

                                if ($arDeliveryPrice["RESULT"] == "ERROR"):
                                    $this->arResult["ERROR"][] = $arDeliveryPrice["TEXT"];
                                else:
                                    $this->arResult["DELIVERY_PRICE"]   = roundEx($arDeliveryPrice["VALUE"], SALE_VALUE_PRECISION);
                                    $this->arResult["PACKS_COUNT"]      = $arDeliveryPrice["PACKS_COUNT"];
                                endif;

                                break;
                            endif;
                        endforeach;
                    endif;
                endif;
            endforeach;
        endif;

        if ($this->arResult['USER']["PAY_SYSTEM_ID"] > 0 || strlen($this->arResult['USER']["DELIVERY_ID"]) > 0):
            if (strlen($this->arResult['USER']["DELIVERY_ID"]) > 0 && $this->arParams["DELIVERY_TO_PAYSYSTEM"] == "d2p"):
                if (strpos($this->arResult['USER']["DELIVERY_ID"], ":")):
                    $tmp        = explode(":", $this->arResult['USER']["DELIVERY_ID"]);
                    $delivery   = trim($tmp[0]);
                else:
                    $delivery   = intval($this->arResult['USER']["DELIVERY_ID"]);
                endif;
            endif;
        endif;

        if(DoubleVal($this->arResult["DELIVERY_PRICE"]) > 0):
            $this->arResult["DELIVERY_PRICE_FORMATED"] = SaleFormatCurrency($this->arResult["DELIVERY_PRICE"], $this->arResult["BASE_LANG_CURRENCY"]);
        endif;

        foreach(GetModuleEvents("sale", "OnSaleComponentOrderOneStepDelivery", true) as $arEvent):
            ExecuteModuleEventEx($arEvent, array(&$this->arResult, &$this->arResult['USER'], &$this->arParams));
        endforeach;
    }

    /**
     * @param array $options
     */
    public function GetPaySystem($options=array())
    {
        $this->arParams['PAY_SYSTEM']['ORDER']  = array(
            "SORT"      => "ASC",
            "PSA_NAME"  => "ASC",
        );
        $this->arParams['PAY_SYSTEM']['FILTER'] = array(
            "ACTIVE"            => "Y",
            "PERSON_TYPE_ID"    => $this->arResult['USER']["PERSON_TYPE_ID"],
            "PSA_HAVE_PAYMENT"  => "Y"
        );

        if(!empty($this->arParams["DELIVERY2PAY_SYSTEM"])):
            foreach($this->arParams["DELIVERY2PAY_SYSTEM"] as $val):
                if(is_array($val[$this->arResult['USER']["DELIVERY_ID"]])):
                    foreach($val[$this->arResult['USER']["DELIVERY_ID"]] as $v):
                        $this->arParams['PAY_SYSTEM']['FILTER']["ID"][] = $v;
                    endforeach;
                elseif(IntVal($val[$this->arResult['USER']["DELIVERY_ID"]]) > 0):
                    $this->arParams['PAY_SYSTEM']['FILTER']["ID"][] = $val[$this->arResult['USER']["DELIVERY_ID"]];
                endif;
            endforeach;
        endif;

        if ($this->arParams["DELIVERY_TO_PAYSYSTEM"] == "p2d"):
            $arD2P = array();
        endif;

        if($this->arResult['USER']["PREPAYMENT_MODE"] && IntVal($this->arResult['USER']["PAY_SYSTEM_ID"]) > 0):
            $arFilter["ID"] = $this->arResult['USER']["PAY_SYSTEM_ID"];
        endif;

        $bFirst = true;

        $dbPaySystem = CSalePaySystem::GetList(
            $this->arParams['PAY_SYSTEM']['ORDER'],
            $this->arParams['PAY_SYSTEM']['FILTER']
        );

        while ($arPaySystem = $dbPaySystem->Fetch()):
            if(
                strlen($this->arResult['USER']["DELIVERY_ID"]) <= 0
                    ||
                $this->arParams["DELIVERY_TO_PAYSYSTEM"] == "p2d"
                    ||
                \CSaleDelivery2PaySystem::isPaySystemApplicable($arPaySystem["ID"], $this->arResult['USER']["DELIVERY_ID"])
            ):

                if(!\CSalePaySystemsHelper::checkPSCompability(
                    $arPaySystem["PSA_ACTION_FILE"],
                    $this->arResult['ORDER'],
                    $this->arResult["ORDER_PRICE"],
                    $this->arResult["DELIVERY_PRICE"],
                    $this->arResult['USER']["DELIVERY_LOCATION"]
                )):
                    continue;
                endif;

                if ($arPaySystem["PSA_LOGOTIP"] > 0):
                    $arPaySystem["PSA_LOGOTIP"] = \CFile::GetFileArray($arPaySystem["PSA_LOGOTIP"]);
                endif;

                $arPaySystem["PSA_NAME"] = htmlspecialcharsEx($arPaySystem["PSA_NAME"]);

                $this->arResult["PAY_SYSTEM"][$arPaySystem["ID"]] = $arPaySystem;

                $this->arResult["PAY_SYSTEM"][$arPaySystem["ID"]]["PRICE"] = \CSalePaySystemsHelper::getPSPrice(
                    $arPaySystem,
                    $this->arResult["ORDER_PRICE"],
                    $this->arResult["DELIVERY_PRICE"],
                    $this->arResult['USER']["DELIVERY_LOCATION"]);

                if (IntVal($this->arResult['USER']["PAY_SYSTEM_ID"]) == IntVal($arPaySystem["ID"]) || IntVal($this->arResult['USER']["PAY_SYSTEM_ID"]) <= 0 && $bFirst):
                    $this->arResult["PAY_SYSTEM"][$arPaySystem["ID"]]["CHECKED"] = "Y";

                    $this->arResult['USER']["PAY_SYSTEM_ID"] = $arPaySystem["ID"];
                endif;

                $bFirst = false;
            endif;
        endwhile;

        if(
            IntVal($this->arResult['USER']["PAY_SYSTEM_ID"]) > 0
                &&
            empty($this->arResult["PAY_SYSTEM"][$this->arResult['USER']["PAY_SYSTEM_ID"]])
        ):
            $bF = true;

            foreach($this->arResult["PAY_SYSTEM"] as $k => $v):
                if($bF):
                    $this->arResult["PAY_SYSTEM"][$k]["CHECKED"] = "Y";

                    $this->arResult['USER']["PAY_SYSTEM_ID"] = $this->arResult["PAY_SYSTEM"][$k]["ID"];

                    $bF = false;
                endif;
            endforeach;
        endif;

        $this->arResult["DELIVERY_PRICE_FORMATED"] = SaleFormatCurrency($this->arResult["DELIVERY_PRICE"], $this->arResult["BASE_LANG_CURRENCY"]);

        if(empty($this->arResult["PAY_SYSTEM"]) && $this->arResult['USER']["PAY_CURRENT_ACCOUNT"] != "Y"):
            $this->arResult["ERROR"][] = GetMessage("SOA_ERROR_PAY_SYSTEM");
        endif;

        foreach(GetModuleEvents("sale", "OnSaleComponentOrderOneStepPaySystem", true) as $arEvent):
            ExecuteModuleEventEx($arEvent, array(&$this->arResult, &$this->arResult['USER'], &$this->arParams));
        endforeach;
    }

    /**
     * @param array $options
     */
    public function SetRelatedOrderProperties($options=array())
    {
        if (count($this->arResult["ORDER_PROP"]["RELATED"]) == 0)
        {
            $this->arParams['ORDER_PROPS']['FILTER'] = array(
                "PERSON_TYPE_ID"    => $this->arResult['USER']["PERSON_TYPE_ID"],
                "ACTIVE"            => "Y",
                "UTIL"              => "N"
            );

            if (intval($this->arResult['USER']["PAY_SYSTEM_ID"]) != 0 && $this->arResult['USER']["PAY_CURRENT_ACCOUNT"] != "Y")
                $this->arParams['ORDER_PROPS']['FILTER']["RELATED"]["PAYSYSTEM_ID"] = $this->arResult['USER']["PAY_SYSTEM_ID"];

            if ($this->arResult['USER']["DELIVERY_ID"] != false):
                $this->arParams['ORDER_PROPS']['FILTER']["RELATED"]["DELIVERY_ID"] = $this->arResult['USER']["DELIVERY_ID"];
            endif;

            if (isset($this->arParams['ORDER_PROPS']['FILTER']["RELATED"]) && count($this->arParams['ORDER_PROPS']['FILTER']["RELATED"]) > 0)
            {
                $arRes = array();

                $this->arParams['ORDER_PROPS']['ORDER']     = array();
                $this->arParams['ORDER_PROPS']['GROUP']     = false;
                $this->arParams['ORDER_PROPS']['LIMIT']     = false;
                $this->arParams['ORDER_PROPS']['SELECT']    = array('*');

                $dbRelatedProps = CSaleOrderProps::GetList(
                    $this->arParams['ORDER_PROPS']['ORDER'],
                    $this->arParams['ORDER_PROPS']['FILTER'],
                    $this->arParams['ORDER_PROPS']['GROUP'],
                    $this->arParams['ORDER_PROPS']['LIMIT'],
                    $this->arParams['ORDER_PROPS']['SELECT']
                );

                while ($arRelatedProps = $dbRelatedProps->GetNext()):
                    $arRes[$arRelatedProps['ID']] = $this->_getOrderPropFormated(
                        array(
                            'arProperties'             => &$arRelatedProps,
                        )
                    );
                    $this->arResult["ORDER_PROP"]['USER_PROPS_'.$arRelatedProps['USER_PROPS']][$arRelatedProps['ID']] = $arRelatedProps;
                    if(!empty($arRes)):
                        $nsOrderRelation = \CSaleOrderProps::GetOrderPropsRelations(array('PROPERTY_ID'=>$arRelatedProps['ID']));

                        while($arOrderRelation = $nsOrderRelation->fetch()):
                            $this->arResult["ORDER_PROP"]["RELATIONS"][$arOrderRelation['PROPERTY_ID']][$arOrderRelation['ENTITY_ID']] = $arOrderRelation;

                        endwhile;
                    endif;
                endwhile;

                $this->arResult["ORDER_PROP"]["RELATED"] = $arRes;
            }
        }
    }

    /**
     * @param array $options
     */
    public function SetDiscounts($options=array())
    {
        DiscountCouponsManager::init();

        foreach(GetModuleEvents("sale", "OnSaleComponentOrderOneStepDiscountBefore", true) as $arEvent):
            ExecuteModuleEventEx($arEvent, array(&$this->arResult, &$this->arResult['USER'], &$this->arParams));
        endforeach;

        foreach ($this->arResult["BASKET_ITEMS"] as $id => $arItem):
            if(\CSaleBasketHelper::isSetItem($arItem)):
                unset($this->arResult["BASKET_ITEMS"][$id]);
            endif;
        endforeach;

        $arOrderDat = \CSaleOrder::DoCalculateOrder(
            SITE_ID,
            $this->user->GetID(),
            $this->arResult["BASKET_ITEMS"],
            $this->arResult['USER']['PERSON_TYPE_ID'],
            $this->arResult['USER']["ORDER_PROP"],
            $this->arResult['USER']["DELIVERY_ID"],
            $this->arResult['USER']["PAY_SYSTEM_ID"],
            array(),
            $this->arResult['ERRORS'],
            $this->arResult['WARNINGS']
        );

        $orderTotalSum = 0;

        if (empty($arOrderDat)):
            $this->arResult['ERROR'][] = GetMessage('SOA_ORDER_CALCULATE_ERROR');

            if (!empty($this->arResult["BASKET_ITEMS"])):
                foreach ($this->arResult["BASKET_ITEMS"] as $key => &$arItem):
                    $arItem["SUM"] = SaleFormatCurrency($arItem["PRICE"] * $arItem["QUANTITY"], $this->arResult["BASE_LANG_CURRENCY"]);

                    $arCols = array("PROPS" => getPropsInfo($arItem));

                    if (isset($arItem["PREVIEW_PICTURE"]) && intval($arItem["PREVIEW_PICTURE"]) > 0):
                        $arCols["PREVIEW_PICTURE"] = CSaleHelper::getFileInfo($arItem["PREVIEW_PICTURE"], array("WIDTH" => 110, "HEIGHT" => 110));
                    endif;

                    if (isset($arItem["DETAIL_PICTURE"]) && intval($arItem["DETAIL_PICTURE"]) > 0):
                        $arCols["DETAIL_PICTURE"] = CSaleHelper::getFileInfo($arItem["DETAIL_PICTURE"], array("WIDTH" => 110, "HEIGHT" => 110));
                    endif;

                    if (isset($arItem["MEASURE_TEXT"]) && strlen($arItem["MEASURE_TEXT"]) > 0):
                        $arCols["QUANTITY"] = $arItem["QUANTITY"]."&nbsp;".$arItem["MEASURE_TEXT"];
                    endif;

                    foreach ($arItem as $tmpKey => $value):
                        if ((strpos($tmpKey, "PROPERTY_", 0) === 0) && (strrpos($tmpKey, "_VALUE") == strlen($tmpKey) - 6)):
                            $code = str_replace(array("PROPERTY_", "_VALUE"), "", $tmpKey);
                            $propData = $this->arIblockProps[$code];
                            $arCols[$tmpKey] = getIblockProps($value, $propData, array("WIDTH" => 110, "HEIGHT" => 110));
                        endif;
                    endforeach;

                    $this->arResult["GRID"]["ROWS"][$arItem["ID"]] = array(
                        "id"        => $arItem["ID"],
                        "data"      => $arItem,
                        "actions"   => array(),
                        "columns"   => $arCols,
                        "editable"  => true
                    );
                endforeach;

                unset($arItem);

                $oldOrder = \CSaleOrder::CalculateOrderPrices($this->arResult["BASKET_ITEMS"]);

                if (!empty($oldOrder)):
                    $this->arResult['ORDER_PRICE'] = $oldOrder['ORDER_PRICE'];
                    $this->arResult["ORDER_PRICE_FORMATED"] = SaleFormatCurrency($this->arResult["ORDER_PRICE"], $this->arResult["BASE_LANG_CURRENCY"]);
                    $this->arResult["ORDER_WEIGHT"] = $oldOrder["ORDER_WEIGHT"];
                    $this->arResult['VAT_SUM'] = $oldOrder['VAT_SUM'];
                    $this->arResult["USE_VAT"] = ($oldOrder['USE_VAT'] == "Y");
                    $this->arResult["VAT_SUM_FORMATED"] = SaleFormatCurrency($this->arResult["VAT_SUM"], $this->arResult["BASE_LANG_CURRENCY"]);
                endif;

                unset($oldOrder);
            endif;
        else:
            $this->arResult["ORDER_PRICE"]              = $arOrderDat['ORDER_PRICE'];
            $this->arResult["ORDER_PRICE_FORMATED"]     = SaleFormatCurrency($this->arResult["ORDER_PRICE"], $this->arResult["BASE_LANG_CURRENCY"]);
            $this->arResult["USE_VAT"]                  = $arOrderDat['USE_VAT'];
            $this->arResult["VAT_SUM"]                  = $arOrderDat["VAT_SUM"];
            $this->arResult["VAT_SUM_FORMATED"]         = SaleFormatCurrency($this->arResult["VAT_SUM"], $this->arResult["BASE_LANG_CURRENCY"]);
            $this->arResult['TAX_PRICE']                = $arOrderDat["TAX_PRICE"];
            $this->arResult['TAX_LIST']                 = $arOrderDat["TAX_LIST"];
            $this->arResult['DISCOUNT_PRICE']           = $arOrderDat["DISCOUNT_PRICE"];
            $this->arResult['DELIVERY_PRICE']           = $arOrderDat['PRICE_DELIVERY'];
            $this->arResult['DELIVERY_PRICE_FORMATED']  = SaleFormatCurrency($arOrderDat["DELIVERY_PRICE"], $this->arResult["BASE_LANG_CURRENCY"]);
            $this->arResult['BASKET_ITEMS']             = $arOrderDat['BASKET_ITEMS'];

            if (!empty($this->arResult["BASKET_ITEMS"])):
                foreach ($this->arResult["BASKET_ITEMS"] as $key => &$arItem):
                    $arItem["SUM"] = SaleFormatCurrency($arItem["PRICE"] * $arItem["QUANTITY"], $this->arResult["BASE_LANG_CURRENCY"]);

                    $arCols = array("PROPS" => $this->GetPropsInfo($arItem));

                    if (isset($arItem["PREVIEW_PICTURE"]) && intval($arItem["PREVIEW_PICTURE"]) > 0):
                        $arCols["PREVIEW_PICTURE"] = \CSaleHelper::getFileInfo($arItem["PREVIEW_PICTURE"], array("WIDTH" => 110, "HEIGHT" => 110));
                    endif;

                    if (isset($arItem["DETAIL_PICTURE"]) && intval($arItem["DETAIL_PICTURE"]) > 0):
                        $arCols["DETAIL_PICTURE"] = \CSaleHelper::getFileInfo($arItem["DETAIL_PICTURE"], array("WIDTH" => 110, "HEIGHT" => 110));
                    endif;

                    if (isset($arItem["MEASURE_TEXT"]) && strlen($arItem["MEASURE_TEXT"]) > 0):
                        $arCols["QUANTITY"] = $arItem["QUANTITY"] . "&nbsp;" . $arItem["MEASURE_TEXT"];
                    endif;

                    foreach ($arItem as $tmpKey => $value) {
                        if ((strpos($tmpKey, "PROPERTY_", 0) === 0) && (strrpos($tmpKey, "_VALUE") == strlen($tmpKey) - 6)):
                            $code = str_replace(array("PROPERTY_", "_VALUE"), "", $tmpKey);
                            $propData = $this->arIblockProps[$code];
                            $arCols[$tmpKey] = getIblockProps($value, $propData, array("WIDTH" => 110, "HEIGHT" => 110));
                        endif;
                    }

                    $this->arResult["GRID"]["ROWS"][$arItem["ID"]] = array(
                        "id" => $arItem["ID"],
                        "data" => $arItem,
                        "actions" => array(),
                        "columns" => $arCols,
                        "editable" => true
                    );

                    BasketBase::checkSpecialItemInBasket($arItem);
                endforeach;

                unset($arItem);
            endif;

            $this->SetTax($options);
        endif;

        foreach(GetModuleEvents("sale", "OnSaleComponentOrderOneStepProcess", true) as $arEvent):
            ExecuteModuleEventEx($arEvent, Array(&$this->arResult, &$this->arResult['USER'], &$this->arParams));
        endforeach;

        $this->arResult['ORDER']['TOTAL'] = $this->arResult["ORDER_PRICE"] + $this->arResult["DELIVERY_PRICE"] + $this->arResult["TAX_PRICE"] - $this->arResult["DISCOUNT_PRICE"];
    }

    /**
     * @param array $options
     */
    protected function SetTax($options=array())
    {
        /* Tax Begin */

        $this->arResult['ORDER']['TOTAL'] = $this->arResult["ORDER_PRICE"] + $this->arResult["DELIVERY_PRICE"] + $this->arResult["TAX_PRICE"] - $this->arResult["DISCOUNT_PRICE"];

        if($this->arParams["PAY_FROM_ACCOUNT"] == "Y"):
            $dbUserAccount = CSaleUserAccount::GetList(
                array(),
                array(
                    "USER_ID"   => $this->user->GetID(),
                    "CURRENCY"  => $this->arResult["BASE_LANG_CURRENCY"],
                )
            );

            if ($arUserAccount = $dbUserAccount->GetNext()):
                if ($arUserAccount["CURRENT_BUDGET"] <= 0):
                    $this->arResult["PAY_FROM_ACCOUNT"] = "N";
                else:
                    if($this->arParams["ONLY_FULL_PAY_FROM_ACCOUNT"] == "Y"):
                        if(DoubleVal($arUserAccount["CURRENT_BUDGET"]) >= DoubleVal($this->arResult['ORDER']['TOTAL'])):
                            $this->arResult["PAY_FROM_ACCOUNT"]         = "Y";
                            $this->arResult["CURRENT_BUDGET_FORMATED"]  = SaleFormatCurrency($arUserAccount["CURRENT_BUDGET"], $this->arResult["BASE_LANG_CURRENCY"]);
                            $this->arResult["USER_ACCOUNT"]             = $arUserAccount;
                        else:
                            $this->arResult["PAY_FROM_ACCOUNT"] = "N";
                        endif;
                    else:
                        $this->arResult["PAY_FROM_ACCOUNT"]         = "Y";
                        $this->arResult["CURRENT_BUDGET_FORMATED"]  = SaleFormatCurrency($arUserAccount["CURRENT_BUDGET"], $this->arResult["BASE_LANG_CURRENCY"]);
                        $this->arResult["USER_ACCOUNT"]             = $arUserAccount;
                    endif;
                endif;
            else:
                $this->arResult["PAY_FROM_ACCOUNT"] = "N";
            endif;
        endif;

        if($this->arResult['USER']["PAY_CURRENT_ACCOUNT"] == "Y"):
            if ($this->arResult["USER_ACCOUNT"]["CURRENT_BUDGET"] > 0):
                $this->arResult["PAYED_FROM_ACCOUNT_FORMATED"] = SaleFormatCurrency((($this->arResult["USER_ACCOUNT"]["CURRENT_BUDGET"] >= $this->arResult['ORDER']['TOTAL']) ? $this->arResult['ORDER']['TOTAL'] : $this->arResult["USER_ACCOUNT"]["CURRENT_BUDGET"]), $this->arResult["BASE_LANG_CURRENCY"]);
            endif;
        endif;

        $this->arResult["ORDER_TOTAL_PRICE_FORMATED"] = SaleFormatCurrency($this->arResult['ORDER']['TOTAL'], $this->arResult["BASE_LANG_CURRENCY"]);
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function GetConfirmOrder($options=array())
    {
        if($this->arResult['USER']["CONFIRM_ORDER"] != "Y" || !empty($this->arResult['USER']["ERROR"])):
            $this->arResult['USER']["CONFIRM_ORDER"] = "N";

            return $this;
        endif;

        if(!$this->user->IsAuthorized() && $this->arParams["ALLOW_AUTO_REGISTER"] == "Y"):
            if(strlen($this->arResult['USER']["USER_EMAIL"]) > 0):
                $NEW_LOGIN = $this->arResult['USER']["USER_EMAIL"];
                $NEW_EMAIL = $this->arResult['USER']["USER_EMAIL"];
                $NEW_NAME = "";
                $NEW_LAST_NAME = "";

                if(strlen($this->arResult['USER']["PAYER_NAME"]) > 0):
                    $arNames = explode(" ", $this->arResult['USER']["PAYER_NAME"]);
                    $NEW_NAME = $arNames[1];
                    $NEW_LAST_NAME = $arNames[0];
                endif;

                $pos = strpos($NEW_LOGIN, "@");

                if ($pos !== false):
                    $NEW_LOGIN = substr($NEW_LOGIN, 0, $pos);
                endif;

                if (strlen($NEW_LOGIN) > 47):
                    $NEW_LOGIN = substr($NEW_LOGIN, 0, 47);
                endif;

                if (strlen($NEW_LOGIN) < 3):
                    $NEW_LOGIN .= "_";
                endif;

                if (strlen($NEW_LOGIN) < 3):
                    $NEW_LOGIN .= "_";
                endif;

                $dbUserLogin = CUser::GetByLogin($NEW_LOGIN);

                if ($arUserLogin = $dbUserLogin->Fetch()):
                    $newLoginTmp = $NEW_LOGIN;
                    $uind = 0;
                    do
                    {
                        $uind++;

                        if ($uind == 10):
                            $NEW_LOGIN = $this->arResult['USER']["USER_EMAIL"];
                            $newLoginTmp = $NEW_LOGIN;
                        elseif ($uind > 10):
                            $NEW_LOGIN = "buyer".time().GetRandomCode(2);
                            $newLoginTmp = $NEW_LOGIN;
                            break;
                        else:
                            $newLoginTmp = $NEW_LOGIN.$uind;
                        endif;

                        $dbUserLogin = CUser::GetByLogin($newLoginTmp);
                    }
                    while ($arUserLogin = $dbUserLogin->Fetch());

                    $NEW_LOGIN = $newLoginTmp;
                endif;

                $GROUP_ID = array(2);
                $def_group = COption::GetOptionString("main", "new_user_registration_def_group", "");

                if($def_group!=""):
                    $GROUP_ID = explode(",", $def_group);
                    $arPolicy = $this->user->GetGroupPolicy($GROUP_ID);
                else:
                    $arPolicy = $this->user->GetGroupPolicy(array());
                endif;

                $password_min_length = intval($arPolicy["PASSWORD_LENGTH"]);

                if($password_min_length <= 0):
                    $password_min_length = 6;
                endif;

                $password_chars = array(
                    "abcdefghijklnmopqrstuvwxyz",
                    "ABCDEFGHIJKLNMOPQRSTUVWXYZ",
                    "0123456789",
                );

                if($arPolicy["PASSWORD_PUNCTUATION"] === "Y"):
                    $password_chars[] = ",.<>/?;:'\"[]{}\|`~!@#\$%^&*()-_+=";
                endif;

                $NEW_PASSWORD = $NEW_PASSWORD_CONFIRM = randString($password_min_length+2, $password_chars);

                $user = new CUser;

                $arAuthResult = $user->Add(Array(
                        "LOGIN"             => $NEW_LOGIN,
                        "NAME"              => $NEW_NAME,
                        "LAST_NAME"         => $NEW_LAST_NAME,
                        "PASSWORD"          => $NEW_PASSWORD,
                        "CONFIRM_PASSWORD"  => $NEW_PASSWORD_CONFIRM,
                        "EMAIL"             => $NEW_EMAIL,
                        "GROUP_ID"          => $GROUP_ID,
                        "ACTIVE"            => "Y",
                        "LID"               => SITE_ID,
                    )
                );

                if (IntVal($arAuthResult) <= 0):
                    $this->arResult["ERROR"][__LINE__] = GetMessage("STOF_ERROR_REG").((strlen($user->LAST_ERROR) > 0) ? ": ".$user->LAST_ERROR : "" );
                else:
                    $this->user->Authorize($arAuthResult);

                    if ($this->user->IsAuthorized()):
                        if($this->arParams["SEND_NEW_USER_NOTIFY"] == "Y"):
                            CUser::SendUserInfo($this->user->GetID(), SITE_ID, GetMessage("INFO_REQ"), true);
                        endif;
                    else:
                        $this->arResult["ERROR"][__LINE__] = GetMessage("STOF_ERROR_REG_CONFIRM");
                    endif;
                endif;
            else:
                $this->arResult["ERROR"][__LINE__] = GetMessage("STOF_ERROR_EMAIL");
            endif;
        endif;

        if ($this->arResult['USER']["PAY_SYSTEM_ID"] <= 0 && $this->arResult['USER']["PAY_CURRENT_ACCOUNT"] != "Y"):
            $this->arResult["ERROR"][] = GetMessage("STOF_ERROR_PAY_SYSTEM");
        endif;

        if(!$this->user->IsAuthorized() && empty($this->arResult["ERROR"])):
            $this->arResult['USER']["CONFIRM_ORDER"] = "N";

            return $this;
        endif;

        $arFields = array(
            "LID"                   => SITE_ID,
            "PERSON_TYPE_ID"        => $this->arResult['USER']["PERSON_TYPE_ID"],
            "PAYED"                 => "N",
            "CANCELED"              => "N",
            "STATUS_ID"             => "N",
            "PRICE"                 => $this->arResult['ORDER']['TOTAL'],
            "CURRENCY"              => $this->arResult["BASE_LANG_CURRENCY"],
            "USER_ID"               => (int)$this->user->GetID(),
            "PAY_SYSTEM_ID"         => $this->arResult['USER']["PAY_SYSTEM_ID"],
            "PRICE_DELIVERY"        => $this->arResult["DELIVERY_PRICE"],
            "DELIVERY_ID"           => (strlen($this->arResult['USER']["DELIVERY_ID"]) > 0 ? $this->arResult['USER']["DELIVERY_ID"] : false),
            "DISCOUNT_VALUE"        => $this->arResult["DISCOUNT_PRICE"],
            "TAX_VALUE"             => $this->arResult["bUsingVat"] == "Y" ? $this->arResult["VAT_SUM"] : $this->arResult["TAX_PRICE"],
            "USER_DESCRIPTION"      => $this->arResult['USER']["~ORDER_DESCRIPTION"]
        );

        $arOrderDat['USER_ID'] = $arFields['USER_ID'];

        if (IntVal($_POST["BUYER_STORE"]) > 0 && $this->arResult['USER']["DELIVERY_ID"] == $this->arResult['USER']["DELIVERY_STORE"]):
            $arFields["STORE_ID"] = IntVal($_POST["BUYER_STORE"]);
        endif;

        if (\Loader::includeModule("statistic")):
            $arFields["STAT_GID"] = \CStatistic::GetEventParam();
        endif;

        $affiliateID = \CSaleAffiliate::GetAffiliate();

        if ($affiliateID > 0):
            $dbAffiliat = \CSaleAffiliate::GetList(array(), array("SITE_ID" => SITE_ID, "ID" => $affiliateID));
            $arAffiliates = $dbAffiliat->Fetch();

            if (count($arAffiliates) > 1):
                $arFields["AFFILIATE_ID"] = $affiliateID;
            endif;
        else:
            $arFields["AFFILIATE_ID"] = false;
        endif;

        $this->arResult["ORDER_ID"] = (int)CSaleOrder::DoSaveOrder($arOrderDat, $arFields, 0, $this->arResult["ERROR"]);

        $arOrder = array();

        if ($this->arResult["ORDER_ID"] > 0 && empty($this->arResult["ERROR"])):
            $arOrder = CSaleOrder::GetByID($this->arResult["ORDER_ID"]);
            \CSaleBasket::OrderBasket($this->arResult["ORDER_ID"], CSaleBasket::GetBasketUserID(), SITE_ID, false);

            $this->arResult["ACCOUNT_NUMBER"] = ($this->arResult["ORDER_ID"] <= 0) ? $this->arResult["ORDER_ID"] : $arOrder["ACCOUNT_NUMBER"];
        endif;

        $withdrawSum = 0.0;

        if (empty($this->arResult["ERROR"])):
            if (
                    ($this->arResult["PAY_FROM_ACCOUNT"] == "Y")
                        &&
                    ($this->arResult['USER']["PAY_CURRENT_ACCOUNT"] == "Y")
                        &&
                    (
                        (
                            ($this->arParams["ONLY_FULL_PAY_FROM_ACCOUNT"] == "Y" && DoubleVal($this->arResult["USER_ACCOUNT"]["CURRENT_BUDGET"]) >= DoubleVal($this->arResult['ORDER']['TOTAL']))
                        )
                            ||
                        ($this->arParams["ONLY_FULL_PAY_FROM_ACCOUNT"] != "Y")
                    )
            ):
                $withdrawSum = CSaleUserAccount::Withdraw(
                    $this->user->GetID(),
                    $this->arResult['ORDER']['TOTAL'],
                    $this->arResult["BASE_LANG_CURRENCY"],
                    $this->arResult["ORDER_ID"]
                );

                if ($withdrawSum > 0):
                    $arFields = array(
                        "SUM_PAID" => $withdrawSum,
                        "USER_ID" => $this->user->GetID()
                    );
                    \CSaleOrder::Update($this->arResult["ORDER_ID"], $arFields);

                    if ($withdrawSum == $this->arResult['ORDER']['TOTAL']):
                        \CSaleOrder::PayOrder($this->arResult["ORDER_ID"], "Y", False, False);
                    endif;
                endif;
            endif;

            if($this->arResult["HAVE_PREPAYMENT"]):
                if($this->psPreAction && $this->psPreAction->IsAction()):
                    $this->psPreAction->orderId           = $this->arResult["ORDER_ID"];
                    $this->psPreAction->orderAmount       = $this->arResult['ORDER']['TOTAL'];
                    $this->psPreAction->deliveryAmount    = $this->arResult["DELIVERY_PRICE"];
                    $this->psPreAction->taxAmount         = $this->arResult["TAX_PRICE"];
                    $orderData = array();

                    $dbBasketItems = CSaleBasket::GetList(
                        array("ID" => "ASC"),
                        array(
                            "FUSER_ID" => CSaleBasket::GetBasketUserID(),
                            "LID" => SITE_ID,
                            "ORDER_ID" => $this->arResult["ORDER_ID"]
                        ),
                        false,
                        false,
                        array("ID", "QUANTITY", "PRICE", "WEIGHT", "NAME", "CURRENCY", "PRODUCT_ID", "DETAIL_PAGE_URL")
                    );

                    while ($arItem = $dbBasketItems->Fetch()):
                        $orderData['BASKET_ITEMS'][] = $arItem;
                    endwhile;

                    $this->psPreAction->payOrder($orderData);
                endif;
            endif;
        endif;

        if (empty($this->arResult["ERROR"])):
            CSaleOrderUserProps::DoSaveUserProfile($this->user->GetID(), $this->arResult['USER']["PROFILE_ID"], $this->arResult['USER']["PROFILE_NAME"], $this->arResult['USER']["PERSON_TYPE_ID"], $this->arResult['USER']["ORDER_PROP"], $this->arResult["ERROR"]);
        endif;

        if (empty($this->arResult["ERROR"])):
            $strOrderList   = "";
            $arBasketList   = array();
            $dbBasketItems  = CSaleBasket::GetList(
                array("ID" => "ASC"),
                array("ORDER_ID" => $this->arResult["ORDER_ID"]),
                false,
                false,
                array("ID", "PRODUCT_ID", "NAME", "QUANTITY", "PRICE", "CURRENCY", "TYPE", "SET_PARENT_ID")
            );

            while ($arItem = $dbBasketItems->Fetch()):
                if (CSaleBasketHelper::isSetItem($arItem)):
                    continue;
                endif;

                $arBasketList[] = $arItem;
            endwhile;

            $arBasketList = getMeasures($arBasketList);

            if (!empty($arBasketList) && is_array($arBasketList)):
                foreach ($arBasketList as $arItem):
                    $measureText = (isset($arItem["MEASURE_TEXT"]) && strlen($arItem["MEASURE_TEXT"])) ? $arItem["MEASURE_TEXT"] : GetMessage("SOA_SHT");

                    $strOrderList .= $arItem["NAME"]." - ".$arItem["QUANTITY"]." ".$measureText.": ".SaleFormatCurrency($arItem["PRICE"], $arItem["CURRENCY"]);
                    $strOrderList .= "\n";
                endforeach;
            endif;

            $arFields = array(
                "ORDER_ID"          => $arOrder["ACCOUNT_NUMBER"],
                "ORDER_DATE"        => Date($this->db->DateFormatToPHP(\CLang::GetDateFormat("SHORT", SITE_ID))),
                "ORDER_USER"        => ( (strlen($this->arResult['USER']["PAYER_NAME"]) > 0) ? $this->arResult['USER']["PAYER_NAME"] : $this->user->GetFormattedName(false)),
                "PRICE"             => SaleFormatCurrency($this->arResult['ORDER']['TOTAL'], $this->arResult["BASE_LANG_CURRENCY"]),
                "BCC"               => \COption::GetOptionString("sale", "order_email", "order@".$_SERVER['SERVER_NAME']),
                "EMAIL"             => (strlen($this->arResult['USER']["USER_EMAIL"])>0 ? $this->arResult['USER']["USER_EMAIL"] : $this->user->GetEmail()),
                "ORDER_LIST"        => $strOrderList,
                "SALE_EMAIL"        => \COption::GetOptionString("sale", "order_email", "order@".$_SERVER['SERVER_NAME']),
                "DELIVERY_PRICE"    => $this->arResult["DELIVERY_PRICE"],
            );

            $eventName = "SALE_NEW_ORDER";

            $bSend = true;
            foreach(GetModuleEvents("sale", "OnOrderNewSendEmail", true) as $arEvent):
                if (ExecuteModuleEventEx($arEvent, array($this->arResult["ORDER_ID"], &$eventName, &$arFields))===false):
                    $bSend = false;
                endif;
            endforeach;

            if($bSend):
                $event = new CEvent;
                $event->Send($eventName, SITE_ID, $arFields, "N");
            endif;

            \CSaleMobileOrderPush::send("ORDER_CREATED", array("ORDER_ID" => $this->arResult["ORDER_ID"]));
        endif;

        if (empty($this->arResult["ERROR"])):
            if(\Loader::includeModule("statistic")):
                $event1 = "eStore";
                $event2 = "order_confirm";
                $event3 = $this->arResult["ORDER_ID"];

                $e = $event1."/".$event2."/".$event3;

                if(
                    (!is_array($_SESSION["ORDER_EVENTS"]))
                        ||
                    (
                        (is_array($_SESSION["ORDER_EVENTS"]))
                            &&
                        (!in_array($e, $_SESSION["ORDER_EVENTS"]))
                    )
                ):
                    CStatistic::Set_Event($event1, $event2, $event3);
                    $_SESSION["ORDER_EVENTS"][] = $e;
                endif;
            endif;

            foreach(GetModuleEvents("sale", "OnSaleComponentOrderOneStepComplete", true) as $arEvent):
                ExecuteModuleEventEx($arEvent, Array($this->arResult["ORDER_ID"], $arOrder, $this->arParams));
            endforeach;
        endif;

        if (empty($this->arResult["ERROR"])):
            $this->arResult["REDIRECT_URL"] = $this->application->GetCurPageParam("ORDER_ID=".urlencode(urlencode($arOrder["ACCOUNT_NUMBER"])), Array("ORDER_ID"));

            if(
                array_key_exists('json', $_REQUEST)
                    &&
                $_REQUEST['json'] == "Y"
                    &&
                (
                    ($this->user->IsAuthorized())
                        ||
                    ($this->arParams["ALLOW_AUTO_REGISTER"] == "Y")
                )
            ):
                if($this->arResult['USER']["CONFIRM_ORDER"] == "Y" || $this->arResult["NEED_REDIRECT"] == "Y"):
                    $this->application->RestartBuffer();
                    echo json_encode(array("success" => "Y", "redirect" => $this->arResult["REDIRECT_URL"]));
                    die();
                endif;
            endif;
        else:
            $this->arResult['USER']["CONFIRM_ORDER"] = "N";
        endif;

    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function GetOrder($options=array())
    {
        if(!isset($_REQUEST["ORDER_ID"])/*||(isset($_REQUEST["ORDER_ID"])&&(int)$_REQUEST["ORDER_ID"]<=0)*/):
            return $this;
        endif;

        $this->arResult["USER_VALS"]["CONFIRM_ORDER"] = "Y";

        $ID         = urldecode(urldecode($_REQUEST["ORDER_ID"]));

        $this->arResult["ORDER"]    = false;

        if ($this->arParams["USE_ACCOUNT_NUMBER"]):
            $this->arParams['ORDER']['ORDER'] = array(
                "DATE_UPDATE" => "DESC",
            );
            $this->arParams['ORDER']['FILTER'] = array(
                "LID"               => SITE_ID,
                "ACCOUNT_NUMBER"    => $ID,
            );
            $this->arParams['ORDER']['GROUP']=false;
            $this->arParams['ORDER']['LIMIT']=false;
            $this->arParams['ORDER']['SELECT']=array(
                'ID',
                'LID',
                'PERSON_TYPE_ID',
                'PAYED',
                'DATE_PAYED',
                'EMP_PAYED_ID',
                'CANCELED',
                'DATE_CANCELED',
                'EMP_CANCELED_ID',
                'REASON_CANCELED',
                'MARKED',
                'DATE_MARKED',
                'EMP_MARKED_ID',
                'REASON_MARKED',
                'STATUS_ID',
                'DATE_STATUS',
                'PAY_VOUCHER_NUM',
                'PAY_VOUCHER_DATE',
                'EMP_STATUS_ID',
                'PRICE_DELIVERY',
                'ALLOW_DELIVERY',
                'DATE_ALLOW_DELIVERY',
                'EMP_ALLOW_DELIVERY_ID',
                'DEDUCTED',
                'DATE_DEDUCTED',
                'EMP_DEDUCTED_ID',
                'REASON_UNDO_DEDUCTED',
                'RESERVED',
                'PRICE',
                'CURRENCY',
                'DISCOUNT_VALUE',
                'SUM_PAID',
                'USER_ID',
                'PAY_SYSTEM_ID',
                'DELIVERY_ID',
                'DATE_INSERT',
                'DATE_INSERT_FORMAT',
                'DATE_UPDATE',
                'USER_DESCRIPTION',
                'ADDITIONAL_INFO',
                'PS_STATUS',
                'PS_STATUS_CODE',
                'PS_STATUS_DESCRIPTION',
                'PS_STATUS_MESSAGE',
                'PS_SUM',
                'PS_CURRENCY',
                'PS_RESPONSE_DATE',
                'COMMENTS',
                'TAX_VALUE',
                'STAT_GID',
                'RECURRING_ID',
                'RECOUNT_FLAG',
                'USER_LOGIN',
                'USER_NAME',
                'USER_LAST_NAME',
                'USER_EMAIL',
                'DELIVERY_DOC_NUM',
                'DELIVERY_DOC_DATE',
                'DELIVERY_DATE_REQUEST',
                'STORE_ID',
                'ORDER_TOPIC',
                'RESPONSIBLE_ID',
                'RESPONSIBLE_LOGIN',
                'RESPONSIBLE_NAME',
                'RESPONSIBLE_LAST_NAME',
                'RESPONSIBLE_SECOND_NAME',
                'RESPONSIBLE_EMAIL',
                'RESPONSIBLE_WORK_POSITION',
                'RESPONSIBLE_PERSONAL_PHOTO',
                'DATE_PAY_BEFORE',
                'DATE_BILL',
                'ACCOUNT_NUMBER',
                'TRACKING_NUMBER',
                'XML_ID',
                'ID_1C',
            );

            $dbOrder = \CSaleOrder::GetList(
                $this->arParams['ORDER']['ORDER'],
                $this->arParams['ORDER']['FILTER'],
                $this->arParams['ORDER']['GROUP'],
                $this->arParams['ORDER']['LIMIT'],
                $this->arParams['ORDER']['SELECT']
            );

            if ($this->arResult["ORDER"] = $dbOrder->Fetch()):
                $this->arResult["ORDER_ID"]         = $this->arResult["ORDER"]["ID"];
                $this->arResult["ACCOUNT_NUMBER"]   = $this->arResult["ORDER"]["ACCOUNT_NUMBER"];
            endif;
        endif;

        if (!$this->arResult["ORDER"]):
            $this->arParams['ORDER']['ORDER'] = array(
                "DATE_UPDATE" => "DESC",
            );
            $this->arParams['ORDER']['FILTER'] = array(
                "LID"   => SITE_ID,
                "ID_1C"    => $ID,
            );

            $dbOrder = \CSaleOrder::GetList(
                $this->arParams['ORDER']['ORDER'],
                $this->arParams['ORDER']['FILTER']
            );

            if($this->arResult["ORDER"] = $dbOrder->GetNext()):
                $this->arResult["ORDER_ID"]         = $ID;
                $this->arResult["ACCOUNT_NUMBER"]   = $this->arResult["ORDER"]["ACCOUNT_NUMBER"];
            endif;
        endif;

        if($this->arResult["ORDER"]):
            foreach(GetModuleEvents("sale", "OnSaleComponentOrderOneStepFinal", true) as $arEvent):
                ExecuteModuleEventEx($arEvent, Array($this->arResult["ORDER_ID"], &$this->arResult["ORDER"], &$this->arParams));
            endforeach;
        endif;

        if ($this->arResult["ORDER"] && $this->arResult["ORDER"]["USER_ID"] == IntVal($this->user->GetID())):
            if (IntVal($this->arResult["ORDER"]["PAY_SYSTEM_ID"]) > 0 && $this->arResult["ORDER"]["PAYED"] != "Y"):
                $this->arParams['PAY_SYSTEM']['ORDER']  = array();
                $this->arParams['PAY_SYSTEM']['FILTER'] = array(
                    "PAY_SYSTEM_ID"     => $this->arResult["ORDER"]["PAY_SYSTEM_ID"],
                    "PERSON_TYPE_ID"    => $this->arResult["ORDER"]["PERSON_TYPE_ID"]
                );
                $this->arParams['PAY_SYSTEM']['GROUP']  = false;
                $this->arParams['PAY_SYSTEM']['LIMIT']  = false;
                $this->arParams['PAY_SYSTEM']['SELECT'] = array(
                    "NAME",
                    "ACTION_FILE",
                    "NEW_WINDOW",
                    "PARAMS",
                    "ENCODING",
                    "LOGOTIP"
                );

                $dbPaySysAction = \CSalePaySystemAction::GetList(
                    $this->arParams['PAY_SYSTEM']['ORDER'],
                    $this->arParams['PAY_SYSTEM']['FILTER'],
                    $this->arParams['PAY_SYSTEM']['GROUP'],
                    $this->arParams['PAY_SYSTEM']['LIMIT'],
                    $this->arParams['PAY_SYSTEM']['SELECT']
                );

                if ($arPaySysAction = $dbPaySysAction->Fetch()):
                    $arPaySysAction["NAME"] = htmlspecialcharsEx($arPaySysAction["NAME"]);

                    if (strlen($arPaySysAction["ACTION_FILE"]) > 0):
                        if ($arPaySysAction["NEW_WINDOW"] != "Y"):
                            \CSalePaySystemAction::InitParamArrays($this->arResult["ORDER"], $this->arResult["ORDER"]["ID"], $arPaySysAction["PARAMS"]);

                            $pathToAction = $_SERVER["DOCUMENT_ROOT"].$arPaySysAction["ACTION_FILE"];

                            $pathToAction = str_replace("\\", "/", $pathToAction);

                            while (substr($pathToAction, strlen($pathToAction) - 1, 1) == "/"):
                                $pathToAction = substr($pathToAction, 0, strlen($pathToAction) - 1);
                            endwhile;

                            if (file_exists($pathToAction)):
                                if (is_dir($pathToAction) && file_exists($pathToAction."/payment.php")):
                                    $pathToAction .= "/payment.php";
                                endif;

                                $arPaySysAction["PATH_TO_ACTION"] = $pathToAction;
                            endif;

                            if(strlen($arPaySysAction["ENCODING"]) > 0):
                                define("BX_SALE_ENCODING", $arPaySysAction["ENCODING"]);

                                AddEventHandler("main", "OnEndBufferContent", "ChangeEncoding");
                            endif;
                        endif;
                    endif;

                    if ($arPaySysAction > 0):
                        $arPaySysAction["LOGOTIP"] = \CFile::GetFileArray($arPaySysAction["LOGOTIP"]);
                    endif;

                    $this->arResult["PAY_SYSTEM"] = $this->arResult["PAY_SYSTEM"][$this->arResult['ORDER']['PAY_SYSTEM_ID']];
                    $this->arResult["PAY_SYSTEM"]['ACTION'] = $arPaySysAction;
                endif;
            endif;
        endif;
    }

    /**
     * @param $source
     *
     * @return string
     */
    protected function GetPropsInfo($source)
    {
        $resultHTML = "";

        foreach ($source["PROPS"] as $val)
            $resultHTML .= str_replace(" ", "&nbsp;", $val["NAME"].": ".$val["VALUE"])."<br />";
        return $resultHTML;
    }

    /**
     * @param array $options
     */
    public function GetStatus($options=array())
    {
        $this->arParams['STATUS']['GETLIST']['ORDER'] = array();
        $this->arParams['STATUS']['GETLIST']['FILTER'] = array('ACTIVE'=>'Y');

        $nsStatus = \CSaleStatus::GetList(
            $this->arParams['STATUS']['GETLIST']['ORDER'],
            $this->arParams['STATUS']['GETLIST']['FILTER']
        );

        while($arItem = $nsStatus->Fetch()):
            $this->arResult['STATUS'][$arItem['ID']] = $arItem;
        endwhile;
    }

    /**
     * @param array $options
     */
    protected function _otherAjaxDetected($options=array()){}

    /**
     * @param array $options
     */
    protected function _otherAuthorize($options=array()){}

    /**
     * @param array $options
     */
    protected function _otherLoad($options=array()){}

    /**
     * @param array $options
     */
    protected function _otherSetArParamsAfter($options=array()){}

    /**
     * @param array $options
     */
    protected function _otherSetArParamsBefore($options=array()){}

    /**
     * @param array $options
     */
    protected function _otherSetArResult($options=array()){}

    /**
     * @param array $options
     */
    protected function _otherSetDefine($options=array()){}

    /**
     * @param array $options
     */
    protected function _otherSetProductsColumns($options=array()){}

    /**
     * @param array $options
     */
    protected function _otherSetProductsColumnsArray($options=array()){}
}

/**
 * @param string $content
 */
function ChangeEncoding($content='')
{
    global $APPLICATION;

    header("Content-Type: text/html; charset=".BX_SALE_ENCODING);

    $content = $APPLICATION->ConvertCharset($content, SITE_CHARSET, BX_SALE_ENCODING);
    $content = str_replace("charset=".SITE_CHARSET, "charset=".BX_SALE_ENCODING, $content);
}

$this->IncludeComponentTemplate();

if ($_REQUEST["AJAX_CALL"] == "Y" || $_REQUEST["is_ajax_post"] == "Y"):
	die();
endif;
?>