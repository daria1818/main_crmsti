<?
define("NEED_AUTH", true);
// Composer autoloader
use Bitrix\Main\EventManager;
use Dotenv\Exception\InvalidFileException;
use Dotenv\Exception\InvalidPathException;
use function Sentry\init;
use Pwd\Tools\Logger;

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/spaceonfire/bitrix-tools/resources/autoload.php';
require_once __DIR__ . '/core_overrides.php';

Logger::$PATH = __DIR__ .'/logs/';

if (class_exists('Dotenv\\Dotenv')) {
    $env = Dotenv\Dotenv::createImmutable(dirname($_SERVER['DOCUMENT_ROOT'], 2));
    // Если на проекте используется другое имя файла, его можно задать вторым параметром
    // пример, $env = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT'], '.environment');
    try {
        $env->load();


        init([
            'dsn' => getenv('SENTRY_DSN'),
            'environment' => getenv('APP_ENV'),
        ]);

    } catch (InvalidFileException | InvalidPathException $e) {
    }
}

/**
 * Константа ИД каталога
 */
define('CATALOG_IBLOCK', 30);
/**
 * Константа ИД ИБ торговых предложений
 */
define('OFFERS_IBLOCK', 64);
define('IBLOCKS_CATALOG', [CATALOG_IBLOCK, OFFERS_IBLOCK]);
/**
 * Автоподключение классов.
 */
\Bitrix\Main\Loader::registerAutoLoadClasses($module = null, [
    'ApiFor1C\\ApiProvider' => '/local/php_interface/api/classes/apiProvider.php', //Класс для обработки входящих запросов
    'ApiFor1C\\Sender' => '/local/php_interface/api/classes/sender.php', //Класс для отправки запросов в 1С
    'ApiFor1C\\Handlers' => '/local/php_interface/api/classes/handlers.php', //Класс для описания событий (хэндлеров)
    'ApiFor1C\\BuyersAndCounterparties' => '/local/php_interface/api/classes/buyersAndCounterparties.php', //Класс для работы с покупателями и контрагентами
    'ApiFor1C\\OrderSender' => '/local/php_interface/api/classes/orderSender.php', // Класс для отправки информации по заказам
    //'ApiFor1C\\OrderUpdater' => '/local/php_interface/api/classes/orderUpdater.php',
    //'ApiFor1C\\Update\\Product' => '/local/php_interface/api/classes/updateProduct.php',
    //'ApiFor1C\\Update\\Sales' => '/local/php_interface/api/classes/salesUpdate.php',
    //'ApiFor1C\\Check\\Product' => '/local/php_interface/api/classes/checkProduct.php',
    'ApiFor1C\\ToolsApi' => '/local/php_interface/api/classes/tools.php', //Класс с различнами вспомогательными функциями
    'Api\\Classes\\Entity\\OrderContactCompanyTable' => '/local/php_interface/api/classes/entity/OrderContactCompanyTable.php', // класс контактов crm
    'Api\\Classes\\Entity\\BasketTable' => '/local/php_interface/api/classes/entity/BasketTable.php', // класс корзин
    'Api\\Classes\\Entity\\OrderTable' => '/local/php_interface/api/classes/entity/OrderTable.php', // класс заказов
    'PWD\\Helpers\\UsersDepartmentTypeField' => '/local/class/pwd/Helpers/UsersDepartmentTypeField.php',
    'RtopTypeEventTable' => '/local/php_interface/api/classes/RtopTypeEvent.php',
    'RtopSaleActionGift' => '/local/php_interface/api/classes/RtopSaleActionGift.php',
    'RtopSaleFreeDelivery' => '/local/php_interface/api/classes/RtopSaleFreeDelivery.php',
]);

define('CRM_USE_CUSTOM_SERVICES', true);

if (defined('CRM_USE_CUSTOM_SERVICES') && CRM_USE_CUSTOM_SERVICES === true)
{
    $fileName = __DIR__ . '/include/crm_services.php';
    if (file_exists($fileName))
    {
        require_once ($fileName);
    }
}

/**
 * Инициализация хэндлеров
 */
ApiFor1C\Handlers::init();

AddEventHandler("sale", "OnOrderNewSendEmail", "bxModifySaleMails");
AddEventHandler('iblock', 'OnIBlockPropertyBuildList', ['PWD\\Helpers\\UsersDepartmentTypeField', 'GetUserTypeDescription']);
//-- Собственно обработчик события

function bxModifySaleMails($orderID, &$eventName, &$arFields)
{

	$arOrder = CSaleOrder::GetByID($orderID);

	//mail("erynrandir@yandex.ru", "OnBeforeUserAddHandler", "{$arFields['LOGIN']} / {$arFields['PASSWORD']}");
	mail("erynrandir@yandex.ru", "OnBeforeUserAddHandler", "bxModifySaleMails");

}

