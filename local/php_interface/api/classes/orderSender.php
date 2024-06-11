<?
namespace ApiFor1C;
use \Bitrix\Main\Diag\Debug, \Bitrix\Main\Loader;

/**
 * Класс для отправки информации по заказам
 */
class OrderSender {
    private 
        /**
         * Класс объекта класса Sender
         */
    	$sender, 
        /**
         * Класс объекта заказа
         */
    	$order, 
        /**
         * Массив информации о пользователе
         */
    	$user, 
        /**
         * Массив типа пользователя (физ, юр, ип, обособленное)
         */
    	$personTypes, 
        /**
         * Массив служб доставок
         */
    	$deliverySystem,
        /**
         * Объект корзины
         */
		$basket,
        /**
         * Объект коллекции свойств
         */
		$propertyCollection,
        /**
         * Объект коллекции платежных систем
         */
		$paymentCollection,
        /**
         * Объект скидок
         */
		$discountData,
        /**
         * Массив платежних систем
         */
		$paySystems,
        /**
         * Массив статусов
         */
        $arStatuses,
        /**
         * [$arSelectBasketProps Массив выборки полей товара корзины]
         * @var array
         */
        $arSelectBasketProps = array(
        	"PRODUCT_XML_ID" => "ID", //id товара
        	"NAME"=> "", //имя товара
        	"PRICE"=> "", //итоговая цена товара
        	"BASE_PRICE"=> "", //базовая цена товара
        	"QUANTITY"=> ""//кол-во товара
        ),
        /**
         * [$arSelectProductProps Массив выборки дополнительных свойств товара корзины]
         * @var array
         */
        $arSelectProductProps = array(
        	"PROPERTY_CML2_ARTICLE"=> "ARTICLE", //Артикул – ключ синхронизации списка товаров
        ),
        /**
         * [$arSelectProps Массив выборки полей заказа]
         * @var array
         */
        $arSelectProps = array( //key = id битрикса, value = id для 1C
			"ID" => "", //id заказа
			"BASKET" => "", //товары (какой товар, цена, количество)
			"PRICE" => "TOTALSUMM", //стоимость
			"COUPON_LIST" => "", //промокод если был применен
			"DATE_INSERT" => "DATE_CREATE", //дата и время создания заказа
			"USER_ID" => "", //ID пользователя
			"FIO" => "", //ФИО пользователя
			"EMAIL" => "", //e-mail
			"PHONE" => "", //телефон
			"ADDRESS" => "", //адрес доставки
			"USER_DESCRIPTION" => "USER_COMMENT", //комментарий
			"PERSON_TYPE_ID" => "", //тип пользователя (физ/юр лицо)
			"PAY_SYSTEM_ID" => "PAY_SYSTEM", //способ оплаты
			"DELIVERY_ID" => "", //способ доставки
			
			//"BONUS_CARD" => "", //номер бонусной карты
			"DM_POINTS_COUNT" => "DM_POINTS_COUNT", //количество списанных баллов
			
			//"STATUS_ID" => "", //статус

			"INN" => "", //ИНН – обязательно, если оформление на юрлицо или обособленное подразделение
			"KPP" => "", //КПП – обязательно, если оформление на обособленное подразделение юрлица

			"CONTACT_NAME" => "", //ФИО Контактного лица
			"CONTACT_PHONE" => "", // Телефон Контактного лица
			"CONTACT_EMAIL" => "", // E-mail Контактного лица
		);

    /**
     * [__construct]
     * @param [type] $order [Объект заказа]
     */
    public function __construct($order) {
        Loader::includeModule('sale');
        Loader::includeModule('iblock');

        $this->sender = new Sender(true);
        $this->order = $order;
        $this->setFields();
    }

    /**
     * [setFields Установка полей]
     */
    private function setFields() {
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

		$this->arStatuses = array(
            "N" => "Заявка у менеджера",
            "P" => "Отгрузка разрешена",
            "S" => "Заявка собирается",
            "Y" => "Заявка собрана",
            "G" => "Заявка на доставке",
            "W" => "Заявка доставлена",
            "L" => "Заявка отменена",
            "F" => "Выполнен",
            "D" => "Отменен",
            "CANCELED" => "Отменен",
    	);

        $this->basket = $this->order->getBasket();
		$this->propertyCollection = $this->order->getPropertyCollection();
		$this->paymentCollection = $this->order->getPaymentCollection();
		$this->discountData = $this->order->getDiscount()->getApplyResult();
    }
        
    /**
     * [onSaleOrderSavedHandler Событие при сохранении заказа]
     * @param  \Bitrix\Main\Event $event [Объект события]
     */
    public static function onSaleOrderSavedHandler(\Bitrix\Main\Event $event) {
        $order = $event->getParameter("ENTITY");
        if ($event->getParameter("IS_NEW")){
        	$orderSender = new self($order);
        	$orderSender->OrderIN();
        }
    }

