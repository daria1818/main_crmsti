<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)	die();
CUtil::InitJSCore(array('window'));
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
												<label for="<?=($code . $value)?>"><?=(isset($name['NAME']) ? $name['NAME'] : $name)?></label>
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
							case 'DUBLE':?>
								<div class="r_duble_field">
									<input type="date" name="<?=$code?>_START" max="<?=date('Y-m-d')?>" class="required"/> - <input type="date" name="<?=$code?>_END" max="<?=date('Y-m-d')?>" class="required">
								</div>
								<?break;
						}?>
						</div>
					</div>
					<?}?>
				<?}?>	
			<?}?>
			<div class="item">
				<a class="crm-menu-bar-btn btn-new" href="#" title="Указать товары" onclick="BX.Crm.Order.Product.listObj_crm_order_product_list.addProductSearch({lang: 'ru', siteId: 'pn', orderId: '0'}); return false;"><span>Указать товары</span></a>
				<div class="r_product_list">
				</div>
			</div>
			<div class="item">
				<a class="crm-menu-bar-btn btn-new" href="#" title="Указать раздел" onclick="window.open('/bitrix/admin/iblock_section_search.php?lang=ru&IBLOCK_ID=30&simplename=Y&n=r_reports_sectionid', '', 'scrollbars=yes,resizable=yes,width=900,height=600,top=' + parseInt((screen.height - 500) / 2 - 14, 10) + ',left=' + parseInt((screen.width - 600) / 2 - 5, 10));"><span>Указать раздел</span></a>
				<input type="hidden" name="SECTION_ID" value="" id="r_reports_sectionid">
				<div class="r_section_list" style="display: none;">
					<div class="r_section_field">
						<span class="r_section_name" id="r_reports_sectionid_link"></span>
						<span class="r_section_remove"></span>
					</div>
				</div>
			</div>
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
