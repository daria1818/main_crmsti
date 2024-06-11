<?require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle('Отчет о потерянных клиентах');

global $APPLICATION;
$APPLICATION->IncludeComponent(
    'bitrix:crm.control_panel',
    '',
    array(
        'ID' => 'REPORT_LIST',
        'ACTIVE_ITEM_ID' => 'REPORT',
    ),
    $component
);

$APPLICATION->IncludeComponent(
    'rtop:custom.reports',
    'grid',
    [
        'SHOW_FILTER' => 'Y',
        'SHOW_ALL_RECORDS' => 'Y',
        'COMPONENT_TEMPLATE' => 'grid'
    ],
    false,
    ['HIDE_ICONS' => 'Y']
);
?>
<?require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>