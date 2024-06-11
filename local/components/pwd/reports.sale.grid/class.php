<?php

use Bitrix\Crm\AddressTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Sale\Internals\BasketTable;
use Pwd\Entity\CatalogTable;
use Pwd\Helpers\UserHelper;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class ReportsSaleGrid extends CBitrixComponent
{
    private $templatePage = '';
    private $sListId = '';
    private $arSchema = [];

    private $regionList = [];

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

        $arParams['COUNT_TOTAL'] = $arParams['COUNT_TOTAL'] === 'N' ? 'N' : 'Y';

        $arParams['SHOW_FILTER'] = $arParams['SHOW_FILTER'] === 'Y' ? 'Y' : 'N';

        $arParams['CUSTOM_FILTER'] = $arParams['CUSTOM_FILTER'] ?? [];
        $arParams['CUSTOM_EXPORTS'] = $arParams['CUSTOM_EXPORTS'] ?? [];

        $arParams['EXCLUDE_FIELDS'] = $arParams['EXCLUDE_FIELDS'] ?? [];
        $arParams['DEFAULT_FIELDS'] = $arParams['DEFAULT_FIELDS'] ?? [
                'PRODUCT_NAME',
                'SUM',
                'COUNT',
                'REGION',
            ];

        $arParams['FILTER_FIELDS'] = [
            'PRODUCT_NAME',
            'REGION',
        ];

        $arParams['ALLOWED_FIELDS'] = $arParams['DEFAULT_FIELDS'];

        return $arParams;
    }


    public function executeComponent()
    {

        CJSCore::Init(['jquery2', 'fx', 'admin', 'filter']);
        Asset::getInstance()->addJs('/bitrix/js/main/core/core_admin_interface.js');

        if (!$this->startResultCache()) {
            return;
        }

        try {
            $this->resolveDependencies();
            //$this->initPermissions();
            $this->initGrid();
            $this->loadData();
            $this->includeComponentTemplate($this->templatePage);
        } catch (Throwable $throwable) {
            ShowError($throwable->getMessage());
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
                throw new Exception('Could not load ' . $sModule . ' module');
            }
        }
    }

    private function initPermissions()
    {
        if (!UserHelper::isViewsOfReports()) {
            throw new Exception('No access report');
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
        $this->initRegionList();
        $this->initFilter();
        $this->getSchema();

        $arNavParams = $this->getNavParams();
        $arFilter = $this->getFilter();
        $arSort = $this->getSort();
        $limit = $this->arParams['SHOW_ALL_RECORDS'] == 'Y' ? 0 : $this->oPageNavigation->getLimit();

        $arRows = $this->getItems([
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
            [
                'id' => 'DATE',
                'name' => 'Период',
                'type' => 'date',
            ],
        ];

        $filterItems[] = [
            'id' => 'BAR',
            'name' => 'Линейка',
            'type' => 'list',
            'items' => $this->getBarList(),
            'params' => ['multiple' => 'Y'],
        ];
        $filterItems[] = [
            'id' => 'PRODUCT',
            'name' => 'Товары',
            'type' => 'text',
        ];
        $filterItems[] = [
            'id' => 'DIR',
            'name' => 'Раздел каталога',
            'type' => 'list',
            'items' => $this->getSectionList(),
        ];
        $filterItems[] = [
            'id' => 'REGION',
            'name' => 'Выбор региона',
            'type' => 'list',
            'items' => array_combine(array_keys($this->regionList), array_keys($this->regionList)),
        ];
        $this->filterField = $filterItems;
    }

    private function convertRegionName(string $region = ''): string
    {
        $region = mb_strtolower($region);
        $region = str_ireplace(['область', 'обл', 'край', 'республика', ' г'], '', $region);
        $region = mb_convert_case($region, MB_CASE_TITLE);
        return trim($region);
    }

    private function initRegionList()
    {
        $this->regionList = [];

        $address = AddressTable::getList([
            'filter' => [
                '!PROVINCE' => false,
            ],
            'select' => ['PROVINCE', 'ANCHOR_ID', 'LOC_ADDR_ID'],
            'order' => [
                'PROVINCE' => 'ASC',
            ],
            'group' => 'ENTITY_ID',
        ]);
        while ($one = $address->fetch()) {
            $one['PROVINCE'] = $this->convertRegionName($one['PROVINCE']);
            $this->regionList[$one['PROVINCE']][] = $one['ANCHOR_ID'];
        }
    }

    private function getSectionList(): array
    {
        $sectionList = [];
        $sections = SectionTable::getList([
            'filter' => [
                'ACTIVE' => 'Y',
                'IBLOCK_ID' => CatalogTable::getIblockId(),
            ],
            'select' => ['NAME', 'ID', 'DEPTH_LEVEL'],
            'order' => [
                'LEFT_MARGIN' => 'ASC',
            ],
        ]);
        while ($one = $sections->fetch()) {
            $sectionList[$one['ID']] = substr('----', 0, $one['DEPTH_LEVEL'] - 1) . ' ' . $one['NAME'];
        }
        return $sectionList;
    }

    private function getBarList(): array
    {
        $barList = [];

        $catalogID = CatalogTable::getIblockId();
        $bars = CIBlockPropertyEnum::GetList(
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
        return $barList;
    }

    private function getSchema()
    {
        $arSchema = [
            [
                'id' => 'PRODUCT_NAME',
                'name' => 'Наименование товара',
                'sort' => 'PRODUCT_NAME',
                'default' => true,
            ],
            [
                'id' => 'COUNT',
                'name' => 'Количество',
                'sort' => 'COUNT',
                'default' => true,
            ],
            [
                'id' => 'SUM',
                'name' => 'Сумма',
                'sort' => 'SUM',
                'default' => true,
            ],
            [
                'id' => 'REGION',
                'name' => 'Регион',
                'sort' => 'REGION',
                'default' => true,
            ],
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

        $productFilter = $setFilter = [];

        foreach ($arRawFilters as $filter => $value) {
            if (empty($value)) {
                continue;
            }

            switch ($filter) {
                case 'PRODUCT':
                    $productFilter['ID'] = explode(';', $value);
                    break;
                case 'DIR':
                    $productFilter['SECTION_ID'] = $value;
                    $productFilter['INCLUDE_SUBSECTIONS'] = 'Y';
                    break;
                case 'REGION':
                    $setFilter['SALE_INTERNALS_BASKET_CONTACT_COMPANY_ENTITY_ID'] = $this->regionList[$value];
                    break;
                case 'DATE_from':
                    $setFilter['>=ORDER.DATE_INSERT'] = $value;
                    break;
                case 'DATE_to':
                    $setFilter['<=ORDER.DATE_INSERT'] = $value;
                    break;
                case 'BAR':
                    $productFilter['PROPERTY_BAR'] = $value;
                    break;
            }
        }

        if (!empty($productFilter)) {
            $setFilter['PRODUCT_ID'] = false;
            $elements = CIBlockElement::GetList(
                [],
                array_merge(['IBLOCK_ID' => CatalogTable::getIblockId()], $productFilter),
                false,
                false,
                ['ID']
            );
            if ($elements->SelectedRowsCount()) {
                $setFilter['PRODUCT_ID'] = [];
            }
            while ($el = $elements->GetNext()) {
                $setFilter['PRODUCT_ID'][] = $el['ID'];
            };
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
                'PRODUCT_NAME' => 'ASC',
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
        $query = BasketTable::getList([
            'order' => [
                'ID' => 'DESC',
            ],
            'filter' =>
                array_merge(
                    [
                        '!ORDER_ID' => false,
                        '!SALE_INTERNALS_BASKET_CONTACT_COMPANY_ID' => false,
                        '!SALE_INTERNALS_BASKET_PRODUCT_NAME' => false,
                        '!PRODUCT_ID' => false,
                    ], $parameters['filter'] ?: []),
            'select' => [
                'CONTACT_COMPANY', 'ID', 'PRODUCT_ID', 'PRODUCT.NAME', 'SUMMARY_PRICE', 'QUANTITY',
            ],
            'runtime' => [
                'CONTACT_COMPANY' => [
                    'data_type' => '\Bitrix\Crm\Binding\OrderContactCompanyTable',
                    'reference' => [
                        '=this.ORDER_ID' => 'ref.ORDER_ID',
                    ],
                ],
            ],
        ]);
        $itemsBasket = $query->fetchAll();

        if ($parameters['count_total']) {
            $this->oPageNavigation->setRecordCount($query->getCount());
        }

        foreach ($itemsBasket as $item) {

            $idProduct = $item['PRODUCT_ID'];
            $contactId = $item['SALE_INTERNALS_BASKET_CONTACT_COMPANY_ENTITY_ID'];
            $count = (int)$item['QUANTITY'];
            $sum = $count * (int)$item['SUMMARY_PRICE'];

            $region = self::recursiveArraySearch($contactId, $this->regionList) ?: 'не указан';

            if (!empty($arRows[$idProduct])) {

                $arRows[$idProduct]['data']['COUNT'] += $count;
                $arRows[$idProduct]['data']['SUM'] += $sum;
                $arRows[$idProduct]['data']['regionList'][] = $region;

            } else {
                $arRows[$idProduct] = [
                    'data' => [
                        'PRODUCT_NAME' => $item['SALE_INTERNALS_BASKET_PRODUCT_NAME'],
                        'COUNT' => $count,
                        'SUM' => $sum,
                        'REGION' => '',
                        'regionList' => [$region],
                    ],
                ];
            }

            $arRows[$idProduct]['data']['regionList'] = array_unique($arRows[$idProduct]['data']['regionList']);
            $arRows[$idProduct]['data']['REGION'] = implode(', ', $arRows[$idProduct]['data']['regionList']);

        }

        return $this->sortView($arRows, $parameters['order']);
    }

    private function recursiveArraySearch($needle, $haystack)
    {
        foreach ($haystack as $key => $value) {
            $current_key = $key;
            if ($needle === $value or (is_array($value) && self::recursiveArraySearch($needle, $value) !== false)) {
                return $current_key;
            }
        }
        return false;
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
