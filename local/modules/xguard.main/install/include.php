<?
global $DB, $MESS, $APPLICATION;
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/filter_tools.php");

IncludeModuleLangFile(__FILE__);
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xguard.main/errors.php");

$DBType = strtolower($DB->type);

\Bitrix\Main\Loader::registerAutoLoadClasses("xguard.main", array(
    "xGuard\\Main" => "lib/main.php",
    "xGuard\\Main\\Ajax\\Init" => "lib/ajax/init.php",
    "xGuard\\Main\\Ajax\\Element" => "lib/ajax/element.php",
    "xGuard\\Main\\Section\\Filter" => "lib/section/filter.php",
    "xGuard\\Main\\Location" => "lib/location.php",
    "xGuard\\Main\\Section\\Button" => "lib/section/button.php",
    "xGuard\\Main\\Basket\\Init" => "lib/basket/init.php",
    "xGuard\\Main\\Order\\Init" => "lib/order/init.php",
));
?>