BX.ready(function(){
	class IframeCreateOrder{
		constructor(result){
			this.result = result;
			this.form = BX('iframe-event-edit-wrapper');
			this.messageAvailable = this.form.querySelector('.iframe-message-no-available');
		}
		processSuccess(data)
		{
			if (BX.type.isNotEmptyString(data.redirectUrl))
			{
				document.location.href = data.redirectUrl;
			}
		}
		clearErrors()
		{
			BX.cleanNode(BX('bx-crm-error'));
		}

		fillErrors(errors)
		{
			var errorNode = BX('bx-crm-error');
			var html = '';

			errors.forEach(function(error){
				html += '<div class="crm-entity-widget-content-error-text">' + error.message + '</div>';
			});

			errorNode.innerHTML = html;
			BX.scrollToNode(errorNode);
		}
		saveAction(event){
			var button = BX.getEventTarget(event);

			BX.addClass(button, 'ui-btn-clock');

			BX.ajax.runComponentAction(
				this.result.componentName,
				'saveOrderAjax',
				{
					mode: 'class',
					signedParameters: this.result.signedParameters,
					data: BX.ajax.prepareForm(this.form)
				}
			)
				.then(function (response) {
					BX.removeClass(button, 'ui-btn-clock');
					this.clearErrors();
					this.processSuccess(response.data);
				}.bind(this))
				.catch(function (response) {
					BX.removeClass(button, 'ui-btn-clock');
					this.fillErrors(response.errors);
				}.bind(this));
		}
		getProductPrice(params){
			BX.ajax.runComponentAction(
				this.result.componentName,
				'getProductPrice',
				{
					mode: 'ajax',
					data: {
						id: params.id
					}
				}
			)
				.then(function (response) {
					params.price = response.data.ajaxRejectData.PRICE;
					params.full_quantity = response.data.ajaxRejectData.QUANTITY;
					this.insertProductTable(params);
				}.bind(this))
				.catch(function (response) {
					params.price = response.data.ajaxRejectData.PRICE;
					params.full_quantity = response.data.ajaxRejectData.QUANTITY;
					this.insertProductTable(params);
				}.bind(this));
		}
		addProductHandler(){
			BX.Crm.Order.Product.listObj_crm_order_product_list.addProductSearch({lang: 'ru', siteId: 'pn', orderId: '0'});
		}
		closeSliderHandler(){
			window.top.BX.SidePanel.Instance.close();
		}
		saveClickHandler(event){
			this.saveAction(event);
		}
		changeRow(e){
			let target = BX.getEventTarget(e);
			let tr = BX.findParent(target, {tagName: 'tr'});
			let values = [];
			for(let input of tr.querySelectorAll('input[type="number"]')){
				values.push(input.value);
			}
			let sum = values.map(Number).reduce(function(price, quantity) { return price * quantity; });
			tr.querySelector('.sum').innerText = sum;
		}
		toggleMessage(){
			this.messageAvailable.style.display = (this.form.querySelectorAll('.iframe-product-disable').length > 0 ? 'block' : 'none');
		}
		bindEvents(){
			this.toggleMessage();
			BX.bind(BX('IFRAME_CREATE_ORDER_ADD_PRODUCT'), 'click', BX.proxy(this.addProductHandler, this))
			BX.bind(BX('IFRAME_CREATE_ORDER_CANCEL'), 'click', BX.proxy(this.closeSliderHandler, this));
			BX.bind(BX('IFRAME_CREATE_ORDER_APPLY_BUTTON'), 'click', BX.proxy(this.saveClickHandler, this));
			let del = this.form.querySelectorAll('.iframe-product-item-delete');
			if(del.length > 0){
				for(let del_elem of del){
					BX.bind(del_elem, 'click', BX.delegate(function(e){
						del_elem.parentNode.remove();
						this.toggleMessage();
					}, this));
				}
			}
			let inputs = this.form.querySelectorAll('input[type="number"]');
			if(inputs){
				for(let input of inputs){
					BX.bind(input, 'change', BX.proxy(this.changeRow, this));
				}
			}			

			this.table = this.form.querySelector('.iframe-product-table tbody');

			var funcNameIframe = 'IframeCreateOrderProduct';					
			window[funcNameIframe] = BX.proxy(function(params, iblockId){this.onProductAdd(params, iblockId);}, this);
		}
		onProductAdd(params, iblockId){
			if(this.table)
				this.getProductPrice(params);
		}
		insertProductTable(params){
			var full_quantity = parseInt(params.full_quantity);
			var item = BX.create('TR', {
				attrs: {
					class: (full_quantity > 0 ? 'iframe-product-item' : 'iframe-product-item iframe-product-disable')
				},
				dataset: {
					id: params.id
				},
				children: [
					BX.create('TD', {
						text: params.name
					})
				]
			});
			if(full_quantity > 0){
					item.append(
						BX.create('TD', {
							children: [
								BX.create('INPUT', {
									attrs: {
										type: 'number',
										name: 'PRODUCTS['+params.id+'][PRICE]',
										value: params.price
									},
									events: {
										change: BX.proxy(this.changeRow, this)
									}
								})
							]
						}),
						BX.create('TD', {
							children: [
								BX.create('INPUT', {
									attrs: {
										type: 'number',
										name: 'PRODUCTS['+params.id+'][QUANTITY]',
										value: params.quantity,
										max: full_quantity
									},
									events: {
										change: BX.proxy(this.changeRow, this)
									}
								})
							]
						})
					);
			}else{
					item.append(
						BX.create('TD', {
							children: [
								BX.create('SPAN', {
									attrs: {
										class: 'input-disabled'
									},
									text: params.price
								})
							]
						}),
						BX.create('TD', {
							children: [
								BX.create('SPAN', {
									attrs: {
										class: 'input-disabled'
									},
									text: params.quantity
								})
							]
						})
					);
			}
			item.append(
					BX.create('TD', {
						text: params.measure
					}),
					BX.create('TD', {
						attrs: {
							class: 'sum'
						},
						text: params.price*params.quantity
					}),
					BX.create('TD', {
						attrs: {
							class: 'iframe-product-item-delete'
						},
						children: [
							BX.create('SPAN', {
								attrs: {
									class: 'icon'
								},
								events: {
									click: BX.delegate(function(e){
										item.remove();
										this.toggleMessage();
									}, this)
								}
							})
						]
					})
			);
			this.table.append(item);
			this.toggleMessage();
		}
	}	
	if(typeof iframeCreateOrder == 'object'){
		const IframeCreateOrderClass = new IframeCreateOrder(iframeCreateOrder);
		IframeCreateOrderClass.bindEvents();
	}
});