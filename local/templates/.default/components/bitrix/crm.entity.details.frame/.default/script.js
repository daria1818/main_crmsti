'use strict';
var bpConnect = (function () {
    var field;
    var enumSelect;
    var fieldValue;
    var propCode;
    var stage;
    var getField = function () {
        BX.ajax.runComponentAction('pwd:crm.bpconnect',
            'getField', { // Вызывается без постфикса Action
                mode: 'class',
                processData: false,
                contentType: false,
                type: 'POST',
            })
            .then(function (data) {
                field = data.data.field;
                enumSelect = data.data.enum;
            });
    };

    var checkField = function (props) {
        stage = true;
        BX.ajax.runComponentAction('pwd:crm.bpconnect',
            'getFieldValue', { // Вызывается без постфикса Action
                mode: 'class',
                processData: false,
                contentType: false,
                type: 'POST',
                data: {
                    idDeal: props._data.ID,
                    idField: propCode
                },
            })
            .then(function (data) {
                fieldValue = data.data.field.VALUE;
                if (enumSelect.ID === fieldValue) {
                    BX.loadExt('salescenter.manager').then(function () {
                        BX.Salescenter.Manager.openApplication({
                            disableSendButton: '',
                            context: 'deal',
                            analyticsLabel: 'salescenterClickButtonPay',
                            ownerTypeId: BX.CrmEntityType.enumeration.deal,
                            ownerId: props._data.ID,
                            sliderOptions: {width: 1500}
                        }).then(function (result) {
                        }.bind(this));
                    }.bind(this));
                }
            });
        // }
    };

    var fieldCode = function (props) {
        getField();
        if (field !== undefined && props._id === field.FIELD_NAME && props._mode === 2) {
            propCode = props._id;
        }
    };

    var stageChange = function () {
        stage = true;
    };

    var checkEntityEditorFieldonLayout = function (props) {
        if (stage && props._id == field.FIELD_NAME && props._mode == 2) {
            BX.ajax.runComponentAction('pwd:crm.bpconnect',
                'getFieldValue', { // Вызывается без постфикса Action
                    mode: 'class',
                    processData: false,
                    contentType: false,
                    type: 'POST',
                    data: {
                        idDeal: props._manager._entityId,
                        idField: props._id
                    },
                })
                .then(function (data) {
                    fieldValue = data.data.field.VALUE;
                    if (enumSelect.ID === fieldValue) {
                        BX.loadExt('salescenter.manager').then(function () {
                            BX.Salescenter.Manager.openApplication({
                                disableSendButton: '',
                                context: 'deal',
                                analyticsLabel: 'salescenterClickButtonPay',
                                ownerTypeId: BX.CrmEntityType.enumeration.deal,
                                ownerId: props._manager._entityId,
                                sliderOptions: {width: 1500}
                            }).then(function (result) {
                            }.bind(this));
                        }.bind(this));
                    }
                });
        }
    };

    var checkEntityEditorFieldonLayoutFromKanban = function (props) {
        BX.ajax.runComponentAction('pwd:crm.bpconnect',
            'getFieldValue', { // Вызывается без постфикса Action
                mode: 'class',
                processData: false,
                contentType: false,
                type: 'POST',
                data: {
                    idDeal: props.id,
                    idField: field.FIELD_NAME
                },
            })
            .then(function (data) {
                fieldValue = data.data.field.VALUE;
                if (enumSelect.ID === fieldValue) {
                    BX.loadExt('salescenter.manager').then(function () {
                        BX.Salescenter.Manager.openApplication({
                            disableSendButton: '',
                            context: 'deal',
                            analyticsLabel: 'salescenterClickButtonPay',
                            ownerTypeId: BX.CrmEntityType.enumeration.deal,
                            ownerId: props._manager._entityId,
                            sliderOptions: {width: 1500}
                        }).then(function (result) {
                        }.bind(this));
                    }.bind(this));
                }
            });
    };

    var checkEntityEditorFieldonLayoutFromList = function (props) {
        if(props['entityData'][field.FIELD_NAME].IS_EMPTY == false && props['entityData'][field.FIELD_NAME].VALUE == enumSelect.ID){
            BX.loadExt('salescenter.manager').then(function () {
                BX.Salescenter.Manager.openApplication({
                    disableSendButton: '',
                    context: 'deal',
                    analyticsLabel: 'salescenterClickButtonPay',
                    ownerTypeId: BX.CrmEntityType.enumeration.deal,
                    ownerId: props.entityId,
                    sliderOptions: {width: 1500}
                }).then(function (result) {
                }.bind(this));
            }.bind(this));
        }
    };

    var checkEntityEditorFieldonLayoutEntityModel = function (props) {
        if(props['_initData'][field.FIELD_NAME].IS_EMPTY == false && props['_initData'][field.FIELD_NAME].VALUE == enumSelect.ID){
            BX.loadExt('salescenter.manager').then(function () {
                BX.Salescenter.Manager.openApplication({
                    disableSendButton: '',
                    context: 'deal',
                    analyticsLabel: 'salescenterClickButtonPay',
                    ownerTypeId: BX.CrmEntityType.enumeration.deal,
                    ownerId: props['_initData'].id,
                    sliderOptions: {width: 1500}
                }).then(function (result) {
                }.bind(this));
            }.bind(this));
        }
    };

    return {
        getField: getField,
        checkField: checkField,
        fieldCode: fieldCode,
        checkEntityEditorFieldonLayout: checkEntityEditorFieldonLayout,
        checkEntityEditorFieldonLayoutFromList: checkEntityEditorFieldonLayoutFromList,
        checkEntityEditorFieldonLayoutFromKanban: checkEntityEditorFieldonLayoutFromKanban,
        checkEntityEditorFieldonLayoutEntityModel: checkEntityEditorFieldonLayoutEntityModel,
        stageChange: stageChange,
    };
})();

