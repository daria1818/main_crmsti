<?php

$siteId = '';
if(isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
{
	$siteId = mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site_id']), 0, 2);
}

if($siteId)
{
	define('SITE_ID', $siteId);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$component = unserialize($_SESSION['CUSTOM_DEAL_INFO']) ?? ['ENTITY_ID' => $request->get('ownerId')];

$APPLICATION->IncludeComponent(
    'bitrix:ui.sidepanel.wrapper',
    '',
    [
        'POPUP_COMPONENT_NAME' => 'rtop:iframe.create.order',
        'POPUP_COMPONENT_TEMPLATE_NAME' => '',
        'POPUP_COMPONENT_PARAMS' => $component,
        'USE_UI_TOOLBAR' => 'Y',
		'USE_PADDING' => false,
		'PLAIN_VIEW' => false,
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL' => "/crm/deal/details/".$request->get('ownerId')."/",
    ]
);

// $APPLICATION->IncludeComponent(
// 	'bitrix:ui.sidepanel.wrapper',
// 	'',
// 	[
// 		'POPUP_COMPONENT_NAME' => 'bitrix:salescenter.app',
// 		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
// 		'POPUP_COMPONENT_PARAMS' => [
// 			'dialogId' => $request->get('dialogId'),
// 			'sessionId' => $request->get('sessionId'),
// 		],
// 		'USE_PADDING' => false,
// 		'USE_UI_TOOLBAR' => 'Y',
// 	]
// );

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');