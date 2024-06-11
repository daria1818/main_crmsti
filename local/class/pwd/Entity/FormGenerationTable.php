<?php

declare(strict_types=1);

namespace Pwd\Entity;

use spaceonfire\BitrixTools\ORM\BaseHighLoadBlockDataManager;

/**
 * Class FormGenerationTable
 *
 * Fields:
 * - ID
 * - UF_ID
 * - UF_DESC
 * - UF_TITLE
 * - UF_DATE
 *
 * @package Pwd\Entity
 */
final class FormGenerationTable extends BaseHighLoadBlockDataManager
{
	/**
	 * @inheritDoc
	 */
	public static function getHLId()
	{
		return 'FormGeneration';
	}
}
