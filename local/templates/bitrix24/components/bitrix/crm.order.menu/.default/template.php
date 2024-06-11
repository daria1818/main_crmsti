<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
CJSCore::Init(array("jquery"));
global $APPLICATION;

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/category.js');

if (!empty($arResult['BUTTONS'])) {
    $type = $arParams['TYPE'];
    $template = 'type2';
    if ($type === 'list' || $type === 'kanban') {
        $template = SITE_TEMPLATE_ID === 'bitrix24' ? 'title' : '';
    } else if ($type === 'details') {
        $template = SITE_TEMPLATE_ID === 'bitrix24' ? 'slider' : 'type2';
    }

    $arSites = [];
    foreach ($arResult['BUTTONS'] as &$value)
    {
        if(isset($value['ITEMS']) && !empty($value['ITEMS']))
        {
            $value['LINK'] = 'javascript:void(0)';
            $value['ONCLICK'] = 'custom_create()';
            $arSites = $value['ITEMS'];
            foreach($arSites as $key => $site)
            {
                if(preg_match('/STIOnline/', $site['TEXT']))
                    unset($arSites[$key]);
            }
            rsort($arSites);
        }
    }?>
    <script>
        var arSites = <?=CUtil::PhpToJSObject($arSites)?>;
        function custom_create()
        {            
            var choiceSite = BX.PopupWindowManager.create('choiceSite', null, {
                autoHide: false,
                offsetLeft: 0,
                offsetTop: 0,
                overlay: true,
                closeByEsc: true,
                titleBar: true,
                closeIcon: true,
                contentColor: 'white',
                className: 'choiceSite'
            });
            choiceSite.setTitleBar('Выбрать сайт');
            var choiceSiteBlock = BX.create('DIV', {
                attrs: {}
            });
            for (var i = 0; i < arSites.length; i++) {
                var siteItem = BX.create('A', {
                    attrs: {
                        class: 'ui-btn ui-btn-success',
                        href: 'javascript:void(0)'
                    },
                    text: arSites[i].TEXT,
                    dataset: {
                        onclick: arSites[i].ONCLICK
                    },
                    events: {
                        click: BX.delegate(function(e){
                            eval(e.target.dataset.onclick);
                            choiceSite.close();
                        }, this)
                    }
                });
                choiceSiteBlock.append(siteItem);
            }
            choiceSite.setContent(choiceSiteBlock);
            choiceSite.show();
        }
    </script>
    <?
    $APPLICATION->IncludeComponent(
        'bitrix:crm.interface.toolbar',
        $template,
        array(
            'TOOLBAR_ID' => $arResult['TOOLBAR_ID'],
            'BUTTONS' => $arResult['BUTTONS'],
        ),
        $component,
        array('HIDE_ICONS' => 'Y')
    );
}

if (isset($arResult['SONET_SUBSCRIBE']) && is_array($arResult['SONET_SUBSCRIBE'])):
    $subscribe = $arResult['SONET_SUBSCRIBE'];
    ?>
    <script type="text/javascript">
        BX.ready(
            function () {
                BX.CrmSonetSubscription.create(
                    "<?=CUtil::JSEscape($subscribe['ID'])?>",
                    {
                        "entityType": "<?=CCrmOwnerType::OrderName?>",
                        "serviceUrl": "<?=CUtil::JSEscape($subscribe['SERVICE_URL'])?>",
                        "actionName": "<?=CUtil::JSEscape($subscribe['ACTION_NAME'])?>"
                    }
                );
            }
        );
    </script><?
endif;
if (is_array($arResult['STEXPORT_PARAMS'])) {
    \Bitrix\Main\UI\Extension::load('ui.progressbar');
    \Bitrix\Main\UI\Extension::load('ui.buttons');
    \Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/common.js');
    \Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/export.js');
    ?>
    <script type="text/javascript">
        BX.ready(
            function () {
                BX.Crm.ExportManager.create(
                    "<?=CUtil::JSEscape($arResult['STEXPORT_PARAMS']['managerId'])?>",
                    <?=CUtil::PhpToJSObject($arResult['STEXPORT_PARAMS'])?>
                );
            }
        );
    </script>
    <?php
}