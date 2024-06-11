<?
namespace ApiFor1C;
use \Bitrix\Main\Diag\Debug, \Bitrix\Main\Loader;
/**
 * Класс для работы с покупателями и контрагентами
 */
class BuyersAndCounterparties {
    //public $sender, $order, $user, $properties, $counterparties;
    private 
    	/**
    	 * Объект класса Sender
    	 */
    	$sender, 
    	/**
    	 * Оъект заказа
    	 */
    	$order, 
    	/**
    	 * Массив информации о пользователе
    	 */
    	$user, 
    	/**
    	 * Массив свойств
    	 */
    	$properties, 
    	/**
    	 * Массив контрагентов
    	 */
    	$counterparties;

    protected 
    	/**
    	 * Массив типа пользователя (физ, юр, ип, обособленное)
    	 */
    	$personTypes, 
    	/**
    	 * Массив служб доставок
    	 */
    	$deliverySystem;
    /**
     * [__construct]
     * @param [type] $order [Объект заказа]
     */
    public function __construct($order)
    {
        Loader::includeModule('sale');
        Loader::includeModule('iblock');

        $this->sender = new Sender(true);
        $this->order = $order;
        $this->setFields();
        
    }

    /**
     * [setFields Установка полей]
     */
    private function setFields(){
        if($USER_ID = $this->order->getField("USER_ID")){
            $this->user = \CUser::GetByID($USER_ID)->fetch();
        }

        $this->personTypes = array(
            1 => "I", //Физическое лицо 
            2 => "E", //Юридическое лицо
            5 => "IE", //Индивидуальный предприниматель
            6 => "SU", //Обособленное подразделение
        );

        $this->deliverySystem = array(
            1 => "C", //Доставка курьером
            2 => "P", //Самовывоз
        );

        $propertyCollection = $this->order->getPropertyCollection();
        $this->properties = $fields = [];
        foreach($propertyCollection->getArray()["properties"] as $prop){
            $this->properties[$prop["CODE"]] = $prop["VALUE"][0]; 
        }
    }

    /**
     * [OnSaleOrderSaved Событие при сохранении заказа]
     * @param \Bitrix\Main\Event $event [Объект события]
     */
    public static function OnSaleOrderSaved(\Bitrix\Main\Event $event)
    {
        $order = $event->getParameter("ENTITY");
        $oldValues = $event->getParameter("VALUES");
        $isNew = $event->getParameter("IS_NEW");
        if ($isNew)
        {
            $bac = new self($order);
            $bac->RegistrationCorrespondent();
            $bac->SearchCorrespondent();
            if($bac->CheckCorrespondent()){
            	$bac->CreateCorrespondent();
            }
        }
    }

    /**
     * [RegistrationCorrespondent Регистрация профиля покупателя]
     */
    private function RegistrationCorrespondent()
    {
        /*
        LOGIN 
        EMAIL 
        TRUE Признак редактирования – если Истина, то данные (за исключением значения «Логин» и «E-Mail») редактируются
        PHONE № телефона
        FULL_NAME ФИО
        ? Паспортные данные
        PASSPORT_ISSUE_DATE Дата выдачи
        PASSPORT_NUMBER Номер паспорта
        PASSPORT_SERIES Серия паспорта 
        ISSUE_NAME Кем выдан
        ? Код подразделения
        ? Дата регистрации по месту жительства
        */
        $fields = array(
            "LOGIN" => $this->user["LOGIN"],
            "EMAIL" => $this->properties["EMAIL"],
            "EDITABLE" => true,
            "PHONE" => $this->properties["PHONE"],
            "FULL_NAME" => $this->properties["FULL_NAME"],
            "PASSPORT_ISSUE_DATE" => $arProperties["PASSPORT_ISSUE_DATE"],
            "PASSPORT_NUMBER" => $this->properties["PASSPORT_NUMBER"],
            "PASSPORT_SERIES" => $this->properties["PASSPORT_SERIES"],
            "ISSUE_NAME" => $this->properties["ISSUE_NAME"],
        );
        $this->sender->send($fields, __FUNCTION__);
    }
    
