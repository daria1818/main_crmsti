<?php

declare(strict_types=1);

namespace Pwd\Entity;

use spaceonfire\BitrixTools\ORM\IblockElement;

class CatalogTable extends IblockElement
{
	/**
	 * @return string
	 */
	public static function getIblockCode(): string
	{
		return 'CRM_PRODUCT_CATALOG';
	}
}
