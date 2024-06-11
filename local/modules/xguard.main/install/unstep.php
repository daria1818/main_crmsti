<?if(!check_bitrix_sessid())return;
IncludeModuleLangFile(__FILE__);
echo CAdminMessage::ShowNote(GetMessage('MODULE_UNINSTALLED'));
?>
<form action="<?echo $APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?=LANG;?>">
	<input type="submit" name="" value="<?=GetMessage("MOD_BACK");?>">
<form>