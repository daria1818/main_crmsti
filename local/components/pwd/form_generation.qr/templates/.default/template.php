<?php

(defined('B_PROLOG_INCLUDED') && B_PROLOG_INCLUDED === true) || die();

/**
 * @var $arResult array
 * @var $arParams array
 * @var $component Pwd\Components\FormQrGenerationComponent
 * @var $this CBitrixComponentTemplate
 */

if ($arResult['GENERATION_MODE']) {
    require __DIR__ . '/.component.form-generation.php';
} else {
    require __DIR__ . '/.component.form-contact.php';
}

?>
<div id="okay" class="modal fade">
    Вы успешно зарегистрированы
</div>
<!--/noindex-->
<script type="text/javascript">
    (function () {
        var FormQrGenerationComponent = BX.namespace('__pwd.FormQrGenerationComponent');
    })();
</script>
