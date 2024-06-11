<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Api\Classes\Entity\OrderContactCompanyTable;
use Api\Classes\Entity\BasketTable;

/** @global CMain $APPLICATION */
global $USER, $DB, $APPLICATION;

$sExportType = !empty($arParams['EXPORT_TYPE']) ?
    strval($arParams['EXPORT_TYPE']) : (!empty($_REQUEST['type']) ? strval($_REQUEST['type']) : '');
$isInExportMode = false;

$isStExport = false;    // Step-by-step export mode
if (!empty($sExportType))
{
    $sExportType = mb_strtolower(trim($sExportType));
    switch ($sExportType)
    {
        case 'csv':
        case 'excel':
            $isInExportMode = true;
            $isStExport = (isset($arParams['STEXPORT_MODE']) && $arParams['STEXPORT_MODE'] === 'Y');
            break;
        default:
            $sExportType = '';
    }
}
$arResult['IS_EXPORT_MODE'] = $isInExportMode ? 'Y' : 'N';
$arResult['EXPORT_TYPE'] = $isInExportMode ? $sExportType : '';
$isStExportAllFields = ((isset($arParams['STEXPORT_INITIAL_OPTIONS']['EXPORT_ALL_FIELDS'])
        && $arParams['STEXPORT_INITIAL_OPTIONS']['EXPORT_ALL_FIELDS'] === 'Y')
    || (isset($arParams['STEXPORT_EXPORT_ALL_FIELDS'])
        && $arParams['STEXPORT_EXPORT_ALL_FIELDS'] === 'Y'));
$arResult['STEXPORT_EXPORT_ALL_FIELDS'] = ($isStExport && $isStExportAllFields) ? 'Y' : 'N';
$isStExportIncludeSubsections = (isset($arParams['STEXPORT_INITIAL_OPTIONS']['INCLUDE_SUBSECTIONS'])
    && $arParams['STEXPORT_INITIAL_OPTIONS']['INCLUDE_SUBSECTIONS'] === 'Y');
$arResult['STEXPORT_INCLUDE_SUBSECTIONS'] = ($isStExport && $isStExportIncludeSubsections) ? 'Y' : 'N';
$arResult['STEXPORT_MODE'] = $isStExport ? 'Y' : 'N';
$arResult['STEXPORT_TOTAL_ITEMS'] = isset($arParams['STEXPORT_TOTAL_ITEMS']) ?
    (int)$arParams['STEXPORT_TOTAL_ITEMS'] : 0;

$isErrorOccured = false;
$errorMessage = '';

if (!$isErrorOccured && !CModule::IncludeModule('crm'))
{
    $errorMessage = GetMessage('CRM_MODULE_NOT_INSTALLED');
    $isErrorOccured = true;
}

if (!$isErrorOccured && !CCrmSecurityHelper::IsAuthorized())
{
    $errorMessage = GetMessage('CRM_PERMISSION_DENIED');
    $isErrorOccured = true;
}

