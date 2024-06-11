<?php /** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUndefinedFieldInspection */

/** @noinspection PhpUndefinedConstantInspection */

/** @noinspection PhpUndefinedClassInspection */

/** @noinspection PhpUndefinedNamespaceInspection */

/**
 * Bork Framework
 *
 * @package    Bork
 * @subpackage main
 * @copyright  2014 Bork
 */

namespace xGuard\Main\Profile;

use \xGuard\Main;

/**
 * Base entity
 */

IncludeModuleLangFile(__FILE__);

/**
 * Class Base
 *
 * @package xGuard\Main\Profile
 */
class Base extends Main
{

    /**
     * @var int
     */
    protected $type;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var array
     */
    protected $model;

    /**
     * @var
     */
    protected $params = [];

    /**
     * Base constructor.
     *
     * @param array $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->IncludeModule('sale');
        $this->IncludeModule('catalog');
    }

    /**
     * @param int|string $code
     */
    public function setCode($code = null)
    {
        $this->code = $code ?? '0';
    }

    /**
     * @param int $type
     */
    public function setType(int $type = null)
    {
        $this->type = $type ?? 1;
    }

    /**
     * @param array $params
     */
    public function setParams(array $params = null)
    {
        $this->params = $params ?? [];
    }

    /**
     * @param null $model
     */
    public function setModel($model = null)
    {
        if (null !== $model) {
            $this->model = $model;

            return;
        }

        $this->params['SALE_ORDER_PROPERTIES']['GETLIST'] = array(
            'ORDER'  => array('SORT' => 'ASC'),
            'FILTER' => array('ACTIVE' => 'Y', 'USER_PROPS' => 'Y'),
        );

        /** @noinspection PhpUndefinedClassInspection */
        $nsItem = \CSaleOrderProps::GetList(
            $this->params['SALE_ORDER_PROPERTIES']['GETLIST']['ORDER'],
            $this->params['SALE_ORDER_PROPERTIES']['GETLIST']['FILTER']
        );

        /** @noinspection PhpUndefinedMethodInspection */
        while ($arItem = $nsItem->Fetch()):
            $this->model['ORDER_PROPS'][] = $arItem['ID'];
            $this->model['ORDER_PROPS_ID'][$arItem['ID']] = $arItem['CODE'];
            $this->model['ORDER_PROPS_CODE'][$arItem['CODE']] = $arItem['ID'];
        endwhile;

        $this->params['SALE_ORDER_USER_PROPERTIES']['GETLIST'] = array(
            'ORDER'  => array('SORT' => 'ASC'),
            'FILTER' => array(
                'ORDER_PROPS_ID' => $this->model['ORDER_PROPS'],
                'USER_PROPS_ID'  => $this->code,
            ),
        );
        /** @noinspection PhpUndefinedClassInspection */
        $nsItem = \CSaleOrderUserPropsValue::GetList(
            $this->params['SALE_ORDER_USER_PROPERTIES']['GETLIST']['ORDER'],
            $this->params['SALE_ORDER_USER_PROPERTIES']['GETLIST']['FILTER']
        );
        /** @noinspection PhpUndefinedMethodInspection */
        while ($arItem = $nsItem->Fetch()):
            $ignoreGroup = \constant('PERSON_GROUP_TYPE_HIDE_'.$arItem['PROP_PERSON_TYPE_ID']);

            if ($ignoreGroup === $arItem['PROP_PROPS_GROUP_ID']):
                continue;
            endif;

            switch ($this->type) {
                case '1':
                default:
                    if (empty($this->model['DATA'])):
                        $this->model['DATA'][] = array(
                            'NAME'       => '',
                            'FIELD_NAME' => 'PERSON_TYPE_ID',
                            'VALUE'      => $arItem['PROP_PERSON_TYPE_ID'],
                        );
                    endif;

                    $this->model['DATA'][] = array(
                        'NAME'       => $arItem['PROP_NAME'],
                        'FIELD_NAME' => $arItem['PROP_CODE'],
                        'VALUE'      => $arItem['VALUE'],
                    );
                    break;
                case '2':
                    $this->model[$arItem['PROP_CODE']] = $arItem['VALUE'];
                    break;
                case '3':
                    $this->model['PROFILES'][$arItem['PROP_CODE']] = $arItem;
                    $this->model['PROFILE_CODE'][$arItem['PROP_CODE']] = $arItem['VALUE'];
                    $this->model['PROFILE_ID'][$arItem['ORDER_PROPS_ID']] = $arItem['VALUE'];
                    break;
            }
        endwhile;

        if (!empty($this->code)):
            $this->model['USER_PROFILE_ID'] = $this->code;
        endif;

        switch ($this->type) {
            case '2':
                unset($this->model['ORDER_PROPS'],$this->model['ORDER_PROPS_CODE'],$this->model['ORDER_PROPS_ID']);
            break;
            case '3':
                if (isset($this->params['REQUEST']['PERSON_TYPE_ID'])):
                    $this->params['SALE_ORDER_PROPERTIES_ADDITIONAL']['GETLIST'] = array(
                        'ORDER'  => array('SORT' => 'ASC'),
                        'FILTER' => array('ACTIVE' => 'Y', 'USER_PROPS' => 'N', 'PERSON_TYPE_ID' => $this->params['REQUEST']['PERSON_TYPE_ID']),
                    );
                    /** @noinspection PhpUndefinedClassInspection */
                    $nsItem = \CSaleOrderProps::GetList(
                        $this->params['SALE_ORDER_PROPERTIES_ADDITIONAL']['GETLIST']['ORDER'],
                        $this->params['SALE_ORDER_PROPERTIES_ADDITIONAL']['GETLIST']['FILTER']
                    );
                    /** @noinspection PhpUndefinedMethodInspection */
                    while ($arItem = $nsItem->Fetch()):
                        $this->model['PROFILES'][$arItem['CODE']] = array(
                            'PROP_NAME'      => $arItem['NAME'],
                            'PROP_CODE'      => $arItem['CODE'],
                            'ORDER_PROPS_ID' => $arItem['ID'],
                            'VALUE'          => $this->params['REQUEST'][$arItem['CODE']],
                        );
                        $this->model['ORDER_PROPS_ID'][$arItem['ID']] = $arItem['CODE'];
                        $this->model['ORDER_PROPS_CODE'][$arItem['CODE']] = $arItem['ID'];
                        $this->model['PROFILE_CODE'][$arItem['CODE']] = $this->params['REQUEST'][$arItem['CODE']];
                        $this->model['PROFILE_ID'][$arItem['ID']] = $this->params['REQUEST'][$arItem['CODE']];
                    endwhile;
                endif;
                break;
        }
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        if (null === $this->model) {
            $this->setModel();
        }

        return $this->model;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function removeModel(int $id = null): bool
    {
        return (bool) (new \CSaleOrderUserProps)->delete($id??$this->code);
    }
}

