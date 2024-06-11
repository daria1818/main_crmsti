<?php

use Bitrix\Main\Context;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityProgressBarComponent $component */

CJSCore::Init(array("jquery"));

$guid = $arResult['GUID'];
$prefix = mb_strtolower($guid);
$activityEditorID = "{$prefix}_editor";

\Bitrix\Main\UI\Extension::load(["crm.scoringbutton"]);

//region LEGEND
if (isset($arResult['LEGEND'])) {
    $this->SetViewTarget('crm_details_legend');
    ?><a href="#"
         onclick="BX.Crm.DealCategoryChanger.processEntity(<?= $arResult['ENTITY_ID'] ?>,{ usePopupMenu: true, anchor: this }); return false;">
    <?= htmlspecialcharsbx($arResult['LEGEND']) ?>
    </a><?
    $this->EndViewTarget();
}
//endregion

$APPLICATION->IncludeComponent(
    'bitrix:crm.activity.editor',
    '',
    array(
        'CONTAINER_ID' => '',
        'EDITOR_ID' => $activityEditorID,
        'PREFIX' => $prefix,
        'ENABLE_UI' => false,
        'ENABLE_TOOLBAR' => false,
        'ENABLE_EMAIL_ADD' => true,
        'ENABLE_TASK_ADD' => $arResult['ENABLE_TASK'],
        'MARK_AS_COMPLETED_ON_VIEW' => false,
        'SKIP_VISUAL_COMPONENTS' => 'Y'
    ),
    $component,
    array('HIDE_ICONS' => 'Y')
);

$APPLICATION->IncludeComponent(
    'bitrix:crm.deal.menu',
    '',
    array(
        'PATH_TO_DEAL_LIST' => $arResult['PATH_TO_DEAL_LIST'],
        'PATH_TO_DEAL_SHOW' => $arResult['PATH_TO_DEAL_SHOW'],
        'PATH_TO_DEAL_EDIT' => $arResult['PATH_TO_DEAL_EDIT'],
        'PATH_TO_DEAL_FUNNEL' => $arResult['PATH_TO_DEAL_FUNNEL'],
        'PATH_TO_DEAL_IMPORT' => $arResult['PATH_TO_DEAL_IMPORT'],
        'ELEMENT_ID' => $arResult['ENTITY_ID'],
        'CATEGORY_ID' => $arResult['CATEGORY_ID'],
        'MULTIFIELD_DATA' => isset($arResult['ENTITY_DATA']['MULTIFIELD_DATA'])
            ? $arResult['ENTITY_DATA']['MULTIFIELD_DATA'] : array(),
        'OWNER_INFO' => $arResult['ENTITY_INFO'],
        'CONVERSION_PERMITTED' => $arResult['CONVERSION_PERMITTED'],
        'IS_RECURRING' => $arResult['ENTITY_DATA']['IS_RECURRING'],
        'BIZPROC_STARTER_DATA' => $arResult['BIZPROC_STARTER_DATA'],
        'TYPE' => 'details',
        'SCRIPTS' => array(
            'DELETE' => 'BX.Crm.EntityDetailManager.items["' . CUtil::JSEscape($guid) . '"].processRemoval();',
            'EXCLUDE' => 'BX.Crm.EntityDetailManager.items["' . CUtil::JSEscape($guid) . '"].processExclusion();'
        )
    ),
    $component
);

?>
    <script type="text/javascript">
        BX.message({
            "CRM_TIMELINE_HISTORY_STUB": "<?=GetMessageJS('CRM_DEAL_DETAIL_HISTORY_STUB')?>",
        });

        <? if($arResult['ENTITY_ID'] > 0): ?>
        new BX.CrmScoringButton({
            mlInstalled: <?= (\Bitrix\Crm\Ml\Scoring::isMlAvailable() ? 'true' : 'false')?>,
            scoringEnabled: <?= (\Bitrix\Crm\Ml\Scoring::isEnabled() ? 'true' : 'false')?>,
            scoringParameters: <?= \Bitrix\Main\Web\Json::encode($arResult['SCORING']) ?>,
            entityType: '<?= CCrmOwnerType::DealName ?>',
            entityId: <?= (int)$arResult['ENTITY_ID']?>,
            isFinal: <?= $arResult['IS_STAGE_FINAL'] ? 'true' : 'false' ?>,
        });
        <? endif; ?>
    </script><?

$editorContext = array('PARAMS' => $arResult['CONTEXT_PARAMS']);
if (isset($arResult['ORIGIN_ID']) && $arResult['ORIGIN_ID'] !== '') {
    $editorContext['ORIGIN_ID'] = $arResult['ORIGIN_ID'];
}
if (isset($arResult['INITIAL_DATA'])) {
    $editorContext['INITIAL_DATA'] = $arResult['INITIAL_DATA'];
}