/** @var $CrmPerms CCrmPerms */
$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if (!$isErrorOccured
    && !(CCrmPerms::IsAccessEnabled($CrmPerms)
        && $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ')))
{
    $errorMessage = GetMessage('CRM_PERMISSION_DENIED');
    $isErrorOccured = true;
}

if ($isErrorOccured)
{
    if ($isStExport)
    {
        return array('ERROR' => $errorMessage);
    }
    else
    {
        ShowError($errorMessage);
        return;
    }
}

$arResult['CAN_DELETE'] = $arResult['CAN_EDIT'] = $arResult['CAN_ADD_SECTION'] = $arResult['CAN_EDIT_SECTION'] =
    $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

$arParams['PATH_TO_PRODUCT_LIST'] = CrmCheckPath('PATH_TO_PRODUCT_LIST', $arParams['PATH_TO_PRODUCT_LIST'], $APPLICATION->GetCurPage().'?section_id=#section_id#');
$arParams['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath('PATH_TO_PRODUCT_SHOW', $arParams['PATH_TO_PRODUCT_SHOW'], $APPLICATION->GetCurPage().'?product_id=#product_id#&show');
$arParams['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath('PATH_TO_PRODUCT_EDIT', $arParams['PATH_TO_PRODUCT_EDIT'], $APPLICATION->GetCurPage().'?product_id=#product_id#&edit');
$arParams['~PATH_TO_PRODUCT_FILE'] = CrmCheckPath(
    'PATH_TO_PRODUCT_FILE', $arParams['~PATH_TO_PRODUCT_FILE'],
    $APPLICATION->GetCurPage().'?product_id=#product_id#&field_id=#field_id#&file_id=#file_id#&file'
);
$arParams['PATH_TO_PRODUCT_FILE'] = htmlspecialcharsbx($arParams['~PATH_TO_PRODUCT_FILE']);

// prepare URI template
$curParam = $APPLICATION->GetCurParam();
$curParam = preg_replace('/(^|[^\w])bxajaxid=[\d\w]*([^\d\w]|$)/', '', $curParam);
$curParam = preg_replace('/(?<!\w)list_section_id=\d*(?=([^\d]|$))/', 'list_section_id=#section_id#', $curParam);
$curParam = preg_replace('/(^|&)tree=\w*(?=(&|$))/', '', $curParam);
$arResult['PAGE_URI_TEMPLATE'] = $arParams['PATH_TO_PRODUCT_LIST'].($curParam <> '' ? '?'.$curParam.'&tree=Y' : '?tree=Y');
unset($curParam);

$arFilter = $arSort = array();
$bInternal = false;
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';

$bVatMode = $arResult['VAT_MODE'] = CCrmTax::isVatMode();

$arResult['VAT_RATE_LIST_ITEMS'] = array();
if ($bVatMode)
    $arResult['VAT_RATE_LIST_ITEMS'] = CCrmVat::GetVatRatesListItems();

// measure list items
$arResult['MEASURE_LIST_ITEMS'] = array('' => GetMessage('CRM_MEASURE_NOT_SELECTED'));
$measures = \Bitrix\Crm\Measure::getMeasures(100);
if (is_array($measures))
{
    foreach ($measures as $measure)
        $arResult['MEASURE_LIST_ITEMS'][$measure['ID']] = $measure['SYMBOL'];
    unset($measure);
}
unset($measures);

if (isset($arResult['PRODUCT_ID']))
{
    unset($arResult['PRODUCT_ID']);
}

if (!empty($arParams['INTERNAL_FILTER']) || $arResult['GADGET'] == 'Y')
{
    $bInternal = true;
}

$arResult['INTERNAL'] = $bInternal;
if (!empty($arParams['INTERNAL_FILTER']) && is_array($arParams['INTERNAL_FILTER']))
{
    $arParams['GRID_ID_SUFFIX'] = $this->GetParent() !== null ? $this->GetParent()->GetName() : '';
    $arFilter = $arParams['INTERNAL_FILTER'];
}

if (!empty($arParams['INTERNAL_SORT']) && is_array($arParams['INTERNAL_SORT']))
{
    $arSort = $arParams['INTERNAL_SORT'];
}

if (!isset($arParams['PRODUCT_COUNT']))
{
    $arParams['PRODUCT_COUNT'] = 20;
}

$arResult['GRID_ID'] = 'CRM_PRODUCT_LIST'.($bInternal ? '_'.$arParams['GRID_ID_SUFFIX'] : '');
$arResult['FILTER'] = $arResult['FILTER2LOGIC'] = $arResult['FILTER_PRESETS'] = array();

$arResult['CATALOG_TYPE_ID'] = CCrmCatalog::GetCatalogTypeID();
$catalogID = isset($arParams['~CATALOG_ID']) ? intval($arParams['~CATALOG_ID']) : 0;
if ($catalogID <= 0)
{
    $catalogID = CCrmCatalog::EnsureDefaultExists();
}
$arResult['CATALOG_ID'] = $catalogID;

$arResult['SECTION_LIST'] = array();

$arSections = array();
$arSections[''] = GetMessage('CRM_PRODUCT_LIST_FILTER_SECTION_ALL');
$arSections['0'] = GetMessage('CRM_PRODUCT_LIST_FILTER_SECTION_ROOT');
if (!$isInExportMode)
{
    $rsSections = CIBlockSection::GetList(
        array('left_margin' => 'asc'),
        array(
            'IBLOCK_ID' => $catalogID,
            /*'GLOBAL_ACTIVE' => 'Y',*/
            'CHECK_PERMISSIONS' => 'N'
        )
    );

    while($arSection = $rsSections->GetNext())
    {
        $arResult['SECTION_LIST'][$arSection['ID']] =
            array(
                'ID' => $arSection['ID'],
                'NAME' => $arSection['~NAME'],
                'LIST_URL' => str_replace(
                    '#section_id#',
                    $arSection['ID'],
                    $arResult['PAGE_URI_TEMPLATE']
                )
            );

        $arSections[$arSection['ID']] = str_repeat(' . ', $arSection['DEPTH_LEVEL']).$arSection['~NAME'];
    }
}

$arResult['FILTER'] = array(
    array(
        'id' => 'ID',
        'name' => GetMessage('CRM_COLUMN_ID'),
        'type' => 'string',
        'default' => true
    ),
    array(
        'id' => 'XML_ID',
        'name' => GetMessage('CRM_COLUMN_XML_ID'),
        'type' => 'string',
        'default' => false
    ),
    array(
        'id' => 'NAME',
        'name' => GetMessage('CRM_COLUMN_NAME'),
        'type' => 'string',
        'default' => true
    ),
    array(
        'id' => 'CODE',
        'name' => GetMessage('CRM_COLUMN_CODE'),
        'type' => 'string',
        'default' => false
    ),
    array(
        'id' => 'LIST_SECTION_ID',
        'name' => GetMessage('CRM_COLUMN_SECTION'),
        'type' => 'list',
        'default' => true,
        'items' => $arSections,
        'value' => '0'
    ),
    array(
        'id' => 'ACTIVE',
        'name' => GetMessage('CRM_COLUMN_ACTIVE'),
        'type' => 'list',
        'items' => array(
            '' => GetMessage('CRM_PRODUCT_LIST_FILTER_CHECKBOX_NOT_SELECTED'),
            'Y' => GetMessage('CRM_PRODUCT_LIST_FILTER_CHECKBOX_YES'),
            'N' => GetMessage('CRM_PRODUCT_LIST_FILTER_CHECKBOX_NO')
        )
    ),
    array(
        'id' => 'DESCRIPTION',
        'name' => GetMessage('CRM_COLUMN_DESCRIPTION')
    )
);
$arResult['FILTER_PRESETS'] = array();
//}
unset($arSections);

// Headers initialization -->
$arResult['HEADERS'] = array(
    array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'id', 'default' => false, 'editable' => false),
    array('id' => 'XML_ID', 'name' => GetMessage('CRM_COLUMN_XML_ID'), 'sort' => 'xml_id', 'default' => false, 'editable' => false),
    array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME'), 'sort' => 'name', 'default' => true, 'editable' => false, 'params' => array('size' => 45)),
    array('id' => 'CODE', 'name' => GetMessage('CRM_COLUMN_CODE'), 'sort' => 'name', 'default' => false, 'editable' => false, 'params' => array('size' => 45)),
    array('id' => 'PRICE', 'name' => GetMessage('CRM_COLUMN_PRICE'), 'default' => true, 'editable' => false),
    array('id' => 'MEASURE', 'name' => GetMessage('CRM_COLUMN_MEASURE'), 'default' => true, 'editable' => false),
    array('id' => 'ORDERS', 'name' => GetMessage('CRM_COLUMN_ORDERS'), 'default' => true, 'editable' => false)
);
if ($bVatMode)
{
    $arResult['HEADERS'][] = array('id' => 'VAT_ID', 'name' => GetMessage('CRM_COLUMN_VAT_ID'), 'default' => true, 'editable' =>false);
    $arResult['HEADERS'][] = array('id' => 'VAT_INCLUDED', 'name' => GetMessage('CRM_COLUMN_VAT_INCLUDED'), 'default' => true, 'editable' => false, 'type' => 'checkbox');
}
$arResult['HEADERS'] = array_merge(
    $arResult['HEADERS'],
    array(
        array('id' => 'SECTION_ID', 'name' => GetMessage('CRM_COLUMN_SECTION'), 'default' => true, 'editable' => false, 'type' => 'list'),
        array('id' => 'SORT', 'name' => GetMessage('CRM_COLUMN_SORT'), 'sort' => 'sort', 'default' => false, 'editable' => false),
        array('id' => 'ACTIVE', 'name' => GetMessage('CRM_COLUMN_ACTIVE'), 'sort' => 'active', 'default' => false, 'editable' => false, 'type' => 'checkbox'),
        array('id' => 'DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_DESCRIPTION'), 'sort' => 'description', 'default' => true, 'editable' => false),
        array('id' => 'PREVIEW_PICTURE', 'name' => GetMessage('CRM_PRODUCT_FIELD_PREVIEW_PICTURE'), 'sort' => 'preview_picture', 'default' => false, 'editable' => false),
        array('id' => 'DETAIL_PICTURE', 'name' => GetMessage('CRM_PRODUCT_FIELD_DETAIL_PICTURE'), 'sort' => 'detail_picture', 'default' => false, 'editable' => false),
    )
);
// <-- Headers initialization

// Product properties
// <editor-fold defaultstate="collapsed" desc="Product properties">
$exportProps = [];
if ($isInExportMode)
{
    $propUserTypeListExport = CCrmProductPropsHelper::GetPropsTypesByOperations(false, ['export']);
    $exportProps = CCrmProductPropsHelper::GetProps($catalogID, $propUserTypeListExport, ['export']);
    unset($propUserTypeListExport);
}
$arPropUserTypeList = CCrmProductPropsHelper::GetPropsTypesByOperations(false, array('view', 'filter'));
$arResult['PROP_USER_TYPES'] = $arPropUserTypeList;
$arProps = CCrmProductPropsHelper::GetProps($catalogID, $arPropUserTypeList);
$arResult['PROPS'] = $arProps;
$arFilterable = array();
$arCustomFilter = array();
$arDateFilter = array();
CCrmProductPropsHelper::ListAddFilterFields($arPropUserTypeList, $arProps, $arResult['GRID_ID'],
    $arResult['FILTER'], $arFilterable, $arCustomFilter, $arDateFilter);
CCrmProductPropsHelper::ListAddHeades($arPropUserTypeList, $arProps, $arResult['HEADERS']);
// </editor-fold>

$bTree = false;
// check hit from section tree
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_REQUEST['tree']))
{
    $bTree = ($_REQUEST['tree'] === 'Y');
    unset($_GET['tree'], $_REQUEST['tree']);
}

