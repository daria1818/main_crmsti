<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Highloadblock\HighloadBlockTable;

\Bitrix\Main\Loader::includeModule('highloadblock');

$hlblock = HighloadBlockTable::getById(27)->fetch();

$entity = HighloadBlockTable::compileEntity($hlblock); 
$entity_data_class = $entity->getDataClass(); 
$result = $entity_data_class::getList([])->fetchAll();

$types = array_column($result, 'UF_NAME', 'UF_CODE');

foreach($types as $code => $type){
	$arResult['CUSTOM_TYPE_EVENT'][$code] = ['NAME' => $type];
}

$eventID = $arParams['event']['ID'];

$result = RtopTypeEventTable::getList(['filter' => ['EVENT_ID' => $eventID]])->fetch();

if(!empty($result)){
	$arResult['CUSTOM_TYPE_EVENT'][$result['TYPE']]['SELECTED'] = 'selected';
}