<?
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
if (!$request->isPost())
	return;

global $APPLICATION;

$APPLICATION->IncludeComponent("rtop:custom.reports", "", array());
?>