    /**
     * [OrderIN Отправка заказа в 1С]
     */
    public function OrderIN() {
    	$fields = $this->getOrderFields();
        $this->sender->send($fields, __FUNCTION__);
    }

    /**
     * [getOrderFields Получение свойств заказа]
     * @return [array] [Свойства заказа]
     */
    private function getOrderFields() {
		$arOrderVals = array();

		$idItems = [];
        foreach ($this->basket->getBasketItems() as $basketItem) {
            $arBasketPropsPre = $basketItem->getFields()->getValues();
            $idItems[$arBasketPropsPre['PRODUCT_ID']] = $arBasketPropsPre['PRODUCT_ID'];
        }
        if(!empty($idItems)){
            $arSelect = array_merge(Array("ID", "IBLOCK_ID"), array_keys($this->arSelectProductProps));
            $arFilter = Array("IBLOCK_ID"=>CATALOG_IBLOCK, "ID" => $idItems);
            $res = \CIBlockElement::GetList(Array("SORT"=>"ASC"), $arFilter, false, false, $arSelect);
            while($arItem = $res->Fetch()) {
                $arProductProps[$arItem['ID']] = $arItem;
            }
        }
		foreach ($this->arSelectProps as $prop => $renameProp) {
			$key = $renameProp ? : $prop;
			switch ($prop) {
				case 'BASKET':
					foreach ($this->basket->getBasketItems() as $basketItem) {
						$arBasketItem = array();
                        $arBasketProps = $basketItem->getFields()->getValues();
						foreach ($this->arSelectBasketProps as $basketProp => $renameBasketProp) {
							$basketKey = $renameBasketProp ? : $basketProp;
							switch ($basketProp) {
								case 'CML2_ARTICLE':
                                    $itemPropertyCollection = $basketItem->getPropertyCollection();
                                    $propsItem = $itemPropertyCollection->getPropertyValues();
                                    if ($propsItem[$basketProp]["VALUE"]) {
                                        $arBasketItem[$basketKey] = $propsItem[$basketProp]["VALUE"];
                                    }
                                    break;
								default:
									if($basketPropVal = $arBasketProps[$basketProp]){
										$arBasketItem[$basketKey] = $basketPropVal;
									}
								break;
							}
						}
						foreach ($this->arSelectProductProps as $productProp => $renameProductProp) {
							$productKey = $renameProductProp ? : $productProp;
							switch ($productProp) {
								default:
									if($productPropVal = $arProductProps[$arBasketProps["PRODUCT_ID"]][$productProp."_VALUE"]){
										$arBasketItem[$productKey] = $productPropVal;
									}
								break;
							}
						}
						$arOrderVals[$key][] = $arBasketItem;
					}
					break;
				case 'COUPON_LIST':
					$arOrderVals[$key] = $this->discountData['COUPON_LIST'] ? array_keys($this->discountData['COUPON_LIST']) : array();
					break;
				case 'FIO':
					if($propName = $this->propertyCollection->getPayerName())
						$arOrderVals[$key] = $propName->getValue();
					break;
				case 'EMAIL':
					if($propEmail = $this->propertyCollection->getUserEmail())
						$arOrderVals[$key] = $propEmail->getValue();
					break;
				case 'PHONE':
					if($propPhone = $this->propertyCollection->getPhone())
						$arOrderVals[$key] = $propPhone->getValue();
					break;
				case 'ADDRESS':
					if($propAddress = $this->propertyCollection->getAddress())
					$arOrderVals[$key] = $propAddress->getValue();
					break;
				case 'PERSON_TYPE_ID':
					$arOrderVals[$key] = $this->personTypes[$this->order->getField($prop)];
					break;
				case 'PAY_SYSTEM_ID':
					foreach ($this->paymentCollection as $payment) {
                        $psID = $payment->getPaymentSystemId();
                        $arOrderVals[$key] = $this->getPaySystem($payment->getPaymentSystemId(), $this->order->getField('PERSON_TYPE_ID'));
                    }
					break;
				case 'DELIVERY_ID':
					$arOrderVals[$key] = $this->deliverySystem[$this->order->getField($prop)];
					break;
				case 'STATUS_ID':
					$arOrderVals[$key] = $this->getStatus($this->order->getField($prop), $this->order->isCanceled());
					break;
				case 'DATE_INSERT':
					$arOrderVals[$key] = new \DateTime($this->order->getField($prop));
					break;
				case 'USER_FIO':
					if($this->user['NAME'] || $this->user['LAST_NAME'] || $this->user['SECOND_NAME']){
						$nameTemplate = \CSite::GetNameFormat(false);
						$nameTemplate = "#LAST_NAME# #NAME# #SECOND_NAME#";
						$arOrderVals[$key] = \CUser::FormatName($nameTemplate, array("NAME" => $this->user['NAME'], "LAST_NAME" => $this->user['LAST_NAME'], "SECOND_NAME" => $this->user['SECOND_NAME']));
					}
					break;
				case 'USER_PHONE':
					if($this->user['PERSONAL_PHONE']){
						$arOrderVals[$key] = $this->user['PERSONAL_PHONE'];
					}
					break;
				case 'USER_EMAIL':
					if($this->user['EMAIL']){
						$arOrderVals[$key] = $this->user['EMAIL'];
					}
					break;
				default:
					if($propField = $this->order->getField($prop)){
						$arOrderVals[$key] = $propField;
					} else {
						foreach ($this->propertyCollection as $property)
					    {
					    	if($property->getField('CODE') == $prop)
					    		if($propVals = $property->getValue())
				        			$arOrderVals[$key] = $propVals;
					    }
					}
					break;
			}
		}
		return $arOrderVals;
    }