$APPLICATION->IncludeComponent(
    'bitrix:crm.entity.details',
    '',
    array(
        'GUID' => $guid,
        'ENTITY_TYPE_ID' => ($arResult['ENTITY_DATA']['IS_RECURRING'] !== 'Y') ? \CCrmOwnerType::Deal : \CCrmOwnerType::DealRecurring,
        'ENTITY_ID' => $arResult['IS_EDIT_MODE'] ? $arResult['ENTITY_ID'] : 0,
        'ENTITY_INFO' => $arResult['ENTITY_INFO'],
        'READ_ONLY' => $arResult['READ_ONLY'],
        'TABS' => $arResult['TABS'],
        'SERVICE_URL' => '/bitrix/components/bitrix/crm.deal.details/ajax.php?' . bitrix_sessid_get(),
        'EDITOR' => array(
            'GUID' => "{$guid}_editor",
            'CONFIG_ID' => $arResult['EDITOR_CONFIG_ID'],
            'ENTITY_CONFIG' => $arResult['ENTITY_CONFIG'],
            'ENTITY_CONTROLLERS' => $arResult['ENTITY_CONTROLLERS'],
            'ENTITY_FIELDS' => $arResult['ENTITY_FIELDS'],
            'ENTITY_DATA' => $arResult['ENTITY_DATA'],
            'ENABLE_SECTION_EDIT' => true,
            'ENABLE_SECTION_CREATION' => true,
            'ENABLE_USER_FIELD_CREATION' => $arResult['ENABLE_USER_FIELD_CREATION'],
            'USER_FIELD_ENTITY_ID' => $arResult['USER_FIELD_ENTITY_ID'],
            'USER_FIELD_CREATE_PAGE_URL' => $arResult['USER_FIELD_CREATE_PAGE_URL'],
            'USER_FIELD_CREATE_SIGNATURE' => $arResult['USER_FIELD_CREATE_SIGNATURE'],
            'USER_FIELD_FILE_URL_TEMPLATE' => $arResult['USER_FIELD_FILE_URL_TEMPLATE'],
            'SERVICE_URL' => '/bitrix/components/bitrix/crm.deal.details/ajax.php?' . bitrix_sessid_get(),
            'EXTERNAL_CONTEXT_ID' => $arResult['EXTERNAL_CONTEXT_ID'],
            'CONTEXT_ID' => $arResult['CONTEXT_ID'],
            'CONTEXT' => $editorContext,
            'ATTRIBUTE_CONFIG' => array(
                'ENTITY_SCOPE' => $arResult['ENTITY_ATTRIBUTE_SCOPE'],
                'CAPTIONS' => array(
                    'REQUIRED_SHORT' => GetMessage('CRM_DEAL_DETAIL_ATTR_REQUIRED_SHORT'),
                    'REQUIRED_FULL' => GetMessage('CRM_DEAL_DETAIL_ATTR_REQUIRED_FULL'),
                    'GROUP_TYPE_GENERAL' => GetMessage('CRM_DEAL_DETAIL_ATTR_GR_TYPE_GENERAL'),
                    'GROUP_TYPE_PIPELINE' => GetMessage('CRM_DEAL_DETAIL_ATTR_GR_TYPE_PIPELINE'),
                    'GROUP_TYPE_JUNK' => GetMessage('CRM_DEAL_DETAIL_ATTR_GR_TYPE_JUNK')
                )
            )
        ),
        'TIMELINE' => array(
            'GUID' => "{$guid}_timeline",
            'ENABLE_WAIT' => true,
            'PROGRESS_SEMANTICS' => $arResult['PROGRESS_SEMANTICS'],
            'WAIT_TARGET_DATES' => $arResult['WAIT_TARGET_DATES']
        ),
        'ENABLE_PROGRESS_BAR' => true,
        'ENABLE_PROGRESS_CHANGE' => ($arResult['ENTITY_DATA']['IS_RECURRING'] !== 'Y' && !$arResult['READ_ONLY']),
        'ACTIVITY_EDITOR_ID' => $activityEditorID,
        'EXTRAS' => array('CATEGORY_ID' => $arResult['CATEGORY_ID']),
        'ANALYTIC_PARAMS' => array('deal_category' => $arResult['CATEGORY_ID']),
        'PATH_TO_USER_PROFILE' => $arResult['PATH_TO_USER_PROFILE']
    )
);

