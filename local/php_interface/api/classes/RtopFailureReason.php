<?
/*Причина отмены заказа*/
use Bitrix\Main\Entity;

class RtopFailureReasonTable extends Entity\DataManager
{
	public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'rtop_failure_reason';
    }

    public static function getMap()
    {
        return array(
        	new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true,
            )),
        	new Entity\IntegerField('ORDER_ID', array(
                'required' => true
            )),
            new Entity\IntegerField('FAILURE_ID', array(
                'required' => true,
            ))
        );
    }
}
?>