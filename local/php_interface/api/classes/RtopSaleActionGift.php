<?
use Bitrix\Main,
	Bitrix\Main\Localization\Loc,
	Bitrix\Sale;

class RtopSaleActionGift extends CSaleActionGiftCtrlGroup
{
	public static function GetControlID()
	{
		return 'RtopGiftCondGroup';
	}

	public static function GetControlDescr()
	{
		$controlDescr = parent::GetControlDescr();
		$controlDescr['FORCED_SHOW_LIST'] = array(
			'GifterCondIBElement'
		);
		$controlDescr['SORT'] = 300;

		return $controlDescr;
	}

	public static function GetControlShow($arParams)
	{
		return array(
			'controlId' => static::GetControlID(),
			'group' => true,
			'containsOneAction' => true,
			'label' => 'Предоставить подарок за 10 рублей',
			'defaultText' => '',
			'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
			'control' => array(
				'Предоставить подарок за 10 рублей'
			),
			'mess' => array(
				'ADD_CONTROL' => Loc::getMessage('BT_SALE_SUBACT_ADD_CONTROL'),
				'SELECT_CONTROL' => Loc::getMessage('BT_SALE_SUBACT_SELECT_CONTROL'),
				'DELETE_CONTROL' => Loc::getMessage('BT_SALE_ACT_GROUP_DELETE_CONTROL')
			)
		);
	}

	public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
	{
		//I have to notice current method can work only with Gifter's. For example, it is CCatalogGifterProduct.
		//Probably in future we'll add another gifter's and create interface or class, which will tell about attitude to CSaleActionGiftCtrlGroup.
		$mxResult = '';
		$boolError = false;

		if (!isset($arSubs) || !is_array($arSubs) || empty($arSubs))
		{
			$boolError = true;
		}
		else
		{
			$mxResult = 'RtopSaleActionGift::applySimpleGift(' . $arParams['ORDER'] . ', ' . implode('; ',$arSubs) . ');';
		}
		return $mxResult;
	}

	public static function applySimpleGift(array &$order, $filter)
	{
		\Bitrix\Sale\Discount\Actions::increaseApplyCounter();

		$actionDescription = array(
			'ACTION_TYPE' => \Bitrix\Sale\Discount\Formatter::TYPE_SIMPLE_GIFT
		);
		\Bitrix\Sale\Discount\Actions::setActionDescription(\Bitrix\Sale\Discount\Actions::RESULT_ENTITY_BASKET, $actionDescription);

		if (!is_callable($filter))
			return;

		if (empty($order['BASKET_ITEMS']) || !is_array($order['BASKET_ITEMS']))
			return;

		\Bitrix\Sale\Discount\Actions::disableBasketFilter();

		$itemsCopy = $order['BASKET_ITEMS'];
		Main\Type\Collection::sortByColumn($itemsCopy, 'PRICE', null, null, true);
		$filteredBasket = \Bitrix\Sale\Discount\Actions::getBasketForApply(
			$itemsCopy,
			$filter,
			array(
				'GIFT_TITLE' => 'Подарок'
			)
		);
		unset($itemsCopy);

		\Bitrix\Sale\Discount\Actions::enableBasketFilter();

		if (empty($filteredBasket))
			return;

		$applyBasket = array_filter($filteredBasket, '\Bitrix\Sale\Discount\Actions::filterBasketForAction');
		unset($filteredBasket);
		if (empty($applyBasket))
			return;

		foreach ($applyBasket as $basketCode => $basketRow)
		{
			$basketRow['DISCOUNT_PRICE'] = $basketRow['~DISCOUNT_PRICE'] = $basketRow['BASE_PRICE'] - 10;
			$basketRow['PRICE'] = $basketRow['~PRICE'] = 10;

			$order['BASKET_ITEMS'][$basketCode] = $basketRow;

			$rowActionDescription = $actionDescription;
			$rowActionDescription['BASKET_CODE'] = $basketCode;
			\Bitrix\Sale\Discount\Actions::setActionResult(\Bitrix\Sale\Discount\Actions::RESULT_ENTITY_BASKET, $rowActionDescription);
			unset($rowActionDescription);
		}
		unset($basketCode, $basketRow);
	}
}
?>