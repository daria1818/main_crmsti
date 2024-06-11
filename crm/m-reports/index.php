<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle('Отчет по линейкам');
?>
<?php
$APPLICATION->IncludeComponent(
    'pwd:reports.grid',
    '',
    [
        'SHOW_FILTER' => 'Y',
        'TYPE_REPORTS' => 'barCompany',
        'SHOW_ALL_RECORDS' => 'Y',
    ],
    false,
    ['HIDE_ICONS' => 'Y']
);
?>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>