// \Bitrix\Main\Loader::includeModule("xguard.main");
// @include($_SERVER['DOCUMENT_ROOT'].'/include/const.php');
@include(__DIR__.'/debug.php');


require_once($_SERVER["DOCUMENT_ROOT"] . "/debug/vendor/autoload.php");


$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandlerCompatible(
    "sale",
    "OnCondSaleActionsControlBuildList",
    ["RtopSaleActionGift", "GetControlDescr"]
);

$eventManager->addEventHandlerCompatible(
    "sale",
    "OnCondSaleControlBuildList",
    ["RtopSaleFreeDelivery", "GetControlDescr"]
);


//AddEventHandler("main", "OnBeforeUserRegister", "domovoyOnBeforeUserRegisterCheck");
AddEventHandler("main", "OnAfterUserRegister", "domovoyOnAfterUserRegisterCheck");

//AddEventHandler("main", "OnAfterUserUpdate", "domovoyOnAfterUserUpdateCheck");
//AddEventHandler("main", "OnAfterUserAdd", "domovoyOnAfterUserAddHandler");


/*
AddEventHandler("iblock", "OnBeforeIBlockElementUpdate", "DoNotUpdate");

function DoNotUpdate(&$arFields)
{
    if ($_REQUEST['mode']=='import')
    {
		unset($arFields['NAME']);
        unset($arFields['PREVIEW_TEXT']);
        unset($arFields['DETAIL_TEXT']);
		unset($arFields['PREVIEW_PICTURE']);
        unset($arFields['DETAIL_PICTURE']);
    }
}


AddEventHandler("iblock", "OnBeforeIBlockElementAdd","DoNotAdd");

function DoNotAdd(&$arFields)
{
    if ($_REQUEST['mode']=='import')
    {
		unset($arFields['NAME']);
        unset($arFields['PREVIEW_TEXT']);
        unset($arFields['DETAIL_TEXT']);
		unset($arFields['PREVIEW_PICTURE']);
        unset($arFields['DETAIL_PICTURE']);
    }
}
*/

$eventManager->addEventHandler('documentgenerator', 'onBeforeProcessDocument', function(\Bitrix\Main\Event $event){

    $document = $event->getParameter('document');
    //$logger = Logger::getLogger('test', 'test');
    //$logger->log('orderID = ');
});

function get_SOAP()
{

	$config = new matejsvajger\NTLMSoap\Common\NTLMConfig([
		'domain'   => 'dentlmen-server',
		'username' => 'im_user',
		'password' => 'Ckj;ysqGfhjkm'
	]);

	$client = new matejsvajger\NTLMSoap\Client("http://91.242.161.153/dentlmenTEST/ws/DtmWS/?wsdl", $config);

	$soap_func = print_r($client->__getFunctions(), true);
	debugfile($soap_func, '1c.log');

	//print_r($client->__getTypes());

	return $client;

}





