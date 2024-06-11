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
                // console.log(data);
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
                // console.log(data);
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
        // console.log(field);
        if (field !== undefined && props._id === field.FIELD_NAME && props._mode === 2) {
            propCode = props._id;
        }
    };

    var stageChange = function () {
        stage = true;
    };

    var checkEntityEditorFieldonLayout = function (props) {
        console.log(stage);
        console.log(props);
        console.log(field);
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
                    // console.log('checkEntityEditorFieldonLayout', data);
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
                    // console.log('checkEntityEditorFieldonLayout', data);
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
    BX.addCustomEvent('Crm.EntityProgress.onSaveBefore', BX.delegate(
        function (props) {
            // console.log('Crm.EntityProgress.onSaveBefore');
            bpConnect.fieldCode(props);
            setTimeout(bpConnect.checkEntityEditorFieldonLayout, 1000, props);
        }
    ));
    BX.addCustomEvent('Crm.EntityModel.Change', BX.delegate(
        function (props) {
            // console.log('Crm.EntityModel.Change');
            if (props != undefined && props._data != undefined && props._data.STAGE_ID === 'WON') {
                console.log('Crm.EntityModel.Change - WON');
                bpConnect.stageChange();
                setTimeout(bpConnect.checkField, 1000, props);
            }
        }
    ));
    BX.addCustomEvent('Crm.EntityProgress.Change', BX.delegate(
        function (props) {
            // console.log('stageChange');
            bpConnect.stageChange();
        }
    ));
    BX.addCustomEvent('BX.UI.EntityEditorField:onLayout', BX.delegate(
        function (props) {
            console.log('BX.UI.EntityEditorField:onLayout');
            bpConnect.fieldCode(props);
            setTimeout(bpConnect.checkEntityEditorFieldonLayout, 1000, props);
        }
    ));
    BX.addCustomEvent('onCrmEntityUpdate', BX.delegate(
        function (props) {
            console.log('onCrmEntityUpdate');
            setTimeout(bpConnect.checkEntityEditorFieldonLayoutFromList, 1000, props);
        }
    ));
    BX.addCustomEvent('Kanban.Grid:onItemMoved', BX.delegate(
        function (props) {
            console.log('Kanban.Grid:onItemMoved');
            setTimeout(bpConnect.checkEntityEditorFieldonLayoutFromKanban, 1000, props);
        }
    ));
    BX.addCustomEvent('Crm.EntityModel.Change', BX.delegate(
        function (props) {
            console.log('Crm.EntityModel.Change');
            setTimeout(bpConnect.checkEntityEditorFieldonLayoutEntityModel, 1000, props);
        }
    ));
});
