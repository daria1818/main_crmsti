<?php


namespace Api\Classes\Entity;

use Bitrix\Main\ORM\Data\DataManager,
    Bitrix\Main\ORM\Fields\BooleanField,
    Bitrix\Main\ORM\Fields\DateField,
    Bitrix\Main\ORM\Fields\DatetimeField,
    Bitrix\Main\ORM\Fields\FloatField,
    Bitrix\Main\ORM\Fields\IntegerField,
    Bitrix\Main\ORM\Fields\StringField,
    Bitrix\Main\ORM\Fields\TextField,
    Bitrix\Main\ORM\Fields\Validators\LengthValidator;

class OrderTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_sale_order';
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
            new StringField(
                'LID',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateLid'],
                ]
            ),
            new IntegerField(
                'PERSON_TYPE_ID',
                [
                    'required' => true,
                ]
            ),
            new BooleanField(
                'PAYED',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                ]
            ),
            new DatetimeField(
                'DATE_PAYED',
                []
            ),
            new IntegerField(
                'EMP_PAYED_ID',
                []
            ),
            new BooleanField(
                'CANCELED',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                ]
            ),
            new DatetimeField(
                'DATE_CANCELED',
                []
            ),
            new IntegerField(
                'EMP_CANCELED_ID',
                []
            ),
            new StringField(
                'REASON_CANCELED',
                [
                    'validation' => [__CLASS__, 'validateReasonCanceled'],
                ]
            ),
            new StringField(
                'STATUS_ID',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateStatusId'],
                ]
            ),
            new DatetimeField(
                'DATE_STATUS',
                [
                    'required' => true,
                ]
            ),
            new IntegerField(
                'EMP_STATUS_ID',
                []
            ),
            new FloatField(
                'PRICE_DELIVERY',
                [
                    'default' => 0.0000,
                ]
            ),
            new FloatField(
                'PRICE_PAYMENT',
                [
                    'default' => 0.0000,
                ]
            ),
            new BooleanField(
                'ALLOW_DELIVERY',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                ]
            ),
            new DatetimeField(
                'DATE_ALLOW_DELIVERY',
                []
            ),
            new IntegerField(
                'EMP_ALLOW_DELIVERY_ID',
                []
            ),
            new BooleanField(
                'DEDUCTED',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                ]
            ),
            new DatetimeField(
                'DATE_DEDUCTED',
                []
            ),
            new IntegerField(
                'EMP_DEDUCTED_ID',
                []
            ),
            new StringField(
                'REASON_UNDO_DEDUCTED',
                [
                    'validation' => [__CLASS__, 'validateReasonUndoDeducted'],
                ]
            ),
            new BooleanField(
                'MARKED',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                ]
            ),
            new DatetimeField(
                'DATE_MARKED',
                []
            ),
            new IntegerField(
                'EMP_MARKED_ID',
                []
            ),
            new StringField(
                'REASON_MARKED',
                [
                    'validation' => [__CLASS__, 'validateReasonMarked'],
                ]
            ),
            new BooleanField(
                'RESERVED',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                ]
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
                'DISCOUNT_VALUE',
                [
                    'default' => 0.0000,
                ]
            ),
            new IntegerField(
                'USER_ID',
                [
                    'required' => true,
                ]
            ),
            new IntegerField(
                'PAY_SYSTEM_ID',
                []
            ),
            new StringField(
                'DELIVERY_ID',
                [
                    'validation' => [__CLASS__, 'validateDeliveryId'],
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
            new StringField(
                'USER_DESCRIPTION',
                [
                    'validation' => [__CLASS__, 'validateUserDescription'],
                ]
            ),
            new StringField(
                'ADDITIONAL_INFO',
                [
                    'validation' => [__CLASS__, 'validateAdditionalInfo'],
                ]
            ),
            new StringField(
                'PS_STATUS',
                [
                    'validation' => [__CLASS__, 'validatePsStatus'],
                ]
            ),
            new StringField(
                'PS_STATUS_CODE',
                [
                    'validation' => [__CLASS__, 'validatePsStatusCode'],
                ]
            ),
            new StringField(
                'PS_STATUS_DESCRIPTION',
                [
                    'validation' => [__CLASS__, 'validatePsStatusDescription'],
                ]
            ),
            new StringField(
                'PS_STATUS_MESSAGE',
                [
                    'validation' => [__CLASS__, 'validatePsStatusMessage'],
                ]
            ),
            new FloatField(
                'PS_SUM',
                []
            ),
            new StringField(
                'PS_CURRENCY',
                [
                    'validation' => [__CLASS__, 'validatePsCurrency'],
                ]
            ),
            new DatetimeField(
                'PS_RESPONSE_DATE',
                []
            ),
            new TextField(
                'COMMENTS',
                []
            ),
            new FloatField(
                'TAX_VALUE',
                [
                    'default' => 0.00,
                ]
            ),
            new StringField(
                'STAT_GID',
                [
                    'validation' => [__CLASS__, 'validateStatGid'],
                ]
            ),
            new FloatField(
                'SUM_PAID',
                [
                    'default' => 0.00,
                ]
            ),
            new BooleanField(
                'IS_RECURRING',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                ]
            ),
            new IntegerField(
                'RECURRING_ID',
                []
            ),
            new StringField(
                'PAY_VOUCHER_NUM',
                [
                    'validation' => [__CLASS__, 'validatePayVoucherNum'],
                ]
            ),
            new DateField(
                'PAY_VOUCHER_DATE',
                []
            ),
            new IntegerField(
                'LOCKED_BY',
                []
            ),
            new DatetimeField(
                'DATE_LOCK',
                []
            ),
            new BooleanField(
                'RECOUNT_FLAG',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'Y',
                ]
            ),
            new IntegerField(
                'AFFILIATE_ID',
                []
            ),
            new StringField(
                'DELIVERY_DOC_NUM',
                [
                    'validation' => [__CLASS__, 'validateDeliveryDocNum'],
                ]
            ),
            new DateField(
                'DELIVERY_DOC_DATE',
                []
            ),
            new BooleanField(
                'UPDATED_1C',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                ]
            ),
            new IntegerField(
                'STORE_ID',
                []
            ),
            new StringField(
                'ORDER_TOPIC',
                [
                    'validation' => [__CLASS__, 'validateOrderTopic'],
                ]
            ),
            new IntegerField(
                'CREATED_BY',
                []
            ),
            new IntegerField(
                'RESPONSIBLE_ID',
                []
            ),
            new IntegerField(
                'COMPANY_ID',
                []
            ),
            new DatetimeField(
                'DATE_PAY_BEFORE',
                []
            ),
            new DatetimeField(
                'DATE_BILL',
                []
            ),
            new StringField(
                'ACCOUNT_NUMBER',
                [
                    'validation' => [__CLASS__, 'validateAccountNumber'],
                ]
            ),
            new StringField(
                'TRACKING_NUMBER',
                [
                    'validation' => [__CLASS__, 'validateTrackingNumber'],
                ]
            ),
            new StringField(
                'XML_ID',
                [
                    'validation' => [__CLASS__, 'validateXmlId'],
                ]
            ),
            new StringField(
                'ID_1C',
                [
                    'validation' => [__CLASS__, 'validateId1c'],
                ]
            ),
            new StringField(
                'VERSION_1C',
                [
                    'validation' => [__CLASS__, 'validateVersion1c'],
                ]
            ),
            new IntegerField(
                'VERSION',
                [
                    'default' => 0,
                ]
            ),
            new BooleanField(
                'EXTERNAL_ORDER',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                ]
            ),
            new BooleanField(
                'RUNNING',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                ]
            ),
            new StringField(
                'BX_USER_ID',
                [
                    'validation' => [__CLASS__, 'validateBxUserId'],
                ]
            ),
            new TextField(
                'SEARCH_CONTENT',
                []
            ),
            new BooleanField(
                'IS_SYNC_B24',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                ]
            ),
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
     * Returns validators for REASON_CANCELED field.
     *
     * @return array
     */
    public static function validateReasonCanceled()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for STATUS_ID field.
     *
     * @return array
     */
    public static function validateStatusId()
    {
        return [
            new LengthValidator(null, 2),
        ];
    }

    /**
     * Returns validators for REASON_UNDO_DEDUCTED field.
     *
     * @return array
     */
    public static function validateReasonUndoDeducted()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for REASON_MARKED field.
     *
     * @return array
     */
    public static function validateReasonMarked()
    {
        return [
            new LengthValidator(null, 255),
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
     * Returns validators for DELIVERY_ID field.
     *
     * @return array
     */
    public static function validateDeliveryId()
    {
        return [
            new LengthValidator(null, 50),
        ];
    }

    /**
     * Returns validators for USER_DESCRIPTION field.
     *
     * @return array
     */
    public static function validateUserDescription()
    {
        return [
            new LengthValidator(null, 2000),
        ];
    }

    /**
     * Returns validators for ADDITIONAL_INFO field.
     *
     * @return array
     */
    public static function validateAdditionalInfo()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for PS_STATUS field.
     *
     * @return array
     */
    public static function validatePsStatus()
    {
        return [
            new LengthValidator(null, 1),
        ];
    }

    /**
     * Returns validators for PS_STATUS_CODE field.
     *
     * @return array
     */
    public static function validatePsStatusCode()
    {
        return [
            new LengthValidator(null, 5),
        ];
    }

    /**
     * Returns validators for PS_STATUS_DESCRIPTION field.
     *
     * @return array
     */
    public static function validatePsStatusDescription()
    {
        return [
            new LengthValidator(null, 250),
        ];
    }

    /**
     * Returns validators for PS_STATUS_MESSAGE field.
     *
     * @return array
     */
    public static function validatePsStatusMessage()
    {
        return [
            new LengthValidator(null, 250),
        ];
    }

    /**
     * Returns validators for PS_CURRENCY field.
     *
     * @return array
     */
    public static function validatePsCurrency()
    {
        return [
            new LengthValidator(null, 3),
        ];
    }

    /**
     * Returns validators for STAT_GID field.
     *
     * @return array
     */
    public static function validateStatGid()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for PAY_VOUCHER_NUM field.
     *
     * @return array
     */
    public static function validatePayVoucherNum()
    {
        return [
            new LengthValidator(null, 20),
        ];
    }

    /**
     * Returns validators for DELIVERY_DOC_NUM field.
     *
     * @return array
     */
    public static function validateDeliveryDocNum()
    {
        return [
            new LengthValidator(null, 20),
        ];
    }

    /**
     * Returns validators for ORDER_TOPIC field.
     *
     * @return array
     */
    public static function validateOrderTopic()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for ACCOUNT_NUMBER field.
     *
     * @return array
     */
    public static function validateAccountNumber()
    {
        return [
            new LengthValidator(null, 100),
        ];
    }

    /**
     * Returns validators for TRACKING_NUMBER field.
     *
     * @return array
     */
    public static function validateTrackingNumber()
    {
        return [
            new LengthValidator(null, 255),
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
     * Returns validators for ID_1C field.
     *
     * @return array
     */
    public static function validateId1c()
    {
        return [
            new LengthValidator(null, 36),
        ];
    }

    /**
     * Returns validators for VERSION_1C field.
     *
     * @return array
     */
    public static function validateVersion1c()
    {
        return [
            new LengthValidator(null, 15),
        ];
    }

    /**
     * Returns validators for BX_USER_ID field.
     *
     * @return array
     */
    public static function validateBxUserId()
    {
        return [
            new LengthValidator(null, 32),
        ];
    }
}