if (!function_exists('domovoyOnBeforeUserRegisterCheck')):
    function domovoyOnBeforeUserRegisterCheck(&$arFields)
    {
        global $APPLICATION;

        $countReplaceName = $countReplaceLastName = $countReplaceSecondName = 0;

        $APPLICATION->ResetException();

        $arFields['LOGIN'] = $arFields['EMAIL'];
        $arFields['~NAME'] = trim(preg_replace('/[^а-я^\s^\-]+/iu', '', $arFields['NAME'], -1, $countReplaceName));
        $arFields['NAME'] = mb_convert_case($arFields['NAME'], MB_CASE_TITLE);
        $arFields['~LAST_NAME'] = trim(preg_replace('/[^а-я^\s^\-]+/iu', '', $arFields['LAST_NAME'], -1, $countReplaceLastName));
        $arFields['LAST_NAME'] = mb_convert_case($arFields['LAST_NAME'], MB_CASE_TITLE);
        $arFields['~SECOND_NAME'] = trim(preg_replace('/[^а-я^\s^\-]+/iu', '', $_REQUEST['USER_SECOND_NAME'], -1, $countReplaceSecondName));
        $arFields['SECOND_NAME'] = mb_convert_case($_REQUEST['USER_SECOND_NAME'], MB_CASE_TITLE);
        $_REQUEST['SECOND_NAME'] = $arFields['SECOND_NAME'];
        $arFields['PERSONAL_PHONE'] = htmlspecialcharsbx($_REQUEST['USER_PERSONAL_PHONE']);
        $_REQUEST['PERSONAL_PHONE'] = $arFields['PERSONAL_PHONE'];
        $arFields['UF_PROMO_CODE'] = htmlspecialcharsbx($_REQUEST['UF_PROMO_CODE']);
        $arFields['UF_PROMO_CODE'] = empty($arFields['UF_PROMO_CODE']) ? '' : $arFields['UF_PROMO_CODE'];
        $arFields['UF_FB_PROFILE'] = htmlspecialcharsbx($_REQUEST['UF_FB_PROFILE']);
        $arFields['UF_FB_PROFILE'] = empty($arFields['UF_FB_PROFILE']) ? '' : $arFields['UF_FB_PROFILE'];
        $arFields['UF_UTM_SOURCE'] = isset($_SESSION['utm_source']) ? htmlspecialcharsbx($_SESSION['utm_source']) : '';
        $arFields['UF_UTM_MEDIUM'] = isset($_SESSION['utm_medium']) ? htmlspecialcharsbx($_SESSION['utm_medium']) : '';
        $arFields['UF_UTM_CAMPAIGN'] = isset($_SESSION['utm_campaign']) ? htmlspecialcharsbx($_SESSION['utm_campaign']) : '';
        $arFields['UF_UTM_TERM'] = isset($_SESSION['utm_term']) ? htmlspecialcharsbx($_SESSION['utm_term']) : '';
        $arFields['UF_UTM_CONTENT'] = isset($_SESSION['utm_content']) ? htmlspecialcharsbx($_SESSION['utm_content']) : '';

        $countReplaceName = $countReplaceName ? $APPLICATION->ThrowException('ERROR #'.__LINE__, 'REGISTER_ERROR_NOT_CYRILIC_NAME') : $countReplaceName;
        $countReplaceLastName = $countReplaceLastName ? $APPLICATION->ThrowException('ERROR #'.__LINE__, 'REGISTER_ERROR_NOT_CYRILIC_LAST_NAME') : $countReplaceLastName;
        $countReplaceSecondName = $countReplaceSecondName ? $APPLICATION->ThrowException('ERROR #'.__LINE__, 'REGISTER_ERROR_NOT_CYRILIC_SECOND_NAME') : $countReplaceSecondName;

        foreach (array('LOGIN', 'EMAIL', 'PERSONAL_PHONE', 'NAME', 'LAST_NAME', 'UF_EULA') as $keyField):
            if (!isset($arFields[$keyField]) || empty($arFields[$keyField])):
                $APPLICATION->ThrowException(GetMessage('REGISTER_ERROR_EMPTY_'.$keyField), 'REGISTER_ERROR_EMPTY_'.$keyField);
            endif;
        endforeach;

        /*$soap = xGuard\Main\Soap\Params::GetSoapInstance();
        $arFields['PROFILE_NAME'] = trim(implode(' ', array($arFields['LAST_NAME'], $arFields['NAME'], $arFields['SECOND_NAME'],)));
        $arFields['SOAP'] = array(
            'pStruct' => array(
                'Login'      => $arFields['LOGIN'],
                'Edit'       => false,
                'Phone'      => $arFields['PERSONAL_PHONE'],
                'Email'      => $arFields['EMAIL'],
                'FIO'        => $arFields['PROFILE_NAME'],
                'PromoCode'  => $arFields['UF_PROMO_CODE'],
                'FacebookID' => $arFields['UF_FB_PROFILE'],
            ),
        );
        debugfile($arFields, 'user.log');
        $result = $soap->RegUser($arFields['SOAP']);

        if (!$result->return->Status):
            debugfile(array($result), 'user.log');

            $APPLICATION->ThrowException($result->return->ErrorList->Error->_, 'REGISTER_ERROR_EMPTY_UF_FB_PROFILE');

            return false;
        endif;*/

        return !$APPLICATION->GetException();
    }
endif;



