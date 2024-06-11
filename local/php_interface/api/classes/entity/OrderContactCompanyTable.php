<?php


namespace Api\Classes\Entity;

use Bitrix\Main\ORM\Data\DataManager,
    Bitrix\Main\ORM\Fields\IntegerField,
    Bitrix\Main\ORM\Fields\StringField,
    Bitrix\Main\ORM\Fields\Validators\LengthValidator;

class OrderContactCompanyTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_crm_order_contact_company';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            new IntegerField(
                'ID',
                [
                    'primary' => true,
                    'autocomplete' => true,
                ]
            ),
            new IntegerField(
                'ORDER_ID',
                [
                    'required' => true,
                ]
            ),
            new IntegerField(
                'ENTITY_ID',
                [
                    'required' => true,
                ]
            ),
            new IntegerField(
                'ENTITY_TYPE_ID',
                [
                    'required' => true,
                ]
            ),
            new IntegerField(
                'SORT',
                [
                    'required' => true,
                ]
            ),
            new IntegerField(
                'ROLE_ID',
                [
                    'required' => true,
                ]
            ),
            new StringField(
                'IS_PRIMARY',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateIsPrimary'],
                ]
            ),
            new StringField(
                'XML_ID',
                [
                    'validation' => [__CLASS__, 'validateXmlId'],
                ]
            ),
        ];
    }

    /**
     * Returns validators for IS_PRIMARY field.
     *
     * @return array
     */
    public static function validateIsPrimary()
    {
        return [
            new LengthValidator(null, 1),
        ];
    }

    /**
     * Returns validators for XML_ID field.
     *
     * @return array
     */
    public static function validateXmlId()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }
}