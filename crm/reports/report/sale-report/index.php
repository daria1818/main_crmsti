<?php
/** @global Application $APPLICATION  */

use Bitrix\Main\Application;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('Отчет об оборотах по товарам');
?>

<?php
global $APPLICATION;
$APPLICATION->IncludeComponent(
    'bitrix:crm.control_panel',
    '',
    [
        'ID' => 'REPORT_LIST',
        'ACTIVE_ITEM_ID' => 'REPORT',
    ]
);

// $APPLICATION->IncludeComponent(
//     'pwd:reports.sale.grid',
//     '',
//     [
//         'SHOW_FILTER' => 'Y',
//         'SHOW_ALL_RECORDS' => 'Y',
//     ],
//     false,
//     ['HIDE_ICONS' => 'Y']
// );
$APPLICATION->IncludeComponent(
    'rtop:custom.reports.sale.category',
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

<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>