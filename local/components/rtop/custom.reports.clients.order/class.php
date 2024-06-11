<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\UI\Filter\DateType;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Web;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Application;
use Bitrix\Main\UserTable;
use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\Agent\Requisite\ContactAddressConvertAgent;
use Bitrix\Crm\Agent\Requisite\ContactUfAddressConvertAgent;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\ContactAddress;
use Bitrix\Crm\Settings\HistorySettings;
use Bitrix\Crm\Settings\ContactSettings;
use Bitrix\Crm\WebForm\Manager as WebFormManager;
use Bitrix\Crm\Settings\LayoutSettings;

class CCustomReportsClientsOrderComponent extends CBitrixComponent
{
	protected static $crmIncluded = null;
	protected static $saleIncluded = null;
	protected static $IBLOCK_ID = 30;
	protected static $SKU_IBLOCK_ID = 81;
	protected static $filterID = 'CCustomReportsClientsOrderComponent';
	private $aSort = [];

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new ErrorCollection();
	}

	public function debug($array)
	{
		echo "<pre>";print_r($array);echo "</pre>";
	}

	public function onPrepareComponentParams($params)
	{	
		$params['AJAX_PATH'] = !empty($params['AJAX_PATH']) ? trim((string)$params['AJAX_PATH']) : $this->getPath().'/ajax.php';

		$params['DEFAULT_FIELDS'] = [
			'CLIENT',
			'QUANTITY_ORDERS',
			'SUM',
			'REGION',
			'RESPONSIBLE'
		];

		$params['FILTER_FIELDS'] = [
			'DATE',
			'BAR',
			'RESPONSIBLE_ID',
			'REGION',
			'PRODUCT_ID',
			'SECTION_ID',
		];

		$params['ALLOWED_FIELDS'] = $params['DEFAULT_FIELDS'];

		return $params;
	}

	public function executeComponent()
	{
		if(!self::includeCrm())
			return;

		$this->initGrid();

		if($this->startResultCache())
		{
			$this->IncludeComponentTemplate();
		}
		return $this->arResult;
	}

	protected static function includeCrm()
	{
		if (!isset(self::$crmIncluded))
		{
			self::$crmIncluded = Loader::includeModule('crm');
		}

		return self::$crmIncluded;
	}

	protected static function includeSale()
	{
		if (!isset(self::$saleIncluded))
		{
			self::$saleIncluded = Loader::includeModule('sale');
		}

		return self::$saleIncluded;
	}

	protected function getUserList(){
		$arUser = [];
		$dbUser = CUser::GetList(($by = "NAME"), ($order = "ASC"), ['ACTIVE' => 'Y', 'GROUPS_ID' => [16, 17, 18, 19]]);
		while($user = $dbUser->Fetch()){
			$user['NAME'] = trim($user['NAME'] . " " .  $user['LAST_NAME']);
			$arUser[$user['ID']] = $user;
		}
		return $arUser;
	}

	private function initGrid(){
		$this->arResult['FILTER_ID'] = self::$filterID;
		$this->arResult['GRID_ID'] = self::$filterID;

        $gridOptions = new GridOptions(self::$filterID);
        $pageNavigation = new PageNavigation(self::$filterID);
        $filterOptions = new FilterOptions(self::$filterID);

        $filters = [];

		$filterFlags = Crm\Filter\ContactSettings::FLAG_NONE;

		$entityFilter = Crm\Filter\Factory::createEntityFilter(
			new Crm\Filter\ContactSettings(
				array('ID' => $this->arResult['GRID_ID'], 'flags' => $filterFlags)
			)
		);
		$effectiveFilterFieldIDs = $filterOptions->getUsedFields();
		if(empty($effectiveFilterFieldIDs))
		{
			$effectiveFilterFieldIDs = $entityFilter->getDefaultFieldIDs();
		}

		if(!in_array('ASSIGNED_BY_ID', $effectiveFilterFieldIDs, true))
		{
			$effectiveFilterFieldIDs[] = 'ASSIGNED_BY_ID';
		}

		if(!in_array('ACTIVITY_COUNTER', $effectiveFilterFieldIDs, true))
		{
			$effectiveFilterFieldIDs[] = 'ACTIVITY_COUNTER';
		}

		if(!in_array('WEBFORM_ID', $effectiveFilterFieldIDs, true))
		{
			$effectiveFilterFieldIDs[] = 'WEBFORM_ID';
		}

		Tracking\UI\Filter::appendEffectiveFields($effectiveFilterFieldIDs);

		foreach($effectiveFilterFieldIDs as $filterFieldID)
		{
			$filterField = $entityFilter->getField($filterFieldID);
			if($filterField)
			{
				$filters[] = $filterField->toArray();
			}
		}		

		$this->arResult['UI_FILTER'] = $filters;

        if (isset($this->arParams['GRID_COLUMNS'])) {
            $gridOptions->SetVisibleColumns($this->arParams['GRID_COLUMNS']);
        }
        if (empty($gridOptions->GetVisibleColumns())) {
            $gridOptions->SetVisibleColumns($this->arParams['DEFAULT_FIELDS']);
        }

        $this->aSort = $gridOptions->GetSorting(array("sort" => array("CLIENT" => "asc"), "vars" => array("by" => "by", "order" => "order")));

        $this->arResult['GRID_OPTIONS'] = $gridOptions;
        $this->arResult['FILTER_OPTIONS'] = $filterOptions;
        $this->arResult['NAV_OBJECT'] = $pageNavigation;
        $this->arResult['COLUMNS'] = $this->getTHead();
        $this->arResult['ROWS'] = $this->initReport($this->getFilter());
	}

	private function getFilter(){
		$filters = $this->arResult['FILTER_OPTIONS']->getFilter();

		$setFilter = [];

		foreach($filters as $code => $value){
			if(empty($value))
				continue;
			switch($code){
				case 'BAR':
				case 'SECTION_ID':
					$setFilter[$code] = $value;
					break;
				case 'DATE_from':
					$setFilter['DATE_START'] = $value;
					break;
				case 'DATE_to':
					$setFilter['DATE_END'] = $value;
					break;
				case 'PRODUCT_ID':
					$setFilter['PRODUCT_ID'] = explode(";", $value);
					break;
				case 'RESPONSIBLE_ID':
					$setFilter['USER'] = $value;
					break;
				case 'REGION':
					$setFilter['CITY'] = $setFilter['CITY_text'] = $value;
					break;
			}
		}

		if(!empty($setFilter['PRODUCT_ID']))
			$setFilter['PRODUCT_ID'] = array_diff($setFilter['PRODUCT_ID'], [0, null]);

		return $setFilter;
	}

	private function getTHead(){
		$arTHead = array(
			array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'id', 'first_order' => 'desc', 'width' => 60, 'editable' => false, 'type' => 'int', 'class' => 'minimal'),
			array('id' => 'CONTACT_SUMMARY', 'name' => GetMessage('CRM_COLUMN_CONTACT'), 'sort' => 'full_name', 'width' => 200, 'default' => true, 'editable' => false),
		);

		$arTHead = array_merge(
			$arTHead,
			array(
				array('id' => 'CONTACT_COMPANY', 'name' => GetMessage('CRM_COLUMN_CONTACT_COMPANY_INFO'), 'sort' => 'company_title', 'editable' => false),
				array('id' => 'PHOTO', 'name' => GetMessage('CRM_COLUMN_PHOTO'), 'sort' => false, 'editable' => false),
				array(
					'id' => 'HONORIFIC',
					'name' => GetMessage('CRM_COLUMN_HONORIFIC'),
					'sort' => false,
					'type' => 'list',
					'editable' => array(
						'items' => array('0' => GetMessage('CRM_HONORIFIC_NOT_SELECTED')) + CCrmStatus::GetStatusList('HONORIFIC')
					)
				),
				array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME'), 'sort' => 'name', 'editable' => true, 'class' => 'username'),
				array('id' => 'LAST_NAME', 'name' => GetMessage('CRM_COLUMN_LAST_NAME'), 'sort' => 'last_name', 'editable' => true, 'class' => 'username'),
				array('id' => 'SECOND_NAME', 'name' => GetMessage('CRM_COLUMN_SECOND_NAME'), 'sort' => 'second_name', 'editable' => true, 'class' => 'username'),
				array('id' => 'BIRTHDATE', 'name' => GetMessage('CRM_COLUMN_BIRTHDATE'), 'sort' => 'BIRTHDATE', 'first_order' => 'desc', 'type' => 'date', 'editable' => true),
				array('id' => 'POST', 'name' => GetMessage('CRM_COLUMN_POST'), 'sort' => 'post', 'editable' => true),
				array('id' => 'COMPANY_ID', 'name' => GetMessage('CRM_COLUMN_COMPANY_ID'), 'sort' => 'company_title', 'editable' => false),
				array('id' => 'TYPE_ID', 'name' => GetMessage('CRM_COLUMN_TYPE'), 'sort' => 'type_id', 'type' => 'list', 'editable' => array('items' => CCrmStatus::GetStatusList('CONTACT_TYPE'))),
				array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'sort' => 'assigned_by', 'default' => true, 'editable' => false, 'class' => 'username')
			)
		);

		$arTHead = array_merge(
			$arTHead,
			array(
				array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS'), 'sort' => false /**because of MSSQL**/, 'editable' => false),
				array('id' => 'SOURCE_ID', 'name' => GetMessage('CRM_COLUMN_SOURCE'), 'sort' => 'source_id', 'type' => 'list', 'editable' => array('items' => CCrmStatus::GetStatusList('SOURCE'))),
				array('id' => 'SOURCE_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_SOURCE_DESCRIPTION'), 'sort' => false /**because of MSSQL**/, 'editable' => false),
				array('id' => 'EXPORT', 'name' => GetMessage('CRM_COLUMN_EXPORT_NEW'), 'type' => 'checkbox', 'type' => 'checkbox', 'editable' => true),
				array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_CREATED_BY'), 'sort' => 'created_by', 'editable' => false, 'class' => 'username'),
				array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'sort' => 'date_create', 'first_order' => 'desc', 'default' => true, 'class' => 'date'),
				array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_MODIFY_BY'), 'sort' => 'modify_by', 'editable' => false, 'class' => 'username'),
				array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_DATE_MODIFY'), 'sort' => 'date_modify', 'first_order' => 'desc', 'class' => 'date'),
				array('id' => 'WEBFORM_ID', 'name' => GetMessage('CRM_COLUMN_WEBFORM'), 'sort' => 'webform_id', 'type' => 'list')
			)
		);

		return $arTHead;
	}

	private function initReport($params){
		$rows = $this->getRows($params);
		$result = [];
		foreach($rows ?: [] as $id => $row){
			$result[$id]['data'] = [
				'CLIENT' => $row['INFO'],
				'QUANTITY_ORDERS' => $row['QUANTITY'],
				'SUM' => $row['SUM'],
				'REGION' => $row['ADDRESS'],
				'RESPONSIBLE' => $row['RESPONSIBLE'],
			];
		}
		return $this->sortView($result);
	}

	private function getRows($values)
	{
		if(!self::includeSale() || empty($values))
			return;

		if(Loader::includeModule('iblock')){

			$arResult = [];
			$arProducts = [];
			$arOrders = [];
			$arContacts = [];
			$arAddress = [];
			$arPrices = [];
			$arMix = [];

			$arElementFilter = [
				'IBLOCK_ID' => self::$IBLOCK_ID,
				'PROPERTY_BAR' => $values['BAR'],
			];

			if(!empty($values['PRODUCT_ID'])){
				$arElementFilter['ID'] = $values['PRODUCT_ID'];

				$arOffers = CCatalogSku::getProductList($values['PRODUCT_ID'], self::$SKU_IBLOCK_ID);
				if(!empty($arOffers)){
					$skus = array_flip(array_combine(array_keys($arOffers), array_column($arOffers, "ID")));
					$arElementFilter['ID'] = array_merge($arElementFilter['ID'], array_keys($skus));
				}				
			}

			if(!empty($values['SECTION_ID'])){
				$arElementFilter['SECTION_ID'] = $values['SECTION_ID'];
				$arElementFilter['INCLUDE_SUBSECTIONS'] = "Y";
			}

			if(!empty($values['BAR']) || !empty($values['PRODUCT_ID']) || !empty($values['SECTION_ID'])){
				$res = CIBLockElement::GetList(array(), $arElementFilter, false, false, array('ID'));
				while($result = $res->GetNext()){
					$arProducts[] = (isset($skus[$result['ID']]) ? $skus[$result['ID']] : $result['ID']);
				}
			}

			$date_start = new Bitrix\Main\Type\DateTime($values['DATE_START']);
			$date_end = new Bitrix\Main\Type\DateTime($values['DATE_END']);

			$arBasketFilter = [
				"!ORDER_ID" => false,
				'>=BASKET_ORDER_DATE_STATUS' => $date_start,
				'<=BASKET_ORDER_DATE_STATUS' => $date_end,
				'BASKET_ORDER_STATUS_ID' => 'F',
				'BASKET_ORDER_CANCELED' => 'N',
			];

			if(!empty($arProducts)){
				$arBasketFilter['PRODUCT_ID'] = $arProducts;
			}

			$res = \Bitrix\Sale\Internals\BasketTable::getList([
				'filter' => $arBasketFilter,
				'select' => ['BASKET_ORDER_' => 'BASKET_ORDER', 'CONTACT_COMPANY_' => 'CONTACT_COMPANY', 'PRODUCT_ID', 'ORDER_ID', 'PRICE', 'QUANTITY'],
				'runtime' => [
					'BASKET_ORDER' =>  [
	                    'data_type' => '\Bitrix\Sale\Order',
	                    'reference' => [
	                        '=this.ORDER_ID' => 'ref.ID',
	                    ],
	                ],
	                'CONTACT_COMPANY' => [
	                    'data_type' => '\Bitrix\Crm\Binding\OrderContactCompanyTable',
	                    'reference' => [
	                        '=this.ORDER_ID' => 'ref.ORDER_ID',
	                    ],
	                ]
	            ]
			]);
			while($basket = $res->Fetch())
			{
				$arPrices[$basket['ORDER_ID']] += $basket['PRICE']*$basket['QUANTITY'];
				$arOrders[$basket['ORDER_ID']]['RESPONSIBLE']['ID'] = $basket['BASKET_ORDER_RESPONSIBLE_ID'];

				$arMix[$basket['CONTACT_COMPANY_ENTITY_TYPE_ID']][$basket['CONTACT_COMPANY_ENTITY_ID']][] = $basket['ORDER_ID'];
			}
			
			if(!empty($arMix[CCrmOwnerType::Company])){
				
				$addressFilter['ANCHOR_ID'] = array_keys($arMix[CCrmOwnerType::Company]);

				if(!empty($values['CITY']) && !empty($values['CITY_text'])){
					$addressFilter[] = [
						'LOGIC' => 'OR',
							array('CITY' => $values['CITY']),
							array('PROVINCE' => $values['CITY'])
					];	
				}

				$address = new \Bitrix\Crm\EntityAddress();
				$dbRes   = $address->getList(array(
				    'filter' => $addressFilter
				));
				while($arAddr = $dbRes->Fetch()) {
					$item = [];

					$item['CITY'] = trim(!empty($arAddr['CITY']) ? $arAddr['CITY'] : $arAddr['PROVINCE']);
					$item['ADDRESS_1'] = trim($arAddr['ADDRESS_1']);
					$item['ADDRESS_2'] = trim($arAddr['ADDRESS_2']);

					$arAddress[$arAddr['ANCHOR_ID']] = implode(", ", array_diff($item, array(0, null)));
				}

			}

			$arList = [];

			if(!empty($arMix[CCrmOwnerType::Company])){
				$arCompanyFilter = [
					'ID' => array_keys($arMix[CCrmOwnerType::Company]),
					'CHECK_PERMISSION' => 'N'
				];

				if(!empty($values['USER'])){
					$arCompanyFilter['ASSIGNED_BY_ID'] = $values['USER'];
				}

				if(!empty($arAddress) && !empty($values['CITY']) && !empty($values['CITY_text']))
					$arCompanyFilter['ID'] = array_keys($arAddress);

				$rsCompany = CCrmCompany::GetListEx([], $arCompanyFilter, false, false, ['ID', 'TITLE', 'ASSIGNED_BY']);
				while($result = $rsCompany->Fetch()){
					if(isset($arMix[CCrmOwnerType::Company])){
						$item = [
							'ORDERS' => $arMix[CCrmOwnerType::Company][$result['ID']],
							'NAME' => $result['TITLE'],
							'ADDRESS' => $arAddress[$result['ID']],
							'ASSIGNED_BY' => $result['ASSIGNED_BY']
						];

						$arList[CCrmOwnerType::Company][$result['ID']] = $item;
					}
				}
			}

			if(!empty($arMix[CCrmOwnerType::Contact])){

				$arContactFilter = [
					'ID' => array_keys($arMix[CCrmOwnerType::Contact]),
					'CHECK_PERMISSION' => 'N'
				];

				if(!empty($values['USER'])){
					$arContactFilter['ASSIGNED_BY_ID'] = $values['USER'];
				}

				if(!empty($arAddress) && !empty($values['CITY']) && !empty($values['CITY_text']))
					$arContactFilter['COMPANY_ID'] = array_keys($arAddress);

				$arListCompanyKeys = array_keys($arList[CCrmOwnerType::Company]);				

				$dbContact = CCrmContact::GetListEx(['ID' => 'ASC'], $arContactFilter, false, false, ['ID', 'FULL_NAME', 'COMPANY_ID', 'ASSIGNED_BY']);
				while($result = $dbContact->fetch()){
					$result['COMPANY_IDS'] = Bitrix\Crm\Binding\ContactCompanyTable::getContactCompanyIDs($result['ID']);
					if(isset($arMix[CCrmOwnerType::Contact]) && 
						(empty($result['COMPANY_IDS']) || empty(array_intersect($result['COMPANY_IDS'], $arListCompanyKeys)))
					){
						$item = [
							'ORDERS' => $arMix[CCrmOwnerType::Contact][$result['ID']],
							'NAME' => $result['FULL_NAME'],
							'ADDRESS' => $arAddress[$result['COMPANY_ID']],
							'ASSIGNED_BY' => $result['ASSIGNED_BY']
						];

						$arList[CCrmOwnerType::Contact][$result['ID']] = $item;
					}
				}
			}

			if(empty($arList))
				return $arResult;			

			$arUsers = $this->getUserList();

			foreach($arList as $type => $list){
				$code = ($type == CCrmOwnerType::Company ? 'company' : 'contact');
				foreach($list as $ID => $FIELDS){
					$orders = array_intersect_key($arPrices, array_flip($FIELDS['ORDERS']));
					$assigned = $arUsers[$FIELDS['ASSIGNED_BY']];
					$item = [
						'INFO' => "<a href='https://".SITE_SERVER_NAME."/crm/".$code."/details/".$ID."/'>".$FIELDS['NAME']."</a><br/>",
						'QUANTITY' => count($FIELDS['ORDERS']),
						'SUM' => array_sum($orders),
						'RESPONSIBLE' => "<a href='https://".SITE_SERVER_NAME."/company/personal/user/".$assigned['ID']."/'>".$assigned['NAME']."</a><br/>",
						'ADDRESS' => $FIELDS['ADDRESS']
					];
					$arResult[]	= $item;			
				}
			}

			return $arResult;

			// foreach($arOrders as $ID => $FIELDS){
			// 	$key = (isset($FIELDS['COMPANY']) ? $FIELDS['COMPANY'] : $FIELDS['CLIENT']);
			// 	$code = (isset($FIELDS['COMPANY']) ? 'company' : 'contact');

			// 	if(empty($key['ADDRESS']) && !empty($values['CITY']) && !empty($values['CITY_text']))
			// 		continue;

			// 	if($key['NAME'] == "")
			// 		$arResult[$key['ID']]['INFO'] = "<a href='https://".SITE_SERVER_NAME."/shop/orders/details/".$ID."/'>Заказ № ".$ID."</a><br/>";
			// 	else
			// 		$arResult[$key['ID']]['INFO'] = "<a href='https://".SITE_SERVER_NAME."/crm/".$code."/details/".$key['ID']."/'>".$key['NAME']."</a><br/>";

			// 	$arResult[$key['ID']]['QUANTITY']++;
			// 	$arResult[$key['ID']]['SUM'] += $arPrices[$ID];

			// 	if(!isset($tmp_responsible[$key['ID']][$FIELDS['RESPONSIBLE']['ID']])){
			// 		$arResult[$key['ID']]['RESPONSIBLE'] .= "<a href='https://".SITE_SERVER_NAME."/company/personal/user/".$FIELDS['RESPONSIBLE']['ID']."/'>".$FIELDS['RESPONSIBLE']['NAME']."</a><br/>";
			// 		$tmp_responsible[$key['ID']][$FIELDS['RESPONSIBLE']['ID']] = "Y";
			// 	}
			// 	if(isset($key['ADDRESS']))
			// 		$arResult[$key['ID']]['ADDRESS'] = $key['ADDRESS'];
			// }

			// return $arResult;
		}
	}

	private function sortView($result)
	{
		$sort = $this->aSort;
		usort($result, function ($a, $b) use ($sort) {
			$field = array_key_first($sort['sort']);
			$value_a = strip_tags($a['data'][$field]);
			$value_b = strip_tags($b['data'][$field]);
			if($value_a == $value_b)
				return false;
			if(current($sort['sort']) == 'asc')
				return ($value_a < $value_b ? -1 : 1);
			else
				return ($value_a > $value_b ? -1 : 1);
		});

		return $result;
	}

	public static function sendJsonAnswer($result)
	{
		global $APPLICATION;

		$APPLICATION->RestartBuffer();
		header('Content-Type: application/json');

		echo \Bitrix\Main\Web\Json::encode($result);

		CMain::FinalActions();
		die();
	}
}
?>