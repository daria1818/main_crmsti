BX.ready(function(){
	var multiSelects = document.querySelectorAll('.r_multi_field--select'),
		searchInputs = document.querySelectorAll('.r_search_field--search'),
		selects = document.querySelectorAll('.r_select_field--select'),
		overflow = document.querySelector('.r_reports_filter--overflow'),
		form = document.querySelector('form[name="CUSTOM_REPORTS"]'),
		submit = document.querySelector('form[name="CUSTOM_REPORTS"] button'),
		errorBlock = document.querySelector('.r_reports_filter--error'),
		resultBlock = document.querySelector('.r_reports_result');

	var tableToExcel = (function() {
		var uri = 'data:application/vnd.ms-excel;base64,', template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--><meta http-equiv="content-type" content="text/plain; charset=UTF-8"/></head><body><table border="1">{table}</table></body></html>'
		, base64 = function(s) { return window.btoa(unescape(encodeURIComponent(s))) }
		, format = function(s, c) { 	    	 
			return s.replace(/{(\w+)}/g, function(m, p) { return c[p]; }) 
		}
		, downloadURI = function(uri, name) {
		    var link = document.createElement("a");
		    link.download = name;
		    link.href = uri;
		    link.click();
		}
		return function(table, name, fileName) {
			if (!table.nodeType) table = document.getElementById(table)
				var ctx = {worksheet: name || 'Worksheet', table: table.innerHTML}
			var resuri = uri + base64(format(template, ctx))
			downloadURI(resuri, fileName);
		}
	})();	

	var date = new Date();

	if(overflow){
		BX.bind(overflow, "click", BX.delegate(function(e){
			toggleSelects(document.querySelector('.r_multi_field--select.active'));
			toggleSelects(document.querySelector('.r_search_field--search.active'), false, false);
		}));
	}

	if(multiSelects.length > 0){
		for(let item of multiSelects){
			BX.bind(item, "click", BX.delegate(function(e){
				let elem = e.target;
				toggleSelects(elem);
				let text = BX.message('R_REPORTS_NO_SELECT');
				for(let check of elem.nextElementSibling.children){
					BX.bind(check.querySelector('input'), "change", BX.delegate(function(e){
						let labels = document.querySelectorAll('input[name="'+e.target.name+'"]:checked+label');
						if(labels.length > 0){
							text = "";
							for(let label of labels){
								text += label.innerText + ", ";
							}
							text = text.slice(0, -2);
						}else{
							text = BX.message('R_REPORTS_NO_SELECT');
						}
						item.innerText = text;
					}));
				}
			}));
		}
	}

	if(searchInputs.length > 0){
		for(let input of searchInputs){
			let hidden = null;
			if(input.name.match(/_text/)){
				hidden = document.querySelector('input[name="'+input.name.split(/_text/)[0]+'"]');
			}
			BX.bind(input, "keyup", BX.delegate(function(e){
				let elem = e.target;				
				if(elem.value.length > 2){
					for(let div of elem.nextElementSibling.children){
						let re = new RegExp(elem.value, "giu"),
							match = div.innerText.match(re);
						(match != null ? BX.addClass(div, 'active') : BX.removeClass(div, 'active'));
						
					}
					toggleSelects(elem, false, true);
				}else{
					toggleSelects(elem, false);
				}
			}));
			for(let div of input.nextElementSibling.children){
				BX.bind(div, 'click', BX.delegate(function(e){
					input.value = e.target.innerText;
					(hidden != null ? hidden.value = e.target.dataset.value : '');
					toggleSelects(input, false);
				}));
			}
		}
	}

	if(selects.length > 0){
		for(let select of selects){
			let parent = select.offsetParent, hidden = parent.querySelector('input[type="hidden"]');
			BX.bind(select, "click", BX.delegate(function(e){
				toggleSelects(e.target, false, true);
			}));
			for(let div of select.nextElementSibling.children){
				BX.bind(div, "click", BX.delegate(function(e){
					if(select.nextElementSibling.querySelector('.active') != null){
						BX.removeClass(select.nextElementSibling.querySelector('.active'), 'active');
					}
					if(parent.querySelector('.custom--input')){
						BX.remove(parent.querySelector('.custom--input'));
					}
					BX.addClass(e.target, 'active');
					let type = e.target.dataset.input;
					if(type == ""){
						hidden.value = e.target.dataset.code;
						select.innerText = e.target.innerText;
					}else{
						select.innerText = e.target.innerText;
						let input = BX.create('DIV', {
							attrs:{
								className: 'custom--input'
							},
							children: [
								BX.create('LABEL', {
									attrs: {
										for: 'customInput',
										className: 'item_name required_label'
									},
									text: BX.message('R_REPORTS_SET_'+type.toUpperCase())
								}),
								BX.create('INPUT', {
									attrs: {
										type: type,
										id: 'customInput',
										className: 'required',
										min: (type == 'number' ? '1' : ''),
										max: (type == 'date' ? date.getFullYear() + "-" + (parseInt(date.getMonth()+1) < 10 ? "0" + parseInt(date.getMonth()+1) : parseInt(date.getMonth()+1)) + "-" + date.getDate() : '100'),
									},
									events: {
										change: BX.delegate(function(e){
											if(e.target.value != "")
												hidden.value = (e.target.type == 'number' ? this.dataset.code.replace("N", e.target.value)  : e.target.value);
										}, this)
									}
								})
							]
						})
						parent.appendChild(input);
					}
					toggleSelects(select, false);
				}));
			}
		}
	}

	BX.bind(submit, 'click', BX.delegate(function(e){
		e.preventDefault();
		let errorText = [];
		for(let item of form.querySelectorAll('input.required')){
			if(item.value == "" || item.type == 'checkbox' && !item.checked && form.querySelector('input[name="'+item.name+'"]:checked') == null){
				errorText.push(BX.findParent(item, {className: 'item'}).querySelector('.item_name').innerText);
			}
		}

		if(errorText.length > 0){
			let unique = errorText.filter((value, index, self) => self.indexOf(value) === index);
			errorBlock.innerText = BX.message('R_REPORTS_ERROR') + unique.join(', ');
		}else{
			let fields = BX.findChildren(form, {tag: 'input'}, true),
				postData = {};
			for(let f in fields)
			{
				if(fields[f].type == 'radio' && !fields[f].checked)
					continue;	
				if(fields[f].type != 'checkbox'){
					postData[fields[f].name] = fields[f].value;
				}
				if(fields[f].type == 'checkbox' && fields[f].checked){
					if(postData[fields[f].name] == null)
						postData[fields[f].name] = [fields[f].value]
					else
						postData[fields[f].name].push(fields[f].value);
				}
			}

			postData['AJAX_CALL'] = 'Y';

			BX.ajax({
				url: rReportAjaxPath,
				data: postData,
				method: 'POST',
				dataType: 'json',
				onsuccess: function(result){
					if(result.length == 0){
                        resultBlock.innerText = "По вашему запросу нет данных";
					}else{
						resultBlock.innerText = "";		
						let table = document.createElement('table');
						table.setAttribute("id", "report_table");
						table.setAttribute("width", "100%");
						table.setAttribute("border", "1");
						resultBlock.append(BX.create('A', {
							attrs: {
								id: 'download',
								href: 'javascript:void(0)',
							},
							text: 'Экпорт в Excel',
							events: {
								click: BX.delegate(function(){
									tableToExcel('report_table', 'Отчет о продаже товаров по клиентам', 'report.xls');
								}, this)
							}
						}), table);
						table.append(
							BX.create('THEAD', {
								children: [
									BX.create('TR', {
										children: [
											BX.create('TD', {
												text: 'Наименование клиента'
											}),
											BX.create('TD', {
												text: 'Количество выполненных заказов'
											}),
											BX.create('TD', {
												text: 'Сумма оплат'
											}),
											BX.create('TD', {
												text: 'Регион'
											}),
											BX.create('TD', {
												text: 'Ответственный'
											})
										]
									})
								]
							}),
							BX.create('TBODY', {})
						);

						for(let row of Object.keys(result)){
							let newRow = table.tBodies[0].insertRow(0);
							let newCell = newRow.insertCell(0);

							newCell.innerHTML = result[row].INFO;

							newCell = newRow.insertCell(1);

							newCell.innerText = result[row].QUANTITY;

							newCell = newRow.insertCell(2);

							newCell.innerText = result[row].SUM;

							newCell = newRow.insertCell(3);

							newCell.innerText = (result[row].ADDRESS != null ? result[row].ADDRESS : '');

							newCell = newRow.insertCell(4);

							newCell.innerHTML = result[row].RESPONSIBLE;
						}
					}
				}
			});
		}
	}));

	MutationObserver = window.MutationObserver || window.WebKitMutationObserver;

	var trackChange = function(element) {
	  var observer = new MutationObserver(function(mutations, observer) {
	    if(mutations[0].attributeName == "value") {
	        if(element.value != "")
	        	element.nextElementSibling.style.display = 'block';
	        else
	        	element.nextElementSibling.style.display = 'none';
	    }
	  });
	  observer.observe(element, {
	    attributes: true
	  });
	}

	trackChange(BX('r_reports_sectionid'));

	BX.bind(document.querySelector('.r_section_remove'), 'click', BX.delegate(function(e){
		BX('r_reports_sectionid').value = "";
	}));


	function toggleSelects(elem, toggle = true, active = false){
		if(elem == null)
			return;

		if(toggle){
			BX.toggleClass(elem, "active");
			BX.toggleClass(elem.nextElementSibling, "active");
			BX.toggleClass(overflow, "active");
			return;
		}

		if(!active){
			BX.removeClass(elem, "active");
			BX.removeClass(elem.nextElementSibling, "active");
			BX.removeClass(overflow, "active");
		}else{
			BX.addClass(elem, "active");
			BX.addClass(elem.nextElementSibling, "active");
			BX.addClass(overflow, "active");
		}
	}
});