    /**
     * [SearchCorrespondent Поиск контрагента]
     */
    private function SearchCorrespondent()
    {
       /*
        Входящие параметры
            •   E-Mail пользователя сайта
            •   ИНН – для ИП и частного лица
            •   ИНН + КПП – для юрлица и обособленного подразделения юрлица
       */
        $fields = array(
            "EMAIL" => $this->properties["EMAIL"],
        );

        switch ($this->personTypes[$this->order->getPersonTypeId()]) {
            case "I": //Физическое лицо
                $fields["INN"] = $this->properties["INN"];
                break;
            case "E": //Юридическое лицо
                $fields["INN"] = $this->properties["INN"]."_".$this->properties["KPP"];
                break;
            case "IE": //Индивидуальный предприниматель
                $fields["INN"] = $this->properties["INN"];
                break;
            case "SU": //Обособленное подразделение
                $fields["INN"] = $this->properties["INN"]."_".$this->properties["KPP"];
                break;
            default:
                break;
        }
        $this->sender->send($fields, __FUNCTION__);  
        /*
        Список контрагентов:
            •   Вид контрагента = Физ. Лицо:
                o   ФИО
                o   ИНН (для Индивидуального предпринимателя)
            •   Вид контрагента = Юрлицо
                o   ИНН
                o   КПП
                o   Наименование
        */
        $result = $this->sender->getResult();
        if($result["status"] == 1 && $result["Data"]){
        	$this->counterparties = $result["Data"];
        }
    }
    
    /**
     * [CheckCorrespondent Проверка найденных контрагентов]
     */
    private function CheckCorrespondent(){
    	if($this->counterparties){
    		foreach ($this->counterparties as $counterpart) {
    			if($counterpart["PERSON_TYPE"] == $this->personTypes[$this->order->getPersonTypeId()]){
	    			switch ($counterpart["PERSON_TYPE"]) {
			            case "I": //Физическое лицо
			               	//"FIO"
			            	if($counterpart["FIO"] == $this->properties["FULL_NAME"]){
			            		return false;
			            	}
			                break;
		                case "IE": //Индивидуальный предприниматель
			                //"FIO"
							//"INN"
		                	if($counterpart["FIO"] == $this->properties["FULL_NAME"] && $counterpart["INN"] == $this->properties["INN"]){
			            		return false;
			            	}
			                break;
		                case "SU": //Обособленное подразделение
			            case "E": //Юридическое лицо
			                //"FIO"
			                //"INN"
			                //"KPP"
			            	if($counterpart["FIO"] == $this->properties["FULL_NAME"] && $counterpart["INN"] == $this->properties["INN"]."_".$this->properties["KPP"]){
			            		return false;
			            	}
			                break;
			            default:
			                break;
			        }
		        }
    		}
    	}
    	return true;
    }

