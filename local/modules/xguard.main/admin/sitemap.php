<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

CModule::IncludeModule("iblock");

IncludeModuleLangFile(__FILE__);

/** @global CMain $APPLICATION */

global $APPLICATION;

/** @var CAdminMessage $message */

$POST_RIGHT = $APPLICATION->GetGroupRight("xguard");

if($POST_RIGHT=="D"):
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
endif;

$arSiteMap=false;

$notSaveKey = '#notsave#-'.md5(__LINE__);
$moduleId   = 'xguard.sm';

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_REQUEST["save"]))
{
    if(check_bitrix_sessid()):
        \COption::RemoveOption($moduleId);

        foreach($_REQUEST as $keyMValue=>$mValue):
            if(is_array($mValue)):
                foreach($mValue as $mSecondValue):
                    if(!is_array($mSecondValue)&&$mSecondValue==$notSaveKey):
                        unset($_REQUEST[$keyMValue]);
                    endif;
                endforeach;
            endif;
        endforeach;

        COption::SetOptionString($moduleId, "xguard_sm_exclude_mask", $_REQUEST["xguard_sm_exclude_mask"]);
        COption::SetOptionString($moduleId, "xguard_sm_site_id", $_REQUEST["xguard_sm_site_id"]);
        COption::SetOptionString($moduleId, "xguard_sm_iblock", serialize(array_combine($_REQUEST["xguard_sm_iblock"],$_REQUEST["xguard_sm_iblock"])));
        COption::SetOptionString($moduleId, "xguard_sm_use_https", ($_REQUEST["xguard_sm_use_https"] == "Y"? "Y": "N"));

        $arGroup = array('xguard_sm_iblock_section','xguard_sm_iblock_element');

        foreach($arGroup as $groupName):
            if(isset($_REQUEST[$groupName])&&!empty($_REQUEST[$groupName])):
                COption::SetOptionString($moduleId, $groupName, serialize(array_combine($_REQUEST[$groupName],$_REQUEST[$groupName])));

                foreach($_REQUEST[$groupName] as $id):
                    $name = $groupName.'_'.$id;

                    if(isset($_REQUEST[$name])):
                        if(is_array($_REQUEST[$name])):
                            $value = serialize(array_combine($_REQUEST[$name],$_REQUEST[$name]));
                        else:
                            $value = serialize($_REQUEST[$name]);
                        endif;

                        COption::SetOptionString($moduleId, $name, $value);
                    endif;
                endforeach;
            endif;
        endforeach;
    else:
        CAdminMessage::ShowMessage(GetMessage("XGUARD_MAIN_SITEMAP_SESSION_ERR"));
    endif;
}

