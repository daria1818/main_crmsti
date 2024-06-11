<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("KPI");

$APPLICATION->IncludeComponent("bitrix:crm.shop.page.controller", "", array(
	"CONNECT_PAGE" => "N",
	"ADDITIONAL_PARAMS" => array(
		"kpi" => array(
			"IS_ACTIVE" => true
		)
	)
));

$APPLICATION->IncludeComponent(
	"rtop:kpi", 
	".default", 
	array(
		"COMPONENT_TEMPLATE" => ".default"
	),
	false
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>