if (!function_exists('domovoyOnAfterUserRegisterCheck')):

    function domovoyOnAfterUserRegisterCheck($arFields)
    {

        global $APPLICATION, $USER;


		$client = get_SOAP();


        if (empty($arFields['USER_ID'])):

            $APPLICATION->ThrowException('ERROR #'.__LINE__, 'ERROR_EXCHANGE_1C_EMPTY_USER');
            debugfile(array($arFields), 'user.log');

            return false;

        endif;


		$headers = "From: test@". $_SERVER['HTTP_HOST'] . "\r\n" .
		"Reply-To: test@". $_SERVER['HTTP_HOST'] . "\r\n" .
		"X-Mailer: PHP/" . phpversion();
		mail("erynrandir@yandex.ru", "dev.stionline.ru test event", "testOnAfterUserRegisterCheck" . time(), $headers);


		/*
		$arEventFields = array(
			'NAME'  => 'Имя',
			'PHONE' => 'телефон',
			'EMAIL' => 'Почта'
		);

		CEvent::Send("SALE_NEW_ORDER", SITE_ID, $arEventFields);
		*/




        try
		{


            if (\Bitrix\Main\Loader::includeModule('sale')):

                $arParams['SALE_ORDER_PROPERTIES']['GETLIST'] = array(
                    'ORDER'  => array('sort' => 'asc'),
                    'FILTER' => array('ACTIVE' => 'Y', 'USER_PROPS' => 'Y', 'PERSON_TYPE_ID' => PERSON_TYPE_ID_PP),
                );

                $nsItem = \CSaleOrderProps::GetList(
                    $arParams['SALE_ORDER_PROPERTIES']['GETLIST']['ORDER'],
                    $arParams['SALE_ORDER_PROPERTIES']['GETLIST']['FILTER']
                );

                $arGroups = array();

                while ($arItem = $nsItem->Fetch()):
                    $arGroups[$arItem['SORT']] = !isset($arGroups[$arItem['SORT']]) ? (1) : (++$arGroups[$arItem['SORT']]);
                    $arResult['ORDER_PROPS'][$arItem['PERSON_TYPE_ID']][] = $arItem;
                    $arResult['~ORDER_PROPS'][$arItem['CODE']] = $arItem['ID'];
                endwhile;

                $arFields['USER_PROFILE_ID'] = '';
                $arFields['PERSON_TYPE_ID'] = PERSON_TYPE_ID_PP;
                $arFields['PROFILE_NAME'] = trim(implode(' ', array($arFields['LAST_NAME'], $arFields['NAME'], $arFields['SECOND_NAME'],)));
                $arFields['PROPS'] = array(
                    $arResult['~ORDER_PROPS']['FULL_NAME'] => $arFields['PROFILE_NAME'],
                    $arResult['~ORDER_PROPS']['PHONE']     => $arFields['PERSONAL_PHONE'],
                    $arResult['~ORDER_PROPS']['EMAIL']     => $arFields['EMAIL'],
                    $arResult['~ORDER_PROPS']['APPROVE']   => 'N',
                );

                \CSaleOrderUserProps::DoSaveUserProfile($arFields['USER_ID'], $arFields['USER_PROFILE_ID'], $arFields['PROFILE_NAME'], $arFields['PERSON_TYPE_ID'], $arFields['PROPS'], $arErrors);

                $arFields['USER_PROFILE'] = \CSaleOrderUserProps::GetList(
                    array(),
                    array('USER_ID' => $arFields['USER_ID'])
                )->Fetch();
                $arFields['USER_PROFILE_ID'] = $arFields['USER_PROFILE']['ID'];

            endif;







            $arFields['SOAP']=array(
                'pStruct' => array(
                    'Login'     => $arFields['LOGIN'],
                    'Edit'      => false,
                    'Phone'     => $arFields['PERSONAL_PHONE'],
                    'Email'     => $arFields['EMAIL'],
                    'FIO'       => $arFields['PROFILE_NAME'],
                    'PromoCode' => $arFields['UF_PROMO_CODE'],
                    'FacebookID'=> $arFields['UF_FB_PROFILE'],
                ),
            );
            debugfile($arFields['SOAP'], 'user.log');

            //$result = $soap->RegUser($arFields['SOAP']);
			$result = $client->RegUser($arFields['SOAP']);

            if(!$result->return->Status):

                debugfile(array($result),'1c.log');

                $APPLICATION->ThrowException($result->return->ErrorList->Error, 'ERROR_EXCHANGE_1C_CREATE_USER');

                return false;

            endif;



            $arFields['SOAP'] = array(
                'pStruct' => array(
                    'TypeClient' => "ЮрЛицо", //constant('PERSON_TYPE_'.$arFields['PERSON_TYPE_ID']),
                    'Login'      => $arFields['LOGIN'],
                    'Phone'      => $arFields['PERSONAL_PHONE'],
                    'Email'      => $arFields['EMAIL'],
                    'Name'       => $arFields['PROFILE_NAME'],
                    'AddrrLegal' => '',
                ),
            );
            debugfile($arFields['SOAP'], 'user.log');

            //$result = $soap->CreateClient($arFields['SOAP']);
			$result = $client->CreateClient($arFields['SOAP']);

            if (!$result->return->Status):

                debugfile(array($result), '1c.log');

                $APPLICATION->ThrowException($result->return->ErrorList->Error, 'ERROR_EXCHANGE_1C_CREATE_ACCOUNTS');

                return false;

            else:

                $arOrderProperty = \CSaleOrderProps::GetList(
                    array(),
                    array("PERSON_TYPE_ID" => $arFields['PERSON_TYPE_ID'], 'CODE' => 'GUID'),
                    false,
                    false,
                    array("ID", "NAME",)
                )->Fetch();
                $arFields['USER_PROPS_GUID'] = array(
                    "USER_PROPS_ID"  => $arFields['USER_PROFILE_ID'],
                    "ORDER_PROPS_ID" => $arOrderProperty["ID"],
                    "NAME"           => $arOrderProperty["NAME"],
                    "VALUE"          => $result->return->GUID,
                );
                CSaleOrderUserPropsValue::Add($arFields['USER_PROPS_GUID']);
            endif;

            $arFields['SECOND_NAME'] = $_REQUEST['SECOND_NAME'];
            $arFields['PERSONAL_PHONE'] = $_REQUEST['PERSONAL_PHONE'];
            $arFields['UF_UTM_SOURCE'] = isset($_SESSION['utm_source']) ? htmlspecialcharsbx($_SESSION['utm_source']) : '';
            $arFields['UF_UTM_MEDIUM'] = isset($_SESSION['utm_medium']) ? htmlspecialcharsbx($_SESSION['utm_medium']) : '';
            $arFields['UF_UTM_CAMPAIGN'] = isset($_SESSION['utm_campaign']) ? htmlspecialcharsbx($_SESSION['utm_campaign']) : '';
            $arFields['UF_UTM_TERM'] = isset($_SESSION['utm_term']) ? htmlspecialcharsbx($_SESSION['utm_term']) : '';
            $arFields['UF_UTM_CONTENT'] = isset($_SESSION['utm_content']) ? htmlspecialcharsbx($_SESSION['utm_content']) : '';

            unset(
                $_SESSION['utm_source'],
                $_SESSION['utm_medium'],
                $_SESSION['utm_campaign'],
                $_SESSION['utm_term'],
                $_SESSION['utm_content']
            );

            $user = new \CUser;
            $user->Update($arFields['USER_ID'], $arFields);

            debugfile(array($user->LAST_ERROR), 'user.log');


        }
		catch (\SoapFault $e)
		{

            $APPLICATION->ThrowException($e->GetMessage(), 'EXCHANGE_1C_ERROR');
            debugfile(array($e->GetMessage()), '1c.log');

            return false;

        }

        return true;
    }
