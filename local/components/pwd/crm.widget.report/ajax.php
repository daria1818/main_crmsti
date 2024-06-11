<?
use Bitrix\Main\Application, 
    Bitrix\Main\Context, 
    Bitrix\Main\Request, 
    Bitrix\Main\Server,
    Bitrix\Main\UI\Filter\Options;
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

CBitrixComponent::includeComponentClass("pwd:crm.widget.report");

$guid = Context::getCurrent()->getRequest()->getPost('guid');
$entity_id = Context::getCurrent()->getRequest()->getPost('entity_id');
$filterOption = new Options($guid);
$filterData = $filterOption->getFilter([]);

$arResult = (new CrmReport)->getResult($entity_id, $filterData['PERIOD_from'], $filterData['PERIOD_to']);

$json = [
    'deals_all_count' => $arResult['DEALS']['ALL']['COUNT'] ?? '0',
    'deals_all_summ' => $arResult['DEALS']['ALL']['SUMM'] ?? '0',
    'deals_items_f_count' => $arResult['DEALS']['ITEMS']['F']['COUNT'] ?? '0',
    'deals_items_f_summ' => $arResult['DEALS']['ITEMS']['F']['SUMM'] ?? '0',
    'deals_items_d_count' => $arResult['DEALS']['ITEMS']['D']['COUNT'] ?? '0',
    'deals_items_d_summ' => $arResult['DEALS']['ITEMS']['D']['SUMM'] ?? '0',
    'orders_all_count' => $arResult['ORDERS']['ALL']['COUNT'] ?? '0',
    'orders_all_summ' => $arResult['ORDERS']['ALL']['SUMM'] ?? '0',
    'orders_items_f_count' => $arResult['ORDERS']['ITEMS']['F']['COUNT'] ?? '0',
    'orders_items_f_summ' => $arResult['ORDERS']['ITEMS']['F']['SUMM'] ?? '0',
    'orders_items_d_count' => $arResult['ORDERS']['ITEMS']['D']['COUNT'] ?? '0',
    'orders_items_d_summ' => $arResult['ORDERS']['ITEMS']['D']['SUMM'] ?? '0'
];

echo json_encode($json);