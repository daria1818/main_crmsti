<?php
$arUrlRewrite=array (
  1 => 
  array (
    'CONDITION' => '#^/docs/pub/(?<hash>[0-9a-f]{32})/(?<action>[0-9a-zA-Z]+)/\\?#',
    'RULE' => 'hash=$1&action=$2&',
    'ID' => 'bitrix:disk.external.link',
    'PATH' => '/docs/pub/index.php',
    'SORT' => 100,
  ),
  4 => 
  array (
    'CONDITION' => '#^/pub/document/([0-9a-zA-Z_-]+)/([0-9a-zA-Z]+)/([^/]*)#',
    'RULE' => 'id=$1&hash=$2',
    'ID' => 'bitrix:documentgenerator.view',
    'PATH' => '/pub/document.php',
    'SORT' => 100,
  ),
  128 => 
  array (
    'CONDITION' => '#^/pub/calendar-event/([0-9]+)/([0-9a-zA-Z]+)/?([^/]*)#',
    'RULE' => 'event_id=$1&hash=$2',
    'ID' => 'bitrix:calendar.pub.event',
    'PATH' => '/pub/calendar_event.php',
    'SORT' => 100,
  ),
  2 => 
  array (
    'CONDITION' => '#^/disk/(?<action>[0-9a-zA-Z]+)/(?<fileId>[0-9]+)/\\?#',
    'RULE' => 'action=$1&fileId=$2&',
    'ID' => 'bitrix:disk.services',
    'PATH' => '/bitrix/services/disk/index.php',
    'SORT' => 100,
  ),
  3 => 
  array (
    'CONDITION' => '#^/pub/pay/([0-9a-zA-Z_-]+)/([0-9a-zA-Z]+)/([^/]*)#',
    'RULE' => 'account_number=$1&hash=$2',
    'ID' => 'bitrix:crm.invoice.payment.client',
    'PATH' => '/pub/payment.php',
    'SORT' => 100,
  ),
  5 => 
  array (
    'CONDITION' => '#^/docs/pub/(?<hash>[0-9a-f]{32})/(?<action>.*)$#',
    'RULE' => 'hash=$1&action=$2',
    'ID' => 'bitrix:disk.external.link',
    'PATH' => '/docs/pub/index.php',
    'SORT' => 100,
  ),
  89 => 
  array (
    'CONDITION' => '#^\\/?\\/mobile/web_mobile_component\\/(.*)\\/.*#',
    'RULE' => 'componentName=$1',
    'ID' => '',
    'PATH' => '/bitrix/services/mobile/webcomponent.php',
    'SORT' => 100,
  ),
  6 => 
  array (
    'CONDITION' => '#^/pub/(?<hash>[0-9a-f]{32})/(?<action>.*)$#',
    'RULE' => 'hash=$1&action=$2',
    'ID' => 'bitrix:disk.external.link',
    'PATH' => '/pub/index.php',
    'SORT' => 100,
  ),
  7 => 
  array (
    'CONDITION' => '#^/pub/pay/([\\w\\W]+)/([0-9a-zA-Z]+)/([^/]*)#',
    'RULE' => 'account_number=$1&hash=$2',
    'ID' => 'bitrix:crm.invoice.payment.client',
    'PATH' => '/pub/payment.php',
    'SORT' => 100,
  ),
  8 => 
  array (
    'CONDITION' => '#^/pub/pay/([0-9]+)/([0-9a-zA-Z]+)/([^/]*)#',
    'RULE' => 'account_number=$1&hash=$2',
    'ID' => 'bitrix:crm.invoice.payment.client',
    'PATH' => '/pub/payment.php',
    'SORT' => 100,
  ),
  9 => 
  array (
    'CONDITION' => '#^/pub/pay/([0-9]+)/([0-9a-zA-Z]+)/([^/]+)#',
    'RULE' => 'account_number=$1&hash=$2',
    'ID' => 'bitrix:crm.invoice.payment.client',
    'PATH' => '/pub/payment.php',
    'SORT' => 100,
  ),
  46 => 
  array (
    'CONDITION' => '#^/marketplace/view/([a-zA-Z0-9\\.\\_]+)/.*#',
    'RULE' => 'APP=$1',
    'ID' => 'bitrix:app.layout',
    'PATH' => '/marketplace/view/index.php',
    'SORT' => 100,
  ),
  10 => 
  array (
    'CONDITION' => '#^/pub/form/([0-9a-z_]+?)/([0-9a-z]+?)/.*#',
    'RULE' => 'form_code=$1&sec=$2',
    'ID' => '',
    'PATH' => '/pub/form.php',
    'SORT' => 100,
  ),
  88 => 
  array (
    'CONDITION' => '#^\\/?\\/mobile/mobile_component\\/(.*)\\/.*#',
    'RULE' => 'componentName=$1',
    'ID' => '',
    'PATH' => '/bitrix/services/mobile/jscomponent.php',
    'SORT' => 100,
  ),
  11 => 
  array (
    'CONDITION' => '#^/mobile/disk/(?<hash>[0-9]+)/download#',
    'RULE' => 'download=1&objectId=$1',
    'ID' => 'bitrix:mobile.disk.file.detail',
    'PATH' => '/mobile/disk/index.php',
    'SORT' => 100,
  ),
  13 => 
  array (
    'CONDITION' => '#^/online/([\\.\\-0-9a-zA-Z]+)(/?)([^/]*)#',
    'RULE' => 'alias=$1',
    'PATH' => '/desktop_app/router.php',
    'SORT' => 100,
  ),
  12 => 
  array (
    'CONDITION' => '#^/video/([\\.\\-0-9a-zA-Z]+)(/?)([^/]*)#',
    'RULE' => 'alias=$1&videoconf',
    'PATH' => '/desktop_app/router.php',
    'SORT' => 100,
  ),
  14 => 
  array (
    'CONDITION' => '#^/tasks/getfile/(\\d+)/(\\d+)/([^/]+)#',
    'RULE' => 'taskid=$1&fileid=$2&filename=$3',
    'PATH' => '/tasks/getfile.php',
    'SORT' => 100,
  ),
  15 => 
  array (
    'CONDITION' => '#^/pub/pay/([0-9]+)/([0-9a-zA-Z]+)/#',
    'RULE' => 'account_number=$1&hash=$2',
    'ID' => 'bitrix:crm.invoice.payment.client',
    'PATH' => '/pub/payment.php',
    'SORT' => 100,
  ),
  105 => 
  array (
    'CONDITION' => '#^/crm/configs/document_numerators/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.document_numerators.list',
    'PATH' => '/crm/configs/document_numerators/index.php',
    'SORT' => 100,
  ),
  81 => 
  array (
    'CONDITION' => '#^/stssync/contacts_extranet_emp/#',
    'RULE' => '',
    'ID' => 'bitrix:stssync.server',
    'PATH' => '/bitrix/services/stssync/contacts_extranet_emp/index.php',
    'SORT' => 100,
  ),
  85 => 
  array (
    'CONDITION' => '#^/settings/configs/userconsent/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/settings/configs/userconsent.php',
    'SORT' => 100,
  ),
  17 => 
  array (
    'CONDITION' => '#^/extranet/workgroups/create/#',
    'RULE' => '',
    'ID' => 'bitrix:extranet.group_create',
    'PATH' => '/extranet/workgroups/create/index.php',
    'SORT' => 100,
  ),
  18 => 
  array (
    'CONDITION' => '#^/extranet/crm/configs/perms/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.config.perms',
    'PATH' => '/extranet/crm/configs/perms/index.php',
    'SORT' => 100,
  ),
  16 => 
  array (
    'CONDITION' => '#^/extranet/contacts/personal/#',
    'RULE' => '',
    'ID' => 'bitrix:socialnetwork_user',
    'PATH' => '/extranet/contacts/personal.php',
    'SORT' => 100,
  ),
  80 => 
  array (
    'CONDITION' => '#^/stssync/contacts_extranet/#',
    'RULE' => '',
    'ID' => 'bitrix:stssync.server',
    'PATH' => '/bitrix/services/stssync/contacts_extranet/index.php',
    'SORT' => 100,
  ),
  83 => 
  array (
    'CONDITION' => '#^/stssync/calendar_extranet/#',
    'RULE' => '',
    'ID' => 'bitrix:stssync.server',
    'PATH' => '/bitrix/services/stssync/calendar_extranet/index.php',
    'SORT' => 100,
  ),
  37 => 
  array (
    'CONDITION' => '#^/marketplace/configuration/#',
    'RULE' => '',
    'ID' => 'bitrix:rest.configuration',
    'PATH' => '/marketplace/configuration/index.php',
    'SORT' => 100,
  ),
  92 => 
  array (
    'CONDITION' => '#^\\/?\\/mobileapp/jn\\/(.*)\\/.*#',
    'RULE' => 'componentName=$1',
    'ID' => '',
    'PATH' => '/bitrix/services/mobileapp/jn.php',
    'SORT' => 100,
  ),
  19 => 
  array (
    'CONDITION' => '#^/crm/configs/deal_category/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.deal_category',
    'PATH' => '/crm/configs/deal_category/index.php',
    'SORT' => 100,
  ),
  21 => 
  array (
    'CONDITION' => '#^/crm/configs/mailtemplate/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.mail_template',
    'PATH' => '/crm/configs/mailtemplate/index.php',
    'SORT' => 100,
  ),
  20 => 
  array (
    'CONDITION' => '#^/crm/configs/productprops/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.config.productprops',
    'PATH' => '/crm/configs/productprops/index.php',
    'SORT' => 100,
  ),
  114 => 
  array (
    'CONDITION' => '#^/extranet/knowledge/group/#',
    'RULE' => '',
    'ID' => 'bitrix:landing.pub',
    'PATH' => '/extranet/knowledge/group/index.php',
    'SORT' => 100,
  ),
  120 => 
  array (
    'CONDITION' => '#^/extranet/marketplace/app/#',
    'RULE' => '',
    'ID' => 'bitrix:app.layout',
    'PATH' => '/extranet/marketplace/app/index.php',
    'SORT' => 100,
  ),
  23 => 
  array (
    'CONDITION' => '#^/extranet/crm/configs/bp/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.config.bp',
    'PATH' => '/extranet/crm/configs/bp/index.php',
    'SORT' => 100,
  ),
  22 => 
  array (
    'CONDITION' => '#^/bitrix/services/ymarket/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/bitrix/services/ymarket/index.php',
    'SORT' => 100,
  ),
  91 => 
  array (
    'CONDITION' => '#^\\/?\\/mobile/jn\\/(.*)\\/.*#',
    'RULE' => 'componentName=$1',
    'ID' => '',
    'PATH' => '/bitrix/services/mobile/jscomponent.php',
    'SORT' => 100,
  ),
  49 => 
  array (
    'CONDITION' => '#^/crm/configs/automation/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.config.automation',
    'PATH' => '/crm/configs/automation/index.php',
    'SORT' => 100,
  ),
  131 => 
  array (
    'CONDITION' => '#^/shop/documents-catalog/#',
    'RULE' => '',
    'ID' => 'bitrix:catalog.catalog.controller',
    'PATH' => '/shop/documents-catalog/index.php',
    'SORT' => 100,
  ),
  115 => 
  array (
    'CONDITION' => '#^/mobile/knowledge/group/#',
    'RULE' => '',
    'ID' => 'bitrix:landing.pub',
    'PATH' => '/mobile/knowledge/group/index.php',
    'SORT' => 100,
  ),
  90 => 
  array (
    'CONDITION' => '#^\\/?\\/mobile/jn\\/(.*)\\/.*#',
    'RULE' => 'componentName=$1',
    'ID' => '',
    'PATH' => '/bitrix/services/mobile/jscomponent.php',
    'SORT' => 100,
  ),
  82 => 
  array (
    'CONDITION' => '#^/stssync/tasks_extranet/#',
    'RULE' => '',
    'ID' => 'bitrix:stssync.server',
    'PATH' => '/bitrix/services/stssync/tasks_extranet/index.php',
    'SORT' => 100,
  ),
  70 => 
  array (
    'CONDITION' => '#^/shop/import/instagram/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.order.import.instagram',
    'PATH' => '/shop/import/instagram/index.php',
    'SORT' => 100,
  ),
  66 => 
  array (
    'CONDITION' => '#^/crm/configs/exclusion/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/crm/configs/exclusion/index.php',
    'SORT' => 100,
  ),
  104 => 
  array (
    'CONDITION' => '#^/marketing/config/role/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/marketing/config/role.php',
    'SORT' => 100,
  ),
  42 => 
  array (
    'CONDITION' => '#^/company/personal/mail/#',
    'RULE' => '',
    'ID' => 'bitrix:intranet.mail.config',
    'PATH' => '/mail/index.php',
    'SORT' => 100,
  ),
  24 => 
  array (
    'CONDITION' => '#^/crm/configs/locations/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.config.locations',
    'PATH' => '/crm/configs/locations/index.php',
    'SORT' => 100,
  ),
  26 => 
  array (
    'CONDITION' => '#^/crm/configs/mycompany/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.company',
    'PATH' => '/crm/configs/mycompany/index.php',
    'SORT' => 100,
  ),
  25 => 
  array (
    'CONDITION' => '#^/extranet/mobile/webdav#',
    'RULE' => '',
    'ID' => 'bitrix:mobile.webdav.file.list',
    'PATH' => '/extranet/mobile/webdav/index.php',
    'SORT' => 100,
  ),
  27 => 
  array (
    'CONDITION' => '#^/crm/configs/currency/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.currency',
    'PATH' => '/crm/configs/currency/index.php',
    'SORT' => 100,
  ),
  79 => 
  array (
    'CONDITION' => '#^/stssync/contacts_crm/#',
    'RULE' => '',
    'ID' => 'bitrix:stssync.server',
    'PATH' => '/bitrix/services/stssync/contacts_crm/index.php',
    'SORT' => 100,
  ),
  121 => 
  array (
    'CONDITION' => '#^/extranet/marketplace/#',
    'RULE' => '',
    'ID' => 'bitrix:rest.marketplace',
    'PATH' => '/extranet/marketplace/index.php',
    'SORT' => 100,
  ),
  30 => 
  array (
    'CONDITION' => '#^/extranet/docs/shared#',
    'RULE' => '',
    'ID' => 'bitrix:webdav',
    'PATH' => '/extranet/docs/index.php',
    'SORT' => 100,
  ),
  31 => 
  array (
    'CONDITION' => '#^/extranet/workgroups/#',
    'RULE' => '',
    'ID' => 'bitrix:socialnetwork_group',
    'PATH' => '/extranet/workgroups/index.php',
    'SORT' => 100,
  ),
  97 => 
  array (
    'CONDITION' => '#^/marketing/blacklist/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/marketing/blacklist.php',
    'SORT' => 100,
  ),
  28 => 
  array (
    'CONDITION' => '#^/crm/configs/measure/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.config.measure',
    'PATH' => '/crm/configs/measure/index.php',
    'SORT' => 100,
  ),
  35 => 
  array (
    'CONDITION' => '#^/crm/configs/preset/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.config.preset',
    'PATH' => '/crm/configs/preset/index.php',
    'SORT' => 100,
  ),
  34 => 
  array (
    'CONDITION' => '#^/crm/configs/fields/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.config.fields',
    'PATH' => '/crm/configs/fields/index.php',
    'SORT' => 100,
  ),
  29 => 
  array (
    'CONDITION' => '#^/crm/configs/volume/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.volume',
    'PATH' => '/crm/configs/volume/index.php',
    'SORT' => 100,
  ),
  96 => 
  array (
    'CONDITION' => '#^/marketing/template/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/marketing/template.php',
    'SORT' => 100,
  ),
  32 => 
  array (
    'CONDITION' => '#^/crm/configs/exch1c/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.config.exch1c',
    'PATH' => '/crm/configs/exch1c/index.php',
    'SORT' => 100,
  ),
  33 => 
  array (
    'CONDITION' => '#^/crm/reports/report/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.report',
    'PATH' => '/crm/reports/report/index.php',
    'SORT' => 100,
  ),
  98 => 
  array (
    'CONDITION' => '#^/marketing/contact/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/marketing/contact.php',
    'SORT' => 100,
  ),
  129 => 
  array (
    'CONDITION' => '#^/shop/settings/kpi/#',
    'RULE' => '',
    'ID' => 'rtop:kpi',
    'PATH' => '/shop/settings/kpi/index.php',
    'SORT' => 100,
  ),
  41 => 
  array (
    'CONDITION' => '#^/bizproc/processes/#',
    'RULE' => '',
    'ID' => 'bitrix:lists',
    'PATH' => '/bizproc/processes/index.php',
    'SORT' => 100,
  ),
  95 => 
  array (
    'CONDITION' => '#^/marketing/segment/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/marketing/segment.php',
    'SORT' => 100,
  ),
  40 => 
  array (
    'CONDITION' => '#^/crm/configs/perms/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.config.perms',
    'PATH' => '/crm/configs/perms/index.php',
    'SORT' => 100,
  ),
  36 => 
  array (
    'CONDITION' => '#^/marketplace/local/#',
    'RULE' => '',
    'ID' => 'bitrix:rest.marketplace.localapp',
    'PATH' => '/marketplace/local/index.php',
    'SORT' => 100,
  ),
  108 => 
  array (
    'CONDITION' => '#^/timeman/schedules/#',
    'RULE' => '',
    'ID' => 'bitrix:timeman.schedules',
    'PATH' => '/timeman/schedules.php',
    'SORT' => 100,
  ),
  39 => 
  array (
    'CONDITION' => '#^/online/(/?)([^/]*)#',
    'RULE' => '',
    'PATH' => '/desktop_app/router.php',
    'SORT' => 100,
  ),
  103 => 
  array (
    'CONDITION' => '#^/marketing/toloka/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/marketing/toloka.php',
    'SORT' => 100,
  ),
  77 => 
  array (
    'CONDITION' => '#^/stssync/calendar/#',
    'RULE' => '',
    'ID' => 'bitrix:stssync.server',
    'PATH' => '/bitrix/services/stssync/calendar/index.php',
    'SORT' => 100,
  ),
  68 => 
  array (
    'CONDITION' => '#^/shop/buyer_group/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.order.buyer_group',
    'PATH' => '/shop/buyer_group/index.php',
    'SORT' => 100,
  ),
  109 => 
  array (
    'CONDITION' => '#^/timeman/settings/#',
    'RULE' => '',
    'ID' => 'bitrix:timeman.settings',
    'PATH' => '/timeman/settings.php',
    'SORT' => 100,
  ),
  38 => 
  array (
    'CONDITION' => '#^/marketplace/hook/#',
    'RULE' => '',
    'ID' => 'bitrix:rest.hook',
    'PATH' => '/marketplace/hook/index.php',
    'SORT' => 100,
  ),
  116 => 
  array (
    'CONDITION' => '#^/mobile/knowledge/#',
    'RULE' => '',
    'ID' => 'bitrix:landing.pub',
    'PATH' => '/mobile/knowledge/index.php',
    'SORT' => 100,
  ),
  93 => 
  array (
    'CONDITION' => '#^/marketing/letter/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/marketing/letter.php',
    'SORT' => 100,
  ),
  76 => 
  array (
    'CONDITION' => '#^/stssync/contacts/#',
    'RULE' => '',
    'ID' => 'bitrix:stssync.server',
    'PATH' => '/bitrix/services/stssync/contacts/index.php',
    'SORT' => 100,
  ),
  43 => 
  array (
    'CONDITION' => '#^/company/personal/#',
    'RULE' => '',
    'ID' => 'bitrix:socialnetwork_user',
    'PATH' => '/company/personal.php',
    'SORT' => 100,
  ),
  110 => 
  array (
    'CONDITION' => '#^/timeman/worktime/#',
    'RULE' => '',
    'ID' => 'bitrix:timeman.worktime',
    'PATH' => '/timeman/worktime.php',
    'SORT' => 100,
  ),
  47 => 
  array (
    'CONDITION' => '#^/timeman/meeting/#',
    'RULE' => '',
    'ID' => 'bitrix:meetings',
    'PATH' => '/timeman/meeting/index.php',
    'SORT' => 100,
  ),
  45 => 
  array (
    'CONDITION' => '#^/marketplace/app/#',
    'RULE' => '',
    'ID' => 'bitrix:app.layout',
    'PATH' => '/marketplace/app/index.php',
    'SORT' => 100,
  ),
  112 => 
  array (
    'CONDITION' => '#^/knowledge/group/#',
    'RULE' => '',
    'ID' => 'bitrix:landing.pub',
    'PATH' => '/knowledge/group/index.php',
    'SORT' => 100,
  ),
  44 => 
  array (
    'CONDITION' => '#^/crm/configs/tax/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.config.tax',
    'PATH' => '/crm/configs/tax/index.php',
    'SORT' => 100,
  ),
  48 => 
  array (
    'CONDITION' => '#^/crm/configs/ps/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.config.ps',
    'PATH' => '/crm/configs/ps/index.php',
    'SORT' => 100,
  ),
  67 => 
  array (
    'CONDITION' => '#^/shop/orderform/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.order.matcher',
    'PATH' => '/shop/orderform/index.php',
    'SORT' => 100,
  ),
  50 => 
  array (
    'CONDITION' => '#^/crm/configs/bp/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.config.bp',
    'PATH' => '/crm/configs/bp/index.php',
    'SORT' => 100,
  ),
  130 => 
  array (
    'CONDITION' => '#^/shop/documents/#',
    'RULE' => '',
    'ID' => 'bitrix:catalog.store.document',
    'PATH' => '/shop/documents/index.php',
    'SORT' => 100,
  ),
  94 => 
  array (
    'CONDITION' => '#^/marketing/ads/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/marketing/ads.php',
    'SORT' => 100,
  ),
  51 => 
  array (
    'CONDITION' => '#^/company/lists/#',
    'RULE' => '',
    'ID' => 'bitrix:lists',
    'PATH' => '/company/lists/index.php',
    'SORT' => 100,
  ),
  99 => 
  array (
    'CONDITION' => '#^/shop/settings/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.admin.page.controller',
    'PATH' => '/shop/settings/index.php',
    'SORT' => 100,
  ),
  78 => 
  array (
    'CONDITION' => '#^/stssync/tasks/#',
    'RULE' => '',
    'ID' => 'bitrix:stssync.server',
    'PATH' => '/bitrix/services/stssync/tasks/index.php',
    'SORT' => 100,
  ),
  107 => 
  array (
    'CONDITION' => '#^/crm/tracking/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/crm/tracking/index.php',
    'SORT' => 100,
  ),
  102 => 
  array (
    'CONDITION' => '#^/marketing/rc/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/marketing/rc.php',
    'SORT' => 100,
  ),
  53 => 
  array (
    'CONDITION' => '#^/mobile/webdav#',
    'RULE' => '',
    'ID' => 'bitrix:mobile.webdav.file.list',
    'PATH' => '/mobile/webdav/index.php',
    'SORT' => 100,
  ),
  52 => 
  array (
    'CONDITION' => '#^/crm/activity/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.activity',
    'PATH' => '/crm/activity/index.php',
    'SORT' => 100,
  ),
  122 => 
  array (
    'CONDITION' => '#^/shop/catalog/#',
    'RULE' => '',
    'ID' => 'bitrix:catalog.productcard.controller',
    'PATH' => '/shop/catalog/index.php',
    'SORT' => 100,
  ),
  101 => 
  array (
    'CONDITION' => '#^/shop/orders/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.order',
    'PATH' => '/shop/orders/index.php',
    'SORT' => 100,
  ),
  123 => 
  array (
    'CONDITION' => '#^/crm/catalog/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.catalog.controller',
    'PATH' => '/crm/catalog/index.php',
    'SORT' => 100,
  ),
  54 => 
  array (
    'CONDITION' => '#^/crm/company/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.company',
    'PATH' => '/crm/company/index.php',
    'SORT' => 100,
  ),
  56 => 
  array (
    'CONDITION' => '#^/\\.well-known#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/bitrix/groupdav.php',
    'SORT' => 100,
  ),
  60 => 
  array (
    'CONDITION' => '#^/crm/contact/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.contact',
    'PATH' => '/crm/contact/index.php',
    'SORT' => 100,
  ),
  59 => 
  array (
    'CONDITION' => '#^/crm/product/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.product',
    'PATH' => '/crm/product/index.php',
    'SORT' => 100,
  ),
  57 => 
  array (
    'CONDITION' => '#^/marketplace/#',
    'RULE' => '',
    'ID' => 'bitrix:rest.marketplace',
    'PATH' => '/marketplace/index.php',
    'SORT' => 100,
  ),
  55 => 
  array (
    'CONDITION' => '#^/crm/webform/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/crm/webform/index.php',
    'SORT' => 100,
  ),
  58 => 
  array (
    'CONDITION' => '#^/crm/invoice/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.invoice',
    'PATH' => '/crm/invoice/index.php',
    'SORT' => 100,
  ),
  100 => 
  array (
    'CONDITION' => '#^/shop/stores/#',
    'RULE' => '',
    'ID' => 'bitrix:landing.start',
    'PATH' => '/shop/stores/index.php',
    'SORT' => 100,
  ),
  61 => 
  array (
    'CONDITION' => '#^/workgroups/#',
    'RULE' => '',
    'ID' => 'bitrix:socialnetwork_group',
    'PATH' => '/workgroups/index.php',
    'SORT' => 100,
  ),
  125 => 
  array (
    'CONDITION' => '#^/conference/#',
    'RULE' => '',
    'ID' => 'bitrix:im.conference.center',
    'PATH' => '/conference/index.php',
    'SORT' => 100,
  ),
  69 => 
  array (
    'CONDITION' => '#^/shop/buyer/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.order.buyer',
    'PATH' => '/shop/buyer/index.php',
    'SORT' => 100,
  ),
  62 => 
  array (
    'CONDITION' => '#^/crm/button/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/crm/button/index.php',
    'SORT' => 100,
  ),
  113 => 
  array (
    'CONDITION' => '#^/knowledge/#',
    'RULE' => '',
    'ID' => 'bitrix:landing.pub',
    'PATH' => '/knowledge/index.php',
    'SORT' => 100,
  ),
  63 => 
  array (
    'CONDITION' => '#^/crm/quote/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.quote',
    'PATH' => '/crm/quote/index.php',
    'SORT' => 100,
  ),
  126 => 
  array (
    'CONDITION' => '#^/crm/type/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.router',
    'PATH' => '/crm/type/index.php',
    'SORT' => 100,
  ),
  117 => 
  array (
    'CONDITION' => '#^/kb/group/#',
    'RULE' => '',
    'ID' => 'bitrix:landing.start',
    'PATH' => '/kb/group/index.php',
    'SORT' => 100,
  ),
  65 => 
  array (
    'CONDITION' => '#^/crm/deal/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.deal',
    'PATH' => '/crm/deal/index.php',
    'SORT' => 100,
  ),
  64 => 
  array (
    'CONDITION' => '#^/crm/lead/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.lead',
    'PATH' => '/crm/lead/index.php',
    'SORT' => 100,
  ),
  71 => 
  array (
    'CONDITION' => '#^/docs/pub/#',
    'RULE' => '',
    'ID' => 'bitrix:webdav.extlinks',
    'PATH' => '/docs/pub/extlinks.php',
    'SORT' => 100,
  ),
  0 => 
  array (
    'CONDITION' => '#^/pub/site/#',
    'ID' => 'bitrix:landing.pub',
    'PATH' => '/pub/site/index.php',
    'SORT' => 100,
  ),
  87 => 
  array (
    'CONDITION' => '#^/stores/#',
    'RULE' => '',
    'ID' => 'bitrix:landing.start',
    'PATH' => '/stores/index.php',
    'SORT' => 100,
  ),
  124 => 
  array (
    'CONDITION' => '#^/devops/#',
    'ID' => 'bitrix:rest.devops',
    'PATH' => '/devops/index.php',
    'SORT' => 100,
  ),
  111 => 
  array (
    'CONDITION' => '#^/crm/ml/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.ml',
    'PATH' => '/crm/ml/index.php',
    'SORT' => 100,
  ),
  72 => 
  array (
    'CONDITION' => '#^/m/docs/#',
    'RULE' => '',
    'ID' => 'bitrix:mobile.webdav.aggregator',
    'PATH' => '/m/docs/index.php',
    'SORT' => 100,
  ),
  86 => 
  array (
    'CONDITION' => '#^/sites/#',
    'RULE' => '',
    'ID' => 'bitrix:landing.start',
    'PATH' => '/sites/index.php',
    'SORT' => 100,
  ),
  127 => 
  array (
    'CONDITION' => '#^/page/#',
    'RULE' => '',
    'ID' => 'bitrix:intranet.customsection',
    'PATH' => '/page/index.php',
    'SORT' => 100,
  ),
  74 => 
  array (
    'CONDITION' => '#^/docs/#',
    'RULE' => '',
    'ID' => 'bitrix:webdav',
    'PATH' => '/docs/index.php',
    'SORT' => 100,
  ),
  73 => 
  array (
    'CONDITION' => '#^/rest/#',
    'RULE' => '',
    'PATH' => '/bitrix/services/rest/index.php',
    'SORT' => 100,
  ),
  84 => 
  array (
    'CONDITION' => '#^/onec/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.1c.start',
    'PATH' => '/onec/index.php',
    'SORT' => 100,
  ),
  106 => 
  array (
    'CONDITION' => '#^/mail/#',
    'RULE' => '',
    'ID' => 'bitrix:mail.client',
    'PATH' => '/mail/index.php',
    'SORT' => 100,
  ),
  75 => 
  array (
    'CONDITION' => '#^/pub/#',
    'RULE' => '',
    'ID' => 'bitrix:crm.invoice.payment.client',
    'PATH' => '/pub/payment.php',
    'SORT' => 100,
  ),
  119 => 
  array (
    'CONDITION' => '#^/rpa/#',
    'RULE' => '',
    'ID' => 'bitrix:rpa.router',
    'PATH' => '/rpa/index.php',
    'SORT' => 100,
  ),
  118 => 
  array (
    'CONDITION' => '#^/kb/#',
    'RULE' => '',
    'ID' => 'bitrix:landing.start',
    'PATH' => '/kb/index.php',
    'SORT' => 100,
  ),
);
