<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Catalog\ProductTable;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Binding\OrderDealTable;
use Bitrix\Main\Context;
use Bitrix\Sale\Delivery;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Basket;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\ContactTable;
use Bitrix\Crm\CompanyTable;
use Bitrix\Catalog;
use Rtop\KPI\Logger as Log;
use Rtop\KPI\BalanceTable;
use Rtop\KPI\Main as RtopMain;
use Pwd\Tools\Logger;

class CIframeCreateOrder extends CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
	protected $leadFields = [];

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new ErrorCollection();
		//Logger::$PATH = __DIR__ .'/logs/';
		//$logger = Logger::getLogger('create_order1', 'create_order1');
		//$logger->log("");
	}

	public function onPrepareComponentParams($params)
	{
		$params['SITE_ID'] = Context::getCurrent()->getSite();
		return $params;
	}

	protected function listKeysSignedParameters()
	{
		return ['ENTITY_ID', 'SITE_ID', 'ENTITY_DATA', 'PERSON_TYPE_ID'];
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	public function configureActions()
	{
		return [];
	}

	protected function showErrors()
	{
		foreach ($this->getErrors() as $error)
		{
			ShowError($error);
		}
	}

	public function executeComponent()
	{
		if (!$this->loaderModules())
		{
			$this->showErrors();
			return;
		}
		$this->initialLoadAction();
	}

	protected function initialLoadAction()
	{
		$this->arResult = $this->arParams;
		$this->arResult['PERSON_TYPE_ID'] = $this->resolvePersonTypeID($this->arResult['ENTITY_DATA']);

		$stageRes = CCrmDeal::GetList([], ['ID' => $this->arResult['ENTITY_ID']], ['ID', 'STAGE_ID']);
		if($stage = $stageRes->fetch()){
			if($stage['STAGE_ID'] != 'EXECUTING'){
				ShowError(Loc::getMessage('IFRAME_CREATE_ORDER_ERROR_STAGE'));
				return;
			}
		}

		$products = CCrmDeal::LoadProductRows($this->arResult['ENTITY_ID']);
		$productIds = array_column($products, 'PRODUCT_ID', 'PRODUCT_ID');
		if(!empty($products))
		{
			$productList = ProductTable::getList(['filter' => ['ID' => $productIds], 'select' => ['ID', 'QUANTITY']])->fetchAll();
			foreach($productList as $item)
			{
				$productIds[$item['ID']] = ['QUANTITY' => $item['QUANTITY']];
			}
		}

		$this->arResult['PRODUCTS'] = $products;
		$this->arResult['PRODUCTS_AVAILABLE'] = $productIds;

		$this->getParamsBuyer();
		$this->leadFields = $this->getFields();

		if ($this->errorCollection->isEmpty())
		{			
			$this->arResult['FIELDS'] = $this->leadFields;
		}	

		$this->includeComponentTemplate();
	}

	protected function getFields()
	{
		return [
			[
				'ID' => 'RESPONSIBLE_ID', 
				'NAME' => Loc::getMessage('IFRAME_CREATE_ORDER_FIELD_RESPONSLIBLE_ID'),
				'TYPE' => 'user_selector', 
				'REQUIRED' => true,
				'PARAMS' => [
					'LIST' => [],
					'SELECTOR_OPTIONS' => [
						'lazyLoad' => 'Y',
						'context' => 'U',
						'disableLast' => 'Y',
						'contextCode' => '',
						'departmentSelectDisable' => 'Y',
						'userSearchArea' => 'I',
						'allowUserSearch' => 'N',
					]
				]
			],
			[
				'ID' => 'EMAIL',
				'NAME' => Loc::getMessage('IFRAME_CREATE_ORDER_FIELD_EMAIL'),
				'TYPE' => 'text',
				'REQUIRED' => true,
				'VALUE' => $this->arResult['BUYER_EMAIL']
			],
			[
				'ID' => 'PHONE',
				'NAME' => Loc::getMessage('IFRAME_CREATE_ORDER_FIELD_PHONE'),
				'TYPE' => 'text',
				'REQUIRED' => true,
				'VALUE' => $this->arResult['BUYER_PHONE']
			],
		];
	}

	protected function getParamsBuyer()
	{
		$select = ['ID', 'EMAIL_HOME', 'EMAIL_WORK', 'EMAIL_MAILING', 'PHONE_MOBILE', 'PHONE_WORK', 'PHONE_MAILING', 'EMAIL', 'PHONE'];
		if(!empty($this->arParams['ENTITY_DATA']['CLIENT_INFO']['COMPANY_DATA']))
		{
			$res = CompanyTable::getList(['filter' => ['ID' => reset($this->arParams['ENTITY_DATA']['CLIENT_INFO']['COMPANY_DATA'])['id']], 'select' => $select])->fetch();
		}
		else
		{
			$res = ContactTable::getList(['filter' => ['ID' => reset($this->arParams['ENTITY_DATA']['CLIENT_INFO']['CONTACT_DATA'])['id']], 'select' => $select])->fetch();
		}
		$this->arResult['BUYER_EMAIL'] = $res['EMAIL'] ?? ($res['EMAIL_WORK'] ?? ($res['EMAIL_HOME'] ?? $res['EMAIL_MAILING']));
		$this->arResult['BUYER_PHONE'] = $res['PHONE'] ?? ($res['PHONE_WORK'] ?? ($res['PHONE_HOME'] ?? $res['PHONE_MAILING']));
	}

	protected function loaderModules()
	{
		$arModules = ['crm', 'catalog', 'sale', 'rtop.kpi'];
		foreach ($arModules as $module) {
			if (!Loader::includeModule($module)) {
				return false;
			}
		}
		return true;
	}

	protected function resolvePersonTypeID($entityData)
	{
		$companyID = isset($entityData['COMPANY_ID']) ? (int)$entityData['COMPANY_ID'] : 0;
		if($companyID > 0)
		{
			$presets = array_column($entityData['CLIENT_INFO']['COMPANY_DATA'][0]['advancedInfo']['requisiteData'], 'presetId');
			if(in_array('1', $presets))
				$personTypeID = '2';
			if(in_array('2', $presets))
				$personTypeID = '5';
		}
		else{
			$personTypeID = '1';
		}
		if(empty($personTypeID))
			$personTypeID = '2';

		return $personTypeID;
	}

	public function saveOrderAjaxAction()
	{
		$response = [];

		if (!$this->loaderModules())
		{
			return $response;
		}

		$data = $this->request->get('data') ?: [];
		$orderId = $this->saveOrder($data);

		if($this->errorCollection->isEmpty() && !empty($orderId))
		{
			$response['redirectUrl'] = '/shop/orders/details/'.$orderId.'/';
		}

		return $response;
	}

	protected function saveOrder($data)
	{
		foreach($this->getFields() as $field)
		{
			if($field['REQUIRED'] && $data[$field['ID']] == ""){
				$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('IFRAME_CREATE_ORDER_ERROR_EMPTY', ['#FIELD#' => $field['NAME']]));
			}
		}

		if(empty($data['RESPONSIBLE_ID']))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('IFRAME_CREATE_ORDER_ERROR_RESPONSLIBLE'));
		}

		if(empty($data['PRODUCTS']))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('IFRAME_CREATE_ORDER_ERROR_PRODUCTS'));
		}

		if (!$this->errorCollection->isEmpty())
			return;

		global $USER;
		$full_price = 0;
		$order = Order::create($this->arParams['SITE_ID'], 4072);
		$order->setPersonTypeId($data['PERSON_TYPE_ID']);
		$order->setField('CURRENCY', $this->arParams['ENTITY_DATA']['CURRENCY_ID']);

		$shipmentCollection = $order->getShipmentCollection();
		$shipment = $shipmentCollection->createItem();
		$service = Delivery\Services\Manager::getById(Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId());
		$shipment->setFields(array(
			'DELIVERY_ID' => $service['ID'],
			'DELIVERY_NAME' => $service['NAME'],
		));
		$shipmentItemCollection = $shipment->getShipmentItemCollection();

		$basket = Basket::create($this->arParams['SITE_ID']);
		foreach($data['PRODUCTS'] as $id => $product)
		{
			$item = $basket->createItem('catalog', $id);
			$item->setFields(array(
				'QUANTITY' => $product['QUANTITY'],
				'CURRENCY' => $this->arParams['ENTITY_DATA']['CURRENCY_ID'],
				'LID' => $this->arParams['SITE_ID'],
				'PRODUCT_PROVIDER_CLASS' => '\Bitrix\Catalog\Product\CatalogProvider',
				'PRICE' => $product['PRICE'],
				'CUSTOM_PRICE' => "Y"
			));

			$shipmentItem = $shipmentItemCollection->createItem($item);
			$shipmentItem->setQuantity($item->getQuantity());
			$full_price += $product['PRICE']*$product['QUANTITY'];
		}

		$order->setBasket($basket);

		$paymentCollection = $order->getPaymentCollection();
		$payment = $paymentCollection->createItem();
		$paySystemService = PaySystem\Manager::getObjectById(9);
		$payment->setFields(array(
			'PAY_SYSTEM_ID' => $paySystemService->getField("PAY_SYSTEM_ID"),
			'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME"),
			'SUM' => $full_price
		));

		$order->setField('RESPONSIBLE_ID', $this->getResponsibleId($data['RESPONSIBLE_ID']));
		$order->setField('PRICE', $full_price);

		$clientCollection = $order->getContactCompanyCollection();
		$clientCollection->clearCollection();
		foreach($this->arParams['ENTITY_DATA']['CLIENT_INFO']['COMPANY_DATA'] ?: [] as $company)
		{
			$item = $clientCollection->createCompany();
			$item->setFields([
				'ENTITY_ID' => $company['id'],
				'IS_PRIMARY' => 'Y'
			]);
		}
		foreach($this->arParams['ENTITY_DATA']['CLIENT_INFO']['CONTACT_DATA'] ?: [] as $contact)
		{
			$item = $clientCollection->createContact();
			$item->setFields([
				'ENTITY_ID' => $contact['id'],
				'IS_PRIMARY' => 'Y'
			]);
		}

		$propertyCollection = $order->getPropertyCollection();
		$emailProp = $propertyCollection->getUserEmail();
		$emailProp->setValue($data['EMAIL']);
		$phoneProp = $propertyCollection->getPhone();
		$phoneProp->setValue($data['PHONE']);

		/*
		1. Проверяем пользователя на принадлежность к группе Бренд-менеджеры
		2. Если так, то добавляем пометку в свойство заказа, что заказ был сформирован из сделки.
		*/
		$ASSIGNED_BY_ID = CCrmDeal::GetByID($data['DEAL_ID'])['ASSIGNED_BY_ID'];
		$usrDepartment = BalanceTable::getList(
			['filter' => ['USERID' => $ASSIGNED_BY_ID], 'select' => ['DEPARTMENT']]
		)->fetch();
		if($usrDepartment['DEPARTMENT'] == '8028')
		{
			foreach($propertyCollection as $popertyObj)
			{
				if($popertyObj->getField('CODE') == "SOURCE_ORDER") $popertyObj->setValue("DEAL_CRM");
				if($popertyObj->getField('CODE') == "BMUID") $popertyObj->setValue($ASSIGNED_BY_ID);
			}
		}
		//Logger::$PATH = __DIR__ .'/logs/';
		//$logger = Logger::getLogger('create_order', 'create_order');
		//$logger->log($data);


		$order->doFinalAction(true);
		$result = $order->save();

		if($result->isSuccess())
        {
			$orderId = $order->getId();
			if($usrDepartment['DEPARTMENT'] == '8028')
			{
				$client = [];
				$communications = $order->getContactCompanyCollection();
				$companies = $communications->getCompanies();
				foreach ($companies ?:[] as $value)
				{
					$client = ['ID' => $value->getField('ENTITY_ID'), 'TYPE' => 'Company'];
					break;
				}
				if(empty($client))
				{			
					$contacts = $communications->getContacts();
					foreach ($contacts ?:[] as $value)
					{
						$client = ['ID' => $value->getField('ENTITY_ID'), 'TYPE' => 'Contact'];
						break;
					}
				}
				$client['ID'] = $client['ID'] ? $client['ID'] : "";
				$client['TYPE'] = $client['TYPE'] ? $client['TYPE'] : "";
				RtopMain::addBonus("setBonusCreateOrderOfDeal", $USER->GetID(), $client);
			}
		}
		else
		{
			$this->errorCollection[] = new \Bitrix\Main\Error($result->getErrorMessages());
			return;
		}

		if($orderId > 0)
		{
			//OrderDealTable::add(['DEAL_ID' => $this->arParams['ENTITY_ID'], 'ORDER_ID' => $orderId]);
			$deal = new \CCrmDeal(false);
			$entityId = $this->arParams['ENTITY_ID'];
			$arFields = ['CLOSED' => 'Y', 'STAGE_ID' => 'WON'];
			$deal->Update($entityId, $arFields, true, true, ['CURRENT_USER' => $USER->GetID(), 'DISABLE_USER_FIELD_CHECK' => true]);

			return $orderId;
		}
	}

	protected function getResponsibleId($value)
	{
		return (is_numeric($value) ? $value : preg_replace("/[A-Z]+(\d+)/", "$1", $value));
	}
}