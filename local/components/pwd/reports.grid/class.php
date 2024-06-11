<?php

use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Sale\Internals\BasketTable;
use Pwd\Cafeteria\Entity\CafeteriaCatalogTable;
use Pwd\Entity\CatalogTable;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class ReportsOrdersGrid extends \CBitrixComponent
{
    private $templatePage = '';
    private $sListId = '';
    private $arSchema = [];

    private $filterField = [];

    /** @var FilterOptions */
    private $oFilterOptions;

    /** @var PageNavigation */
    private $oPageNavigation;

    /** @var GridOptions */
    private $oGridOptions;

    public function onIncludeComponentLang()
    {
        Loc::loadMessages(__FILE__);
    }


    public function onPrepareComponentParams($arParams)
    {

        $classHash = explode('\\', __CLASS__);
        $arParams['GRID_ID'] = $arParams['GRID_ID'] ?? array_pop($classHash) . $arParams['TYPE_REPORTS'];

        $arParams['GRID_ACTION_PANEL'] = $arParams['GRID_ACTION_PANEL'] ?? ['GROUPS' => []];

        // Must be clearly disabled
        $arParams['COUNT_TOTAL'] = $arParams['COUNT_TOTAL'] === 'N' ? 'N' : 'Y';

        // Must be clearly enabled
        $arParams['SHOW_FILTER'] = $arParams['SHOW_FILTER'] === 'Y' ? 'Y' : 'N';

        $arParams['CUSTOM_FILTER'] = $arParams['CUSTOM_FILTER'] ?? [];
        $arParams['CUSTOM_EXPORTS'] = $arParams['CUSTOM_EXPORTS'] ?? [];


        $arParams['EXCLUDE_FIELDS'] = $arParams['EXCLUDE_FIELDS'] ?? [];
        $arParams['DEFAULT_FIELDS'] = $arParams['DEFAULT_FIELDS'] ?? [
                'COMPANY_NAME',
                'COMPANY_ID',
                'CONTACT_NAME',
                'CONTACT_ID',
            ];

        $arParams['FILTER_FIELDS'] = [
            'COMPANY_NAME',
            'COMPANY_ID',
            'CONTACT_NAME',
            'CONTACT_ID',
        ];

        $arParams['ALLOWED_FIELDS'] = array_unique(array_merge(
            $arParams['ALLOWED_FIELDS'] ?? [],
            $arParams['DEFAULT_FIELDS'],
            [
                'COMPANY_NAME',
                'COMPANY_ID',
                'CONTACT_NAME',
                'CONTACT_ID',
            ]
        ));

        return $arParams;
    }


    public function executeComponent()
    {
        if (!$this->startResultCache()) {
            return;
        }

        try {
            $this->resolveDependencies();
            //$this->initPermissions();
            $this->initGrid();
            $this->loadData();
            $this->includeComponentTemplate($this->templatePage);
        } catch (\Throwable $throwable) {
            \ShowError($throwable->getMessage());
            $this->abortResultCache();
        }
    }

    private function resolveDependencies()
    {
        $arModules = [
            'iblock',
            'sale',
            'catalog',
            'crm',
        ];

        foreach ($arModules as $sModule) {
            if (!Loader::includeModule($sModule)) {
                throw new \Exception('Could not load ' . $sModule . ' module');
            }
        }
    }

    private function initPermissions()
    {
        global $USER;
        if (!$USER->IsAdmin()) {
            throw new \Exception('No access report');
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
        $this->initFilter();
        $this->getSchema();
        $arNavParams = $this->getNavParams();
        $arFilter = $this->getFilter();
        $arSelect = $this->getSelect();
        $arSort = $this->getSort();
        $limit = $this->arParams['SHOW_ALL_RECORDS'] == 'Y' ? 0 : $this->oPageNavigation->getLimit();

        $arRows = $this->getItems([
            'select' => $arSelect,
            'filter' => $arFilter,
            'order' => $arSort['sort'],
            'limit' => $limit,
            'offset' => $arNavParams['offset'],
        ]);

        // Expose data to $arResult
        $this->arResult['GRID_ID'] = $this->sListId;
        $this->arResult['FILTER_ID'] = $this->sListId;
        $this->arResult['UI_FILTER'] = array_merge(
            $this->filterField ?: [], array_filter($this->arSchema, function ($arField) {
                return $arField['isFilter'];
            })
        );
        $this->arResult['ROWS'] = $arRows;
        $this->arResult['COLUMNS'] = $this->arSchema;
        $this->arResult['GRID_OPTIONS'] = $this->oGridOptions;
        $this->arResult['FILTER_OPTIONS'] = $this->oFilterOptions;
        $this->arResult['NAV_OBJECT'] = $this->oPageNavigation;
    }

    private function initFilter()
    {

        $filterItems = [
            ['id' => 'DATE', 'name' => 'Дата', 'type' => 'date'],
        ];

        $barList = [];
        $catalogID = CatalogTable::getIblockId();
        $bars = \CIBlockPropertyEnum::GetList(
            [
                'VALUE' => 'ASC',
            ],
            [
                'CODE' => 'BAR',
                'IBLOCK_ID' => $catalogID,
            ]
        );
        while ($one = $bars->GetNext()) {
            $idProp = CatalogTable::getEnumIdByXmlId('BAR_' . $one['VALUE'], 'PRACTICE');
            $barList[$one['ID']] = CatalogTable::getEnumValueById($idProp, 'PRACTICE');
        }
        $filterItems[] = ['id' => 'BAR', 'name' => 'Линейка', 'type' => 'list', 'items' => $barList];
        $this->filterField = $filterItems;
    }

    private function getSchema()
    {
        $arSchema = [
            ['id' => 'COMPANY_NAME', 'name' => 'Компания', 'sort' => 'COMPANY_NAME', 'default' => true, 'isFilter' => true],
            ['id' => 'COMPANY_ID', 'name' => 'Компании ID', 'sort' => 'COMPANY_ID', 'default' => true],
            ['id' => 'CONTACT_NAME', 'name' => 'Контакт', 'sort' => 'CONTACT_NAME', 'default' => true, 'isFilter' => true],
            ['id' => 'CONTACT_ID', 'name' => 'Контакт ID', 'sort' => 'CONTACT_ID', 'default' => true],
        ];
        $this->arSchema = $arSchema;
        return $arSchema;
    }

    private function getNavParams()
    {
        $arNavParams = $this->oGridOptions->GetNavParams();

        $this->oPageNavigation
            ->allowAllRecords(true)
            ->setPageSize($arNavParams['nPageSize'])
            ->initFromUri();

        $arNavParams['iNumPage'] = (int)$this->oPageNavigation->getCurrentPage();
        $arNavParams['limit'] = $this->oPageNavigation->getLimit();
        $arNavParams['offset'] = $this->oPageNavigation->getOffset();

        return $arNavParams;
    }

    private function getFilter()
    {
        $arRawFilters = $this->oFilterOptions->getFilter();

//        $setFilter = [
//            'basket' => ['>=ORDER.DATE_INSERT' => date('d.m.Y H:i:s'), time() - 60 * 60 * 24 * 30],
//        ];

        foreach ($arRawFilters as $filter => $value) {
            switch ($filter) {
                case 'COMPANY_NAME':
                    $setFilter['company']['%TITLE'] = $value;
                    break;
                case 'COMPANY_ID':
                    $setFilter['company']['ID'] = $value;
                    break;
                case 'CONTACT_NAME':
                    $setFilter['contact']['%FULL_NAME'] = $value;
                    break;
                case 'CONTACT_ID':
                    $setFilter['contact']['ID'] = $value;
                    break;
                case 'DATE_from':
                    $setFilter['basket']['>=ORDER.DATE_INSERT'] = $value;
                    break;
                case 'DATE_to':
                    $setFilter['basket']['<=ORDER.DATE_INSERT'] = $value;
                    break;
                case 'BAR':
                    $elements = \CIBlockElement::GetList(
                        [],
                        [
                            'PROPERTY_BAR' => $value,
                            'IBLOCK_ID' => CatalogTable::getIblockId(),
                        ],
                        false,
                        false,
                        ['ID']
                    );
                    while ($el = $elements->GetNext()) {
                        $setFilter['basket']['PRODUCT_ID'][] = $el['ID'];
                    };
                    break;
            }
        }

        return $setFilter;
    }


    /**
     * Get grid's sorting and transform them to ORM order param
     * @return array
     */
    private function getSort()
    {
        return $this->oGridOptions->getSorting([
            'sort' => [
                'CONTACT_NAME' => 'ASC',
            ],
            'vars' => [
                'by' => 'by',
                'order' => 'order',
            ],
        ]);
    }


    /**
     * Get grid's selected columns and transform them to ORM select param
     * @return array
     */
    private function getSelect()
    {
        return [
            'COMPANY_NAME',
            'COMPANY_ID',
            'CONTACT_NAME',
            'CONTACT_ID',
        ];
    }

    private function getItems(array $parameters = [])
    {

        $arRows = [];

        $itemsBasket = BasketTable::getList([
            'filter' =>
                array_merge(
                    ['!ORDER_ID' => false,
                        '!SALE_INTERNALS_BASKET_CONTACT_COMPANY_ID' => false,
                    ], $parameters['filter']['basket']?:[]),
            'select' => [
                'CONTACT_COMPANY', 'ID', 'ORDER_ID', 'PRODUCT_ID',
            ],
            'group' => ['SALE_INTERNALS_BASKET_CONTACT_COMPANY_ID'],
            'runtime' => [
                'CONTACT_COMPANY' => [
                    'data_type' => '\Bitrix\Crm\Binding\OrderContactCompanyTable',
                    'reference' => [
                        '=this.ORDER_ID' => 'ref.ORDER_ID',
                    ],
                ],
            ],
        ])->fetchAll();

        $companyIds = array_column(array_filter($itemsBasket, function ($item) {
            return $item['SALE_INTERNALS_BASKET_CONTACT_COMPANY_ENTITY_TYPE_ID'] == CCrmOwnerType::Company;
        }), 'SALE_INTERNALS_BASKET_CONTACT_COMPANY_ENTITY_ID');
        $contactIds = array_column(array_filter($itemsBasket, function ($item) {
            return $item['SALE_INTERNALS_BASKET_CONTACT_COMPANY_ENTITY_TYPE_ID'] == CCrmOwnerType::Contact;
        }), 'SALE_INTERNALS_BASKET_CONTACT_COMPANY_ENTITY_ID');

        // Получаем все компании
        if ($companyIds && empty($parameters['filter']['contact'])) {
            $itemsCompany = Bitrix\Crm\CompanyTable::getList([
                'filter' => array_merge(
                    ['ID' => $companyIds],
                    $parameters['filter']['company'] ?: []
                ),
            ])->fetchAll();
        }

        // Получаем все контакты
        if ($contactIds && empty($parameters['filter']['company'])) {
            $itemsContacts = Bitrix\Crm\ContactTable::getList([
                'filter' => array_merge(
                    ['ID' => $contactIds],
                    $parameters['filter']['contact'] ?: []
                ),
            ])->fetchAll();
        }

        foreach ($itemsCompany ?: [] as $arRow) {
            $arRows[] = [
                'data' => [
                    'COMPANY_NAME' => '<a href="/crm/company/details/' . $arRow['ID'] . '/">' . $arRow['TITLE'] . '</a>',
                    'COMPANY_ID' => $arRow['ID'],
                    'CONTACT_NAME' => '',
                    'CONTACT_ID' => '',
                ],
            ];
        }
        foreach ($itemsContacts ?: [] as $arRow) {
            $arRows[] = [
                'data' => [
                    'COMPANY_NAME' => '',
                    'COMPANY_ID' => '',
                    'CONTACT_NAME' => '
                    <a href="/crm/contact/details/' . $arRow['ID'] . '/">
                    ' . $arRow['FULL_NAME'] . '
                    </a><br />' . $arRow['POST'],
                    'CONTACT_ID' => $arRow['ID'],
                ],
            ];
        }
        if ($parameters['count_total']) {
            $this->oPageNavigation->setRecordCount(count($arRows));
        }

        $arRows = $this->sortView($arRows, $parameters['order']);

        return $arRows;
    }

    private function sortView($arRow, $order)
    {
        usort($arRow, function ($a, $b) use ($order) {

            $field = array_key_first($order);
            $order = current($order);

            $valueA = strip_tags($a['data'][$field]);
            $valueB = strip_tags($b['data'][$field]);
            if ($valueA == $valueB) {
                return 0;
            }
            if ($order == 'desc') {
                return ($valueA > $valueB) ? -1 : 1;
            } else {
                return ($valueA < $valueB) ? -1 : 1;
            }
        });
        return $arRow;
    }

}