    /**
     * [getPaySystem Получение платежной системы]
     * @param  [string] $paySystemId [Идентификатор платежной системы]
     * @param  [string] $personType  [Тип пользователя]
     * @return [array]               [Платежная система]
     */
    private function getPaySystem($paySystemId, $personType) {
    	$return = array();
    	if($paySystem = \CSalePaySystem::GetByID($paySystemId, $personType)) {
    		$return = array(
    			"ID" => $paySystem["ID"],
    			"NAME" => $paySystem["NAME"],
    			"DESCRIPTION" => $paySystem["DESCRIPTION"],
    		);
    	}
        return $return;
    }

    /**
     * [getStatus Получение статуса заказа]
     * @param  [string] $id       [Идентификатор статуса]
     * @param  [boolen] $canceled [Флаг отмены]
     * @return [string]           [Статус]
     */
    private function getStatus($id, $canceled) {
    	$status = $canceled ? "CANCELED" : \CSaleStatus::GetByID($id)['ID'];
    	return $this->arStatuses[$status];
    }

    /*public static function onSaleOrderEntitySavedHandler(\Bitrix\Main\Event $event) {
        $order = $event->getParameter("ENTITY");

        $oldValues = $event->getParameter("VALUES");
        $arOrderVals = $order->getFields()->getValues();
     	if(array_key_exists('ID', $oldValues) && $oldValues["ID"] == "" && $arOrderVals['ID'] > 0){
     		return;
     	}
        if(array_key_exists('STATUS_ID', $oldValues) && array_key_exists('DATE_STATUS', $oldValues)) {
            //self::changeStatusOrder($order->getId(), $arOrderVals['STATUS_ID']);
        } elseif(array_key_exists('CANCELED', $oldValues) && array_key_exists('DATE_CANCELED', $oldValues)) {
            //self::cancelOrder($order->getId(), $arOrderVals['CANCELED'], $arOrderVals['REASON_CANCELED']);
        } elseif(array_key_exists('PAYED', $oldValues) && $oldValues['PAYED'] == "N" && array_key_exists('SUM_PAID', $oldValues) && array_key_exists('DATE_PAYED', $oldValues)) {
            //self::orderPayed($order->getId(), $arOrderVals['PAYED'], $arOrderVals['SUM_PAID'], $arOrderVals['DATE_PAYED']);
        } elseif(array_key_exists('PAYED', $oldValues) && $oldValues['PAYED'] == "Y" && array_key_exists('SUM_PAID', $oldValues)) {
            //self::cancelOrderPayed($order->getId(), $arOrderVals['PAYED'], $arOrderVals['SUM_PAID']);
        } else {
            //Debug::writeToFile($arOrderVals, "getValues");
            //Debug::writeToFile($oldValues, "oldValues");
        }
    }*/

    /*private static function cancelOrder($orderId, $value, $description = "") {
    	$data = array(
    		"ID" => $orderId,
    		"STATUS_ID" => $value == "Y" ? $this->arStatuses["CANCELED"] : $this->arStatuses[$value]
    	);
    	if($description){
			$data["DESCRIPTION"] = $description;
    	}
        Debug::writeToFile($data, 'cancelOrder');
    	self::send($data);
    }*/

    /*private static function orderPayed($orderId, $payed, $sum, $date) {
    	$data = array(
    		"ID" => $orderId,
    		"PAYED" => $payed,
    		"SUM" => $sum,
    		"DATE" => MakeTimeStamp($date)
    	);
        Debug::writeToFile($data, 'orderPayed');
        self::send($data);
    }*/

    /*private static function cancelOrderPayed($orderId, $payed, $sum) {
    	$data = array(
    		"ID" => $orderId,
    		"PAYED" => $payed,
    		"SUM" => $sum
    	);
        Debug::writeToFile($data, 'cancelOrderPayed');
        self::send($data);
    }*/

    /*private static function changeStatusOrder($orderId, $statusId) {
    	$data = array(
    		"ID" => $orderId, 
    		"STATUS_ID" => $this->arStatuses[$statusId]
    	);
        Debug::writeToFile($data, 'changeStatusOrder');
        if($this->arStatuses[$statusId]){
        	self::send($data);
    	}
    }*/
}
?>