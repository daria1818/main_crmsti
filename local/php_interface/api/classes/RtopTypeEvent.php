<?
/*Типы события в календаре*/
use Bitrix\Main\Entity;

class RtopTypeEventTable extends Entity\DataManager
{
	public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'rtop_type_event';
    }

    public static function getMap()
    {
        return array(
        	new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true,
            )),
        	new Entity\IntegerField('EVENT_ID', array(
                'required' => true
            )),
            new Entity\StringField('TYPE', array(
                'required' => true,
            ))
        );
    }
}
?>