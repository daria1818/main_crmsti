<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

$this->addExternalCss('/bitrix/js/crm/entity-editor/css/style.css');

\Bitrix\Main\UI\Extension::load('ui.buttons');

if (!empty($arResult['ERRORS']))
{
	foreach ($arResult['ERRORS'] as $error)
	{
		ShowError($error);
	}
}
?>
<form class='iframe-event-edit-wrapper' id="iframe-event-edit-wrapper">
	<input type="hidden" name="PERSON_TYPE_ID" value="<?=$arResult['PERSON_TYPE_ID']?>">
	<input type="hidden" name="ORDER_RESOURCE" value="">
	<input type="hidden" name="DEAL_ID" value="<?=$arResult['ENTITY_ID']?>">
	<div id="bx-crm-error" class="crm-property-edit-top-block"></div>
		<table class="crm-table" width="100%" border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td class="crm-block-inner-table">
					<div class="crm-entity-card-container" style="width: 100%">
						<div class="crm-entity-card-container-content">							
							<div class="crm-entity-card-widget">
								<div class="crm-entity-card-widget-title">
									<div class="iframe-info-panel-title">
										<?=Loc::getMessage('C_IFRAME_TITLE')?>
									</div>
								</div>
								<div class="crm-entity-widget-content">
									<?foreach ($arResult['FIELDS'] as $field){?>
										<div class="iframe-options-item iframe-options-item-border iframe-options-item-destination">
											<div class="iframe-options-item-column-left">
												<span class="iframe-options-item-name">
													<?=$field['NAME']?>
													<?=($field['REQUIRED'] ? '<span style="color: rgb(255, 0, 0);">*</span>' : '')?>
												</span>
											</div>
											<div class="iframe-options-item-column-right">
												<?
												switch ($field['TYPE']) {
													case 'user_selector':
														$APPLICATION->IncludeComponent(
															"bitrix:main.user.selector",
															"",
															array_merge($field['PARAMS'], [
																"ID" => $field['ID'] . "_list",
																"LAZYLOAD" => "Y",
																"INPUT_NAME" => $field['ID'],
																"USE_SYMBOLIC_ID" => true,
																"API_VERSION" => 2,
															])
														);
														break;
													case 'textarea':?>
														<textarea name="<?=$field['ID']?>" class="iframe-options-item-textarea"></textarea>
														<?break;
													default:?>
														<input type="<?=$field['TYPE']?>" name="<?=$field['ID']?>" value="<?=$field['VALUE']?>" class="iframe-options-item-input" autocomplete="off">
														<?break;
												}
												?>
											</div>
										</div>
									<?}?>
									<div class="iframe-product-wrapper">
										<a href="javascript:void(0)" id="IFRAME_CREATE_ORDER_ADD_PRODUCT"><?=Loc::getMessage('C_IFRAME_ADD_PRODUCT')?></a>
										<div class="iframe-message-no-available" style="display: none;"><?=Loc::getMessage('C_IFRAME_WARNING')?></div>
										<table class="iframe-product-table" width="100%" border="0" cellspacing="0" cellpadding="0">
											<thead>
												<tr>
													<td><?=Loc::getMessage('C_IFRAME_TABLE_NAME')?></td>
													<td><?=Loc::getMessage('C_IFRAME_TABLE_PRICE')?></td>
													<td><?=Loc::getMessage('C_IFRAME_TABLE_QUANTITY')?></td>
													<td><?=Loc::getMessage('C_IFRAME_TABLE_MEASURE')?></td>
													<td><?=Loc::getMessage('C_IFRAME_TABLE_SUM')?></td>
													<td></td>
												</tr>
											</thead>
											<tbody>
												<?foreach ($arResult['PRODUCTS'] ?: [] as $item) {
													$enable = $arResult['PRODUCTS_AVAILABLE'][$item['PRODUCT_ID']]['QUANTITY'] > 0;
													$class = ($enable ? 'iframe-product-item' : 'iframe-product-item iframe-product-disable');?>
													<tr class="<?=$class?>" data-id='<?=$item['PRODUCT_ID']?>'>
														<td><?=$item['PRODUCT_NAME']?></td>
														<td>
															<?if($enable){?>
																<input type="number" name="PRODUCTS[<?=$item['PRODUCT_ID']?>][PRICE]" value='<?=$item['PRICE']?>'>
															<?}else{?>
																<span class="input-disabled"><?=$item['PRICE']?></span>
															<?}?>
														</td>
														<td>
															<?if($enable){?>
																<input type="number" name="PRODUCTS[<?=$item['PRODUCT_ID']?>][QUANTITY]" value='<?=$item['QUANTITY']?>' max='<?=$arResult['PRODUCTS_AVAILABLE'][$item['PRODUCT_ID']]['QUANTITY']?>'>
															<?}else{?>
																<span class="input-disabled"><?=$item['QUANTITY']?></span>
															<?}?>
														</td>
														<td><?=$item['MEASURE_NAME']?></td>
														<td class="sum"><?=($item['PRICE']*$item['QUANTITY'])?></td>
														<td class="iframe-product-item-delete">
															<span class="icon"></span>
														</td>
													</tr>
												<?}?>
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</div>
					</div>
				</td>
			</tr>
		</table>
	<div class="crm-footer-container">
		<div class="crm-entity-section-control">
			<a id="IFRAME_CREATE_ORDER_APPLY_BUTTON" class="ui-btn ui-btn-success">
				<?=Loc::getMessage('C_IFRAME_APPLY_BUTTON')?>
			</a>
			<a id="IFRAME_CREATE_ORDER_CANCEL" class="ui-btn ui-btn-link">
				<?=Loc::getMessage('C_IFRAME_CANCEL')?>
			</a>
		</div>
	</div>
</form>
<?
$signer = new \Bitrix\Main\Security\Sign\Signer;
$signedParams = $signer->sign(base64_encode(serialize($arParams)), 'iframe.create.order');
?>
<script>
	var iframeCreateOrder = {
		params: <?=CUtil::PhpToJSObject($arParams)?>,
		signedParameters: '<?=CUtil::JSEscape($this->getComponent()->getSignedParameters())?>',
		componentName: '<?=CUtil::JSEscape($this->getComponent()->getName())?>'
	};
</script>