endif;



if (!function_exists('domovoyOnAfterUserUpdateCheck')):
    function domovoyOnAfterUserUpdateCheck($arFields)
    {
        global $APPLICATION, $USER;


		$client = get_SOAP();


        if (count($arFields) && isset($arFields['PASSWORD'])):
            $_SESSION['PASSWORD_CHANGES_TIME'] = time();
        endif;

        try {

			//$soap = xGuard\Main\Soap\Params::getSoapInstance();

            $arFields['PROFILE_NAME'] = trim(implode(' ', array($arFields['LAST_NAME'], $arFields['NAME'], $arFields['SECOND_NAME'],)));
            $arFields['SOAP'] = array(
                'pStruct' => array(
                    'Login'      => $arFields['LOGIN'],
                    'Edit'       => true,
                    'Phone'      => $arFields['PERSONAL_PHONE'],
                    'Email'      => $arFields['EMAIL'],
                    'FIO'        => $arFields['PROFILE_NAME'],
                    //'PromoCode' => $arFields['UF_PROMO_CODE'],
                    'FacebookID' => $arFields['UF_FB_PROFILE'],
                ),
            );

            $result = $client->RegUser($arFields['SOAP']);
            debugfile(array($result, $arFields['SOAP']), 'user.log');

            if (!$result->return->Status):
                debugfile(array($result), '1c.log');

                $APPLICATION->ThrowException($result->return->ErrorList->Error, 'ERROR_EXCHANGE_1C_CREATE_USER');

                return false;
            endif;


            $arFields['UF_PROMO_CODE'] = trim($arFields['UF_PROMO_CODE']);
            $arFields['UF_PROMO_CODE'] = preg_replace('/[\s\t]/', '', $arFields['UF_PROMO_CODE']);

            debugfile($arFields, 'user.log');


            if (!empty($arFields['UF_PROMO_CODE'])):
                $arFields['SOAP'] = array(
                    'User'  => $arFields['LOGIN'],
                    'Promo' => $arFields['UF_PROMO_CODE'],
                );

                $result = $client->VerifiPromo($arFields['SOAP']);
                debugfile(array($result, $arFields), 'promo_code.log');
                if (!$result->return || !$result->return->Status):
                    $APPLICATION->ThrowException(GetMessage('XGUARD_PROMO_CODE_RETURNS_ERROR'));

                    unset($_SESSION['PROMO_CODE_CHANGES_TIME']);

                    return false;
                endif;

                $group = \Bitrix\Main\GroupTable::getList(['filter' => ['STRING_ID' => $arFields['UF_PROMO_CODE']]])->fetch();

                if (!empty($group)) {
                    $userId = $USER->getId();
                    $arGroupsQuery = CUser::GetUserGroupEx($userId);
                    $arGroups = [];

                    while ($arGroup = $arGroupsQuery->Fetch()) {
                        $arGroups[] = $arGroup;
                    }
                    debugfile($arGroups, 'promo_code.log');

                    $arGroups[] = [
                        'GROUP_ID'         => $group['ID'],
                        'DATE_ACTIVE_FROM' => date('d.m.Y H:i:s'),
                        'DATE_ACTIVE_TO'   => date('d.m.Y H:i:s', strtotime($result->return->DateEnd)),
                    ];

                    CUser::SetUserGroup($userId, $arGroups);

                    if (is_array($_SESSION['SESS_AUTH']['GROUPS'])) {
                        $_SESSION['SESS_AUTH']['GROUPS'][] = $group['ID'];
                    }
                }

                $_SESSION['PROMO_CODE_CHANGES_TIME'] = strtotime($result->return->DateEnd);
            endif;

            debugfile($arFields, 'user.log');

        } catch (\xGuard\Main\Exception $e) {
            $APPLICATION->ThrowException($e->getMessage(), 'EXCHANGE_1C_ERROR');

            return false;
        }

        return true;
    }
