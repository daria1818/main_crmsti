<?
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;

Extension::load(['ui.buttons', 'ui.buttons.icons']);

//$APPLICATION->SetTitle(Loc::getMessage('KPI_HISTORY_TITLE'));

// Prepare toolbar
$bodyClass = $APPLICATION->GetPageProperty('BodyClass', '');
$arBodyClass = explode(' ', $bodyClass);
$arBodyClass[] = 'pagetitle-toolbar-field-view';
$APPLICATION->SetPageProperty('BodyClass', implode(' ', $arBodyClass));


$sFilterViewTarget = 'inside_pagetitle';
$this->SetViewTarget($sFilterViewTarget, 100);
?><div class="pagetitle-container pagetitle-flexible-space"><?
$APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', [
    'FILTER_ID' => $arResult['FILTER_ID'],
    'GRID_ID' => $arResult['GRID_ID'],
    'FILTER' => $arResult['UI_FILTER'],
    'ENABLE_LIVE_SEARCH' => true,
    'ENABLE_LABEL' => true,
    'THEME' => 'ROUNDED',
], $component, ['HIDE_ICONS' => 'Y']);
?>
</div><?
$this->EndViewTarget();


$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
    'GRID_ID' => $arResult['GRID_ID'],
    'COLUMNS' => $arResult['COLUMNS'],
    'ROWS' => $arResult['ROWS'],
    'SHOW_ROW_CHECKBOXES' => $arParams['GRID_SHOW_ROW_CHECKBOXES'] ?? false,
    'NAV_OBJECT' => $arResult['NAV_OBJECT'],
    'AJAX_MODE' => 'Y',
    'AJAX_ID' => CAjax::GetComponentID('bitrix:main.ui.grid', '.default', ''),
    'PAGE_SIZES' => [
        ['NAME' => '20', 'VALUE' => '20'],
        ['NAME' => '50', 'VALUE' => '50'],
        ['NAME' => '100', 'VALUE' => '100'],
    ],
    'AJAX_OPTION_JUMP' => 'Y',
    'SHOW_CHECK_ALL_CHECKBOXES' => false,
    'SHOW_ROW_ACTIONS_MENU' => true,
    'SHOW_GRID_SETTINGS_MENU' => true,
    'SHOW_NAVIGATION_PANEL' => true,
    'SHOW_PAGINATION' => true,
    'SHOW_SELECTED_COUNTER' => true,
    'SHOW_TOTAL_COUNTER' => true,
    'SHOW_PAGESIZE' => true,
    'ACTION_PANEL' => $arParams['CONTROL_PANEL'] ?? false,
    'SHOW_ACTION_PANEL' => true,
    'ALLOW_COLUMNS_SORT' => $arParams['GRID_ALLOW_COLUMNS_SORT'] ?? true,
    'ALLOW_COLUMNS_RESIZE' => $arParams['GRID_ALLOW_COLUMNS_RESIZE'] ?? true,
    'ALLOW_HORIZONTAL_SCROLL' => $arParams['GRID_ALLOW_HORIZONTAL_SCROLL'] ?? true,
    'ALLOW_SORT' => $arParams['GRID_ALLOW_SORT'] ?? true,
    'ALLOW_PIN_HEADER' => $arParams['GRID_ALLOW_PIN_HEADER'] ?? true,
    'AJAX_OPTION_HISTORY' => $arParams['GRID_AJAX_OPTION_HISTORY'] ?? 'N',
    'TOTAL_ROWS_COUNT' => $arResult['NAV_OBJECT']->getRecordCount(),
], false, ['HIDE_ICONS' => 'Y']);
?>