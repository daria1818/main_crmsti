BX.ready(function(){
	var changeAddressPopup = BX.PopupWindowManager.create('sendAjaxAddress', null, {
		autoHide: false,
		offsetLeft: 0,
		offsetTop: 0,
		overlay: true,
		closeByEsc: true,
		titleBar: true,
		closeIcon: true,
		contentColor: 'white',
		className: 'sendAjaxAddress'
	});
	var changeAddressBtn = function(params){
		changeAddressBtn.superclass.constructor.apply(this, arguments);
		this.buttonNode = BX.create('SPAN', {
			props: {className: 'popup-window-button' + (params.className ? ' ' + params.className : '')},
			style: typeof params.style === 'object' ? params.style : {},
			text: params.text,
			events: this.contextEvents
		});
		if (BX.browser.IsIE())
		{
			this.buttonNode.setAttribute('hideFocus', 'hidefocus');
		}
	};
	BX.extend(changeAddressBtn, BX.PopupWindowButton);

	var customOrderAddress = BX.create('INPUT', {
		attrs: {
			type: 'hidden',
			id: 'customOrderAddress',
			name: 'customOrderAddress',
			value: ''
		}
	});

	var observer = new MutationObserver(function(mutations) {
	  mutations.forEach(function(mutation) {	  	
	    if (mutation.type === 'childList' && mutation.addedNodes.length > 0 && (mutation.target.classList.contains('ui-entity-editor-section-content-padding-right') || mutation.target.classList.contains('ui-entity-editor-content-block'))) {
	    	if(mutation.addedNodes[0].tagName != undefined){
	      		let elements = mutation.addedNodes[0].querySelectorAll('input#property_7_text,input#property_19_text');
	      		if(elements.length > 0){
					for(let element of elements){
						addSuggestion(element);
					}
				}
			}
	    }
	    if (mutation.type === 'childList' && mutation.addedNodes.length > 0 && mutation.target.tagName == 'FORM' && mutation.previousSibling == null) {
	    	var inputSite = mutation.target.querySelector("input[name='PRODUCT_COMPONENT_DATA[params][SITE_ID]']");
	    	if(mutation.target.querySelector('.custom_name_site') == null && inputSite != null)
	    	{
	    		mutation.target.prepend(
					BX.create('DIV', {
						attrs: {class: 'custom_name_site ui-entity-editor-section'},
						style: {
							display: 'flex',
							paddingTop: '10px',
							paddingBottom: '10px',
						},
						children: [
							BX.create('DIV', {
								style: {
									marginRight: '20px'
								},
								text: 'Сайт:'
							}),
							BX.create('DIV', {
								text: sites != null ? sites[inputSite.value].NAME : inputSite.value
							})
						]
					})
				);
	    	}
	    }
	  });
	});

	var propertiesBodyBlock = document.body.querySelector('table.bx-layout-table');
	if(propertiesBodyBlock != null)
	{		
		observer.observe(propertiesBodyBlock, {attributes: true,childList: true,subtree: true});
	}

	document.body.append(customOrderAddress);

	BX.addCustomEvent("bx-ui-sls-after-popup-toggled", function(event){
		let elements = document.querySelectorAll('input#property_7_text,input#property_19_text');
		if(elements.length > 0){
			for(let element of elements){
				addSuggestion(element);
			}
		}		
	});
	BX.addCustomEvent("Kanban.Grid:addItem", function(e){
		// console.log(e);
		// debugger;
	});
	BX.addCustomEvent("SidePanel.Slider:onLoad", function(event){
		let slider = event.getSlider();
		if(slider.iframe == null)
			return;
		let elements = slider.iframe.contentWindow.document.body.querySelectorAll('input#property_7_text,input#property_19_text');
		if(elements.length > 0){
			for(let element of elements){
				addSuggestion(element);
			}
		}
		let propertiesBlock = slider.iframe.contentWindow.document.body.querySelector('div[data-cid="properties"]');
		
		if (propertiesBlock) {
	  		observer.observe(propertiesBlock, {attributes: true,childList: true,subtree: true});
	  	}

	  	let formOrder = slider.iframe.contentWindow.document.body.querySelector('form');
	  	if(formOrder == null)
	  		return;
	  	let inputSite = formOrder.querySelector("input[name='PRODUCT_COMPONENT_DATA[params][SITE_ID]']");
	  	if(formOrder.querySelector('.custom_name_site') == null && inputSite != null)
    	{    		
    		formOrder.prepend(
				BX.create('DIV', {
					attrs: {class: 'custom_name_site ui-entity-editor-section'},
					style: {
						display: 'flex',
						paddingTop: '10px',
						paddingBottom: '10px',
					},
					children: [
						BX.create('DIV', {
							style: {
								marginRight: '20px'
							},
							text: 'Сайт:'
						}),
						BX.create('DIV', {
							text: sites != null ? sites[inputSite.value].NAME : inputSite.value
						})
					]
				})
			);
    	}
	});

	BX.addCustomEvent("onLocalStorageSet", function(data) {
		if(data.value == null)
			return;
		if(data.value.entityTypeName === 'ORDER'){
			console.log(data.key);
			if(data.key === 'onCrmEntityCreate'){
				sendAjaxAddress({'ORDER_ID': data.value.entityId, 'ADDRESS': customOrderAddress.value});
			}
			if(data.key === 'onCrmEntityUpdate' && customOrderAddress.value != ''){
				sendAjaxAddress({'ORDER_ID': data.value.entityId, 'ADDRESS': customOrderAddress.value});
			}
		}
	});

	function addSuggestion(element){
		customOrderAddress.value = element.value;
		BX.bind(element, "change", BX.delegate(function(e){
			customOrderAddress.value = e.target.value;
		}, this));
		console.log(customOrderAddress.value);
		if(!element.classList.contains('suggestion')){
			$(element).suggestions({
	      token: "21c5d380f6d3cce414b312ea93eff1b329aa95c9",
	      type: "ADDRESS",
	      onSelect: function(suggestion) {
	      	customOrderAddress.value = suggestion.value;
	      }
	  	});
			element.classList.add('suggestion');
		}
	}

	function sendAjaxAddress(dataSend){
		BX.ajax({
			url: '/shop/orders/ajaxAddress.php',
			data: dataSend,
			method: 'POST',
			onsuccess: function(data){
				console.log('sendAjaxAddress', data);
				if(data == "" || data == null)
					return;
				var data = JSON.parse(data);
				if(data == null)
					return;
				if(data.address_new == null)
					return;
				let content = BX.create('DIV', {
					attrs: {
						class: 'popup-window-content'
					},
					text: 'Изменить адрес доставки у ' + data.name + ' с "' + data.address_old + '"' + ' на "' + data.address_new + '"?'
				});
				changeAddressPopup.setTitleBar('Подтверждение');
				changeAddressPopup.setContent(content);
				changeAddressPopup.setButtons([
					new changeAddressBtn({
						text: 'Да',
						className: 'ui-btn ui-btn-success',
						events: {
							click: BX.delegate(function(e){
								sendAjaxAddress({
									'YES': 'Y',
									'LOC_ADDR_ID': data.LOC_ADDR_ID,
									'ADDRESS_NEW': data.address_new,
									'ENTITY_ID': data.ENTITY_ID,
									'ANCHOR_ID': data.ANCHOR_ID,
									'ANCHOR_TYPE_ID': data.ANCHOR_TYPE_ID,
								});
								changeAddressPopup.close();
							}, this)
						}
					}),
					new changeAddressBtn({
						text: 'Нет',
						className: 'ui-btn ui-btn-link',
						events: {
							click: BX.delegate(function(e){
								changeAddressPopup.close();
							}, this)
						}
					})
				]);
				changeAddressPopup.show();
			}
		})
	}
});