// Try to extract user action data -->
// We have to extract them before call of CGridOptions::GetFilter() or the custom filter will be corrupted.
$actionData = array(
    'METHOD' => $_SERVER['REQUEST_METHOD'],
    'ACTIVE' => false
);
if (!$isInExportMode && check_bitrix_sessid())
{
    $postAction = 'action_button_'.$arResult['GRID_ID'];
    $getAction = 'action_'.$arResult['GRID_ID'];
    if ($actionData['METHOD'] == 'POST')
    {
        if (isset($_POST[$postAction]))
        {
            $actionData['ACTIVE'] = true;

            $actionData['NAME'] = $_POST[$postAction];
            unset($_POST[$postAction], $_REQUEST[$postAction]);

            $allRows = 'action_all_rows_'.$arResult['GRID_ID'];
            $actionData['ALL_ROWS'] = false;
            if (isset($_POST[$allRows]))
            {
                $actionData['ALL_ROWS'] = $_POST[$allRows] == 'Y';
                unset($_POST[$allRows], $_REQUEST[$allRows]);
            }

            if (isset($_POST['ID']))
            {
                $actionData['ID'] = $_POST['ID'];
                unset($_POST['ID'], $_REQUEST['ID']);
            }

            if (isset($_POST['FIELDS']))
            {
                $actionData['FIELDS'] = $_POST['FIELDS'];
                unset($_POST['FIELDS'], $_REQUEST['FIELDS']);
            }

            $actionData['AJAX_CALL'] = false;
            if (isset($_POST['AJAX_CALL']))
            {
                $actionData['AJAX_CALL']  = true;
                // Must be transfered to main.interface.grid
                //unset($_POST['AJAX_CALL'], $_REQUEST['AJAX_CALL']);
            }
        }
        else if (isset($_POST['action']))
        {
            $actionData['ACTIVE'] = true;
            $actionData['NAME'] = $_POST['action'];
            unset($_POST['action'], $_REQUEST['action']);

            if ($actionData['NAME'] === 'ADD_SECTION')
            {
                $actionData['SECTION_NAME'] = trim(isset($_POST['sectionName']) ? $_POST['sectionName'] : '', " \n\r\t");
                unset($_POST['sectionName'], $_REQUEST['sectionName']);
            }
            else if ($actionData['NAME'] === 'RENAME_SECTION')
            {
                $actionData['RENAMED_SECTION_ID'] = isset($_POST['sectionID']) ? intval($_POST['sectionID']) : 0;
                $actionData['NEW_SECTION_NAME'] = trim(isset($_POST['sectionName']) ? $_POST['sectionName'] : '', " \n\r\t");
                unset($_POST['sectionID'], $_REQUEST['sectionID'], $_POST['sectionName'], $_REQUEST['sectionName']);
            }
        }
    }
    else if ($actionData['METHOD'] == 'GET' && isset($_GET[$getAction]))
    {
        $actionData['ACTIVE'] = true;

        $actionData['NAME'] = $_GET[$getAction];
        unset($_GET[$getAction], $_REQUEST[$getAction]);

        if (isset($_GET['ID']))
        {
            $actionData['ID'] = $_GET['ID'];
            unset($_GET['ID'], $_REQUEST['ID']);
        }

        $actionData['AJAX_CALL'] = false;
        if (isset($_GET['AJAX_CALL']))
        {
            $actionData['AJAX_CALL']  = true;
            // Must be transfered to main.interface.grid
            //unset($_GET['AJAX_CALL'], $_REQUEST['AJAX_CALL']);
        }
    }
}
// <-- Try to extract user action data

$CGridOptions = new CCrmGridOptions($arResult['GRID_ID']);

$stExportPageSize = ($isInExportMode && $isStExport && isset($arParams['STEXPORT_PAGE_SIZE'])) ?
    (int)$arParams['STEXPORT_PAGE_SIZE'] : (int)$arParams['PRODUCT_COUNT'];
$stExportPageNumber = isset($arParams['PAGE_NUMBER']) ? (int)$arParams['PAGE_NUMBER'] : 1;
$stExportEnableNextPage = false;
if ($isStExport)
{
    $arNavParams = [
        'nPageSize' => $stExportPageSize,
        'iNumPage' => $stExportPageNumber,
        'bShowAll' => false
    ];
}
else
{
    $arNavParams = CDBResult::GetNavParams(['nPageSize' => $arParams['PRODUCT_COUNT']]);
    $arNavParams = $CGridOptions->GetNavParams($arNavParams);
    $arNavParams['bShowAll'] = false;
}

$arFilter = $gridFilter = $CGridOptions->GetFilter($arResult['FILTER']);
$arFilter['CATALOG_ID'] = $catalogID;

$sectionID = isset($arParams['~SECTION_ID']) ? intval($arParams['~SECTION_ID']) : 0;

$bFilterSection = (
    $bTree
    || !isset($arFilter['GRID_FILTER_APPLIED'])
    || !$arFilter['GRID_FILTER_APPLIED']
    || (isset($arFilter['LIST_SECTION_ID']) && $arFilter['LIST_SECTION_ID'] !== '')
);
if ($bFilterSection)
{
    if (!$bTree
        && isset($arFilter['GRID_FILTER_APPLIED'])
        && $arFilter['GRID_FILTER_APPLIED']
        && isset($arFilter['LIST_SECTION_ID']))
    {
        $sectionID = intval($arFilter['LIST_SECTION_ID']);
    }
    if (!($isInExportMode && $isStExportIncludeSubsections && $sectionID === 0))
    {
        $arFilter['SECTION_ID'] = $sectionID;
    }
}
// reset section filter HACK
if (!is_array($_SESSION['main.interface.grid']))
    $_SESSION['main.interface.grid'] = array();
if (!is_array($_SESSION['main.interface.grid'][$arResult['GRID_ID']]))
    $_SESSION['main.interface.grid'][$arResult['GRID_ID']] = array();
if (!is_array($_SESSION['main.interface.grid'][$arResult['GRID_ID']]['filter']))
    $_SESSION['main.interface.grid'][$arResult['GRID_ID']]['filter'] = array();
if (is_array($_SESSION['main.interface.grid'][$arResult['GRID_ID']]['filter']))
    $_SESSION['main.interface.grid'][$arResult['GRID_ID']]['filter']['LIST_SECTION_ID'] = $bFilterSection ? strval($sectionID) : '';
if (!isset($arFilter['GRID_FILTER_APPLIED']) || !$arFilter['GRID_FILTER_APPLIED'])
    $_REQUEST['LIST_SECTION_ID'] = $_GET['LIST_SECTION_ID'] = $bFilterSection ? strval($sectionID) : '';

$arImmutableFilters = array('ID', 'SECTION_ID', 'LIST_SECTION_ID', 'CATALOG_ID', 'ACTIVE', 'GRID_FILTER_APPLIED', 'GRID_FILTER_ID');
foreach ($arFilter as $k => $v)
{
    if (in_array($k, $arImmutableFilters, true) || preg_match('/^PROPERTY_\d+(_from|_to)*$/', $k))
    {
        continue;
    }

    if (in_array($k, $arResult['FILTER2LOGIC']))
    {
        // Bugfix #26956 - skip empty values in logical filter
        $v = trim($v);
        if ($v !== '')
        {
            $arFilter['?'.$k] = $v;
        }
        unset($arFilter[$k]);
    }
    else if ($k != 'LOGIC')
    {
        $arFilter['%'.$k] = $v;
        unset($arFilter[$k]);
    }
}
foreach($gridFilter as $key => $value)
{
    if (mb_substr($key, -5) == "_from")
    {
        $op = ">=";
        $new_key = mb_substr($key, 0, -5);
    }
    else if (mb_substr($key, -3) == "_to")
    {
        $op = "<=";
        $new_key = mb_substr($key, 0, -3);
        if (array_key_exists($new_key, $arDateFilter))
        {
            if (!preg_match("/\\d\\d:\\d\\d:\\d\\d\$/", $value))
                $v = CCrmDateTimeHelper::SetMaxDayTime($v);
        }
    }
    else
    {
        $op = "";
        $new_key = $key;
    }

    if (array_key_exists($new_key, $arFilterable))
    {
        if ($op == "")
            $op = $arFilterable[$new_key];
        $arFilter[$op.$new_key] = $value;
        if ($op.$new_key !== $key)
            unset($arFilter[$key]);
    }
}
unset($gridFilter);
$arFilter['~REAL_PRICE'] = true;

