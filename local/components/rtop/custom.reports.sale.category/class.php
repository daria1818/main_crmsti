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

class CCustomReportsSaleComponent extends CBitrixComponent
{
	protected static $crmIncluded = null;
	protected static $saleIncluded = null;
	protected static $IBLOCK_ID = 30;
	protected static $SKU_IBLOCK_ID = 81;
	protected static $filterID = 'CCustomReportsSaleComponent';
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
			'PRODUCT',
			'QUANTITY_ORDERS',
			'SUM',
			'REGION',
			'RESPONSIBLE'
		];

		$params['FILTER_FIELDS'] = [
			'DATE',
			'BAR',
			'PRODUCT_CATEGORY',
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

	protected function getPropertyList($code, $required, $custom_name = false)
	{
		$result = array();
		$res = CIBlockProperty::GetList(array("sort"=>"asc"), array("IBLOCK_ID" => self::$IBLOCK_ID, "CODE" => $code));
		if($prop_fields = $res->fetch())
		{
			$result["NAME"] = $prop_fields["NAME"];
			switch ($prop_fields["PROPERTY_TYPE"]) {
				case 'L':
					$db_enum_list = CIBlockProperty::GetPropertyEnum($code, Array("ID" => "asc"), Array("IBLOCK_ID"=> self::$IBLOCK_ID));
					while($ar_enum_list = $db_enum_list->GetNext())
					{
						$result["VALUES"][$ar_enum_list["ID"]] = ($custom_name ? GetMessage("RTOP_CRS_BAR_" . $ar_enum_list["VALUE"]) : $ar_enum_list["VALUE"]);
					}
					break;
			}
			$result["REQUIRED"] = $required;
		}	

		return $result;
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

	private function fieldsForm(){
		$arResult['DUBLE']['DATE'] = [
			'NAME' => 'Период',
			'REQUIRED' => 'Y',
			'VALUES' => 1
		];

		$arResult['MULTI']['BAR'] = $this->getPropertyList('BAR', 'N', true);
		$arResult['MULTI']['PRODUCT_CATEGORY'] = $this->getPropertyList('PRODUCT_CATEGORY', 'N');

		
	    
	    $arResult['MULTI']['USER'] = [
	    	'NAME' => 'Ответственный',
	    	'VALUES' => $this->getUserList(),
	    	'REQUIRED' => 'N'
	    ];

	    $connection = Application::getConnection();

		$sql = "SELECT * FROM `b_crm_addr` WHERE ENTITY_TYPE_ID IN (".CCrmOwnerType::Requisite.") AND (CITY != '' OR PROVINCE != '')";
		$request = $connection->query($sql);

		$arResult['SEARCH']['CITY'] = [
			'NAME' => 'Город',
			'REQUIRED' => 'N'
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
				DateType::LAST_7_DAYS,
				DateType::LAST_30_DAYS,
				DateType::LAST_60_DAYS,
				DateType::LAST_90_DAYS,
				DateType::MONTH,
				DateType::QUARTER,
				DateType::YEAR
			)
		];

		$filters[] = [
			'id' => 'BAR', 'name' => 'Линейка', 'type' => 'list', 'params' => ['multiple' => 'Y'], 'items' => $this->getPropertyList('BAR', 'N', true)['VALUES']
		];

		$filters[] = [
			'id' => 'PRODUCT_CATEGORY', 'name' => 'Категория товара для клиники', 'type' => 'list', 'params' => ['multiple' => 'Y'], 'items' => $this->getPropertyList('PRODUCT_CATEGORY', 'N')['VALUES']
		];

		$filters[] = [
			'id' => 'RESPONSIBLE_ID',
			'name' => 'Ответственный',
			'default' => true,
			'type' => 'dest_selector',
			'params' => array(
				'context' => 'CRM_WIDGET_FILTER_RESPONSIBLE_ID',
				'multiple' => 'Y',
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

		$filters[] = [
			'id' => 'PRODUCT_ID', 'name' => 'Товары', 'type' => 'text'
		];

		$filters[] = [
			'id' => 'SECTION_ID', 'name' => 'Раздел каталога', 'type' => 'text'
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
				case 'BAR':
				case 'PRODUCT_CATEGORY':
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
		$arTHead = [
			[
				'id' => 'CLIENT',
				'name' => 'Наименование клиента',
				'sort' => 'CLIENT',
				'default' => true
			],
			[
				'id' => 'PRODUCT',
				'name' => 'Товар',
				'sort' => 'PRODUCT',
				'default' => true
			],
			[
				'id' => 'QUANTITY_ORDERS',
				'name' => 'Количество выполненных заказов',
				'sort' => 'QUANTITY_ORDERS',
				'default' => true
			],
			[
				'id' => 'SUM',
				'name' => 'Сумма',
				'sort' => 'SUM',
				'default' => true
			],
			[
				'id' => 'REGION',
				'name' => 'Регион',
				'sort' => 'REGION',
				'default' => true
			],
			[
				'id' => 'RESPONSIBLE',
				'name' => 'Ответственный',
				'sort' => 'RESPONSIBLE',
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
				'CLIENT' => $row['INFO'],
				'QUANTITY_ORDERS' => $row['QUANTITY'],
				'SUM' => $row['SUM'],
				'REGION' => $row['ADDRESS'],
				'RESPONSIBLE' => $row['RESPONSIBLE'],
			];

			if (!empty($row['BASKET'])) {
				foreach($row['BASKET'] as $basketItem) {
					$result[]['data'] = [
						'PRODUCT' => CIBlockElement::GetByID($basketItem['ID'])->fetch()['NAME'],
						'SUM' => $basketItem['PRICE'],
						'REGION' => $row['ADDRESS'],
						'RESPONSIBLE' => $row['RESPONSIBLE'],
					];
				}
			}
		}
		return $result;//$this->sortView($result);
	}

	private function initParametersFromRequest(){
		$values = $this->request->getPostList();
		echo self::sendJsonAnswer($this->getRows($values));
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
				'PROPERTY_PRODUCT_CATEGORY' => $values['PRODUCT_CATEGORY']
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

			if(!empty($values['BAR']) || !empty($values['PRODUCT_CATEGORY']) || !empty($values['PRODUCT_ID']) || !empty($values['SECTION_ID'])){
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

			if(!empty($values['USER'])){
				$arBasketFilter['BASKET_ORDER_RESPONSIBLE_ID'] = $values['USER'];
			}

			if(!empty($arProducts)){
				$arBasketFilter['PRODUCT_ID'] = $arProducts;
			}

			$arUsers = $this->getUserList();

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
				$arOrders[$basket['ORDER_ID']]['RESPONSIBLE']['NAME'] = $arUsers[$basket['BASKET_ORDER_RESPONSIBLE_ID']]['NAME'];

				$arMix[$basket['CONTACT_COMPANY_ENTITY_TYPE_ID']][$basket['CONTACT_COMPANY_ENTITY_ID']] = $basket['ORDER_ID'];

				if($basket['CONTACT_COMPANY_ENTITY_TYPE_ID'] == CCrmOwnerType::Contact)
					$arOrders[$basket['ORDER_ID']]['CLIENT']['ID'] = $basket['CONTACT_COMPANY_ENTITY_ID'];
				else
					$arOrders[$basket['ORDER_ID']]['COMPANY']['ID'] = $basket['CONTACT_COMPANY_ENTITY_ID'];

				$arOrders[$basket['ORDER_ID']]['BASKET'][$basket['PRODUCT_ID']]['ID'] = $basket['PRODUCT_ID'];
				$arOrders[$basket['ORDER_ID']]['BASKET'][$basket['PRODUCT_ID']]['PRICE'] += $basket['PRICE']*$basket['QUANTITY'];
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

			if(!empty($arMix[CCrmOwnerType::Contact])){

				$arContactFilter = [
					'ID' => array_keys($arMix[CCrmOwnerType::Contact]),
					'CHECK_PERMISSION' => 'N'
				];

				if(!empty($arAddress) && !empty($values['CITY']) && !empty($values['CITY_text']))
					$arContactFilter['COMPANY_ID'] = array_keys($arAddress);

				$dbContact = CCrmContact::GetList(array('ID' => 'ASC'), $arContactFilter, array(), false);
				while($result = $dbContact->fetch()){
					foreach ($arOrders as $id => &$value) {
						if($value['CLIENT']['ID'] == $result['ID']){
							$value['CLIENT']['NAME'] = $result['FULL_NAME'];
							$value['CLIENT']['COMPANY_TITLE'] = $result['COMPANY_TITLE'];
							if(isset($arAddress[$result['COMPANY_ID']]))
								$value['CLIENT']['ADDRESS'] = $arAddress[$result['COMPANY_ID']];
						}
					}
				}
			}

			if(!empty($arMix[CCrmOwnerType::Company])){
				$arCompanyFilter = [
					'ID' => array_keys($arMix[CCrmOwnerType::Company]),
					'CHECK_PERMISSION' => 'N'
				];

				if(!empty($arAddress) && !empty($values['CITY']) && !empty($values['CITY_text']))
					$arCompanyFilter['ID'] = array_keys($arAddress);

				$rsCompany = CCrmCompany::GetList(array(), $arCompanyFilter, array());
				while($result = $rsCompany->Fetch()){
					foreach ($arOrders as $id => &$value) {
						if($value['COMPANY']['ID'] == $result['ID']){
							$value['COMPANY']['NAME'] = $result['TITLE'];
							if(isset($arAddress[$result['ID']]))
								$value['COMPANY']['ADDRESS'] = $arAddress[$result['ID']];
						}
					}
				}
			}

			$tmp_responsible = [];

			foreach($arOrders as $ID => $FIELDS){
				$key = (isset($FIELDS['COMPANY']) ? $FIELDS['COMPANY'] : $FIELDS['CLIENT']);
				$code = (isset($FIELDS['COMPANY']) ? 'company' : 'contact');

				if(empty($key['ADDRESS']) && !empty($values['CITY']) && !empty($values['CITY_text']))
					continue;

				if($key['NAME'] == "")
					$arResult[$key['ID']]['INFO'] = "<a href='https://".SITE_SERVER_NAME."/shop/orders/details/".$ID."/'>Заказ № ".$ID."</a><br/>";
				else
					$arResult[$key['ID']]['INFO'] = "<a href='https://".SITE_SERVER_NAME."/crm/".$code."/details/".$key['ID']."/'>".$key['NAME']."</a><br/>";

				$arResult[$key['ID']]['QUANTITY']++;
				$arResult[$key['ID']]['SUM'] += $arPrices[$ID];

				if(!isset($tmp_responsible[$key['ID']][$FIELDS['RESPONSIBLE']['ID']])){
					$arResult[$key['ID']]['RESPONSIBLE'] .= "<a href='https://".SITE_SERVER_NAME."/company/personal/user/".$FIELDS['RESPONSIBLE']['ID']."/'>".$FIELDS['RESPONSIBLE']['NAME']."</a><br/>";
					$tmp_responsible[$key['ID']][$FIELDS['RESPONSIBLE']['ID']] = "Y";
				}
				if(isset($key['ADDRESS']))
					$arResult[$key['ID']]['ADDRESS'] = $key['ADDRESS'];

				if (isset($arResult[$key['ID']]['BASKET'])) {
					foreach($FIELDS['BASKET'] as $bask) {
						$arResult[$key['ID']]['BASKET'][$bask['ID']]['PRICE'] += $bask['PRICE'];
					}
				} else {
					$arResult[$key['ID']]['BASKET'] = $FIELDS['BASKET'];
				}
			}

			return $arResult;
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