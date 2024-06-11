<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?
$arButtons = [];
foreach($arResult['BUTTONS'] as $arItem){
    if($arItem['ICON'] == 'btn-crm-product-export' || $arItem['NEWBAR']){
        $arButtons[] = $arItem;
    }
}

$arResult['BUTTONS'] = $arButtons;
?>