$arResult['CUSTOM_FILTER_PROPERTY_VALUES'] = array();

foreach($arCustomFilter as $propID => $customFilter)
{
    if (isset($customFilter['type']) && $customFilter['type'] === 'propertyE')
    {
        $filterValues = array();
        $filterItems = array();

        if (is_array($_REQUEST[$propID]))
        {
            foreach ($_REQUEST[$propID] as $value)
            {
                if ($value > 0)
                {
                    $filterValues[(int)$value] = true;
                }
            }
        }
        $filterValues = array_keys($filterValues);

        $values = array();
        if (!empty($filterValues))
        {
            $res = CIBlockElement::GetList(
                array('NAME' => 'ASC'),
                array(
                    'IBLOCK_ID' => $arProp['LINK_IBLOCK_ID'],
                    'ID' => $filterValues
                ),
                false, false, array('ID', 'NAME')
            );
            while($row = $res->Fetch())
            {
                $values[] = (int)$row['ID'];
                $filterItems[$row['ID']] = $row['NAME'];
            }
            unset($res, $row);
        }

        $arResult['CUSTOM_FILTER_PROPERTY_VALUES'][$propID] = array(
            'values' => $values,
            'items' => $filterItems
        );

        if (!empty($values))
        {
            $arFilter[$propID] = $values;
        }

        unset($filterValues, $filterItems, $values);
    }
    else
    {
        $filtered = false;
        call_user_func_array($customFilter["callback"], array(
            $arProps[$propID],
            array(
                "VALUE" => $propID,
                "GRID_ID" => $arResult["GRID_ID"],
            ),
            &$arFilter,
            &$filtered,
        ));
    }
}


$arFilter['INCLUDE_SUBSECTIONS'] = 'Y';


//Show error message if required
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['error']))
{
    $errorID = mb_strtolower($_GET['error']);
    if (preg_match('/^crm_err_/', $errorID) === 1)
    {
        if (!isset($_SESSION[$errorID]))
        {
            LocalRedirect(CHTTP::urlDeleteParams($APPLICATION->GetCurPage(), array('error')));
        }

        $errorMessage = strval($_SESSION[$errorID]);
        unset($_SESSION[$errorID]);
        if ($errorMessage !== '')
        {
            ShowError(htmlspecialcharsbx($errorMessage));
        }
    }
}

// FILTERED SECTIONS
$arSectionFilter = array (
    'CHECK_PERMISSIONS' => 'N'
);
$arFiltrableFieldMap = array(
    'ID' => 'ID',
    'CATALOG_ID' => 'IBLOCK_ID',
    'SECTION_ID' => 'SECTION_ID',
    'NAME' => 'NAME',
    'CODE' => 'CODE',
    'XML_ID' => 'EXTERNAL_ID'
);
$arFiltrableField = array_keys($arFiltrableFieldMap);
$arIgnoreFilters = array('LIST_SECTION_ID', 'GRID_FILTER_APPLIED', 'GRID_FILTER_ID', '~REAL_PRICE');
$bSkipSections = $isInExportMode;
if (!$bSkipSections)
{
    foreach($arFilter as $k => $v)
    {
        $matches = array();
        if (preg_match('/^([!><=%?][><=%]?[<]?|)(\w+)$/', $k, $matches))
        {
            if (isset($matches[2]) && $matches[2] <> '')
            {
                if (in_array($matches[2], $arFiltrableField, true))
                    $arSectionFilter[$matches[1].$arFiltrableFieldMap[$matches[2]]] = $v;
                else if (!in_array($matches[2], $arIgnoreFilters, true))
                    $bSkipSections = true;
            }
        }
    }
}
unset($arIgnoreFilters, $arFiltrableFieldMap, $arFiltrableField, $fieldSection, $fieldIblock, $k, $v, $matches);


$_arSort = $CGridOptions->GetSorting(
    array(
        'sort' => array('name' => 'asc'),
        'vars' => array('by' => 'by', 'order' => 'order')
    )
);

$arSort = !empty($arSort) ? $arSort : $_arSort['sort'];
if ($isStExport)
{
    if (!is_array($arSort))
    {
        $arSort = array();
    }

    if (!isset($arSort['ID']))
    {
        if (!empty($arSort))
        {
            $arSort['ID'] = (array_shift(array_slice($arSort, 0, 1)) === 'desc') ? 'desc' : 'asc';
        }
        else
        {
            $arSort['ID'] = 'asc';
        }
    }
}
$arResult['SORT'] = $arSort;
$arResult['SORT_VARS'] = $_arSort['vars'];
unset($_arSort, $arSort);

$arSelect = $selectedPropertyIds = $selectedFields = [];
if ($isInExportMode && $isStExportAllFields)
{
    $selectedFieldsMap = [];
    foreach ($arResult['HEADERS'] as $arHeader)
    {
        if (isset($arHeader['id']) && is_string($arHeader['id']) && $arHeader['id'] !== '')
        {
            $selectedFieldsMap[$arHeader['id']] = true;
        }
    }
    $selectedFields = array_keys($selectedFieldsMap);
    unset($selectedFieldsMap);
}
else
{
    $selectedFields = $CGridOptions->GetVisibleColumns();
    if (empty($selectedFields))
    {
        $selectedFields = array();
        foreach ($arResult['HEADERS'] as $arHeader)
        {
            if ($arHeader['default'])
            {
                $selectedFields[] = $arHeader['id'];
            }
        }
    }
}
$arResult['SELECTED_HEADERS'] = [];
if ($isInExportMode)
{
    $arResult['SELECTED_HEADERS'][] = 'ID';
    $arResult['SELECTED_HEADERS'][] = 'XML_ID';
}
foreach ($selectedFields as $fieldName)
{
    if (preg_match('/^PROPERTY_\d+$/', $fieldName))
    {
        $selectedPropertyIds[] = (int)mb_substr($fieldName, 9);

        if (isset($arProps[$fieldName]))
        {
            if ($isInExportMode)
            {
                if (isset($exportProps[$fieldName]))
                {
                    $arResult['SELECTED_HEADERS'][] = $fieldName;
                }
            }
            else
            {
                $arResult['SELECTED_HEADERS'][] = $fieldName;
            }
        }
    }
    else
    {
        $arSelect[] = $fieldName;
        if (!($isInExportMode && ($fieldName === 'ID' || $fieldName === 'XML_ID')))
        {
            $arResult['SELECTED_HEADERS'][] = $fieldName;
        }
    }
}
unset($selectedFields);

// ID must present in select
if (!in_array('ID', $arSelect, true))
{
    $arSelect[] = 'ID';
}

if ($isInExportMode && !in_array('XML_ID', $arSelect, true))
{
    $arSelect[] = 'XML_ID';
}

//SECTION_ID must present in select
if (!in_array('SECTION_ID', $arSelect, true))
{
    $arSelect[] = 'SECTION_ID';
}

//PREVIEW_PICTURE must present in select
if (!in_array('PREVIEW_PICTURE', $arSelect, true))
{
    $arSelect[] = 'PREVIEW_PICTURE';
}

// Force select currency ID if price selected
if (in_array('PRICE', $arSelect) && !in_array('CURRENCY_ID', $arSelect, true))
{
    $arSelect[] = 'CURRENCY_ID';
}

