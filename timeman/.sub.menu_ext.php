<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Intranet\Site\Sections\TimemanSection;
use Bitrix\Main\Loader;

if (!Loader::includeModule('intranet'))
{
	return;
}

$menuItems = [];
foreach (TimemanSection::getItems() as $item)
{
	if ($item['available'] && ToolsManager::getInstance()->checkAvailabilityByToolId($item['id']))
	{
		$menuItems[] = [
			$item['title'] ?? '',
			$item['url'] ?? '',
			$item['extraUrls'] ?? [],
			$item['menuData'] ?? [],
			'',
		];
	}
}

$aMenuLinks = array_merge($menuItems, $aMenuLinks);