{
    $arParams = isset($arParams) ? $arParams : array();

    $_GET['by']     = isset($_GET['by']) ? $_GET['by'] : 'ID';
    $_GET['order']  = isset($_GET['order']) ? $_GET['order'] : 'ASC';

    $arParams['IBLOCK_TYPE']['GET_LIST']['ORDER'] = (strtoupper($by) === "ID") ? array($_GET['by'] => $_GET['order']) : array(
        $_GET['by'] => $_GET['order'],
        "ID" => "ASC",
    );
    $arParams['IBLOCK_TYPE']['GET_LIST']['FILTER'] = array();

    $arParams['xguard_sm_site_id']          = COption::GetOptionString($moduleId, "xguard_sm_site_id");
    $arParams['xguard_sm_exclude_mask']     = COption::GetOptionString($moduleId, "xguard_sm_exclude_mask");
    $arParams['xguard_sm_iblock']           = COption::GetOptionString($moduleId, "xguard_sm_iblock");
    $arParams['xguard_sm_iblock']           = !empty($arParams['xguard_sm_iblock'])?unserialize($arParams['xguard_sm_iblock']):'';

    $arGroup = array('xguard_sm_iblock_section','xguard_sm_iblock_element');

    foreach($arGroup as $groupName):
        $arParams[$groupName] = unserialize(COption::GetOptionString($moduleId, $groupName));
        $arParams[$groupName] = $arParams[$groupName]?$arParams[$groupName]:array();

            foreach($arParams[$groupName] as $id):
                $name = $groupName.'_'.$id;

                $arParams[$name] = unserialize(COption::GetOptionString($moduleId, $name));
        endforeach;
    endforeach;

    $arParams['IBLOCK_SECTION']['GET_LIST']['ORDER'] = array(
        'ID'    => 'ASC',
    );
    $arParams['IBLOCK_SECTION_ENUM']['GET_LIST']['ORDER'] = array(
        'ID'    => 'ASC',
    );
    $arParams['IBLOCK_SECTION']['GET_LIST']['FILTER'] = array(
        'ENTITY_ID' => '',
        'LANG'      => LANGUAGE_ID,
    );
    $arParams['IBLOCK_SECTION_ENUM']['GET_LIST']['FILTER'] = array(
        'USER_FIELD_ID' => '',
    );
    $arParams['IBLOCK_ELEMENT']['GET_LIST']['ORDER'] = array(
        'ID'    => 'ASC',
    );
    $arParams['IBLOCK_ELEMENT_ENUM']['GET_LIST']['ORDER'] = array(
        'ID'    => 'ASC',
    );
    $arParams['IBLOCK_ELEMENT']['GET_LIST']['FILTER'] = array(
        'IBLOCK_ID' => '',
    );
    $arParams['IBLOCK_ELEMENT_ENUM']['GET_LIST']['FILTER'] = array(
        'IBLOCK_ID' => '',
    );

    $arResult['IBLOCK_HTML'] = array();

    foreach($arParams['xguard_sm_iblock'] as $idIblock => $arIblock):
        $arParams['IBLOCK_SECTION']['GET_LIST']['FILTER']['ENTITY_ID'] = 'IBLOCK_'.$idIblock.'_SECTION';

        $rsData = \CUserTypeEntity::GetList(
            $arParams['IBLOCK_SECTION']['GET_LIST']['ORDER'],
            $arParams['IBLOCK_SECTION']['GET_LIST']['FILTER']
        );

        while($arData = $rsData->Fetch()):
            $propertyNameHtml='xguard_sm_iblock_section_'.$arData['ID'];
            $arData['HTML']     = array();
            $arData['HTML'][]   = '<div id="section'.$arData['ID'].'" class="xguard-sm-iblock-section-value#ACTIVE#">';
            $arData['HTML'][]   = '<span>'.$arData['EDIT_FORM_LABEL'].' ('.$arData['FIELD_NAME'].')</span><br>';
            $arData['HTML'][]   = '<input type="hidden" name="'.$propertyNameHtml.'[]" value="'.$notSaveKey.'"#HIDDEN_DISABLED#>';

            if($arData['USER_TYPE_ID']=='enumeration'):
                $arParams['IBLOCK_SECTION_ENUM']['GET_LIST']['FILTER']['USER_FIELD_ID']=$arData['ID'];

                $rsEnum = \CUserFieldEnum::GetList(
                    $arParams['IBLOCK_SECTION_ENUM']['GET_LIST']['ORDER'],
                    $arParams['IBLOCK_SECTION_ENUM']['GET_LIST']['FILTER']
                );

                $arData['VALUE'][] = array(
                    'ID'            => '',
                    'USER_FIELD_ID' => $arData['ID'],
                    'VALUE'         => $arData['SETTINGS']['CAPTION_NO_VALUE'],
                    'DEF'           => 'N',
                    'SORT'          => 0,
                    'XML_ID'        => '',
                );

                while($arEnum = $rsEnum->Fetch()):
                    $arData['VALUE'][$arEnum['ID']] = $arEnum;
                endwhile;

                $arProperties=array();
                $bSelected = false;

                foreach($arData['VALUE'] as $arValue):
                    $bSelected = $bSelected ? $bSelected : isset($arParams[$propertyNameHtml][$arValue['ID']]);

                    $arProperties[]   = '<option value="'.$arValue['ID'].'"'.(isset($arParams[$propertyNameHtml][$arValue['ID']])?' selected="selected"':'').'>'.($arValue['VALUE']).'</option>';
                endforeach;

                $arData['HTML'][]   = '<select name="'.$propertyNameHtml.'[]" multiple size="10"'.($bSelected?'':' disabled="disabled"').'>';
                $arData['HTML'] = array_merge($arData['HTML'],$arProperties);
                $arData['HTML'][]   = '</select>';
            else:
                $bSelected = isset($arParams[$propertyNameHtml]);

                $arData['HTML'][] = '<input name="'.$propertyNameHtml.'" value="'.$arParams[$propertyNameHtml].'"'.($bSelected?'':' disabled="disabled"').'>';
            endif;

            $arData['HTML'][]='</div>';

            $arData['HTML'] = implode('',$arData['HTML']);

            if($bSelected):
                $arData['HTML']=str_replace(array('#ACTIVE#','#HIDDEN_DISABLED#'),array(' active',' disabled="disabled"'),$arData['HTML']);
            else:
                $arData['HTML']=str_replace(array('#ACTIVE#','#HIDDEN_DISABLED#'),array('',''),$arData['HTML']);
            endif;

            $arResult['xguard_sm_iblock_section'][$idIblock][$arData['ID']]   = $arData;
        endwhile;

        $propertyNameHtml = 'xguard_sm_iblock_section';

        $arResult[$propertyNameHtml.'_html'][]   = '<select name="'.$propertyNameHtml.'[]" multiple size="10" onchange="javascript:return JSCXguardSiteMap.Change.apply(this,[{key:\'section\',class:\'xguard-sm-iblock-section-value\'}]);">';

        foreach($arResult[$propertyNameHtml][$idIblock] as $arProperty):
            $arResult[$propertyNameHtml.'_html'][]   = '<option value="'.$arProperty['ID'].'"'.(isset($arParams[$propertyNameHtml][$arProperty['ID']])?' selected="selected"':'').'>'.($arProperty['EDIT_FORM_LABEL'].' ('.$arProperty['FIELD_NAME'].')').'</option>';
        endforeach;

        $arResult[$propertyNameHtml.'_html'][]   = '</select>';

        $arResult[$propertyNameHtml.'_html'] = implode('',$arResult[$propertyNameHtml.'_html']);

        $arParams['IBLOCK_ELEMENT']['GET_LIST']['FILTER']['IBLOCK_ID'] = $idIblock;

        $rsData = \CIBlockProperty::GetList(
            $arParams['IBLOCK_ELEMENT']['GET_LIST']['ORDER'],
            $arParams['IBLOCK_ELEMENT']['GET_LIST']['FILTER']
        );

        while($arData = $rsData->Fetch()):
            $propertyNameHtml = 'xguard_sm_iblock_element_'.$arData['ID'];
            $arData['HTML'][]   = '<div id="element'.$arData['ID'].'" class="xguard-sm-iblock-element-value#ACTIVE#">';
            $arData['HTML'][]   = '<span>'.$arData['NAME'].' ('.$arData['CODE'].')</span><br>';
            $arData['HTML'][]   = '<input type="hidden" name="'.$propertyNameHtml.'[]" value="'.$notSaveKey.'"#HIDDEN_DISABLED#>';

            if($arData['PROPERTY_TYPE']=='L'):
                $arParams['IBLOCK_ELEMENT_ENUM']['GET_LIST']['FILTER']['PROPERTY_ID']=$arData['ID'];

                $rsEnum = \CIBlockPropertyEnum::GetList(
                    $arParams['IBLOCK_ELEMENT_ENUM']['GET_LIST']['ORDER'],
                    $arParams['IBLOCK_ELEMENT_ENUM']['GET_LIST']['FILTER']
                );

                $arData['VALUE'][] = array(

                );

                while($arEnum = $rsEnum->Fetch()):
                    $arData['VALUE'][$arEnum['ID']] = $arEnum;
                endwhile;

                $arProperties=array();
                $bSelected = false;


                foreach($arData['VALUE'] as $arValue):
                    $bSelected = $bSelected ? $bSelected : isset($arParams[$propertyNameHtml][$arValue['ID']]);

                    $arProperties[]   = '<option value="'.$arValue['ID'].'"'.(isset($arParams[$propertyNameHtml][$arValue['ID']])?' selected="selected"':'').'>'.($arValue['VALUE']).'</option>';
                endforeach;

                $arData['HTML'][]   = '<select name="'.$propertyNameHtml.'[]" multiple size="10"'.($bSelected?'':' disabled="disabled"').'>';
                $arData['HTML'] = array_merge($arData['HTML'],$arProperties);
                $arData['HTML'][]   = '</select>';
            else:
                $bSelected = isset($arParams[$propertyNameHtml]);

                $arData['HTML'][] = '<input name="'.$propertyNameHtml.'" value="'.$arParams[$propertyNameHtml].'"'.($bSelected?'':' disabled="disabled"').'>';
            endif;

            $arData['HTML'][]='</div>';
            $arData['HTML'] = implode('',$arData['HTML']);

            if($bSelected):
                $arData['HTML']=str_replace(array('#ACTIVE#','#HIDDEN_DISABLED#'),array(' active',' disabled="disabled"'),$arData['HTML']);
            else:
                $arData['HTML']=str_replace(array('#ACTIVE#','#HIDDEN_DISABLED#'),array('',''),$arData['HTML']);
            endif;

            $arResult['xguard_sm_iblock_element'][$idIblock][$arData['ID']]   = $arData;
        endwhile;
    endforeach;

    $propertyNameHtml = 'xguard_sm_iblock_element';

    $arResult[$propertyNameHtml.'_html'][]   = '<select name="'.$propertyNameHtml.'[]" multiple size="10" onchange="javascript:JSCXguardSiteMap.Change.apply(this,[{key:\'element\',class:\'xguard-sm-iblock-element-value\'}]);">';

    foreach($arResult[$propertyNameHtml][$idIblock] as $arProperty):
        $arResult[$propertyNameHtml.'_html'][]   = '<option value="'.$arProperty['ID'].'"'.(isset($arParams[$propertyNameHtml][$arProperty['ID']])?' selected="selected"':'').'>'.($arProperty['NAME']).'</option>';
    endforeach;

    $arResult[$propertyNameHtml.'_html'][]   = '</select>';

    $arResult[$propertyNameHtml.'_html'] = implode('',$arResult[$propertyNameHtml.'_html']);

    if(!$USER->IsAdmin()):
        $arTypesToShow  = array();
        $rsIBlocks      = CIBlock::GetList(array(), array("MIN_PERMISSION" => "X"));

        while($arIBlock = $rsIBlocks->Fetch()):
            $arTypesToShow[$arIBlock["IBLOCK_TYPE_ID"]] = $arIBlock["IBLOCK_TYPE_ID"];
        endwhile;

        if(empty($arTypesToShow)):
            $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
        else:
            $arParams['IBLOCK_TYPE']['GET_LIST']['FILTER']["=ID"] = $arTypesToShow;
        endif;
    endif;

    $rsData = CIBlockType::GetList(
        $arParams['IBLOCK_TYPE']['GET_LIST']['ORDER'],
        $arParams['IBLOCK_TYPE']['GET_LIST']['FILTER']
    );

    $arResult['IBLOCK_TYPES']  = array();

    while($arItem = $rsData->Fetch()):
        $arItem['LANG'] = CIBlockType::GetByIDLang($arItem['ID'], LANGUAGE_ID);

        $arResult['IBLOCK_TYPES'][$arItem['ID']]   = $arItem;
    endwhile;

    $arParams['IBLOCK']['GET_LIST']['ORDER']    = array('IBLOCK_TYPE_ID'=>'ASC');
    $arParams['IBLOCK']['GET_LIST']['FILTER']   = array('IBLOCK_TYPE_ID'=>array_keys($arResult['IBLOCK_TYPES']));

    $rsData = CIBlock::GetList(
        $arParams['IBLOCK']['GET_LIST']['ORDER'],
        $arParams['IBLOCK']['GET_LIST']['FILTER']
    );

    while($arItem = $rsData->Fetch()):
        $arResult['IBLOCK_HTML'][]   = '<option value="'.$arItem['ID'].'"'.(isset($arParams['xguard_sm_iblock'][$arItem['ID']])?' selected="selected"':'').'>'.($arResult['IBLOCK_TYPES'][$arItem['IBLOCK_TYPE_ID']]['LANG']['NAME'].' - '.$arItem['NAME']).'</option>';
        $arResult['IBLOCK'][$arItem['ID']]   = $arItem;
    endwhile;

    $arResult['IBLOCK_HTML'] = implode('',$arResult['IBLOCK_HTML']);

    $APPLICATION->SetTitle(GetMessage("XGUARD_MAIN_SITEMAP_TITLE"));

    $aTabs = array(
        array("DIV" => "edit1", "TAB" => "Sitemap", "ICON"=>"main_user_edit", "TITLE"=>GetMessage("XGUARD_MAIN_SITEMAP_TAB_TITLE")),
    );
    $tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);

    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

    if(is_object($message))
        echo $message->Show();
    ?>
    <div id="reindex_result_div"></div>
    <script type="text/javascript">
        JSCXguardSiteMap = {
            Change:function(a,b,c,d,e,f,g)
            {
                try
                {
                    JSCXguardSiteMap.DisabledElementsContainer(a);

                    for(d in this.options)
                    {
                        if(!this.options[d].selected)
                        {
                            continue;
                        };

                        c=document.getElementById(a.key+this.options[d].value);

                        if(c)
                        {
                            c.className+=' active';
                            JSCXguardSiteMap.DisabledElements.apply(c,[a]);
                        };
                    };
                }
                catch(e)
                {
                    console.log(e.stack);
                };

                return this;
            },
            DisabledElementsContainer:function(a,b,c)
            {
                try
                {
                    b=document.getElementsByClassName(a.class);

                    if(b)
                    {
                        for(c in b)
                        {
                            if(typeof b[c] == 'object')
                            {
                                if(/active/.test(b[c].className))
                                {
                                    b[c].className=b[c].className.replace(' active','');

                                    JSCXguardSiteMap.DisabledElements.apply(b[c],[a]);
                                }

                            };
                        };
                    };
                }
                catch(e)
                {
                    console.log(e.stack);
                };
            },
            DisabledElements:function(a,b,c,d,e)
            {
                try
                {
                    b=['input','select','textarea'];

                    for(c in b)
                    {
                        d=this.getElementsByTagName(b[c]);

                        if(d.length)
                        {
                            for(e=0;e<d.length;e++)
                            {
                                if(d.hasOwnProperty(e))
                                {
                                    d[e].disabled=!d[e].disabled;
                                };
                            };

                            //break;
                        };
                    };
                }
                catch(e)
                {
                    console.log(e.stack);
                };
            }
        };
    </script>

    <form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?lang=<?echo htmlspecialcharsbx(LANG)?>" name="fs">
        <?
        echo bitrix_sessid_post();
        $tabControl->Begin();
        $tabControl->BeginNextTab();
        ?>
        <tr>
            <td width="40%"><?echo GetMessage("XGUARD_MAIN_SITEMAP_SITE")?></td>
            <td width="60%"><?echo CLang::SelectBox("xguard_sm_site_id", $arParams['xguard_sm_site_id']);?></td>
        </tr>
        <tr>
            <td><?echo GetMessage("XGUARD_MAIN_IBLOCK_TYPES")?></td>
            <td><select name="xguard_sm_iblock[]" multiple size="10"><?=$arResult['IBLOCK_HTML'];?></select></td>
        </tr>
        <?/*
        <tr>
            <td><?echo GetMessage("XGUARD_MAIN_SITEMAP_STEP")?></td>
            <td><input type="text" name="xguard_sm_max_execution_time" id="xguard_sm_max_execution_time" size="5" value="<?=$xguard_sm_max_execution_time;?>"> <?echo GetMessage("XGUARD_MAIN_SITEMAP_STEP_sec")?></td>
        </tr>
        <tr>
            <td><?echo GetMessage("XGUARD_MAIN_SITEMAP_RECORD_LIMIT")?></td>
            <td><input type="text" name="xguard_sm_record_limit" id="xguard_sm_record_limit" size="5" value="<?=$xguard_sm_record_limit;?>"></td>
        </tr>
        */?>
        <?if(!empty($arResult['xguard_sm_iblock_section'])):?>
            <tr>
                <td><?echo GetMessage("XGUARD_MAIN_IBLOCK_SECTION")?></td>
                <td>
                    <?=$arResult['xguard_sm_iblock_section_html'];?>
                </td>
            </tr>
        <?endif;?>
        <?if(!empty($arResult['xguard_sm_iblock_section'])):?>
            <tr>
                <td><?echo GetMessage("XGUARD_MAIN_IBLOCK_SECTION_VALUE")?></td>
                <td>
                    <?foreach($arResult['xguard_sm_iblock_section'] as $arIblock):?>
                        <?foreach($arIblock as $arProperty):?>
                            <?=$arProperty['HTML'];?>
                        <?endforeach;?>
                    <?endforeach;?>
                </td>
            </tr>
        <?endif;?>
        <?if(!empty($arResult['xguard_sm_iblock_element'])):?>
            <tr>
                <td><?echo GetMessage("XGUARD_MAIN_IBLOCK_ELEMENT")?></td>
                <td>
                    <?=$arResult['xguard_sm_iblock_element_html'];?>
                </td>
            </tr>
        <?endif;?>
        <?if(!empty($arResult['xguard_sm_iblock_element'])):?>
            <tr>
                <td><?echo GetMessage("XGUARD_MAIN_IBLOCK_ELEMENT_VALUE")?></td>
                <td>
                    <?foreach($arResult['xguard_sm_iblock_element'] as $arIblock):?>
                        <?foreach($arIblock as $arProperty):?>
                            <?=$arProperty['HTML'];?>
                        <?endforeach;?>
                    <?endforeach;?>
                </td>
            </tr>
        <?endif;?>
        <tr>
            <td>
                <label for="exclude_mask"><?echo GetMessage("XGUARD_MAIN_EXCLUDE_MASK")?></label>
            </td>
            <td>
                <textarea rows="5" name="xguard_sm_exclude_mask" style="width:100%"><?=$arParams['xguard_sm_exclude_mask'];?></textarea>
            </td>
        </tr>
        <tr>
            <td><label for="sm_use_https"><?echo GetMessage("XGUARD_MAIN_SITEMAP_USE_HTTPS")?>:</label></td>
            <td><input type="checkbox" id="sm_use_https" name="xguard_sm_use_https" value="Y"<?
                if(COption::GetOptionString($moduleId, "xguard_sm_use_https")=="Y") echo ' checked="checked"'?>></td>
        </tr>
        <?
        $tabControl->Buttons();
        ?>
        <input type="submit" id="save" name="save" value="<?=GetMessage("XGUARD_MAIN_SITEMAP_SAVE")?>" class="adm-btn-save">
        <?
        $tabControl->End();
        ?>
    </form>

    <?echo BeginNote();?>
    <?echo GetMessage("XGUARD_MAIN_SITEMAP_NOTE")?>
    <?echo EndNote();?>
<style type="text/css">
    .xguard-sm-iblock-section-value{display:none;}
    .xguard-sm-iblock-section-value.active{display:block;}
    .xguard-sm-iblock-element-value{display:none;}
    .xguard-sm-iblock-element-value.active{display:block;}
</style>
    <?
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
}
?>