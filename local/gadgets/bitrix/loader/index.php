<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?\Bitrix\Main\UI\Extension::load("ui.buttons"); ?>

<? 
if (isset($_FILES['xls-file']) && !empty($_FILES['xls-file'])) {
	$count = 0;
	$offerCount = 0;
	$iblockOffersId = 0;

	if (($handle = fopen($_FILES['xls-file']['tmp_name'], "r")) !== FALSE) {
		if ($iblockInfo = CCatalog::GetByID($arGadget["SETTINGS"]["IBLOCK_ID"])) {
			if (isset($iblockInfo['OFFERS_IBLOCK_ID']) && intval($iblockInfo['OFFERS_IBLOCK_ID']) > 0) {
				$iblockOffersId = intval($iblockInfo['OFFERS_IBLOCK_ID']);
			}
		}

	    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
	        if ($data[0]) {
	        	CModule::IncludeModule('iblock');
	        	CModule::IncludeModule('catalog');

	        	$rsData = CIBlockElement::GetList([], ['IBLOCK_ID' => $arGadget["SETTINGS"]["IBLOCK_ID"], 'PROPERTY_CML2_ARTICLE' => trim($data[0])], false, false, ['ID']);

	        	if ($arElement = $rsData->fetch()) {
	        		CIBlockElement::SetPropertyValuesEx($arElement['ID'], $arGadget["SETTINGS"]["IBLOCK_ID"], [$arGadget["SETTINGS"]["PROPERTIES"] => $data[1]]);
	        		$count++;

	        		if ($iblockOffersId > 0) {
	        			$rsData = CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockOffersId, 'PROPERTY_CML2_LINK' => $arElement['ID']], false, false, ['ID']);

			        	while($arElementOffer = $rsData->fetch()) {
			        		CIBlockElement::SetPropertyValuesEx($arElementOffer['ID'], $iblockOffersId, [$arGadget["SETTINGS"]["PROPERTIES"] => $data[1]]);
			        		$offerCount++;
			        	}
		        	}
	        	}
	        }
	    }
	    
	    fclose($handle);

	    ShowMessage(['TYPE' => 'OK', 'MESSAGE' => 'Обновлено ' . $count . ' товаров и ' . $offerCount . ' торговых предложений']);
	} else {
		ShowError('Не получается прочитать файл');
	}
} 
?>

<form method="post" enctype='multipart/form-data'>
	<div>
		<label>Выберите файл csv</label>
		<div style="margin-top: 5px;"><input type="file" name="xls-file" required accept=".csv"></div>
	</div>
	<button type="submit" class="ui-btn ui-btn-success" style="margin-top: 10px;">Загрузить</button>
</form>
