<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/company/personal.php");
$APPLICATION->SetTitle("Задачи пользователей");
global $USER;
\CModule::IncludeModule('tasks');

$user_id = $USER->GetID();

$APPLICATION->IncludeComponent(
	"bitrix:ui.sidepanel.wrapper",
	"",
	array(
		'POPUP_COMPONENT_NAME' => 'rtop:tasks.task.list',
		"POPUP_COMPONENT_TEMPLATE_NAME" => "",
		"POPUP_COMPONENT_PARAMS" => array(
		),
		//"POPUP_COMPONENT_PARENT" => $component
	)
);
?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>