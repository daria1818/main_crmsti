<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Grid;
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
use Bitrix\Main\Page\Asset;
use Bitrix\Main\ORM\Query;
use Bitrix\Main\Entity;
use Bitrix\Main\UserTable;

class CCustomReportsDmComponent extends CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
	private $templatePage = '';
    private $sListId = '';

	protected static $IBLOCK_ID = 30;
	protected static $SKU_IBLOCK_ID = 81;
	protected static $filterID = 'CCustomReportsDmComponent';
	/** @var FilterOptions */
    private $oFilterOptions;

    /** @var PageNavigation */
    private $oPageNavigation;

    /** @var GridOptions */
    private $oGridOptions;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new ErrorCollection();
	}

	public function onIncludeComponentLang()
    {
        Loc::loadMessages(__FILE__);
    }

    protected function listKeysSignedParameters()
    {
        return ['ID', 'PAGE_NUMBER', 'GRID_ID'];
    }

    public function configureActions()
    {
        return [];
    }

	public function debug($array)
	{
		echo "<pre>";print_r($array);echo "</pre>";
	}

	
	public function onPrepareComponentParams($params)
	{	
		try {            
            $this->loaderModules();
        } catch (Throwable $throwable) {
            ShowError($throwable->getMessage());
        }

        $classHash = explode('\\', __CLASS__);

        $params['GRID_ID'] = $params['GRID_ID'] ?? array_pop($classHash) . $params['TYPE_REPORTS'];

        $params['SHOW_FILTER'] = $params['SHOW_FILTER'] === 'N' ? 'N' : 'Y';
        $params['GRID_SHOW_ROW_CHECKBOXES'] = false;

		$params['DEFAULT_FIELDS'] = [
			'ID',
			'DATE_INSERT',
			'STATUS_ID',
			'CANCELED',
			'SUM',
			'RESPONSIBLE',
			'CONTACT',
			'COMPANY',
			'PRODUCTS'
		];

		$params['FILTER_FIELDS'] = [
			'DATE_INSERT',
			'STATUS_ID',
			'CANCELED',
			'RESPONSIBLE_ID',
			'PRODUCT_ID',
		];

		$params['ALLOWED_FIELDS'] = $params['DEFAULT_FIELDS'];

		return $params;
	}

	private function loaderModules()
    {
        $arModules = ['crm', 'main', 'iblock', 'sale'];

        foreach ($arModules as $module) {
            if (!Loader::includeModule($module)) {
                throw new Exception('Could not load ' . $module . ' module');
            }
        }
    }

    public function executeComponent()
    {
        CJSCore::Init(['jquery2', 'fx', 'admin', 'filter']);
        Asset::getInstance()->addJs('/bitrix/js/main/core/core_admin_interface.js');
        if (!$this->startResultCache()) {
            return;
        }
        global $APPLICATION;

        $APPLICATION->SetTitle(Loc::getMessage('DM_TITLE'));

        try {
            $this->initGrid();
            $this->loadData();
            $this->includeComponentTemplate($this->templatePage);
        } catch (Throwable $throwable) {
            ShowError($throwable->getMessage());
            $this->abortResultCache();
        }
    }

    private function initGrid()
    {
        $this->sListId = $this->arParams['GRID_ID'];
        $this->oGridOptions = new GridOptions($this->sListId);
        $this->oPageNavigation = new PageNavigation($this->sListId);
        $this->oFilterOptions = new FilterOptions($this->sListId);

        if (isset($this->arParams['GRID_COLUMNS'])) {
            $this->oGridOptions->SetVisibleColumns($this->arParams['GRID_COLUMNS']);
        }
        if (empty($this->oGridOptions->GetVisibleColumns())) {
            $this->oGridOptions->SetVisibleColumns($this->arParams['DEFAULT_FIELDS']);
        }
	}

	private function loadData()
    {
        $this->arResult['UI_FILTER'] = $this->initFilter();
        $this->arResult['COLUMNS'] = $this->getTHead();
        
        
        $arNavParams = $this->getNavParams();
        $arFilter = $this->getFilter();  
        $arSort = $this->getSort();
        $limit = $this->arParams['SHOW_ALL_RECORDS'] == 'Y' ? 0 : $this->oPageNavigation->getLimit();

        $arRows = $this->getItems([
            'filter' => $arFilter,
            'order' => $arSort['sort'],
            'limit' => $limit,
            'offset' => $arNavParams['offset']
        ]);

        $this->arResult['GRID_ID'] = $this->sListId;
        $this->arResult['FILTER_ID'] = $this->sListId;
        $this->arResult['FILTER_OPTIONS'] = $this->oFilterOptions;  
        $this->arResult['ROWS'] = $arRows;
        $this->arResult['GRID_OPTIONS'] = $this->oGridOptions;
        $this->arResult['NAV_OBJECT'] = $this->oPageNavigation;
    }

	private function initFilter()
	{
		$filterField = [];
        foreach($this->arParams['FILTER_FIELDS'] ?:[] as $name)
        {
            switch($name){
                case 'DATE_INSERT':
					$filterField[] = [
						'id' => $name,
						'name' => Loc::getMessage('DM_FILTER_FIELD_' . $name),
						'type' => 'date',
						'exclude' => array(
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
					break;
				case 'RESPONSIBLE_ID':
					$filterField[] = [
						'id' => $name,
						'name' => Loc::getMessage('DM_FILTER_FIELD_' . $name),
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
					break;
				case 'PRODUCT_ID':
					$filterField[] = [
						'id' => $name,
						'name' => Loc::getMessage('DM_FILTER_FIELD_' . $name),
						'type' => 'text'
					];
					break;
				case 'CANCELED':
					$filterField[] = [
						'id' => $name,
						'name' => Loc::getMessage('DM_FILTER_FIELD_' . $name),
						'type' => 'list',
						'items' => [
							'Y' => 'Да',
							'N' => 'Нет'
						]
					];
					break;
				case 'STATUS_ID':
					$statusList = \Bitrix\Sale\Internals\StatusLangTable::getList([
						'filter' => [
							'LID' => 'ru',
							'STATUS.TYPE' => 'O'
						],
						'select' => ['STATUS_ID', 'NAME']
					])->fetchAll();
					foreach($statusList as $item)
					{
						$items[$item['STATUS_ID']] = $item['NAME'];
					}
					$filterField[] = [
						'id' => $name,
						'name' => Loc::getMessage('DM_FILTER_FIELD_' . $name),
						'type' => 'list',
						'items' => $items
					];
					break;
			}
		}
		
		return $filterField;
	}

	private function getTHead()
	{
        $head = [];

        foreach($this->arParams['DEFAULT_FIELDS'] ?: [] as $field)
        {
            $item = [
                'id' => $field,
                'name' => Loc::getMessage('DM_HEAD_FIELD_' . $field),
                'sort' => (!in_array($field,['ID', 'DATE_INSERT']) ? "" : $field),
                'default' => true
            ];

            $head[] = $item;
        }

        return $head;
    }

    private function getNavParams()
    {
        $arNavParams = $this->oGridOptions->GetNavParams();

        $this->oPageNavigation
            ->allowAllRecords(false)
            ->setPageSize($arNavParams['nPageSize'])
            ->initFromUri();

        $resetRows = $this->request->get('grid_action') == 'pagination';
        if($resetRows)
            $_SESSION[$this->sListId . '_pageNum'] = (int)$this->oPageNavigation->getCurrentPage();

        if(isset($_SESSION[$this->sListId . '_pageNum']))
            $this->oPageNavigation->setCurrentPage($_SESSION[$this->sListId . '_pageNum']);

        $arNavParams['iNumPage'] = (int)$this->oPageNavigation->getCurrentPage();
        $arNavParams['limit'] = $this->oPageNavigation->getLimit();
        $arNavParams['offset'] = $this->oPageNavigation->getOffset();

        return $arNavParams;
    }

	private function getFilter()
	{
		$filters = $this->oFilterOptions->getFilter();

		$setFilter = [];

		foreach($filters as $code => $value){
			if(empty($value))
				continue;
			switch($code){
				case 'DATE_INSERT_from':
					$setFilter['>=DATE_INSERT'] = $value;
					break;
				case 'DATE_INSERT_to':
					$setFilter['<=DATE_INSERT'] = $value;
					break;
				case 'PRODUCT_ID':
					$setFilter['PRODUCT_ID'] = explode(";", $value);
					break;
				case 'RESPONSIBLE_ID':
				case 'CANCELED':
					$setFilter[$code] = $value;
					break;
			}
		}

		if(!empty($setFilter['PRODUCT_ID']))
			$setFilter['PRODUCT_ID'] = array_diff($setFilter['PRODUCT_ID'], [0, null]);

		return $setFilter;
	}

	private function getSort()
    {
        return $this->oGridOptions->getSorting([
            'sort' => [
                'ID' => 'asc',
            ],
            'vars' => [
                'by' => 'by',
                'order' => 'order',
            ],
        ]);
    }

    private function getItems(array $parameters = [])
    {
        $arRows = [];

        $parameters['filter'] = array_merge($parameters['filter'], ['LID' => 'dm']);

        $arProducts = [];

        if(isset($parameters['filter']['PRODUCT_ID']))
        {
        	$basketList = \Bitrix\Sale\Internals\BasketTable::getList([
        		'filter' => [
        			'PRODUCT_ID' => $parameters['filter']['PRODUCT_ID'],
        			'!ORDER_ID' => false
        		],
        		'select' => ['ID', 'ORDER_ID', 'NAME', 'PRICE', 'CURRENCY']
        	])->fetchAll();
        	
        	foreach($basketList ?: [] as $item)
        	{
        		$arProducts[$item['ORDER_ID']][] = [
        			'NAME' => $item['NAME'],
        			'PRICE' => CurrencyFormat($item['PRICE'], $item['CURRENCY']),
        		];
        	}
        	unset($parameters['filter']['PRODUCT_ID']);
        	$parameters['filter']['ID'] = array_unique(array_column($basketList, 'ORDER_ID'));
        }

        $orderList = \Bitrix\Crm\Order\Order::getList([
        	'order' => $parameters['order'],
        	'filter' => $parameters['filter'],
        	'select' => ['*', 'STATUS_TABLE_' => 'STATUS_TABLE', 'USER_TABLE_' => 'USER_TABLE'],
        	'count_total' => true,
        	'limit' => $parameters['limit'],
            'offset' => $parameters['offset'],
            'runtime' => [
                'CONTACT_COMPANY' => [
                    'data_type' => '\Bitrix\Crm\Binding\OrderContactCompanyTable',
                    'reference' => [
                        '=this.ID' => 'ref.ORDER_ID',
                    ],
                ],
                new Entity\ReferenceField(
					'STATUS_TABLE',
					\Bitrix\Sale\Internals\StatusLangTable::class,
					Query\Join::on('this.STATUS_ID', '=', 'ref.STATUS_ID')->where('ref.LID', 'ru')
				),
				new Entity\ReferenceField(
					'USER_TABLE',
					\Bitrix\Main\UserTable::class,
					Query\Join::on('this.RESPONSIBLE_ID', 'ref.ID')
				),
            ]
        ]);

        $itemsList = $orderList->fetchAll();

        $this->arResult["ROWS_COUNT"] = $orderList->getCount();
        $this->oPageNavigation->setRecordCount($orderList->getCount());

        $this->enableNextPage = $this->oPageNavigation->getCurrentPage() < $this->oPageNavigation->getPageCount();

        if(empty($itemsList))
        	return [];

        $typeContact = \CCrmOwnerType::Contact;
		$typeCompany = \CCrmOwnerType::Company;

        $orderContactCompany = \Bitrix\Crm\Binding\OrderContactCompanyTable::getList([
        	'filter' => [
        		'ORDER_ID' => array_column($itemsList, 'ID')
        	],
        	'select' => ['*', 'FULLNAME' => 'CONTACT_TABLE.FULL_NAME', 'TITLE' => 'COMPANY_TABLE.TITLE'],
        	'runtime' => [
				new Entity\ReferenceField(
					'CONTACT_TABLE',
					\Bitrix\Crm\ContactTable::class,
					Query\Join::on('this.ENTITY_ID', 'ref.ID')->where('this.ENTITY_TYPE_ID', $typeContact)
				),
				new Entity\ReferenceField(
					'COMPANY_TABLE',
					\Bitrix\Crm\CompanyTable::class,
					Query\Join::on('this.ENTITY_ID', 'ref.ID')->where('this.ENTITY_TYPE_ID', $typeCompany)
				)
        	]
        ])->fetchAll();

        $orderContactCompanyList = [];

        foreach($orderContactCompany ?: [] as $item)
        {
        	if($item['ENTITY_TYPE_ID'] == $typeContact)
        	{
        		$orderContactCompanyList[$item['ORDER_ID']] = [
        			'CONTACT' => "<a href='https://".SITE_SERVER_NAME."/crm/contact/details/".$item['ENTITY_ID']."/'>".$item['FULLNAME']."</a>"
        		];
        	}
        	else
        	{
        		$orderContactCompanyList[$item['ORDER_ID']] = [
        			'COMPANY' => "<a href='https://".SITE_SERVER_NAME."/crm/company/details/".$item['ENTITY_ID']."/'>".$item['TITLE']."</a>"
        		];
        	}
        }

        if(empty($arProducts))
        {
        	$basketList = \Bitrix\Sale\Internals\BasketTable::getList([
        		'filter' => [
        			'ORDER_ID' => array_column($itemsList, 'ID')
        		],
        		'select' => ['ID', 'ORDER_ID', 'NAME', 'PRICE', 'CURRENCY']
        	])->fetchAll();
        	
        	foreach($basketList ?: [] as $item)
        	{
        		$arProducts[$item['ORDER_ID']][] = [
        			'NAME' => $item['NAME'],
        			'PRICE' => CurrencyFormat($item['PRICE'], $item['CURRENCY']),
        		];
        	}
        }

        foreach($itemsList ?: [] as $item)
        {
        	$responsible = "<a href='https://".SITE_SERVER_NAME."/company/personal/user/".$item['USER_TABLE_ID']."/'>".trim($item['USER_TABLE_NAME'] . " " . $item['USER_TABLE_LAST_NAME']) . "</a>";

        	$orderId = "<a href='https://".SITE_SERVER_NAME."/shop/orders/details/".$item['ID']."/'>".$item['ID']."</a>";

        	$products = '';
        	foreach($arProducts[$item['ID']] ?: [] as $product){
        		if(!empty($products))
        			$products .= "\n";
        		$products .= $product['NAME'] . ' - ' . $product['PRICE'];
        	}

            $arRows[$item['ID']] = [
            	'data' => [
            		'ID' => $orderId,
            		'DATE_INSERT' => $item['DATE_INSERT']->format('d.m.Y H:i:s'),
            		'DATE_INSERT_STRING' => $item['DATE_INSERT']->toString(),
            		'STATUS_ID' => $item['STATUS_TABLE_NAME'],
            		'CANCELED' => $item['CANCELED'] == 'Y' ? 'Да' : 'Нет',
            		'SUM' => CurrencyFormat($item['PRICE'], $item['CURRENCY']),
            		'RESPONSIBLE' => $responsible,
            		'CONTACT' => $orderContactCompanyList[$item['ID']]['CONTACT'] ?? '',
            		'COMPANY' => $orderContactCompanyList[$item['ID']]['COMPANY'] ?? '',
            		'PRODUCTS' => $products
            	]
            ];
        }

        return $this->sortView($arRows, $parameters['order']);
    }

    private function sortView($arRow, $order)
    {
        usort($arRow, function ($a, $b) use ($order) {

            $field = array_key_first($order);
            $order = current($order);            

            if(in_array($field, ['DATE_INSERT']))
            {
                $valueA = strtotime(strip_tags($a['data'][$field . '_STRING']));
                $valueB = strtotime(strip_tags($b['data'][$field . '_STRING']));
            }
            else
            {
                $valueA = strip_tags($a['data'][$field]);
                $valueB = strip_tags($b['data'][$field]);
            }  
            
            if ($valueA == $valueB)
                return 0;

            if ($order == 'desc')
                return ($valueA > $valueB) ? -1 : 1;
            else
                return ($valueA < $valueB) ? -1 : 1;
        });

        return $arRow;
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