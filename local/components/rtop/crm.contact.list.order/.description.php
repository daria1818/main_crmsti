<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_CONTACT_LIST_NAME'),
	'DESCRIPTION' => GetMessage('CRM_CONTACT_LIST_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 20,
	'PATH' => array(
		'ID' => 'rtop',
		'NAME' => GetMessage('CRM_NAME'),
		'CHILD' => array(
			'ID' => 'contact',
			'NAME' => GetMessage('CRM_CONTACT_NAME')
		)
	),
	'CACHE_PATH' => 'Y'
);
?>