endif;

/**
 * обработчик события на формирование документа
 */
\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'documentgenerator',
    'onBeforeProcessDocument',
    function(\Bitrix\Main\Event $event) {
        $sProductsPlaceholder = 'PRODUCTS';

        /** @var \Bitrix\DocumentGenerator\Document $document */
        $obDocument = $event->getParameter('document');

        $arFields = $obDocument->getFields([], true);
        $nDelivery = $arFields['TotalSum']['VALUE'] - $arFields['TotalRaw']['VALUE'];

        $obProvider = $obDocument->getProvider();

        $obProductsProvider = $obProvider->getValue($sProductsPlaceholder);
        if($obProductsProvider instanceof \Bitrix\DocumentGenerator\DataProvider\ArrayDataProvider)
        {
            $obProductsProvider->addItem([
                'NAME' => 'Доставка',
                'PRICE' => number_format($nDelivery, 2, '.', ''),
                'QUANTITY' => 1.0,
                'PRICE_EXCLUSIVE' => number_format($nDelivery, 2, '.', ''),
                'PRICE_NETTO' => number_format($nDelivery, 2, '.', ''),
                'PRICE_BRUTTO' => number_format($nDelivery, 2, '.', ''),
                'DISCOUNT_RATE' => 0.0,
                'DISCOUNT_SUM' => '0.0000',
                'TAX_RATE' => 0.0,
                'TAX_INCLUDED' => 'Y',
                'MEASURE_CODE' => '',
                'MEASURE_NAME' => 'шт',
                'PRICE_RAW' => number_format($nDelivery, 2, '.', ''),
                'PRICE_RAW_NETTO' => number_format($nDelivery, 2, '.', ''),
                'CUSTOMIZED' => 'Y',
                'DISCOUNT_TYPE_ID' => '',
                'CURRENCY_ID' => 'RUB',
                'PRICE_RAW_SUM' => number_format($nDelivery, 2, '.', ''),
                'TAX_VALUE_SUM' => 0,
                'PRICE_EXCLUSIVE_SUM' => number_format($nDelivery, 2, '.', ''),
                'PRICE_SUM' => number_format($nDelivery, 2, '.', ''),
            ]);
        }
    }
);