// Force select description type if description selected
if (in_array('DESCRIPTION', $arSelect) && !in_array('DESCRIPTION_TYPE', $arSelect, true))
{
    $arSelect[] = 'DESCRIPTION_TYPE';
}

// get page number
$navParams = CDBResult::GetNavParams();
$pageNum = (int)$navParams['PAGEN'];
$pageSize = (int)$arNavParams['nPageSize'];
if ($pageSize < 1)
{
    $pageSize = 10;
}
unset($navParams);
if ($pageNum < 1)
{
    $pageNum = 1;
}
$pageOffsetNext = $pageSize * $pageNum;
$pageOffset = $pageOffsetNext - $pageSize;

// SECTIONS -->
$arResultData = array();
$navSectionCount = 0;
$navSectionFetchCount = 0;
$bSkipSections = true;
if (!$bSkipSections)
{
    $section = new CIBlockSection();
    $navSectionCount = $section->GetCount($arSectionFilter);
    unset($section);
    if ($navSectionCount > $pageOffset)
    {
        $navSectionFetchCount = $navSectionCount - $pageOffset;
        if ($navSectionFetchCount > $pageSize)
        {
            $navSectionFetchCount = $pageSize;
        }
    }
    if ($navSectionCount > 0)
    {
        $rsSection = CIBlockSection::GetList(
            $arResult['SORT'],
            $arSectionFilter,
            false,
            $arSelect,
            [
                'nPageSize' => $pageSize,
                'iNumPage' => $pageNum,
                'bShowAll' => false
            ]
        );
        $GLOBALS['NavNum']--;
        $fetchIndex = 0;
        while($fetchIndex++ < $navSectionFetchCount && $arSectionRow = $rsSection->Fetch())
        {
            $arSectionRow['TYPE'] = 'S';
            $arResultData[] = $arSectionRow;
        }
        unset($rsSection, $fetchIndex, $arSectionRow);
    }
}
unset($arSectionFilter);
// SECTIONS <--

// PRODUCTS -->

if(!$arFilter['SECTION_ID']){
    unset($arFilter['SECTION_ID']);
    unset($arFilter['LIST_SECTION_ID']);
}
$arFilter['INCLUDE_SUBSECTIONS'] = 'Y';

$arOrdersByProducts = [];

if($arParams['ENTITY_ID'] && is_numeric($arParams['ENTITY_ID'])){
    $arOrdersList = OrderContactCompanyTable::getList([
        'select' => ['ORDER_ID'],
        'filter' => ['ENTITY_ID' => $arParams['ENTITY_ID']]
    ])->fetchAll();

    if($arOrdersList){
        $arProductsList = BasketTable::getList([
            'select' => [
                'PRODUCT_ID',
                'ORDER_ID',
                'ORDER_NAME' => 'ORDER.ACCOUNT_NUMBER',
                'NAME'
            ],
            'filter' => ['=ORDER_ID' => array_column($arOrdersList, 'ORDER_ID')],
            'runtime' => [
                'ORDER' => [
                    'data_type' => '\Api\Classes\Entity\OrderTable',
                    'reference' => [
                        'this.ORDER_ID' => 'ref.ID'
                    ]
                ]
            ]
        ])->fetchAll();

        if($arProductsList){
//            $arFilter['ID'] = array_unique(array_column($arProductsList, 'PRODUCT_ID'));
            $arFilter['NAME'] = array_unique(array_column($arProductsList, 'NAME'));

            foreach($arProductsList as $arPItem){
                if(empty($arOrdersByProducts[$arPItem['NAME']])){
                    $arOrdersByProducts[$arPItem['NAME']] = [];
                }
                $arOrdersByProducts[$arPItem['NAME']][] = $arPItem;
            }
        }
    }
}

if(empty($arFilter['NAME'])){
    // если фильтрации по товарам нет - делаем фейковый фильтр
    $arFilter['NAME'] = false;
}

$arPricesSelect = $arVatsSelect = array();
$arSelect = CCrmProduct::DistributeProductSelect($arSelect, $arPricesSelect, $arVatsSelect);
$navProductCount = 0;
$navProductFetchCount = 0;
if (!$isStExport || $stExportPageNumber <= 1)
{
    $navProductCount = (int)cCrmProductGetList([], $arFilter, [], false, true);
}
if ($isStExport)
{
    if ($stExportPageNumber === 1)
    {
        $arResult['STEXPORT_TOTAL_ITEMS'] = $navProductCount;
    }
    $obRes = cCrmProductGetList($arResult['SORT'], $arFilter, $arSelect, $arNavParams);
}
else
{
    $navTotalCount = $navSectionCount + $navProductCount;

    if ($navSectionCount < $pageOffsetNext)
    {
        $navProductPageOffset = $pageOffsetNext - $navSectionCount;
        $navProductSurplus = $navProductPageOffset % $pageSize;
        $navProductPageList = [];
        if ($navProductPageOffset > $pageSize)
        {
            $navProductPageNumber = ($navProductPageOffset - $navProductSurplus) / $pageSize;
            $navProductPageEnd = $pageOffsetNext - $navProductSurplus;
            if ($navTotalCount <= $navProductPageEnd)
            {
                $navProductPageList[] = [
                    'pageSize' => $pageSize,
                    'pageNum' => $navProductPageNumber,
                    'offset' => $navProductSurplus,
                    'count' => $pageSize - ($pageOffsetNext - $navTotalCount)
                ];
            }
            else
            {
                $navProductPageList[] = [
                    'pageSize' => $pageSize,
                    'pageNum' => $navProductPageNumber,
                    'offset' => $navProductSurplus,
                    'count' => $pageSize - $navProductSurplus
                ];
                $navProductPageList[] = [
                    'pageSize' => $pageSize,
                    'pageNum' => $navProductPageNumber + 1,
                    'offset' => 0,
                    'count' => (
                    $navTotalCount < $pageOffsetNext ?
                        $navTotalCount - ($pageOffsetNext - $navProductSurplus) :
                        $navProductSurplus
                    )
                ];
            }
            unset($navProductPageNumber, $navProductPageEnd);
        }
        else
        {
            $navProductPageSize = $navProductSurplus > 0 ? $navProductSurplus : $pageSize;
            $navProductPageList[] = [
                'pageSize' => $navProductPageSize,
                'pageNum' => 1,
                'offset' => 0,
                'count' => $navProductPageSize
            ];
            unset($navProductPageSize);
        }
        unset($navProductPageOffset, $navProductSurplus);

        foreach ($navProductPageList as $navPageInfo)
        {
            $rsProduct = cCrmProductGetList(
                $arResult['SORT'],
                $arFilter,
                $arSelect,
                [
                    'nPageSize' => $navPageInfo['pageSize'],
                    'iNumPage' => $navPageInfo['pageNum'],
                    'bShowAll' => false
                ]
            );
            $GLOBALS['NavNum']--;
            $fetchIndex = 0;
            $fetchCount = $navPageInfo['offset'] + $navPageInfo['count'];
            while($fetchIndex++ < $fetchCount && $arProductRow = $rsProduct->Fetch())
            {
                if ($fetchIndex > $navPageInfo['offset'])
                {
                    $arProductRow['TYPE'] = 'P';
                    $arResultData[] = $arProductRow;
                }
            }
            unset($fetchIndex, $fetchCount, $arProductRow);
        }
        unset($navProductPageList, $rsProduct, $navPageInfo);
    }

    $obRes = new CDBResult;
    $obRes->InitFromArray($arResultData);
    $obRes->InitNavStartVars($pageSize, false, $pageNum);
    $obRes->NavPageNomer = $pageNum;
    $obRes->nSelectedCount = $obRes->NavRecordCount = $navTotalCount;
    $obRes->NavPageCount = floor($obRes->NavRecordCount / $pageSize);
    if($obRes->NavRecordCount % $obRes->NavPageSize > 0)
    {
        $obRes->NavPageCount++;
    }
    unset($navTotalCount);
}

