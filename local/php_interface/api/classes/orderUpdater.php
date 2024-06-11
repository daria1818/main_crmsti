<?
namespace ApiFor1C\Order;
use \Bitrix\Main\Diag\Debug, \Bitrix\Sale\Order;

class OrderUpdater {
	private
		$messages = array(
        	"ORDER_NOT_FOUND" => "Заказ не найден!",
        	"STATUS_NOT_FOUND" => "Статус не найден!",
    	),
    	$arStatuses = array(
            //"Отменен" => "CANCELED", //старый вариант
            "Отменен" => "OC",
            "Доставлен" => "F",
            "Отправлен" => "OD",
            "Обработан" => "PR",
            "Оплачен" => "OP",
        	"Принят" => "N",
            "К отгрузке" => "ON",
    	);
	public
		$order,
		$result = array();

	public function __construct(int $orderId) {
		$this->order = Order::load($orderId);
		if($this->order === null) {
			$this->result["error"][] = $this->messages["ORDER_NOT_FOUND"];
			return;
		}
    }

    public function setStatus($status) {

    	if(!$this->arStatuses[$status]){
    		$this->result["error"][] = $this->messages["STATUS_NOT_FOUND"];
			return;
    	} else {
            $this->order->setField("STATUS_ID", $this->arStatuses[$status]);

            if($this->arStatuses[$status] == "OC"){
                //$this->order->isPaid(); // true, если все оплаты оплачены
                //$this->order->hasPaidPayment(); // true, если хотя бы одна оплата оплачена
                if($this->order->isPaid()) {
                    $paymentCollection = $this->order->getPaymentCollection();
                    foreach ($paymentCollection as $onePayment) {
                        $onePayment->setPaid("N"); // отмена оплаты
                        //$onePayment->setPaid("Y"); // оплата
                        //$onePayment->setReturn("Y"); // возврат (деньги возвращаются на внутренний счет или в платежную систему, если обработчик реализует интерфейс Sale\PaySystem\IRefund)
                    }
                }
                $this->order->setField("CANCELED", "Y");
            }
            if($this->arStatuses[$status] == "PR"){
                $this->order->setField("CANCELED", "N");
            }
        }

        /*$r = $this->order->setField('CANCELED', 'Y');
        if (!$r->isSuccess())
        {
            var_dump($r->getErrorMessages());
        }*/
    	$this->order->save();
    }

    public function getErrorMessages($returnArray = false) {
    	return $this->result["error"] ? ($returnArray ? $this->result["error"] : implode("\r\n", $this->result["error"])) : array();
    }
}
?>