AddEventHandler("sale", "OnSaleStatusOrder", "addTaskForProduct");
function addTaskForProduct($ID, $val){
    if($val == "F"){
        Bitrix\Main\Loader::includeModule("sale");
        Bitrix\Main\Loader::includeModule("catalog");
        Bitrix\Main\Loader::includeModule("crm");
        Bitrix\Main\Loader::includeModule("tasks");

        $order = Bitrix\Sale\Order::load($ID);
        if(!$order->isCanceled()){

            $date_insert = $order->getField("DATE_INSERT")->format("d.m.Y");

            $arProducts = [];
            $basketRes = Bitrix\Sale\Internals\BasketTable::getList([
                'filter' => [
                    'ORDER_ID' => $ID
                ],
                'select' => [
                    'ORDER_ID', 'PRODUCT_ID', 'NAME', 'ID', 'CONTACT_COMPANY', 'COMPANY', 'CONTACT'
                ],
                'runtime' => [
                    'CONTACT_COMPANY' => [
                        'data_type' => '\Bitrix\Crm\Binding\OrderContactCompanyTable',
                        'reference' => [
                            '=this.ORDER_ID' => 'ref.ORDER_ID',
                        ],
                    ],
                    'COMPANY' => [
                        'data_type' => '\Bitrix\Crm\CompanyTable',
                        'reference' => [
                            '=this.CONTACT_COMPANY.ENTITY_ID' => 'ref.ID',
                        ],
                    ],
                    'CONTACT' => [
                        'data_type' => '\Bitrix\Crm\ContactTable',
                        'reference' => [
                            '=this.CONTACT_COMPANY.ENTITY_ID' => 'ref.ID',
                        ],
                    ]
                ],
            ]);
            while ($item = $basketRes->fetch()) {
                $mxResult = CCatalogSku::GetProductInfo($item['PRODUCT_ID']);

                $id = (is_array($mxResult) ? $mxResult['ID'] : $item['PRODUCT_ID']);

                $contact = [
                    'ID' => $item['SALE_INTERNALS_BASKET_CONTACT_ID'],
                    'FULL_NAME' => $item['SALE_INTERNALS_BASKET_CONTACT_FULL_NAME'],
                ];

                $arProducts[$id]['CONTACT'] = $contact;

                if(!isset($arProducts[$id]['COMPANY']) || empty($arProducts[$id]['COMPANY']['ID'])){
                    $company = [
                        'ID' => $item['SALE_INTERNALS_BASKET_COMPANY_ID'],
                        'TITLE' => $item['SALE_INTERNALS_BASKET_COMPANY_TITLE'],
                    ];

                    $arProducts[$id]['COMPANY'] = $company;
                }
            }
            if(!empty($arProducts)){

                $prop = CIBlockPropertyEnum::GetList([],[
                    "IBLOCK_ID" => 30,
                    "CODE" => "PERIOD_USE"
                ]);
                while($fields = $prop->GetNext()){
                    $pediodUse[$fields['ID']] = $fields['XML_ID'];
                }

                $today = new \Bitrix\Main\Type\DateTime(date('Y-m-d H:i:s',time()),'Y-m-d H:i:s');

                $res = CIBlockElement::GetList([], ['ID' => array_keys($arProducts), '!PROPERTY_RESPONSIBLE' => false, '!PROPERTY_PERIOD_USE' => false], false, false, ['ID', 'NAME', 'PROPERTY_RESPONSIBLE', 'PROPERTY_PERIOD_USE']);
                while($fields = $res->GetNext()){
                    $add = preg_match("/_/", $pediodUse[$fields['PROPERTY_PERIOD_USE_ENUM_ID']]) ? str_replace("_", " ", $pediodUse[$fields['PROPERTY_PERIOD_USE_ENUM_ID']]) : preg_replace("/[^0-9]/", '', $fields['PROPERTY_PERIOD_USE_VALUE']) . " months";

                    $name = ($arProducts[$fields['ID']]['COMPANY']['ID'] <> '' ? $arProducts[$fields['ID']]['COMPANY']['TITLE'] : $arProducts[$fields['ID']]['CONTACT']['FULL_NAME']) . " период использования товара";

                    $description = "Необходимо проработать покупателя на основании <a href='https://".SITE_SERVER_NAME."/shop/orders/details/" . $ID . "/'>заказа №". $ID  ." от " . $date_insert . "</a><br/>";
                    if($arProducts[$fields['ID']]['COMPANY']['ID'] <> '')
                        $description .= "Компания <a href='https://".SITE_SERVER_NAME."/crm/company/details/".$arProducts[$fields['ID']]['COMPANY']['ID']."/'>".$arProducts[$fields['ID']]['COMPANY']['TITLE']."</a><br/>";
                    if($arProducts[$fields['ID']]['CONTACT']['ID'] <> '')
                        $description .= "Клиент <a href='https://".SITE_SERVER_NAME."/crm/contact/details/".$arProducts[$fields['ID']]['CONTACT']['ID']."/'>".$arProducts[$fields['ID']]['CONTACT']['FULL_NAME']."</a><br/>";

                    $description .= "Товар - " . $fields['NAME'] . ". Период использования - " . $fields['PROPERTY_PERIOD_USE_VALUE'];

                    $obTask = new CTasks;
                    $ID = $obTask->Add([
                        "TITLE" => $name,
                        "DESCRIPTION" => $description,
                        "DEADLINE" => $today->add($add),
                        "RESPONSIBLE_ID" => $fields['PROPERTY_RESPONSIBLE_VALUE'],
                        "CREATED_BY" => 1,
                        //"ACCOMPLICES" => [4035]
                    ]);
                }
            }
        }
    }
}

