<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use \Bitrix\Highloadblock\HighloadBlockTable;
$arResult['CUSTOM_FAILURE_LIST'] = [];
if($arResult['ENTITY_TYPE_ID'] === CCrmOwnerType::Order){

	\Bitrix\Main\Loader::includeModule('highloadblock');

	$hlblock = HighloadBlockTable::getById(29)->fetch();

	$entity = HighloadBlockTable::compileEntity($hlblock); 
	$entity_data_class = $entity->getDataClass(); 
	$result = $entity_data_class::getList([])->fetchAll();
	foreach($result as $item){
		$arResult['CUSTOM_FAILURE_LIST'][] = $item;
	}
}
?>