    /**
     * [CreateCorrespondent Регистрация нового контрагента]
     */
    private function CreateCorrespondent()
    {
        /*
        +•   Вид контрагента (Юрлицо, Физическое лицо, Индивидуальный предприниматель, Обособленное подразделение юрлица). 
        +•   Головной контрагент (ИНН+КПП) – если обособленное подразделение юридического лица
        +•   Расчетный счет - для Юр. лица, Индивидуальный предприниматель, Обособленное подразделение юр.лица)
            +o   № счета
            +o   БИК
            +o   Корр.счет
            +o   Наименование банка
            +o   ИНН – обязательно для юридического лица и Индивидуальный предприниматель.
            +o   КПП – обязательно для обособленного подразделения юрлица
            +o   ИНН+КПП – ключ поиска контрагента и синхронизации для юридического лица
        +•   E-Mail регистрации на сайте – для нового партнера создать контактное лицо. Если новый контрагент в рамках существующего партнера (определяется по поиску в списке партнеров) – контактное лицо не создается
        +•   Наименование – строка 
        +•   Полное наименование – строка 
        +•   Юридический адрес - строка
        +•   Фактический адрес - строка 
        +•   Адрес доставки - строка 
        +•   Телефон - строка 
        +•   Логин - строка
        +•   Список «Контактные лица» (если есть необходимость создать несколько контактных лиц). При создании контактного лица осуществляется поиск по Email и партнеру и если найден, то производится редактирование ФИО и должность существующего элемента (остальные реквизиты не изменяются).
            +o   ФИО - строка; 
            +o   № телефона - строка; 
            +o   е-мейл - строка; 
            +o   Должность - строка
        -•   Паспортные данные – для физического лица, если присутствуют спец.товары.
            +o   ФИО
            +o   Серия
            +o   Номер
            +o   Дата выдачи
            +o   Кем выдан
            ?o   Код подразделения
            ?o   Дата регистрации по месту жительства
        •   Вид доставки:
            +o   Самовывоз
            -o   До клиента
                +   Адрес доставки к клиенту;
                   Время доставки; 
                +   Контактное лицо
            ?o   Силами перевозчика:
                   ИНН перевозчика
                   Наименование перевозчика 
                   Адрес доставки груза к перевозчику
                   Адрес доставки до клиента
            +o   Комментарий - Строка 255

        */
        $fields = array(
            "PERSON_TYPE" => $this->personTypes[$this->order->getPersonTypeId()],
            "EMAIL" => $this->properties["EMAIL"],
            "FULL_NAME" => $this->properties["FULL_NAME"],
            "PHONE" => $this->properties["PHONE"],
            "LOGIN" => $this->user["LOGIN"],
            "BRAND" => $this->properties["BRAND"],
            "ADDRESS_REGISTER" => $this->properties["ADDRESS_REGISTER"],
            "ADDRESS_FACT" => $this->properties["ADDRESS_FACT"],
            "ADDRESS_DELIVERY" => $this->properties["ADDRESS_DELIVERY"],
            "CONTACT_NAME" => $this->properties["CONTACT_NAME"],
            "CONTACT_PHONE" => $this->properties["CONTACT_PHONE"],
            "CONTACT_EMAIL" => $this->properties["CONTACT_EMAIL"],
            "CONTACT_POSITION" => $this->properties["CONTACT_POSITION"],
            "COMMENTS" => $this->properties["COMMENTS"],
            "DELIVERY_SYSTEM" => $this->deliverySystem[is_array($this->order->getDeliverySystemId()) ? current($this->order->getDeliverySystemId()) : $this->order->getDeliverySystemId()],
        );
        if($fields["PERSON_TYPE"] != "I") {
            if($fields["PERSON_TYPE"] == "E" || $fields["PERSON_TYPE"] == "SU") {
                $fields["MAIN_COUNTERPART"] = $this->properties["INN"]."_".$this->properties["KPP"];
            }
            $fields += array(
                "RA" => $this->properties["RA"],
                "BIK" => $this->properties["BIK"],
                "KA" => $this->properties["KA"],
                "BANK_NAME" => $this->properties["BANK_NAME"],
                "INN" => $this->properties["INN"],
                "KPP" => $this->properties["KPP"],
                "INN_KPP" => $this->properties["INN"]."_".$this->properties["KPP"],
            );
        } else {
            $fields += array(
                "PASSPORT_NUMBER" => $this->properties["PASSPORT_NUMBER"],
                "PASSPORT_SERIES" => $this->properties["PASSPORT_SERIES"],
                "PASSPORT_ISSUE_DATE" => $this->properties["PASSPORT_ISSUE_DATE"],
                "ISSUE_NAME" => $this->properties["ISSUE_NAME"],
                "INN" => "",
            );
        }
        if($fields["DELIVERY_SYSTEM"] != "P") {
           $fields += array(
                "DELIVERY_ADDRESS" => $this->properties["DELIVERY_ADDRESS"],
                "DELIVERY_CONTACT" => $this->properties["DELIVERY_CONTACT"],
            ); 
        }
        $this->sender->send($fields, __FUNCTION__);
    } 
}
?>