AddEventHandler('calendar', 'OnAfterCalendarEventEdit', 'OnAfterCalendarEventEditHandler');
function OnAfterCalendarEventEditHandler($arFields, $bNew, $userId){
    $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
    $postList = $request->getPostList();
    $type = $postList['event_type'];
    $eventID = $arFields['ID'];

    $result = RtopTypeEventTable::getList(['filter' => ['EVENT_ID' => $eventID]])->fetch();
    if(empty($result))
    {
        RtopTypeEventTable::add(['EVENT_ID' => $eventID, 'TYPE' => $type]);
    }
    elseif($result['TYPE'] != $type)
    {
        RtopTypeEventTable::update($result['ID'], ['TYPE' => $type]);
    }
}

AddEventHandler('calendar', 'OnAfterCalendarEventDelete', 'OnAfterCalendarEventDeleteHandler');
function OnAfterCalendarEventDeleteHandler($ID){
    $result = RtopTypeEventTable::getList(['filter' => ['EVENT_ID' => $ID]])->fetch();
    if(!empty($result))
    {
        RtopTypeEventTable::delete($result['ID']);
    }
}

AddEventHandler("crm", "OnAfterCrmControlPanelBuild", "setBuldEditCompanyMenu");
function setBuldEditCompanyMenu(&$items)
{
    global $USER;
    if(!$USER->IsAdmin())
        return;

    $items[] = array(
        "ID" => "BULDEDIT",
        "NAME" => 'Редактирование по списку',
        "TITLE" => 'Редактирование по списку',
        "URL" => "/crm/company/bulk_edit/",
        "MENU_ID" => "menu_crm_buldedit",
    );
}

AddEventHandler("sale", 'OnSalePropertyValueSetField', 'OnSaleOrderSetFieldDentlman');
function OnSaleOrderSetFieldDentlman($entity, $name, $value, $old_value)
{
    if($entity->getField('CODE') != 'EMAIL' || !empty($old_value))
        return;

    $order = $entity->getOrder();
    if($order->getSiteId() != 'dm')
        return;

    $ORDER_ID = $order->getId();

    \Bitrix\Main\Loader::includeModule('crm');
    $data = \Bitrix\Crm\Binding\OrderContactCompanyTable::getList([
        'filter' => [
            'ORDER_ID' => $ORDER_ID
        ]
    ])->fetchAll();

    $companyId = 0;
    $contactId = 0;

    if(in_array($order->getPersonTypeId(), [2,5]))
    {
        $ar = \CCrmFieldMulti::GetList(
            [],
            [
                'ENTITY_ID' => 'COMPANY',
                'VALUE' => $value,
            ]
        )->Fetch();

        if(empty($ar))
            return;

        $companyId = $ar['ELEMENT_ID'];

        $CC = \Bitrix\Crm\Binding\ContactCompanyTable::getList(['filter' => ['COMPANY_ID' => $contactId], 'select' => ['CONTACT_ID']])->fetch();
        $contactId = $CC['CONTACT_ID'];
    }
    else
    {
        $ar = \CCrmFieldMulti::GetList(
            [],
            [
                'ENTITY_ID' => 'CONTACT',
                'VALUE' => $value,
            ]
        )->Fetch();

        if(empty($ar))
            return;

        $contactId = $ar['ELEMENT_ID'];
    }

    foreach($data as $item)
    {
        \Bitrix\Crm\Binding\OrderContactCompanyTable::update($item['ID'], ['ENTITY_ID' => ($item['ENTITY_TYPE_ID'] == \CCrmOwnerType::Contact ? $contactId : $companyId)]);
        if($item['ENTITY_TYPE_ID'] == \CCrmOwnerType::Contact)
        {
            $ob = new \CCrmContact(false);
            $ob->Delete($item['ENTITY_ID']);
        }
        else
        {
            $ob = new \CCrmCompany(false);
            $ob->Delete($item['ENTITY_ID']);
        }
    }
}

$eventManager->addEventHandler("sale", "OnBeforeOrderUpdate", "OnBeforeOrderUpdateHandler");
function OnBeforeOrderUpdateHandler($orderID, &$arFields)
{
    $order = \Bitrix\Sale\Order::load($orderID);
    $arFields['PERSON_TYPE_ID'] = $order->getPersonTypeId();

    $logger = Logger::getLogger('OnBeforeOrderUpdate', 'OnBeforeOrderUpdate');
    $logger->log('orderID = ' . $orderID);
    $logger->log('fields = ');
    $logger->log($arFields);
}