$arResult['PRODUCTS'] = array();
$arResult['PERMS']['ADD']    = true;
$arResult['PERMS']['WRITE']  = true;
$arResult['PERMS']['DELETE'] = true;
$arProductId = array();
$arPropertyValues = array();
$cnt = 0;
while($arElement = $obRes->GetNext())
{
    if($isStExport && $stExportPageSize > 0 && ++$cnt > $stExportPageSize)
    {
        break;
    }

    if ($isStExport)
    {
        $arElement['TYPE'] = $arElement['~TYPE'] = 'P';
        if (isset($arElement['SECTION_ID']) && $arElement['SECTION_ID'] > 0)
        {
            $arElement['SECTION_PATH'] = CCrmProductSection::GetPath($catalogID, $arElement['SECTION_ID'], ['NAME']);
        }
        else
        {
            $arElement['SECTION_PATH'] = [];
        }
    }

    if ($arElement['TYPE'] === 'S')
    {
        $arElement['DELETE'] = $arElement['EDIT'] = true;
        $arResult['SECTIONS'][$arElement['ID']] = $arElement;
    }
    else if ($arElement['TYPE'] === 'P')
    {
        //$CCrmProduct->cPerms->CheckEnityAccess('PRODUCT', 'WRITE', $arContactAttr[$arElement['ID']])
        //$CCrmProduct->cPerms->CheckEnityAccess('PRODUCT', 'DELETE', $arContactAttr[$arElement['ID']])

        $arElement['DELETE'] = $arElement['EDIT'] = true;

        $arElement['PATH_TO_PRODUCT_SHOW'] =
            CComponentEngine::MakePathFromTemplate(
                $arParams['PATH_TO_PRODUCT_SHOW'],
                array('product_id' => $arElement['ID'])
            );

        $arElement['PATH_TO_PRODUCT_EDIT'] =
            CComponentEngine::MakePathFromTemplate(
                $arParams['PATH_TO_PRODUCT_EDIT'],
                array('product_id' => $arElement['ID'])
            );

        $arElement['PATH_TO_PRODUCT_DELETE'] =
            CHTTP::urlAddParams(
                CComponentEngine::MakePathFromTemplate(
                    $arParams['PATH_TO_PRODUCT_LIST'],
                    //array('section_id' => isset($arElement['SECTION_ID']) ? $arElement['SECTION_ID'] : '0')
                    array('section_id' => $sectionID)
                ),
                array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $arElement['TYPE'].$arElement['ID'], 'sessid' => bitrix_sessid())
            );

        foreach ($arPricesSelect as $fieldName)
            $arElement['~'.$fieldName] = $arElement[$fieldName] = null;
        foreach ($arVatsSelect as $fieldName)
            $arElement['~'.$fieldName] = $arElement[$fieldName] = null;
        $arProductId[] = $arElement['ID'];

        $arResult['PRODUCTS'][$arElement['NAME']] = $arElement;
        //$arResult['PRODUCT_ID_ARY'][$arElement['ID']] = $arElement['ID'];

        // Product properties
        $propsCount = count($selectedPropertyIds);
        if ($propsCount > 0)
        {
            $stepSize = 500;
            $stepCount = (int)floor($propsCount / $stepSize) + 1;
            $offset = $range = 0;
            $stepIds = [];
            while ($stepCount > 0)
            {
                $range = ($stepCount > 1) ? $stepSize : $propsCount - $offset;
                if ($range > 0)
                {
                    $stepIds = array_slice($selectedPropertyIds, $offset, $range);

                    $rsProperties = CIBlockElement::GetProperty(
                        $catalogID,
                        $arElement['ID'],
                        array(
                            'sort' => 'asc',
                            'id' => 'asc',
                            'enum_sort' => 'asc',
                            'value_id' => 'asc',
                        ),
                        array(
                            'ID' => $stepIds,
                            'ACTIVE' => 'Y',
                            'EMPTY' => 'N',
                            'CHECK_PERMISSIONS' => 'N'
                        )
                    );
                    $prevPropID = '';
                    $prevPropMultipleValuesInfo = array();
                    $controlSettings = [];
                    while ($arProperty = $rsProperties->Fetch())
                    {
                        if (isset($arProperty['USER_TYPE']) && !empty($arProperty['USER_TYPE'])
                            && !array_key_exists($arProperty['USER_TYPE'], $arPropUserTypeList))
                            continue;

                        $propID = 'PROPERTY_' . $arProperty['ID'];

                        if ($isInExportMode)
                        {
                            $controlSettings['MODE'] =
                                CCrmProductPropsHelper::AjustExportMode($sExportType, $arProperty);
                        }

                        // region Prepare multiple values
                        if (!empty($prevPropID) && $propID !== $prevPropID && !empty($prevPropMultipleValuesInfo))
                        {
                            $methodName = $prevPropMultipleValuesInfo['methodName'];
                            $method = $prevPropMultipleValuesInfo['propertyInfo']['PROPERTY_USER_TYPE'][$methodName];
                            $arPropertyValues[$arElement['ID']][$prevPropID] = call_user_func_array(
                                $method,
                                array(
                                    $prevPropMultipleValuesInfo['propertyInfo'],
                                    array("VALUE" => $prevPropMultipleValuesInfo['value']),
                                    $prevPropMultipleValuesInfo['controlSettings']
                                )
                            );
                        }
                        // endregion Prepare multiple values

                        if ($propID !== $prevPropID)
                        {
                            $prevPropID = $propID;
                            $prevPropMultipleValuesInfo = array();
                        }

                        if (!isset($arPropertyValues[$arElement['ID']][$propID]))
                            $arPropertyValues[$arElement['ID']][$propID] = array();

                        $userTypeMultipleWithMultipleMethod = $userTypeMultipleWithSingleMethod =
                        $userTypeSingleWithSingleMethod = false;
                        if (isset($arProperty['USER_TYPE']) && !empty($arProperty['USER_TYPE'])
                            && is_array($arPropUserTypeList[$arProperty['USER_TYPE']]))
                        {
                            $userTypeMultipleWithMultipleMethod = (
                                isset($arProperty['MULTIPLE']) && $arProperty['MULTIPLE'] === 'Y'
                                && array_key_exists(
                                    'GetPublicViewHTMLMulty', $arPropUserTypeList[$arProperty['USER_TYPE']]
                                )
                            );
                            $userTypeMultipleWithSingleMethod = (
                                isset($arProperty['MULTIPLE']) && $arProperty['MULTIPLE'] === 'Y'
                                && array_key_exists(
                                    'GetPublicViewHTML', $arPropUserTypeList[$arProperty['USER_TYPE']]
                                )
                            );
                            $userTypeSingleWithSingleMethod = (
                                (!isset($arProperty['MULTIPLE']) || $arProperty['MULTIPLE'] !== 'Y')
                                && array_key_exists('GetPublicViewHTML', $arPropUserTypeList[$arProperty['USER_TYPE']])
                            );
                        }
                        if ($userTypeMultipleWithMultipleMethod || $userTypeMultipleWithSingleMethod
                            || $userTypeSingleWithSingleMethod)
                        {
                            $propertyInfo = $arProps[$propID];
                            $propertyInfo['PROPERTY_USER_TYPE'] = $arPropUserTypeList[$arProperty['USER_TYPE']];
                            $methodName = $userTypeMultipleWithMultipleMethod ?
                                'GetPublicViewHTMLMulty' : 'GetPublicViewHTML';
                            if ($userTypeMultipleWithMultipleMethod)
                            {
                                if (is_array($prevPropMultipleValuesInfo['value']))
                                {
                                    $prevPropMultipleValuesInfo['value'][] = $arProperty["VALUE"];
                                }
                                else
                                {
                                    $prevPropMultipleValuesInfo['propertyInfo'] = $propertyInfo;
                                    $prevPropMultipleValuesInfo['controlSettings'] = $controlSettings;
                                    $prevPropMultipleValuesInfo['methodName'] = $methodName;
                                    $prevPropMultipleValuesInfo['value'] = array($arProperty["VALUE"]);
                                }
                            }
                            else
                            {
                                if (CCrmProductPropsHelper::isTypeSupportingUrlTemplate($propertyInfo))
                                {
                                    $controlSettings['DETAIL_URL'] =
                                        CComponentEngine::MakePathFromTemplate(
                                            $arParams['PATH_TO_PRODUCT_SHOW'],
                                            [
                                                'product_id' => $arProperty['VALUE'],
                                            ]
                                        );
                                }
                                $method = $arPropUserTypeList[$arProperty['USER_TYPE']][$methodName];
                                $params = [
                                    $propertyInfo,
                                    [
                                        "VALUE" => $arProperty["VALUE"]
                                    ],
                                    $controlSettings
                                ];
                                $arPropertyValues[$arElement['ID']][$propID][] = call_user_func_array($method, $params);
                            }
                            unset($propertyInfo);
                        }
                        else if ($arProperty["PROPERTY_TYPE"] == "L")
                        {
                            $arPropertyValues[$arElement['ID']][$propID][] =
                                htmlspecialcharsex($arProperty["VALUE_ENUM"]);
                        }
                        else
                        {
                            $arPropertyValues[$arElement['ID']][$propID][] =
                                htmlspecialcharsex($arProperty["VALUE"]);
                        }
                    }

                    // region Prepare multiple values for last property
                    if (!empty($prevPropID) && !empty($prevPropMultipleValuesInfo))
                    {
                        $methodName = $prevPropMultipleValuesInfo['methodName'];
                        $method = $prevPropMultipleValuesInfo['propertyInfo']['PROPERTY_USER_TYPE'][$methodName];
                        $arPropertyValues[$arElement['ID']][$prevPropID] = call_user_func_array(
                            $method,
                            array(
                                $prevPropMultipleValuesInfo['propertyInfo'],
                                array("VALUE" => $prevPropMultipleValuesInfo['value']),
                                $prevPropMultipleValuesInfo['controlSettings']
                            )
                        );
                    }
                    // endregion Prepare multiple values for last property

                    unset($rsProperties, $arProperty, $propID, $prevPropID, $prevPropMultipleValuesInfo,
                        $controlSettings);
                }
                $offset += $stepSize;
                $stepCount--;
            }
        }
        unset($propsCount, $stepSize, $stepCount, $offset, $range, $stepIds);
    }
}

