<?
$module_id = "xguard.main";
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/include.php");
IncludeModuleLangFile(__FILE__);
$RIGHT = $APPLICATION->GetGroupRight($module_id);

$arAllOptions = array(

	);

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "form_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
	array("DIV" => "editHelpJS", "TAB" => GetMessage("MAIN_TAB_HELP_JS"), "ICON" => "form_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_HELP_JS")),
	array("DIV" => "editHelpPHP", "TAB" => GetMessage("MAIN_TAB_HELP_PHP"), "ICON" => "form_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_HELP_PHP")),
	array("DIV" => "edit2", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "form_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();
?>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?=LANGUAGE_ID?>">
	<?=bitrix_sessid_post()?>
	<?$tabControl->BeginNextTab();?>
	<?if(is_array($arAllOptions)):?>
		<?foreach($arAllOptions as $Option):?>
			<?$val = COption::GetOptionString($module_id, $Option[0]);
			$type = $Option[2];?>
			<tr>
				<td valign="top">
					<?if($type[0]=="checkbox"):?>
						<label for="<?=htmlspecialcharsbx($Option[0]);?>"><?=$Option[1];?></label>
					<?else:?>
						<?=$Option[1];?>
					<?endif;?>
				</td>
				<td valign="top" nowrap>
					<?switch($type[0])
					{
						case 'checkbox':
							?>
							<input type="checkbox" name="<?echo htmlspecialcharsbx($Option[0])?>" id="<?echo htmlspecialcharsbx($Option[0])?>" value="Y"<?if($val=="Y")echo" checked";?>>
							<?
						break;
						case 'text':
							?>
							<input type="text" size="<?echo $type[1]?>" maxlength="255" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0])?>">
							<?
						break;
						case 'textarea':
							?>
							<textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0])?>"><?echo htmlspecialcharsbx($val)?></textarea>
							<?
						break;
					}?>
				</td>
			</tr>
		<?endforeach;?>
	<?endif;?>
	<?$tabControl->BeginNextTab();?>
	<?$tabControl->BeginNextTab();?>
	<?$tabControl->BeginNextTab();?>
	<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
	<?$tabControl->Buttons();?>
	<input <?if ($RIGHT<"W") echo "disabled" ?> type="submit" name="Update" value="<?=GetMessage("SAVE")?>">
	<input type="hidden" name="Update" value="Y">
	<input type="reset" name="reset" value="<?=GetMessage("RESET")?>">
	<input <?if ($RIGHT<"W") echo "disabled" ?> type="button" title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="RestoreDefaults();" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
	<?$tabControl->End();?>
</form>