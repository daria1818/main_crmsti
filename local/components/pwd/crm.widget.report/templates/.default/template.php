<style>
.static-reports .crm-fake-widget-content-amt a, .static-reports .crm-fake-widget-content-amt a:hover {
    font-size: 36px;
    line-height: 62px;
    font-weight: normal;
    opacity: 1;
    font-family: "OpenSans-Light",Helvetica,Arial,sans-serif;
    border: 0;
}
.static-reports-filter {
    display: flex;
}
</style>
<div class="static-reports-filter">
<?php
$APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', [
    'FILTER_ID' => $arParams['GUID'],
    'GRID_ID' => $arParams['GUID'],
    'FILTER' => [
        ['id' => 'PERIOD', 'name' => 'Отчетный период', 'type' => 'date', 'default' => true],
    ],
    'ENABLE_LIVE_SEARCH' => true,
    'ENABLE_LABEL' => true
]);
?>
</div>
<div class="crm-fake-widget-50-50 static-reports" id="<?=$arParams['GUID']?>">
	<div class="crm-fake-widget-row">
		<div class="crm-fake-widget-container crm-fake-widget-left">
			<div class="crm-fake-widget crm-fake-widget-total">
				<div class="crm-fake-widget-total-top">
					<div class="crm-fake-widget-head">
						<span class="crm-fake-widget-title-container">
							<span class="crm-fake-widget-title-inner">
								<span class="crm-fake-widget-title" title="Сумма выигранных сделок">Общее количество сделок</span>
							</span>
						</span>
					</div>
					<div class="crm-fake-widget-content">
						<div class="crm-fake-widget-content-amt">
							<a class="crm-fake-widget-content-text"
                               id="deals_all_count"><span><?=$arResult['DEALS']['ALL']['COUNT']?></span> шт
                                .</a>
						</div>
						<div class="crm-fake-widget-content-amt">
							<a class="crm-fake-widget-content-text" id="deals_all_summ"><span><?=$arResult['DEALS']['ALL']['SUMM']?></span> руб.</a>
						</div>
					</div>
				</div>
				<div class="crm-fake-widget-total-bottom">
					<div class="crm-fake-widget-total-left">
						<div class="crm-fake-widget-head">
							<span class="crm-fake-widget-title-container">
								<span class="crm-fake-widget-title-inner">
									<span class="crm-fake-widget-title" title="Сумма сделок в работе">Выигранные сделки</span>
								</span>
							</span>
						</div>
						<div class="crm-fake-widget-content">
							<div class="crm-fake-widget-content-amt">
								<a class="crm-fake-widget-content-text" id="deals_items_f_count"
                                ><span><?=$arResult['DEALS']['ITEMS']['F']['COUNT']?></span> шт.</a>
							</div>
							<div class="crm-fake-widget-content-amt">
								<a class="crm-fake-widget-content-text" id="deals_items_f_summ"
                                ><span><?=$arResult['DEALS']['ITEMS']['F']['SUMM']?></span> руб.</a>
							</div>
						</div>
					</div>
					<div class="crm-fake-widget-total-right">
						<div class="crm-fake-widget-head">
							<span class="crm-fake-widget-title-container">
								<span class="crm-fake-widget-title-inner">
									<span class="crm-fake-widget-title" title="Сумма проигранных сделок">Отмененные сделки</span>
								</span>
							</span>
						</div>
						<div class="crm-fake-widget-content">
							<div class="crm-fake-widget-content-amt">
								<a class="crm-fake-widget-content-text" id="deals_items_d_count"
                                ><span><?=$arResult['DEALS']['ITEMS']['D']['COUNT']?></span> шт.</a>
							</div>
							<div class="crm-fake-widget-content-amt">
								<a class="crm-fake-widget-content-text" id="deals_items_d_summ"
                                ><span><?=$arResult['DEALS']['ITEMS']['D']['SUMM']?></span> руб.</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="crm-fake-widget-container crm-fake-widget-right">
			<div class="crm-fake-widget crm-fake-widget-total">
				<div class="crm-fake-widget-total-top">
					<div class="crm-fake-widget-head">
						<span class="crm-fake-widget-title-container">
							<span class="crm-fake-widget-title-inner">
								<span class="crm-fake-widget-title" title="Сумма выигранных сделок">Общее количество и сумма заказов</span>
							</span>
						</span>
					</div>
					<div class="crm-fake-widget-content">
						<div class="crm-fake-widget-content-amt">
							<a class="crm-fake-widget-content-text"
                               id="orders_all_count"><span><?=$arResult['ORDERS']['ALL']['COUNT']?></span> шт.</a>
						</div>
						<div class="crm-fake-widget-content-amt">
							<a class="crm-fake-widget-content-text"
                               id="orders_all_summ"><span><?=$arResult['ORDERS']['ALL']['SUMM']?></span> руб.</a>
						</div>
					</div>
				</div>
				<div class="crm-fake-widget-total-bottom">
					<div class="crm-fake-widget-total-left">
						<div class="crm-fake-widget-head">
							<span class="crm-fake-widget-title-container">
								<span class="crm-fake-widget-title-inner">
									<span class="crm-fake-widget-title" title="Сумма сделок в работе">Успешные заказы</span>
								</span>
							</span>
						</div>
						<div class="crm-fake-widget-content">
							<div class="crm-fake-widget-content-amt">
								<a class="crm-fake-widget-content-text"
                                   id="orders_items_f_count">
                                    <span><?=$arResult['ORDERS']['ITEMS']['F']['COUNT']?></span> шт.
                                </a>
							</div>
							<div class="crm-fake-widget-content-amt">
								<a class="crm-fake-widget-content-text" id="orders_items_f_summ"
                                ><span><?=$arResult['ORDERS']['ITEMS']['F']['SUMM']?></span> руб.</a>
							</div>
						</div>
					</div>
					<div class="crm-fake-widget-total-right">
						<div class="crm-fake-widget-head">
							<span class="crm-fake-widget-title-container">
								<span class="crm-fake-widget-title-inner">
									<span class="crm-fake-widget-title" title="Сумма проигранных сделок">Отмененные заказы</span>
								</span>
							</span>
						</div>
						<div class="crm-fake-widget-content">
							<div class="crm-fake-widget-content-amt">
								<a class="crm-fake-widget-content-text" id="orders_items_d_count"
                                ><span><?=$arResult['ORDERS']['ITEMS']['D']['COUNT']?></span> шт.</a>
							</div>
							<div class="crm-fake-widget-content-amt">
								<a class="crm-fake-widget-content-text" id="orders_items_d_summ"
                                ><span><?=$arResult['ORDERS']['ITEMS']['D']['SUMM']?></span> руб.</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
BX.addCustomEvent('BX.Main.Filter:apply', 
	BX.delegate(function (command, params) {
		BX.ajax({
	        url: '<?=$arResult['PATH_AJAX']?>',
	        data: {
	            guid: command,
                entity_id: <?=$arParams['ENTITY_ID']?>
	        },
	        dataType: 'json',
	        timeout: 30,
	        method: 'POST',
	        onsuccess: function( res ) {
                $.each(res, function (name, value) {
                    $('#' + name + ' > span').text(value);
                });
	        },
	    })
	})
);
</script>