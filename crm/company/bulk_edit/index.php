<?require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle('Редактирование по списку');?>
<?
global $APPLICATION;
$APPLICATION->IncludeComponent(
    'bitrix:crm.control_panel',
    '',
    [
        'ID' => 'BULDEDIT',
        'ACTIVE_ITEM_ID' => 'BULDEDIT',
    ]
);

$APPLICATION->IncludeComponent(
    'rtop:crm.company.list.bulkedit',
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