<?php

global $DB, $MESS, $APPLICATION;
/** @noinspection PhpIncludeInspection */
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/filter_tools.php';

IncludeModuleLangFile(__FILE__);
IncludeModuleLangFile(
    $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/xguard.main/errors.php'
);

$DBType = strtolower($DB->type);

/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection ClassConstantCanBeUsedInspection */
\Bitrix\Main\Loader::registerAutoLoadClasses(
    'xguard.main',
    array(
        "xGuard\\Main\\CPHPCacheRedis"          => 'lib/CPHPCacheRedis.php',
        "xGuard\\Main"                          => 'lib/main.php',
        "xGuard\\Main\\Exception"               => 'lib/exception.php',
        "xGuard\\Main\\Soap\\All"               => 'lib/soap/all.php',
        "xGuard\\Main\\Soap\\AllHandler"        => 'lib/soap/all.php',
        "xGuard\\Main\\Soap\\Mail"              => 'lib/soap/mail.php',
        "xGuard\\Main\\Soap\\SoapClientNTLM"    => 'lib/soap/ntlm.php',
        "xGuard\\Main\\Soap\\Params"            => 'lib/soap/Params.php',
        "xGuard\\Main\\Ajax\\Init"              => 'lib/ajax/init.php',
        "xGuard\\Main\\Ajax\\Element"           => 'lib/ajax/element.php',
        "xGuard\\Main\\Section\\Filter"         => 'lib/section/filter.php',
        "xGuard\\Main\\Location"                => 'lib/location.php',
        "xGuard\\Main\\Section\\Button"         => 'lib/section/button.php',
        "xGuard\\Main\\Basket\\Init"            => 'lib/basket/init.php',
        "xGuard\\Main\\Order\\Init"             => 'lib/order/init.php',
        "xGuard\\Main\\Order\\Mail"             => 'lib/order/mail.php',
        "xGuard\\Main\\Mail"                    => 'lib/mail.php',
        "xGuard\\Main\\Export\\Yandex"          => 'lib/export/yandex.php',
        "xGuard\\Main\\Seo\\CDN"                => 'lib/seo/cdn.php',
        "xGuard\\Main\\Seo\\SiteMap"            => 'lib/seo/sitemap.php',
        "xGuard\\Main\\Basket\\Base"            => 'lib/basket/Base.php',
        "xGuard\\Main\\Profile\\Base"           => 'lib/profile/Base.php',
        "xGuard\\Main\\Order\\Base"             => 'lib/order/Base.php',
        "xGuard\\Main\\Catalog\\Elements"       => 'lib/catalog/Elements.php',
        "xGuard\\Main\\Form\\Base"              => 'lib/form/Base.php',
        "xGuard\\Main\\Catalog\\Product\\Price" => 'lib/catalog/product/Price.php',
    )
);
