<?php
use Bitrix\Crm\Activity\CommunicationWidgetPanel;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\UI;

UI\Extension::load("ui.tooltip");

/* @global CMain $APPLICATION */
/* @var array $arResult */

global $APPLICATION;
$APPLICATION->SetTitle($arResult['PAGE_TITLE']);
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
CJSCore::Init(array('amcharts', 'amcharts_pie'));

$element = $arResult['ELEMENT'];
$loadbars = $arResult['LOADBARS'];
$primaryBar = $loadbars['primary'];
unset($loadbars['primary']);

$comments = \CrmClientPortraitComponent::prepareComments($element['COMMENTS']);

$rowData = CommunicationWidgetPanel::getPortraitRowData($arResult['ENTITY_TYPE_ID']);

if (isset($arParams['IS_FRAME']) && $arParams['IS_FRAME'] === 'Y' && empty($arParams['IS_FRAME_RELOAD'])):?>
	<div class="pagetitle-wrap">
		<div class="pagetitle-inner-container">
			<div class="pagetitle-container pagetitle-flexible-space" style="overflow: hidden;">
				<?
				$APPLICATION->ShowViewContent("inside_pagetitle");
				?>
			</div>
		</div>
	</div>
<?endif?>
	<div class="crm-portriat">
		<div class="crm-portrait-title"><?=$arResult['PAGE_TITLE']?></div>
		<? $APPLICATION->ShowViewContent('widget_panel_head'); ?>
    </div>


	<?$APPLICATION->IncludeComponent(
		'pwd:crm.widget.report',
		'',
		[
			'ENTITY_ID' => $arResult['ELEMENT']['ID'],
			'GUID' => mb_strtolower($arResult['ENTITY_TYPE']).'_portrait'
		]
	);?>