bpConnect.getField();
$(function () {
    bpConnect.getField();

    BX.addCustomEvent('Crm.EntityModel.Change', BX.delegate(
        function (props) {
            console.log('Crm.EntityModel.Change')
            //console.log(props)

            if (props != undefined && props._data != undefined && props._data.STAGE_ID === 'WON') {
                bpConnect.stageChange();
                setTimeout(bpConnect.checkField, 1000, props);
            }
        }
    ));
    BX.addCustomEvent('Crm.EntityProgress.Change', BX.delegate(
        function (props) {
            console.log('Crm.EntityProgress.Change')
            //console.log(props)

            bpConnect.stageChange();
            if(props._entityType == "ORDER" && props._currentSemantics == "failure"){
                props._settings.serviceUrl = props._settings.serviceUrl.replace(/bitrix\/components/, 'local/components');
            }
        }
    ));
    BX.addCustomEvent("CrmProcessFailureDialogContentCreated", BX.delegate(
        function(val){
            if(val.getValue() == "D"){
                let content = val.getWrapper().querySelector('.crm-invoice-term-dialog-params');
                if(CustomFailureList != null && CustomFailureList.length > 0)
                {
                    var textareaReason = content.querySelector('textarea');
                    var commentHeader = content.querySelector('.comment-header');
                    let select = BX.create('SELECT', {
                        attrs: {
                            name: 'CUSTOM_FAILURE_SELECT',
                        },
                        events: {
                            change: BX.delegate(function(e){
                                let target = e.target;
                                let option = target.options[target.selectedIndex];
                                target.nextElementSibling.value = option.value;
                                textareaReason.style.display = (option.dataset.comment == 0 ? 'none' : 'block');
                                commentHeader.style.display = (option.dataset.comment == 0 ? 'none' : 'block');
                            })
                        }
                    });
                    for (var i = 0; i < CustomFailureList.length; i++) {
                        if(i == 0 && CustomFailureList[i].UF_TEXTAREA == 0){
                            textareaReason.style.display = 'none';
                            commentHeader.style.display = 'none';
                        }
                        select.appendChild(
                            BX.create('OPTION', {
                                attrs: {
                                    value: CustomFailureList[i].ID
                                },
                                dataset: {
                                    comment: CustomFailureList[i].UF_TEXTAREA
                                },
                                text: CustomFailureList[i].UF_NAME
                            })
                        );
                    }
                    content.prepend(BX.create('DIV', {
                        attrs: {
                            class: 'custom-failure-list'
                        },
                        children: [
                            BX.create('LABEL', {
                                attrs: {
                                    for: 'CUSTOM_FAILURE'
                                },
                                text: 'Причины отмены заказа'
                            }),
                            select,
                            BX.create('INPUT', {
                                attrs:{
                                    type: 'text',
                                    name: 'CUSTOM_FAILURE',
                                    value: '1'
                                },
                                style: {
                                    display: 'none'
                                }
                            })
                        ]
                    }));
                }
            }            
        }
    ));
    BX.addCustomEvent("Crm.EntityProgress.onSaveBefore", BX.delegate(
        function(progressControl, params){
            console.log(progressControl,params);
            if(progressControl._entityType !== "ORDER")
                return;
            // let select = progressControl.getWrapper().querySelector("select");
            // if(select == null)
            //     return;
            // params[select.name] = select.options[selectedIndex].value;
            // console.log(params);
        }
    ));
    BX.addCustomEvent('BX.UI.EntityEditorField:onLayout', BX.delegate(
        function (props) {
            console.log('BX.UI.EntityEditorField:onLayout')
            console.log(props)

            bpConnect.fieldCode(props);
            setTimeout(bpConnect.checkEntityEditorFieldonLayout, 1000, props);
        }
    ));
    BX.addCustomEvent('onCrmEntityUpdate', BX.delegate(
        function (props) {
            console.log('onCrmEntityUpdate')
            console.log(props)

            setTimeout(bpConnect.checkEntityEditorFieldonLayoutFromList, 1000, props);
        }
    ));
    BX.addCustomEvent('Kanban.Grid:onItemMoved', BX.delegate(
        function (props) {
            console.log('Kanban.Grid:onItemMoved')
            console.log(props)

            setTimeout(bpConnect.checkEntityEditorFieldonLayoutFromKanban, 1000, props);
        }
    ));
});
