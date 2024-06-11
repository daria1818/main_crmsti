<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

global $APPLICATION;

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!Loader::includeModule('crm'))
{
	return;
}

if(!function_exists('__getProductPrice'))
{
	function __getProductPrice($id)
	{
		$result = ["PRICE" => 0, "QUANTITY" => 0];

		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);		
		global $USER;
		$arPrice = CCatalogProduct::GetOptimalPrice($id, 1, $USER->GetUserGroupArray(), 'N');
		$result["PRICE"] = $arPrice['DISCOUNT_PRICE'];
		$res = CCatalogProduct::GetList([], ["ID" => $id], false, false, ["ID", "QUANTITY"]);		
		if($amount = $res->fetch()){
			$result["QUANTITY"] = $amount["QUANTITY"];
		}
		echo CUtil::PhpToJSObject($result);
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

Loc::loadMessages(__FILE__);

$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
if($request->get('action') == "getProductPrice")
{
	CUtil::JSPostUnescape();
	$APPLICATION->RestartBuffer();
	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	Loader::includeModule('catalog');
	__getProductPrice($request->get('id'));	
}

$signer = new \Bitrix\Main\Security\Sign\Signer;

try
{
	$params = $signer->unsign($request->get('signedParameters'), 'iframe.create.order');
	$params = unserialize(base64_decode($params), ['allowed_classes' => false]);
}
catch (\Bitrix\Main\Security\Sign\BadSignatureException $e)
{
	die();
}

$request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter);

global $APPLICATION;

$APPLICATION->IncludeComponent(
	'rtop:iframe.create.order',
	'',
	$params
);