<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

use \Bitrix\Main\Page\Asset;

/** @var \CBitrixComponentTemplate $this  */

global $APPLICATION;

Asset::getInstance()->addCss($this->GetFolder().'/splitter.css');
Asset::getInstance()->addJs($this->GetFolder().'/splitter.js');
Asset::getInstance()->addJs($this->GetFolder().'/list_manager.js');

$viewOptionId = 'crm_product_template_list_default_contact';
$splitterState = CUserOptions::GetOption(
	'crm',
	$viewOptionId,
	array(
		'rightSideWidth' => 250,
		'rightSideClosed' => 'N'
	)
);
if (!is_array($splitterState))
{
	$splitterState = array(
		'rightSideWidth' => 250,
		'rightSideClosed' => "N"
	);
}
$splitterState['rightSideWidth'] = intval($splitterState['rightSideWidth']);
if ($splitterState['rightSideWidth'] < 100)
{
	$splitterState['rightSideWidth'] = 250;
	$splitterState['rightSideClosed'] = "N";
}
if ($splitterState['rightSideClosed'] !== "Y"
	&& $splitterState['rightSideClosed'] !== "N")
{
	$splitterState['rightSideClosed'] = "N";
}

?>
<div class="contact_products_list-block">
    <div class="bx-crm-container bx-crm-container-contact_products_list">
        <table class="bx-crm-goods-table">
            <tr>
                <td class="bx-crm-goods-cont-cell">
                    <div class="bx-crm-interface-toolbar-container">
                        <?php
                        // Toolbar
                        $APPLICATION->ShowViewContent('crm_product_menu');
                        ?>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="bx-crm-goods-cont-cell">
                    <div class="bx-crm-interface-product-list">
                    <?php
                    // List
                    $productListResult =
                    $APPLICATION->IncludeComponent(
                        'pwd:crm.product.list',
                        '',
                        array(
                            'CATALOG_ID' => $arParams['CATALOG_ID'],
                            'ENTITY_ID' => $arParams['ENTITY_ID'],
                            'SECTION_ID' => '',
                            'PATH_TO_INDEX' => '',
                            'PATH_TO_PRODUCT_LIST' => '',
                            'PATH_TO_PRODUCT_SHOW' => '',
                            'PATH_TO_PRODUCT_EDIT' => '',
                            'PATH_TO_PRODUCT_FILE' => '',
                            'PATH_TO_SECTION_LIST' => '',
                            'PRODUCT_COUNT' => '20'
                        ),
                        $component
                    );
                    ?>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <?php

    // Toolbar
    $this->SetViewTarget('crm_product_menu');
    $APPLICATION->IncludeComponent(
        'pwd:crm.product.menu',
        'contact-prod-menu',
        array(
            'CATALOG_ID' => $arParams['CATALOG_ID'],
            'ENTITY_ID' => $arParams['ENTITY_ID'],
            'SECTION_ID' => '',
            'PATH_TO_INDEX' => '',
            'PATH_TO_PRODUCT_LIST' => '',
            'PATH_TO_PRODUCT_SHOW' => '',
            'PATH_TO_PRODUCT_EDIT' => '',
            'PATH_TO_PRODUCT_FILE' => '',
            'PATH_TO_SECTION_LIST' => '',
            'PRODUCT_COUNT' => '20',
            'TYPE' => 'list'
        ),
        $component
    );
    $this->EndViewTarget();

    ?>
</div>
<script type="text/javascript">
	BX.namespace("BX.Crm");
	BX.Crm.productListManager = BX.Crm.ProductListManagerClass.create({
		splitterBtnId: "bx-crm-goods-drug-btn",
		splitterNodeId: "bx-crm-table-sidebar-cell",
		hideBtnId: "bx-crm-goods-drug-btn-inner",
		viewOptionId: "<?= CUtil::JSEscape($viewOptionId) ?>",
		splitterState: <?= CUtil::PhpToJSObject($splitterState) ?>
	});
</script>