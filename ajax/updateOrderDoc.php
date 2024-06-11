<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");?>
<?

if( $_REQUEST['doc'] && $_REQUEST['id'] ){
	$ID = $_REQUEST['id'];
	$DOC = $_REQUEST['doc'];

	CModule::IncludeModule('sale');
	$arOrder = CSaleOrder::GetByID($ID);

	$arFields = array(
      "COMMENTS" => $DOC,
   );
   CSaleOrder::Update($ID, $arFields);

}
?>