if ($arResult['CONVERSION_PERMITTED'] && $arResult['CAN_CONVERT'] && isset($arResult['CONVERSION_CONFIG'])):
    ?>
    <script type="text/javascript">
        BX.ready(
            function () {
                BX.CrmDealConversionScheme.messages =
                <?=CUtil::PhpToJSObject(\Bitrix\Crm\Conversion\DealConversionScheme::getJavaScriptDescriptions(false))?>;

                BX.CrmDealConverter.messages =
                    {
                        accessDenied: "<?=GetMessageJS("CRM_DEAL_CONV_ACCESS_DENIED")?>",
                        generalError: "<?=GetMessageJS("CRM_DEAL_CONV_GENERAL_ERROR")?>",
                        dialogTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_TITLE")?>",
                        syncEditorLegend: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_LEGEND")?>",
                        syncEditorFieldListTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_FILED_LIST_TITLE")?>",
                        syncEditorEntityListTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_ENTITY_LIST_TITLE")?>",
                        continueButton: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_CONTINUE_BTN")?>",
                        cancelButton: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_CANCEL_BTN")?>"
                    };
                BX.CrmDealConverter.permissions =
                    {
                        invoice: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_INVOICE'])?>,
                        quote: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_QUOTE'])?>
                    };
                BX.CrmDealConverter.settings =
                    {
                        serviceUrl: "<?='/bitrix/components/bitrix/crm.deal.details/ajax.php?action=convert&' . bitrix_sessid_get()?>",
                        config: <?=CUtil::PhpToJSObject($arResult['CONVERSION_CONFIG']->toJavaScript())?>
                    };
                BX.CrmEntityType.setCaptions(<?=CUtil::PhpToJSObject(CCrmOwnerType::GetJavascriptDescriptions())?>);
                BX.onCustomEvent(window, "BX.CrmEntityConverter:applyPermissions", [BX.CrmEntityType.names.deal]);
            }
        );
    </script><?
endif;
$request = \Bitrix\Main\Context::getCurrent()->getRequest();
$orderId = $request->get("order_id");
$newResult = [
    'ENTITY_ID' => $arResult['ENTITY_ID'],
    'PERMISSION_ENTITY_TYPE' => $arResult['PERMISSION_ENTITY_TYPE'],
    'READ_ONLY' => $arResult['READ_ONLY'],
    'PRODUCT_DATA_FIELD_NAME' => $arResult['PRODUCT_DATA_FIELD_NAME'],
    'PATH_TO_PRODUCT_EDIT' => $arResult['PATH_TO_PRODUCT_EDIT'],
    'PATH_TO_PRODUCT_SHOW' => $arResult['PATH_TO_PRODUCT_SHOW'],
    'ENTITY_DATA' => [
        'CURRENCY_ID' => $arResult['ENTITY_DATA']['CURRENCY_ID'],
        'OPPORTUNITY' => $arResult['ENTITY_DATA']['OPPORTUNITY'],
        'TYPE_ID' => $arResult['ENTITY_DATA']['TYPE_ID'],
        'STAGE_ID' => $arResult['ENTITY_DATA']['STAGE_ID'],
        'TAX_VALUE' => $arResult['ENTITY_DATA']['TAX_VALUE'],
        'COMPANY_ID' => $arResult['ENTITY_DATA']['COMPANY_ID'],
        'LOCATION_ID' => $arResult['ENTITY_DATA']['LOCATION_ID'],
        'CLIENT_INFO' => $arResult['ENTITY_DATA']['CLIENT_INFO']
    ]
];
$_SESSION['CUSTOM_DEAL_INFO'] = serialize($newResult);
?>
<script>
    $(function(){
        setTimeout(() => $('[name=UF_CRM_1607379225]').val(<?=$orderId?>), 5000);
    });
    BX.ready(function(){
        BX.addCustomEvent("SidePanel.Slider:onLoad", function(event){
            let slider = event.getSlider();
            if(slider.iframe == null)
                return;

            let body = slider.iframe.contentWindow.document.body;
            var btnCustom = body.querySelector('button.crm-entity-widget-content-block-inner-pay-button');
            let stage = body.querySelector('[data-cid="STAGE_ID"] .ui-entity-editor-content-block-text');
            if(btnCustom){
                if(stage == null || stage.innerText != 'Готов к заказу')
                    btnCustom.setAttribute('disabled', "disabled");
                else
                    btnCustom.removeAttribute("disabled");
            }
            for(let step of body.querySelectorAll('.crm-entity-section-status-step')){
                BX.bind(step, "click", BX.delegate(function(e){
                    if(step.dataset.id == 'EXECUTING'){
                        btnCustom.removeAttribute("disabled");
                    }
                    else{
                        btnCustom.setAttribute('disabled', "disabled");
                    }
                }));
            }
        });
    });
</script>