$arResult['PROPERTY_VALUES'] = $arPropertyValues;
unset($arPropertyValues);
CCrmProduct::ObtainPricesVats($arResult['PRODUCTS'], $arProductId, $arPricesSelect, $arVatsSelect,
    (isset($arFilter['~REAL_PRICE']) && $arFilter['~REAL_PRICE'] === true));

//получаем цены и заказы
foreach($arResult['PRODUCTS'] as $mKey => $arProd){
    if(!$arProd['PRICE']) {
        $arProdPrice = CPrice::GetBasePrice($arProd['ID']);
        if ($arProdPrice) {
            $arResult['PRODUCTS'][$mKey]['PRICE'] = $arProdPrice['PRICE'];
            $arResult['PRODUCTS'][$mKey]['CURRENCY_ID'] = $arProdPrice['CURRENCY'];
        }
    }

    $arPOrders = [];
    foreach($arOrdersByProducts[$mKey] as $arO){
        $arPOrders[] = '<a href="/shop/orders/details/'.$arO['ORDER_ID'].'/" target="_blank">'.($arO['ORDER_NAME'] ? GetMessage('CRM_COLUMN_ORDER_LABEL').$arO['ORDER_NAME'] : $arO['ORDER_ID']).'</a>';
    }

    $arResult['PRODUCTS'][$mKey]['ORDERS'] = implode('<br>', $arPOrders);
}

$productMeasureInfos = \Bitrix\Crm\Measure::getProductMeasures($arProductId);
if (!is_array($productMeasureInfos))
    $productMeasureInfos = array();
$arResult['PRODUCT_MEASURE_INFOS'] = $productMeasureInfos;
// <-- PRODUCTS
if (!$isInExportMode)
{
    $arResult['ROWS_COUNT'] = $obRes->SelectedRowsCount();
    $arResult['NAV_OBJECT'] = $obRes;
    $arResult['BACK_URL_SECTION_ID'] = $bFilterSection ? $sectionID : '';

    $this->IncludeComponentTemplate();
    include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.product/include/nav.php');

    $result = array(
        'PARAMS' => $arParams,
        'ROWS_COUNT' => $arResult['ROWS_COUNT']
    );
    if ($bFilterSection)
    {
        $result['SECTION_ID'] = $sectionID;
    }

    return $result;
}
else
{
    $arResult['SECTION_MAX_DEPTH'] = cGetMaxDepth($arParams['CATALOG_ID']);

    if ($isStExport)
    {
        $numRows = count($arResult['PRODUCTS']);
        $enableNextPage = ($numRows === 0
            || ($arResult['STEXPORT_TOTAL_ITEMS'] > ($stExportPageSize * ($stExportPageNumber - 1) + $numRows)));

        $arResult['STEXPORT_IS_FIRST_PAGE'] = $stExportPageNumber === 1 ? 'Y' : 'N';
        $arResult['STEXPORT_IS_LAST_PAGE'] = $enableNextPage ? 'N' : 'Y';

        $this->__templateName = '.default';

        $this->IncludeComponentTemplate($sExportType);

        return array(
            'PROCESSED_ITEMS' => $numRows,
            'TOTAL_ITEMS' => $arResult['STEXPORT_TOTAL_ITEMS']
        );
    }
    else
    {
        $APPLICATION->RestartBuffer();
        // hack. any '.default' customized template should contain 'excel' page
        $this->__templateName = '.default';

        if($sExportType === 'csv')
        {
            Header('Content-Type: text/csv');
            Header('Content-Disposition: attachment;filename=products.csv');
        }
        elseif($sExportType === 'excel')
        {
            Header('Content-Type: application/vnd.ms-excel');
            Header('Content-Disposition: attachment;filename=products.xls');
        }
        Header('Content-Type: application/octet-stream');
        Header('Content-Transfer-Encoding: binary');

        // add UTF-8 BOM marker
        if (defined('BX_UTF') && BX_UTF)
            echo chr(239).chr(187).chr(191);

        $this->IncludeComponentTemplate($sExportType);

        die();
    }
}

/**
 * получение товаров
 * @param array $arOrder
 * @param array $arFilter
 * @param array $arSelectFields
 * @param bool $arNavStartParams
 * @param bool $arGroupBy
 * @return CCrmProductResult|CIBlockResult
 */
