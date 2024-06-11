<?php
use Bitrix\Main\EventManager;
use Pwd\EventHandler\Iblock;
use Pwd\Tools\Logger;

$eventManager = EventManager::getInstance();
//// Обновлении товара
//$eventManager->addEventHandler(
//    "iblock",
//    "OnAfterIBlockElementUpdate",
//    [Iblock::class, 'OnAfterIBlockElementUpdateAdd']
//);
//$eventManager->addEventHandler(
//    "iblock",
//    "OnAfterIBlockElementAdd",
//    [Iblock::class, 'OnAfterIBlockElementUpdateAdd']
//);

$eventManager->addEventHandler("sale", "OnOrderAdd", "OnOrderAddHandler");
function OnOrderAddHandler($orderID, $arFields)
{
	$logger = Logger::getLogger('OnOrderAdd_test', 'OnOrderAdd_test');
	$logger->log('orderID = ' . $orderID);
	$logger->log('fields = ');
	$logger->log($arFields);
}
$eventManager->addEventHandler("crm", "OnAfterCrmDealUpdate", "OnAfterCrmDealUpdateHandler");
function OnAfterCrmDealUpdateHandler(&$arFields)
{
	$logger = Logger::getLogger('OnOrderAdd_test', 'OnOrderAdd_test');
	$logger->log('fields = ');
	$logger->log($arFields);
}