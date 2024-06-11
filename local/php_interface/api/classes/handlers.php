<?
namespace ApiFor1C;
/**
 * Класс для описания событий (хэндлеров)
 */
class Handlers {
	/**
	 * [init Инициализация хэндлеров]
	 */
    public static function init() {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
       	$eventManager->addEventHandler("sale", "OnSaleOrderSaved", array("\\ApiFor1C\\BuyersAndCounterparties", "OnSaleOrderSaved"));
       	$eventManager->addEventHandler("sale", "OnSaleOrderSaved", array("\\ApiFor1C\\OrderSender", "onSaleOrderSavedHandler"));
    }
}
?>