function cCrmProductGetList($arOrder = array(), $arFilter = array(), $arSelectFields = array(), $arNavStartParams = false, $arGroupBy = false)
{
    $arProductFields = [
        'ID' => 'ID',
        'CATALOG_ID' => 'IBLOCK_ID',
        'PRICE' => false,
        'CURRENCY_ID' => false,
        'ORIGINATOR_ID' => false,
        'ORIGIN_ID' => false,
        'NAME' => 'NAME',
        'ACTIVE' => 'ACTIVE',
        'SECTION_ID' => 'IBLOCK_SECTION_ID',
        'PREVIEW_PICTURE' => 'PREVIEW_PICTURE',
        'PREVIEW_TEXT' => 'PREVIEW_TEXT',
        'PREVIEW_TEXT_TYPE' => 'PREVIEW_TEXT_TYPE',
        'DETAIL_PICTURE' => 'DETAIL_PICTURE',
        'DESCRIPTION' => 'DETAIL_TEXT',
        'DESCRIPTION_TYPE' => 'DETAIL_TEXT_TYPE',
        'SORT' => 'SORT',
        'VAT_ID' => false,
        'VAT_INCLUDED' => false,
        'MEASURE' => false,
        'XML_ID' => 'XML_ID'
    ];

    // Rewrite order
    // <editor-fold defaultstate="collapsed" desc="Rewrite order ...">
    $arOrderRewrited = array();
    foreach ($arOrder as $k => $v)
    {
        $uk = strtoupper($k);
        if ((isset($arProductFields[$uk]) && $arProductFields[$uk] !== false)
            || preg_match('/^PROPERTY_\d+$/', $uk))
            $arOrderRewrited[$uk] = $v;
    }
    if (strlen($arOrder['ORIGINATOR_ID'].$arOrder['ORIGIN_ID']) > 0)
    {
        if (strlen($arOrder['ORIGINATOR_ID']) > 0) $arOrderRewrited['XML_ID'] = $arOrder['ORIGINATOR_ID'];
        else $arOrderRewrited['XML_ID'] = $arOrder['ORIGIN_ID'];
    }
    // </editor-fold>

    // Rewrite filter
    // <editor-fold defaultstate="collapsed" desc="Rewrite filter ...">
    $arAdditionalFilter = $arFilterRewrited = array();

    $arOptions = array();
    if (isset($arFilter['~REAL_PRICE']))
    {
        $arOptions['REAL_PRICE'] = true;
        unset($arFilter['~REAL_PRICE']);
    }

    foreach ($arProductFields as $fieldProduct => $fieldIblock)
    {
        foreach($arFilter as $k => $v)
        {
            $matches = array();
            if (preg_match('/^([!><=%?][><=%]?[<]?|)'.$fieldProduct.'$/', $k, $matches))
            {
                if ($fieldIblock)
                {
                    if($fieldIblock === 'IBLOCK_SECTION_ID')
                    {
                        //HACK: IBLOCK_SECTION_ID is not supported in filter
                        $fieldIblock = 'SECTION_ID';
                    }

                    $arFilterRewrited[$matches[1].$fieldIblock] = $v;
                }
                else
                {
                    $arAdditionalFilter[$k] = $v;
                }
            }
            else if (preg_match('/^([!><=%?][><=%]?[<]?|)(PROPERTY_\d+)$/', $k, $matches))
            {
                $arFilterRewrited[$matches[1].$matches[2]] = $v;
            }
        }
    }
    if (strlen($arFilter['ORIGINATOR_ID'].$arFilter['ORIGIN_ID']) > 0)
    {
        if (strlen($arFilter['ORIGINATOR_ID']) > 0 && strlen($arFilter['ORIGIN_ID']) > 0)
        {
            $arFilterRewrited['XML_ID'] = $arFilter['ORIGINATOR_ID'].'#'.$arFilter['ORIGIN_ID'];
        }
        else
        {
            if (strlen($arFilter['ORIGINATOR_ID']) > 0)
            {
                $arFilterRewrited['%XML_ID'] = $arFilter['ORIGINATOR_ID'].'#';
            }
            else
            {
                $arFilterRewrited['%XML_ID'] = '#'.$arFilter['ORIGIN_ID'];
            }
        }
    }

    $catalogID = isset($arFilter['CATALOG_ID']) ? intval($arFilter['CATALOG_ID']) : 0;
    $arFilterRewrited['IBLOCK_ID'] = $catalogID;

    // </editor-fold>

    // Rewrite select
    // <editor-fold defaultstate="collapsed" desc="Rewrite select ...">
    $arSelect = $arSelectFields;
    if (!is_array($arSelect))
    {
        $arSelect = array();
    }

    if (empty($arSelect))
    {
        $arSelect = array();
        foreach (array_keys($arProductFields) as $fieldName)
        {
            if (!in_array($fieldName, array('PRICE', 'CURRENCY_ID', 'VAT_ID', 'VAT_INCLUDED', 'MEASURE'), true))
                $arSelect[] = $fieldName;
        }
    }
    else if (in_array('*', $arSelect, true))
    {
        $arSelect = array_keys($arProductFields);
    }

    $arAdditionalSelect = $arSelectRewrited = array();
    foreach ($arProductFields as $fieldProduct => $fieldIblock)
    {
        if (in_array($fieldProduct, $arSelect, true))
        {
            if ($fieldIblock) $arSelectRewrited[] = $fieldIblock;
            else $arAdditionalSelect[] = $fieldProduct;
        }
    }
    foreach ($arSelect as $v)
    {
        if ((isset($arProductFields[$v]) && $arProductFields[$v] !== false) || preg_match('/^PROPERTY_\d+$/', $v))
            $arSelectRewrited[] = $arProductFields[$v];
        else if (isset($arProductFields[$v]))
            $arAdditionalSelect[] = $v;
    }
    if (!in_array('ID', $arSelectRewrited, true))
        $arSelectRewrited[] = 'ID';

    if (!in_array('XML_ID', $arSelectRewrited, true))
    {
        $bSelectXmlId = false;
        foreach ($arSelect as $k => $v)
        {
            if ($v === 'ORIGINATOR_ID' || $v === 'ORIGIN_ID')
            {
                $bSelectXmlId = true;
                break;
            }
        }
        if ($bSelectXmlId) $arAdditionalSelect[] = $arSelectRewrited[] = 'XML_ID';
    }
    // </editor-fold>

    $arNavStartParamsRewrited = false;
    if (is_array($arNavStartParams))
        $arNavStartParamsRewrited = $arNavStartParams;
    else
    {
        if (is_numeric($arNavStartParams))
        {
            $nTopCount = intval($arNavStartParams);
            if ($nTopCount > 0)
                $arNavStartParamsRewrited = array('nTopCount' => $nTopCount);
        }
    }

    $dbRes = CIBlockElement::GetList($arOrderRewrited, $arFilterRewrited, ($arGroupBy === false) ? false : array(), $arNavStartParamsRewrited, $arSelectRewrited);
    if ($arGroupBy === false)
        $dbRes = new CCrmProductResult($dbRes, $arProductFields, $arAdditionalFilter, $arAdditionalSelect, $arOptions);

    return $dbRes;
}

/**
 * максимальная вложенносьт каталога
 * @param int $catalogId
 * @return int
 */
function cGetMaxDepth($nCatalogId = 0)
{
    $nResult = 0;

    if ($nCatalogId > 0)
    {
        $obConnection = \Bitrix\Main\Application::getInstance()->getConnection();
        $obRes = $obConnection->query(
            "SELECT MAX(DEPTH_LEVEL) AS MAX_DEPTH FROM b_iblock_section WHERE IBLOCK_ID = {$nCatalogId}"
        );
        $mRow = is_object($obRes) ? $obRes->fetch() : null;
        if (is_array($mRow) && isset($mRow['MAX_DEPTH']))
        {
            $nResult = (int)$mRow['MAX_DEPTH'];
        }
    }

    return $nResult;
}