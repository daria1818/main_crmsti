<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;

$arProducts = [
    [
        'id' => 'tab_products',
        'name' => Loc::getMessage('RM_CRM_COMPANY_DETAILS_PRODUCTS_LABEL'),
        'loader' => [
            'serviceUrl' => '/local/components/pwd/crm.product/ajax.simple.php?ENTITY_ID='.$arResult['ENTITY_ID'],
        ]
    ]
];

$nPos = 0;
foreach($arResult['TABS'] as $n => $arItem){
    if($arItem['id'] == 'tab_order'){
        $nPos = $n;
        break;
    }
}

array_splice($arResult['TABS'], $nPos + 1, 0, $arProducts);
?>
