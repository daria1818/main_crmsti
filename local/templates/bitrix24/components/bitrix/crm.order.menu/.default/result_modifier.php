<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if($arParams['ORDER_ID'] && is_int($arParams['ORDER_ID'])) {
    $arResult['BUTTONS'][] = [
        'TEXT' => 'Создать сделку',
        'TITLE' => 'Создать сделку',
        'ONCLICK' => "BX.Crm.Page.openPage('/crm/deal/details/0/?order_id=".$arParams['ORDER_ID']."')",
        'ICON' => "btn-copy",
        'TYPE' => 'crm-deal-button',
        'ELEMENTID' => $arParams['ELEMENT_ID'] ?? ''
    ];
}
?>
