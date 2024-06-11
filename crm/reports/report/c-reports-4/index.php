<?require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle('Контакты с заказами');?>
<?
global $APPLICATION;
$APPLICATION->IncludeComponent(
    'bitrix:crm.control_panel',
    '',
    [
        'ID' => 'REPORT_LIST',
        'ACTIVE_ITEM_ID' => 'REPORT',
    ]
);

$APPLICATION->IncludeComponent(
    'rtop:crm.contact.list.order',
    '',
    [
        'SHOW_FILTER' => 'Y',
        'SHOW_ALL_RECORDS' => 'Y',
        'COMPONENT_TEMPLATE' => ''
    ],
    false,
    ['HIDE_ICONS' => 'Y']
);
?>
<?require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>