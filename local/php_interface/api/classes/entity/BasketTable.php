<?php


namespace Api\Classes\Entity;

use Bitrix\Main\ORM\Data\DataManager,
    Bitrix\Main\ORM\Fields\BooleanField,
    Bitrix\Main\ORM\Fields\DatetimeField,
    Bitrix\Main\ORM\Fields\FloatField,
    Bitrix\Main\ORM\Fields\IntegerField,
    Bitrix\Main\ORM\Fields\StringField,
    Bitrix\Main\ORM\Fields\Validators\LengthValidator;

class BasketTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_sale_basket';
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
                'FUSER_ID',
                [
                    'required' => true,
                ]
            ),
            new IntegerField(
                'ORDER_ID',
                []
            ),
            new IntegerField(
                'PRODUCT_ID',
                [
                    'required' => true,
                ]
            ),
            new IntegerField(
                'PRODUCT_PRICE_ID',
                []
            ),
            new IntegerField(
                'PRICE_TYPE_ID',
                []
            ),
            new FloatField(
                'PRICE',
                [
                    'required' => true,
                ]
            ),
            new StringField(
                'CURRENCY',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateCurrency'],
                ]
            ),
            new FloatField(
                'BASE_PRICE',
                []
            ),
            new BooleanField(
                'VAT_INCLUDED',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'Y',
                ]
            ),
            new DatetimeField(
                'DATE_INSERT',
                [
                    'required' => true,
                ]
            ),
            new DatetimeField(
                'DATE_UPDATE',
                [
                    'required' => true,
                ]
            ),
            new DatetimeField(
                'DATE_REFRESH',
                []
            ),
            new FloatField(
                'WEIGHT',
                []
            ),
            new FloatField(
                'QUANTITY',
                [
                    'default' => 0.0000,
                ]
            ),
            new StringField(
                'LID',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateLid'],
                ]
            ),
            new BooleanField(
                'DELAY',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                ]
            ),
            new StringField(
                'NAME',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateName'],
                ]
            ),
            new BooleanField(
                'CAN_BUY',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'Y',
                ]
            ),
            new StringField(
                'MODULE',
                [
                    'validation' => [__CLASS__, 'validateModule'],
                ]
            ),
            new StringField(
                'CALLBACK_FUNC',
                [
                    'validation' => [__CLASS__, 'validateCallbackFunc'],
                ]
            ),
            new StringField(
                'NOTES',
                [
                    'validation' => [__CLASS__, 'validateNotes'],
                ]
            ),
            new StringField(
                'ORDER_CALLBACK_FUNC',
                [
                    'validation' => [__CLASS__, 'validateOrderCallbackFunc'],
                ]
            ),
            new StringField(
                'DETAIL_PAGE_URL',
                [
                    'validation' => [__CLASS__, 'validateDetailPageUrl'],
                ]
            ),
            new FloatField(
                'DISCOUNT_PRICE',
                [
                    'required' => true,
                ]
            ),
            new StringField(
                'CANCEL_CALLBACK_FUNC',
                [
                    'validation' => [__CLASS__, 'validateCancelCallbackFunc'],
                ]
            ),
            new StringField(
                'PAY_CALLBACK_FUNC',
                [
                    'validation' => [__CLASS__, 'validatePayCallbackFunc'],
                ]
            ),
            new StringField(
                'PRODUCT_PROVIDER_CLASS',
                [
                    'validation' => [__CLASS__, 'validateProductProviderClass'],
                ]
            ),
            new StringField(
                'CATALOG_XML_ID',
                [
                    'validation' => [__CLASS__, 'validateCatalogXmlId'],
                ]
            ),
            new StringField(
                'PRODUCT_XML_ID',
                [
                    'validation' => [__CLASS__, 'validateProductXmlId'],
                ]
            ),
            new StringField(
                'DISCOUNT_NAME',
                [
                    'validation' => [__CLASS__, 'validateDiscountName'],
                ]
            ),
            new StringField(
                'DISCOUNT_VALUE',
                [
                    'validation' => [__CLASS__, 'validateDiscountValue'],
                ]
            ),
            new StringField(
                'DISCOUNT_COUPON',
                [
                    'validation' => [__CLASS__, 'validateDiscountCoupon'],
                ]
            ),
            new FloatField(
                'VAT_RATE',
                [
                    'default' => 0.0000,
                ]
            ),
            new BooleanField(
                'SUBSCRIBE',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                ]
            ),
            new BooleanField(
                'DEDUCTED',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                ]
            ),
            new BooleanField(
                'RESERVED',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                ]
            ),
            new BooleanField(
                'BARCODE_MULTI',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                ]
            ),
            new FloatField(
                'RESERVE_QUANTITY',
                []
            ),
            new BooleanField(
                'CUSTOM_PRICE',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                ]
            ),
            new StringField(
                'DIMENSIONS',
                [
                    'validation' => [__CLASS__, 'validateDimensions'],
                ]
            ),
            new IntegerField(
                'TYPE',
                []
            ),
            new IntegerField(
                'SET_PARENT_ID',
                []
            ),
            new IntegerField(
                'MEASURE_CODE',
                []
            ),
            new StringField(
                'MEASURE_NAME',
                [
                    'validation' => [__CLASS__, 'validateMeasureName'],
                ]
            ),
            new StringField(
                'RECOMMENDATION',
                [
                    'validation' => [__CLASS__, 'validateRecommendation'],
                ]
            ),
            new StringField(
                'XML_ID',
                [
                    'validation' => [__CLASS__, 'validateXmlId'],
                ]
            ),
            new IntegerField(
                'SORT',
                [
                    'default' => 100,
                ]
            ),
            new StringField(
                'MARKING_CODE_GROUP',
                [
                    'validation' => [__CLASS__, 'validateMarkingCodeGroup'],
                ]
            ),
        ];
    }

    /**
     * Returns validators for CURRENCY field.
     *
     * @return array
     */
    public static function validateCurrency()
    {
        return [
            new LengthValidator(null, 3),
        ];
    }

    /**
     * Returns validators for LID field.
     *
     * @return array
     */
    public static function validateLid()
    {
        return [
            new LengthValidator(null, 2),
        ];
    }

    /**
     * Returns validators for NAME field.
     *
     * @return array
     */
    public static function validateName()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for MODULE field.
     *
     * @return array
     */
    public static function validateModule()
    {
        return [
            new LengthValidator(null, 100),
        ];
    }

    /**
     * Returns validators for CALLBACK_FUNC field.
     *
     * @return array
     */
    public static function validateCallbackFunc()
    {
        return [
            new LengthValidator(null, 100),
        ];
    }

    /**
     * Returns validators for NOTES field.
     *
     * @return array
     */
    public static function validateNotes()
    {
        return [
            new LengthValidator(null, 250),
        ];
    }

    /**
     * Returns validators for ORDER_CALLBACK_FUNC field.
     *
     * @return array
     */
    public static function validateOrderCallbackFunc()
    {
        return [
            new LengthValidator(null, 100),
        ];
    }

    /**
     * Returns validators for DETAIL_PAGE_URL field.
     *
     * @return array
     */
    public static function validateDetailPageUrl()
    {
        return [
            new LengthValidator(null, 250),
        ];
    }

    /**
     * Returns validators for CANCEL_CALLBACK_FUNC field.
     *
     * @return array
     */
    public static function validateCancelCallbackFunc()
    {
        return [
            new LengthValidator(null, 100),
        ];
    }

    /**
     * Returns validators for PAY_CALLBACK_FUNC field.
     *
     * @return array
     */
    public static function validatePayCallbackFunc()
    {
        return [
            new LengthValidator(null, 100),
        ];
    }

    /**
     * Returns validators for PRODUCT_PROVIDER_CLASS field.
     *
     * @return array
     */
    public static function validateProductProviderClass()
    {
        return [
            new LengthValidator(null, 100),
        ];
    }

    /**
     * Returns validators for CATALOG_XML_ID field.
     *
     * @return array
     */
    public static function validateCatalogXmlId()
    {
        return [
            new LengthValidator(null, 100),
        ];
    }

    /**
     * Returns validators for PRODUCT_XML_ID field.
     *
     * @return array
     */
    public static function validateProductXmlId()
    {
        return [
            new LengthValidator(null, 100),
        ];
    }

    /**
     * Returns validators for DISCOUNT_NAME field.
     *
     * @return array
     */
    public static function validateDiscountName()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for DISCOUNT_VALUE field.
     *
     * @return array
     */
    public static function validateDiscountValue()
    {
        return [
            new LengthValidator(null, 32),
        ];
    }

    /**
     * Returns validators for DISCOUNT_COUPON field.
     *
     * @return array
     */
    public static function validateDiscountCoupon()
    {
        return [
            new LengthValidator(null, 32),
        ];
    }

    /**
     * Returns validators for DIMENSIONS field.
     *
     * @return array
     */
    public static function validateDimensions()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for MEASURE_NAME field.
     *
     * @return array
     */
    public static function validateMeasureName()
    {
        return [
            new LengthValidator(null, 50),
        ];
    }

    /**
     * Returns validators for RECOMMENDATION field.
     *
     * @return array
     */
    public static function validateRecommendation()
    {
        return [
            new LengthValidator(null, 40),
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

    /**
     * Returns validators for MARKING_CODE_GROUP field.
     *
     * @return array
     */
    public static function validateMarkingCodeGroup()
    {
        return [
            new LengthValidator(null, 100),
        ];
    }
}