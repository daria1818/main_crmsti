BX.ready(function() {
	$('input[name=SECTION_ID]').attr('id', 'SECTION_ID');
	$('body').on('focus', 'input[name=SECTION_ID]', function () {
		$('input[name=SECTION_ID]').attr('id', 'SECTION_ID');
        window.open(
			'/bitrix/admin/iblock_section_search.php?lang=ru&IBLOCK_ID=30&simplename=Y&n=SECTION_ID', 
			'', 
			'scrollbars=yes,resizable=yes,width=900,height=600,top=' + parseInt((screen.height - 500) / 2 - 14, 10) + ',left=' + parseInt((screen.width - 600) / 2 - 5, 10)
		);
    });

    $('body').on('focus', 'input[name=PRODUCT_ID]', function () {
    	BX.Crm.Order.Product.listObj_crm_order_product_list.addProductSearch({lang: 'ru', siteId: 'pn', orderId: '0'});
    })

    BX.addCustomEvent("BX.Main.Filter:customEntityBlur", function(e){
    	displayDeleteFields();
    });

    BX.addCustomEvent("onWindowClose", function(e){
    	let element = document.getElementById("CCustomReportsClientsComponent_search_container");
    	if(!BX.hasClass(element, "main-ui-filter-search--showed"))
    		element.click();
    })

    function displayDeleteFields(){
    	let inputs = document.querySelectorAll('input[name=PRODUCT_ID], input[name=SECTION_ID]');
    	if(inputs.length > 0){
    		for(let item of inputs){
    			if(item.value != ''){    				
    				item.nextElementSibling.classList.remove('main-ui-hide');
    			}
    		}
    	}
    }
});