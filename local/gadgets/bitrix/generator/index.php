<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
CJSCore::Init(array("jquery"));
\Bitrix\Main\UI\Extension::load("ui.buttons");
?>

<? 
$requests = [];

$rsRequests = CIBlockElement::GetList(['ID' => 'desc'], ['IBLOCK_ID' => 97, 'ACTIVE' => 'N'], false, false, []);
while($arRequest = $rsRequests->GetNextElement()) {
	$fields = $arRequest->getFields();
	$fields['PROPERTIES'] = $arRequest->getProperties();

	$rsProducts = CIBlockElement::GetList([], ['ID' => $fields['PROPERTIES']['PRODUCTS']['VALUE']], false, false, ['ID', 'NAME']);
	while($arProduct = $rsProducts->fetch()) {
		$fields['PRODUCTS'][$arProduct['ID']] = $arProduct['NAME'];
	}

	$requests[] = $fields;
}
?>

<? if (!empty($requests)) :?>
	<? foreach($requests as $request) :?>
		<?
		$usr = $USER->GetByID($request['PROPERTIES']['USER']['VALUE'])->fetch();
		?>
		<div class="js-admin-generator-request" data-id="<?=$request['ID']?>" style="margin-bottom: 20px;">
			<h3><?=$usr['LAST_NAME'] . ' ' . $usr['NAME'] . ' ' . $usr['SECOND_NAME']?></h3>
			<div><b>Дата: </b><?=$request['PROPERTIES']['DATE_FROM']['VALUE'] . ' - ' . $request['PROPERTIES']['DATE_TO']['VALUE']?></div>
			<br>
			<? foreach($request['PROPERTIES']['PRODUCTS']['VALUE'] as $key => $product) :?>
				<div>
					<?=$request['PRODUCTS'][$product]?> - <b><?=$request['PROPERTIES']['DISCOUNTS']['VALUE'][$key]?>%</b>
				</div>
			<? endforeach; ?>
			<br>
			<div>
				<button class="ui-btn ui-btn-success js-admin-generator-accept">Согласовать</button>
				<button class="ui-btn ui-btn-danger js-admin-generator-cancel">Отказать</button>
			</div>
			<br>
			<hr>
		</div>
	<? endforeach; ?>
<? else :?>
	<p>Список согласований пуст</p>
<? endif; ?>

<script type="text/javascript">
	$(function() {
		$('.js-admin-generator-accept').on('click', function() {
			var _this = this;

			BX.ajax.runComponentAction(
				'rtop:form.generation.link',
				'requestAccept',
				{
					mode: 'ajax',
					data: {requestId: $(_this).parents('.js-admin-generator-request').data('id')}
				}
			)
				.then(function (response) {
					if(response.status == 'success') {
						$(_this).parents('.js-admin-generator-request').remove();
					}
				}.bind(this))
				.catch(function (response) {
					console.log(response);
				}.bind(this));
		})

		$('.js-admin-generator-cancel').on('click', function() {
			var _this = this;

			BX.ajax.runComponentAction(
				'rtop:form.generation.link',
				'requestCancel',
				{
					mode: 'ajax',
					data: {requestId: $(_this).parents('.js-admin-generator-request').data('id')}
				}
			)
				.then(function (response) {
					if(response.status == 'success') {
						$(_this).parents('.js-admin-generator-request').remove();
					}
				}.bind(this))
				.catch(function (response) {
					console.log(response);
				}.bind(this));
		})
	})
</script>