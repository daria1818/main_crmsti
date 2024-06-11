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

class CCustomReportsComponent extends CBitrixComponent
{
	protected static $crmIncluded = null;
	protected static $saleIncluded = null;
	protected static $filterID = 'CCustomReportsComponent';
	private $aSort = [];
	protected static $arSpec = [];
	protected static $idFieldContact = 572;
	protected static $idFieldCompany = 416;

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
			'ORDER',
			'REGION'
		];

		$params['FILTER_FIELDS'] = [
			'DATE',
			'COMPANY_TYPE_LIST',
			'SPECIAL_LIST',
			'REGION',			
			'RESPONSIBLE_ID',
		];

		$params['ALLOWED_FIELDS'] = $params['DEFAULT_FIELDS'];

		return $params;
	}

	public function executeComponent()
	{
		if(!self::includeCrm())
			return;

		if($this->request->get('AJAX_CALL') == 'Y')
		{
			$this->initParametersFromRequest();
			return;
		}

		($this->arParams['COMPONENT_TEMPLATE'] == 'grid' ? $this->fieldsFormGrid() : $this->fieldsForm());

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

	protected function cleanedField($field){
		$field = str_ireplace(['область', 'обл', 'край', 'республика', ' г'], "", $field);
		return $this->mbUcfirst(mb_strtolower(trim($field)));
	}

	protected function mbUcfirst($string, $encoding = 'UTF-8'){
		$strlen = mb_strlen($string, $encoding);
		$firstChar = mb_substr($string, 0, 1, $encoding);
		$then = mb_substr($string, 1, $strlen - 1, $encoding);
		return mb_strtoupper($firstChar, $encoding) . $then;
	}

	protected function getSpecialization($idField){
		if(isset(self::$arSpec[$idField]))
			return self::$arSpec[$idField];

		$spec = [];
		$obEnum = new \CUserFieldEnum; 
		$rsEnum = $obEnum->GetList(array(), array("USER_FIELD_ID" => $idField)); 
		while($arEnum = $rsEnum->Fetch()){
			$spec[$arEnum['ID']] = $arEnum['VALUE'];
		} 

		self::$arSpec[$idField] = $spec;

		return $spec;
	}

	private function fieldsForm(){	    
	    $arResult['SELECT']['TIME'] = [
	    	'NAME' => 'Период',
	    	'VALUES' => [
	    		['NAME' => '1 месяц', 'CODE' => '1 month', 'SORT' => 10],
		    	['NAME' => '3 месяца', 'CODE' => '3 months', 'SORT' => 20],
		    	['NAME' => '6 месяцев', 'CODE' => '6 months', 'SORT' => 30],
		    	['NAME' => '1 год', 'CODE' => '1 year', 'SORT' => 40],
		    	['NAME' => 'N дней', 'CODE' => 'N days', 'SORT' => 50, 'INPUT' => 'number'],
		    	['NAME' => 'С даты', 'CODE' => 'DD.MM.YYYY', 'SORT' => 60, 'INPUT' => 'date']
		    ],
		    'REQUIRED' => 'Y'
	    ];

		$arResult['MULTI']['COMPANY_TYPE_LIST'] = [
			'NAME' => 'Тип клиента',
			'VALUES' => CCrmStatus::GetStatusListEx('COMPANY_TYPE'), //тип компании
			'REQUIRED' => 'N'
		];

		$obEnum = new \CUserFieldEnum; 
		$rsEnum = $obEnum->GetList(array(), array("USER_FIELD_ID" => 572)); 
		while($arEnum = $rsEnum->Fetch()){
			$spec[$arEnum['ID']] = $arEnum['VALUE'];
		} 

		$arResult['MULTI']['SPECIAL_LIST'] = [
			'NAME' => 'Специализация',
			'VALUES' => $spec,
			'REQUIRED' => 'Y'
		]; //специализация


		$connection = Application::getConnection();

		$sql = "SELECT * FROM `b_crm_addr` WHERE ENTITY_TYPE_ID IN (8) AND (CITY != '' OR PROVINCE != '')";
		$request = $connection->query($sql);

		$arResult['SEARCH'] = [
			'CITY' => [
				'NAME' => 'Город',
				'REQUIRED' => 'N'
			],
			// 'REGION' => [
			// 	'NAME' => 'Регион',
			// 	'REQUIRED' => 'N'
			// ]
		];

		$cities = [];

		while($result = $request->fetch())
		{
			if(!empty($result['CITY']))
				$cities[] = $this->cleanedField($result['CITY']); //регион

			if(!empty($result['PROVINCE']))
				$cities[] = $this->cleanedField($result['PROVINCE']); 
		}

		$arResult['SEARCH']['CITY']['VALUES'] = array_unique($cities);

		$dbUser = CUser::GetList(($by = "NAME"), ($order = "ASC"), ['ACTIVE' => 'Y', 'GROUPS_ID' => [16, 17, 18, 19]]);
		while($user = $dbUser->Fetch()){
			$user['NAME'] = trim($user['NAME'] . " " .  $user['LAST_NAME']);
			$arUser[] = $user;
		}
	    
	    $arResult['SEARCH']['USER'] = [
	    	'NAME' => 'Ответственный',
	    	'VALUES' => $arUser,
	    	'REQUIRED' => 'N'
	    ];

	    $this->arResult = $arResult;
	}

	private function fieldsFormGrid(){
		$this->arResult['FILTER_ID'] = self::$filterID;
		$this->arResult['GRID_ID'] = self::$filterID;
		$filters[] = [
			'id' => 'DATE', 'name' => 'Период', 'type' => 'date', 'exclude' => array(
				DateType::NONE,
				DateType::CURRENT_DAY,
				DateType::CURRENT_WEEK,
				DateType::CURRENT_MONTH,
				DateType::CURRENT_QUARTER,
				DateType::YESTERDAY,
				DateType::TOMORROW,
				DateType::PREV_DAYS,
				DateType::NEXT_DAYS,
				DateType::NEXT_WEEK,
				DateType::NEXT_MONTH,
				DateType::LAST_MONTH,
				DateType::LAST_WEEK,
				DateType::EXACT,
				DateType::QUARTER,
			)
		];

		$filters[] = [
			'id' => 'RESPONSIBLE_ID',
			'name' => 'Ответственный',
			'default' => true,
			'type' => 'dest_selector',
			'params' => array(
				'context' => 'CRM_WIDGET_FILTER_RESPONSIBLE_ID',
				'multiple' => 'N',
				'contextCode' => 'U',
				'enableAll' => 'N',
				'enableSonetgroups' => 'N',
				'allowEmailInvitation' => 'N',
				'allowSearchEmailUsers' => 'N',
				'departmentSelectDisable' => 'Y',
				'isNumeric' => 'Y',
				'prefix' => 'U',
			)
		];

		$filters[] = [
			'id' => 'COMPANY_TYPE_LIST', 'name' => 'Тип клиента', 'type' => 'list', 'params' => ['multiple' => 'Y'], 'items' => CCrmStatus::GetStatusListEx('COMPANY_TYPE')
		];

		$filters[] = [
			'id' => 'SPECIAL_LIST', 'name' => 'Специализация', 'type' => 'list', 'params' => ['multiple' => 'Y'], 'items' => $this->getSpecialization(self::$idFieldCompany)
		];

		$connection = Application::getConnection();

		$sql = "SELECT * FROM `b_crm_addr` WHERE ENTITY_TYPE_ID IN (".CCrmOwnerType::Requisite.") AND (CITY != '' OR PROVINCE != '')";
		$request = $connection->query($sql);
		$cities = [];
		while($result = $request->fetch())
		{
			if(!empty($result['CITY']))
				$cities[] = $this->cleanedField($result['CITY']); //регион

			if(!empty($result['PROVINCE']))
				$cities[] = $this->cleanedField($result['PROVINCE']); 
		}

		$cities_unique = array_unique($cities);

		sort($cities_unique);

		$filters[] = [
			'id' => 'REGION', 'name' => 'Регион', 'type' => 'list', 'items' => array_combine(array_values($cities_unique), array_values($cities_unique)),
		];

		$this->arResult['UI_FILTER'] = $filters;
		$this->initGrid();
	}

	private function initGrid(){
        $gridOptions = new GridOptions(self::$filterID);
        $pageNavigation = new PageNavigation(self::$filterID);
        $filterOptions = new FilterOptions(self::$filterID);

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
				case 'COMPANY_TYPE_LIST':
				case 'SPECIAL_LIST':
					$setFilter[$code] = $value;
					break;
				case 'DATE_from':
					$setFilter['DATE_START'] = $value;
					break;
				case 'DATE_to':
					$setFilter['DATE_END'] = $value;
					break;
				case 'RESPONSIBLE_ID':
					$setFilter['USER'] = $setFilter['USER_text'] = $value;
					break;
				case 'REGION':
					$setFilter['CITY'] = $setFilter['CITY_text'] = $value;
					break;
			}
		}

		return $setFilter;
	}

	private function getTHead(){
		$arTHead = [
			[
				'id' => 'CLIENT',
				'name' => 'Наименование клиента',
				'sort' => 'CLIENT',
				'default' => true
			],
			[
				'id' => 'ORDER',
				'name' => 'Дата и сумма последнего заказа',
				'sort' => 'ORDER',
				'default' => true
			],
			[
				'id' => 'REGION',
				'name' => 'Регион',
				'sort' => 'REGION',
				'default' => true
			],
		];

		return $arTHead;
	}

	private function initReport($params){
		$rows = $this->getRows($params);
		$result = [];
		foreach($rows ?: [] as $id => $row){
			$result[$id]['data'] = [
				'CLIENT' => $row['CLIENT'],
				'ORDER' => $row['END'],
				'REGION' => $row['ADDRESS']
			];
		}
		return $this->sortView($result);
	}

	private function initParametersFromRequest(){
		$values = $this->request->getPostList();
		echo self::sendJsonAnswer($this->getRows($values));
	}

	protected function getRows($values){
		if(!self::includeSale() || empty($values))
			return;
		if(empty($values['SPECIAL_LIST']) || empty($values['DATE_START']))
			return;

		$arCompanies = [];
		$arCompaniesIds = [];
		$arAddress = [];
		$arClients = [];
		$arDeals = [];
		$arResult = [];

		$arCompanyFilter = [
			'UF_CRM_1594114543075' => $values['SPECIAL_LIST'],
			'CHECK_PERMISSION' => 'N'
		];

		if(!empty($values['COMPANY_TYPE_LIST']))
			$arCompanyFilter['COMPANY_TYPE'] = $values['COMPANY_TYPE_LIST'];

		if(!empty($values['USER']) && !empty($values['USER_text']))
			$arCompanyFilter['ASSIGNED_BY_ID'] = $values['USER'];

		$rsCompany = CCrmCompany::GetListEx([], $arCompanyFilter, false, false, ['ID', 'TITLE', 'ASSIGNED_BY']);
		while($arComp = $rsCompany->GetNext()){
			$arCompaniesIds[] = $arComp['ID'];
			$arCompanies[$arComp['ID']]['NAME'] = $arComp['TITLE'];
			$arCompanies[$arComp['ID']]['ASSIGNED_BY'] = $arComp['ASSIGNED_BY'];
		}

		$arContactFilter = [
			'CHECK_PERMISSION' => 'N'			
		];

		if(!empty($values['COMPANY_TYPE_LIST']))
			$arContactFilter['COMPANY_ID'] = $arCompaniesIds;
		
		if(!empty($values['SPECIAL_LIST'])){
			$this->getSpecialization(self::$idFieldContact);
			$names = array_intersect_key(self::$arSpec[self::$idFieldCompany], array_flip($values['SPECIAL_LIST']));
			$ids = array_intersect(self::$arSpec[self::$idFieldContact], $names);
			$arContactFilter['UF_CRM_1615128120398'] = array_keys($ids);
		}

		if(!empty($values['USER']) && !empty($values['USER_text']))
			$arContactFilter['ASSIGNED_BY_ID'] = $values['USER'];

		$dbContact = CCrmContact::GetList(array(), $arContactFilter, array(), false);
		while($result = $dbContact->fetch()){
			$arClients[$result['ID']]['NAME'] = $result['FULL_NAME'];
			if(!empty($result['COMPANY_ID']))
				$arCompanies[$result['COMPANY_ID']]['CLIENT_ID'] = $result['ID'];
		}

		$addressFilter['ANCHOR_ID'] = array_keys($arCompanies);

		if(!empty($addressFilter['ANCHOR_ID']) || !empty($values['CITY']) && !empty($values['CITY_text'])){

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

			if(!empty($values['CITY']) && !empty($values['CITY_text'])){
				$tmp = $arCompanies;
				unset($arCompanies);
				$arCompanies = array_intersect_key($tmp, $arAddress);
				unset($tmp);
				$clientsID = array_column($arCompanies, 'CLIENT_ID');

				if(!empty($clientsID)){
					foreach($arClients ?: [] as $id){
						if(!in_array($id, $clientsID))
							unset($arClients[$id]);
					}
				}else{
					$arClients = [];
				}
			}
		}

		$date_start = new Bitrix\Main\Type\DateTime($values['DATE_START']);
		$date_end = new Bitrix\Main\Type\DateTime($values['DATE_END']);

		if(!empty($arCompanies) || !empty($arClients)){
			$merge = array_merge(array_keys($arClients), array_keys($arCompanies));

			$arCompaniesDuble = array_flip($merge);

			$connection = Application::getConnection();

			$sql = "SELECT ORDER_ID, ENTITY_ID FROM `b_crm_order_contact_company` WHERE ENTITY_ID IN (".implode(",", array_keys($arCompaniesDuble)).")";
			$request = $connection->query($sql);

			$arOrderIds = array_column($request->fetchAll(), "ENTITY_ID", "ORDER_ID");

			if(!empty($arOrderIds)){
				
				$res = Bitrix\Crm\Order\Order::getList(array('order' => array("DATE_INSERT" => "ASC"),'filter' => array('ID' => array_keys($arOrderIds))));
				while($order = $res->Fetch()){
					if(strtotime($order['DATE_INSERT']) >= strtotime($date_start) 
						&& strtotime($order['DATE_INSERT']) <= strtotime($date_end)
					)
						unset($arCompaniesDuble[$arOrderIds[$order['ID']]]);
					else{
						$item = [
							'ID' => '<a href="https://'.SITE_SERVER_NAME.'/shop/orders/details/'. $order['ID'] . '/">Заказ №' . $order['ID'] . "</a>",
	      					'DATE' => 'от ' . $order['DATE_INSERT']->format("d.m.Y H:i:s"),
	      					'SUM' => 'на сумму ' . $order['PRICE'] . " руб."
						];
	      				$arOrders[$arOrderIds[$order['ID']]] = $item;
					}
				}
			}			

			$arDealsFilter[] = [
				'LOGIC' => 'OR',
					array("CONTACT_ID" => array_keys($arClients)),
					array("COMPANY_ID" => array_keys($arCompanies))
			];

			$arDealsFilter["CHECK_PERMISSIONS"] = "N";

			$dbDeals = CCrmDeal::GetList(array("DATE_CREATE" => "ASC"), $arDealsFilter, array());
		
			if($dbDeals->SelectedRowsCount() > 0){
	      		while($result = $dbDeals->Fetch()){
	      			$key = (!empty($result['COMPANY_ID']) ? $result['COMPANY_ID'] : $result['CONTACT_ID']);

	      			if(strtotime($result['DATE_CREATE']) >= strtotime($date_start) 
						&& strtotime($result['DATE_CREATE']) <= strtotime($date_end)
					){
	      				unset($arCompaniesDuble[$result['CONTACT_ID']]);
	      				unset($arCompaniesDuble[$result['COMPANY_ID']]);
	      			}
	      			else{
	      				$item = [
							'ID' => '<a href="https://'.SITE_SERVER_NAME.'/crm/deal/details/'. $result['ID'] . '/">Сделка №' . $result['ID'] . "</a>",
	      					'DATE' => 'от ' . $result['DATE_CREATE'],
	      					'SUM' => 'на сумму ' . $result['OPPORTUNITY'] . " руб."
						];
	      				$arDeals[$key] = $item;
	      			}
	      		}
			}
		}

		if(!empty($arCompaniesDuble)){
			foreach($arCompaniesDuble as $id => $value){
				$item = [];

				$fields = (isset($arCompanies[$id]) ? $arCompanies[$id] : $arClients[$id]);
				$link = (isset($arCompanies[$id]) ? 'company' : 'contact');

				$item['END'] = "<div>" . implode("<br>", $arDeals[$id]) . "</div><div>" . implode("<br>", $arOrders[$id]) . "</div>";

				$item['CLIENT'] = "<a href='https://".SITE_SERVER_NAME."/crm/".$link."/details/".$id."/'>".$fields['NAME']."</a><br/>";
				$item['ADDRESS'] = $arAddress[$id];

				$arResult[$id] = $item;
			}
		}

		return $arResult;

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