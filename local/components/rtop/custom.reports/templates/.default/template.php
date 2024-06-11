<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)	die();

//s($arResult);?>
<div class="r_reports_filter">
	<div class="r_reports_filter--wrapper">
		<form name="CUSTOM_REPORTS" method="POST" action="">
			<div class="r_reports_filter--error"></div>
			<?foreach($arResult as $key => $params){?>
				<?foreach($params as $code => $param){?>
					<?if(!empty($param['VALUES'])){?>
					<div class="item">
						<div class="item_name<?=($param['REQUIRED'] == 'Y' ? ' required_label' : '')?>"><?=$param['NAME']?></div>
						<div class="item_block">
						<?switch ($key) {
							case 'MULTI':?>
								<div class="r_multi_field">
									<div class="r_multi_field--select"><?=GetMessage('R_REPORTS_NO_SELECT')?></div>
									<div class="r_multi_field--list">
										<?foreach($param['VALUES'] as $value => $name){?>
											<div class="r_multi_field--item">
												<input type="checkbox" name="<?=$code?>" value="<?=$value?>" id="<?=($code . $value)?>" <?=($param['REQUIRED'] == 'Y' ? 'class="required"' : '')?>/>
												<label for="<?=($code . $value)?>"><?=$name?></label>
											</div>
										<?}?>
									</div>
								</div>
								<?break;
							case 'SEARCH':?>							
								<div class="r_search_field">
									<input type="text" name="<?=$code?>_text" class="r_search_field--search<?=($param['REQUIRED'] == 'Y' ? ' required' : '')?>" placeholder="Введите минимум 3 символа для поиска"/>									
									<div class="r_search_field--list">
										<?foreach($param['VALUES'] as $value => $name){?>
											<div data-value="<?=(isset($name['ID']) ? $name['ID'] : $name)?>"><?=(isset($name['NAME']) ? $name['NAME'] : $name)?></div>
										<?}?>
									</div>
									<input type="hidden" name="<?=$code?>" value="" />
								</div>							
								<?break;
							case 'SELECT':?>
								<div class="r_select_field">
									<div class="r_select_field--select"><?=GetMessage('R_REPORTS_NO_SELECT')?></div>
									<div class="r_select_field--list">
										<div data-code="" data-input=""><?=GetMessage('R_REPORTS_NO_SELECT')?></div>
										<?foreach($param['VALUES'] as $value => $name){?>
											<div data-code="<?=$name['CODE']?>" data-input="<?=$name['INPUT']?>"><?=$name['NAME']?></div>
										<?}?>
									</div>
									<input type="hidden" name="<?=$code?>" value="" <?=($param['REQUIRED'] == 'Y' ? 'class="required"' : '')?>>
								</div>
								<?break;
						}?>
						</div>
					</div>
					<?}?>
				<?}?>	
			<?}?>
			<div class="r_reports_submit">
				<button type="submit"><?=GetMessage('R_REPORTS_FORM_SUBMIT')?></button>
			</div>
		</form>
	</div>
	<div class="r_reports_filter--overflow"></div>
</div>
<div class="r_reports_result">
</div>
<script>
	BX.message({
		R_REPORTS_SET_NUMBER: '<?=GetMessageJS('R_REPORTS_SET_NUMBER')?>',
		R_REPORTS_SET_DATE: '<?=GetMessageJS('R_REPORTS_SET_DATE')?>',
		R_REPORTS_NO_SELECT: '<?=GetMessageJS('R_REPORTS_NO_SELECT')?>',
		R_REPORTS_ERROR: '<?=GetMessageJS('R_REPORTS_ERROR')?>',
	});

	var rReportAjaxPath = '<?=$arParams['AJAX_PATH']?>';
</script>
