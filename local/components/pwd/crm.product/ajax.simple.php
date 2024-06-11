<?require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_before.php");?>
<?php

use Bitrix\Main\Application;
$arRequest = Application::getInstance()->getContext()->getRequest()->toArray();

global $APPLICATION;
Header('Content-Type: text/html; charset='.LANG_CHARSET);
$APPLICATION->ShowAjaxHead();
?>
<?
$APPLICATION->IncludeComponent(
    "pwd:crm.product",
    ".default",
    array(
        "SEF_MODE" => "N",
        "ENTITY_ID" => ($arRequest['ENTITY_ID'] ? $arRequest['ENTITY_ID'] : 0),
        "CATALOG_ID" => CATALOG_IBLOCK,
        "SEF_FOLDER" => "/crm/product/"
    ),
    false
);
?>
