<?
use \Bitrix\Main,
    \Bitrix\Main\Localization\Loc,
    \Bitrix\Sale,
    \Bitrix\Sale\Discount\Actions,
    \Bitrix\Sale\Discount\Formatter;

class RtopSaleFreeDelivery extends CSaleCondCtrlComplex
{
    public static function GetClassName()
    {
        return __CLASS__;
    }

    public static function GetControlDescr()
    {
        $description = parent::GetControlDescr();
        $description['SORT'] = 9000;
        return $description;
    }

    public static function GetControlID()
    {
        return "FreeDeliveryValue";
    }

    public static function GetControlShow($arParams)
    {
        $arControls = static::GetControls();
        $arResult = [
            'controlgroup' => true,
            'group' => false,
            'label' => 'Правило бесплатной доставки',
            'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
            'children' => []
        ];
        foreach ($arControls as &$arOneControl)
        {
            $arResult['children'][] = array(
                'controlId' => $arOneControl['ID'],
                'group' => false,
                'label' => $arOneControl['LABEL'],
                'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
                'control' => array(
                    array(
                        'id' => 'prefix',
                        'type' => 'prefix',
                        'text' => $arOneControl['PREFIX']
                    ),
                    static::GetLogicAtom($arOneControl['LOGIC']),
                    static::GetValueAtom($arOneControl['JS_VALUE'])
                )
            );
        }
        if (isset($arOneControl))
            unset($arOneControl);

        return $arResult;
    }

    public static function GetControls($controlId = false)
    {
        $controlList = [
            'FreeDeliveryValue' => [
                'ID' => 'FreeDeliveryValue',
                'FIELD' => 'VALUE',
                'FIELD_TYPE' => 'string',
                'FIELD_LENGTH' => 255,
                'MULTIPLE' => 'N',
                'LABEL' => 'Значение бесплатной доставки',
                'PREFIX' => 'Значение бесплатной доставки',
                'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_CONT, BT_COND_LOGIC_NOT_CONT)),
                'JS_VALUE' => [
                    'type' => 'input'
                ],
                'PHP_VALUE' => ''
            ]
        ];

        foreach ($controlList as &$control)
        {
            if (!isset($control['PARENT']))
                $control['PARENT'] = true;
            $control['MULTIPLE'] = 'N';
            $control['GROUP'] = 'N';
        }
        unset($control);
        if (false === $controlId)
        {
            return $controlList;
        }
        elseif (isset($controlList[$controlId]))
        {
            return $controlList[$controlId];
        }
        else
        {
            return false;
        }
    }

    public static function Generate($oneCondition, $params, $control, $subs = false)
    {
        $mxResult = '';
        $boolError = false;

        if (is_string($control))
        {
            $control = static::GetControls($control);
        }
        $boolError = !is_array($control);
        $values = array();
        if (!$boolError)
        {
            $values = static::Check($oneCondition, $params, $control, false);
            $boolError = (false === $values);
        }
        if (!$boolError)
        {
            $type = $oneCondition['logic'];
            if ($control['ID'] === 'FreeDeliveryValue')
            {
                $mxResult = 'RtopSaleFreeDelivery::applyFreeDelivery("'.$values['value'].'", "'.$type.'")';
            }
        }

        return $mxResult;
    }

    public static function applyFreeDelivery($value, $type)
    {
        $session = \Bitrix\Main\Application::getInstance()->getSession();
        return $value == $session->get('FDT_VALUE');
    }
}
?>