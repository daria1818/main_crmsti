<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses($module = null, [
    'Bitrix\\SalesCenter\\Controller\\Order' => '/local/core_overrides/modules/salescenter/lib/controller/order.php',
    'CSaleOrderLoader' => '/local/core_overrides/modules/sale/general/order_loader.php',
]);

AddEventHandler('main', 'OnEpilog', function () {

    $assetsDir = dirname(__DIR__) . '/core_overrides';

    $assets = [
        'crm_entity_editor' => require $assetsDir . '/js/crm/entity-editor/config.php',
        'salescenter_app' => require $assetsDir . '/js/salescenter/config.php',
        'rpa_kanban' => require $assetsDir . '/js/rpa/kanban/config.php',
        'rpa_timeline' => require $assetsDir . '/js/rpa/timeline/config.php',
    ];

    foreach ($assets as $ext => $asset) {
        CJSCore::RegisterExt($ext, $assets);
    }
});
