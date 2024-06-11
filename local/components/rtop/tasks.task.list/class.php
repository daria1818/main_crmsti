<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Grid;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Page\Asset;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Main\ORM\Query;
use Bitrix\Main\Entity;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class RtopTasksTaskListComponent extends TasksBaseComponent
{
	private $templatePage = '';
    private $sListId = '';

    /** @var FilterOptions */
    private $oFilterOptions;

    /** @var PageNavigation */
    private $oPageNavigation;

    /** @var GridOptions */
    private $oGridOptions;
    protected $enableNextPage = false;

    public function onIncludeComponentLang()
    {
        Loc::loadMessages(__FILE__);
    }

    public function onPrepareComponentParams($params)
    {   
        try {            
            $this->loaderModules();
            $this->initPermissions();
        } catch (Throwable $throwable) {
            ShowError($throwable->getMessage());
            exit();
        }

        $classHash = explode('\\', __CLASS__);

        $arParams['GRID_ID'] = $arParams['GRID_ID'] ?? array_pop($classHash) . $arParams['TYPE_REPORTS'];

        $arParams['SHOW_FILTER'] = $arParams['SHOW_FILTER'] === 'N' ? 'N' : 'Y';
        $arParams['GRID_SHOW_ROW_CHECKBOXES'] = false;

        $arParams['DEFAULT_FIELDS'] = [
            'TITLE',
            'CHANGED_DATE',
            'DEADLINE',
            'ORIGINATOR_NAME',           
            'RESPONSIBLE_NAME',
            'STATUS',
            'GROUP_NAME'
        ];

        $arParams['FILTER_FIELDS'] = [
            'RESPONSIBLE_ID',
            'CREATED_BY',
            'DEADLINE',
            'STATUS'
        ];

        $arParams['ALLOWED_FIELDS'] = $arParams['DEFAULT_FIELDS'];

        return $arParams;
    }

    private function loaderModules()
    {
        $arModules = ['tasks', 'crm'];

        foreach ($arModules as $module) {
            if (!Loader::includeModule($module)) {
                throw new Exception('Could not load ' . $module . ' module');
            }
        }
    }

    private function initPermissions()
    {
        global $USER;
        if (!$USER->IsAdmin()) {
            throw new Exception('Доступ запрещен');
        }
    }

    public function executeComponent()
    {
        CJSCore::Init(['jquery2', 'fx', 'admin', 'filter']);
        Asset::getInstance()->addJs('/bitrix/js/main/core/core_admin_interface.js');
        if (!$this->startResultCache()) {
            return;
        }

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
        $arFilter = $this->getMyFilter();  
        $arSort = $this->getSort();
        $limit = $this->arParams['SHOW_ALL_RECORDS'] == 'Y' ? 0 : $this->oPageNavigation->getLimit();

        //$this->processGridActions($arFilter);

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
        		case 'RESPONSIBLE_ID':
        		case 'CREATED_BY':
        			$filterField[] = [
                        'id' => $name,
                        'name' => Loc::getMessage('TASKS_FIELD_' . $name),
                        'default' => true,
                        'type' => 'dest_selector',
                        'params' => array(
                            'context' => 'CRM_WIDGET_FILTER_' . $name,
                            'multiple' => 'Y',
                            'contextCode' => 'U',
                            'enableAll' => 'N',
                            'enableSonetgroups' => 'N',
                            'allowEmailInvitation' => 'N',
                            'allowSearchEmailUsers' => 'N',
                            'departmentSelectDisable' => 'N',
                            'isNumeric' => 'Y',
                            'prefix' => 'U',
                        )
                    ];
        			break;
        		case 'DEADLINE':
                    $filterField[] = ['id' => $name, 'name' => Loc::getMessage('TASKS_FIELD_' . $name), 'type' => 'date'];
                    break;
                case 'STATUS':
                	$filterField[] = ['id' => $name, 'name' => Loc::getMessage('TASKS_FIELD_' . $name), 'type' => 'list', 'params' => ['multiple' => 'Y'], 'items' => [
                        CTasks::STATE_PENDING => Loc::getMessage('TASKS_STATUS_2'),
                        CTasks::STATE_IN_PROGRESS => Loc::getMessage('TASKS_STATUS_3'),
                        CTasks::STATE_SUPPOSEDLY_COMPLETED => Loc::getMessage('TASKS_STATUS_4'),
                        CTasks::STATE_COMPLETED => Loc::getMessage('TASKS_STATUS_5'),
                        CTasks::STATE_DEFERRED => Loc::getMessage('TASKS_STATUS_6'),
                    ]];
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
                'name' => Loc::getMessage("TASKS_HEAD_" . $field),
                'sort' => (in_array($field,['ORIGINATOR_NAME', 'RESPONSIBLE_NAME', 'GROUP_NAME']) ? "" : $field),
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

    private function getMyFilter()
    {
        $filters = $this->oFilterOptions->getFilter();

        $setFilter = [];

        foreach($filters as $code => $value){
            if(empty($value)){
                continue;
            }
            switch($code){
                case 'DEADLINE_from':
                    if(!empty($value)){
                        $setFilter['>DEADLINE'] = new DateTime($value);
                    }
                    break;
                case 'DEADLINE_to':
                    if(!empty($value)){
                        $setFilter['<=DEADLINE'] = new DateTime($value);
                    }
                    break;
                case 'STATUS':
                	$setFilter['='.$code] = $value;
                    break;
                case 'RESPONSIBLE_ID':
                    $setFilter['A_RESPONSIBLE_ID'] = $value;
                    break;
                case 'CREATED_BY':
                    $setFilter['A_CREATOR_ID'] = $value;
                	break;
            }
        }

        return $setFilter;
    }

    private function getSort()
    {
        return $this->oGridOptions->getSorting([
            'sort' => [
                'CHANGED_DATE' => 'desc',
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

        $select = [
        	'ID',
			'TITLE',
			'DESCRIPTION',
			'DEADLINE',
			'STATUS',
			'A_CREATOR_' => 'CREATOR',
			'A_RESPONSIBLE_'=> 'RESPONSIBLE',
			'CHANGED_DATE',
			'A_GROUP_'=> 'GROUP',
		];

		$defaultFilter = [
            'ZOMBIE' => 'N',
			// '!DEADLINE' => null,
			// '>=DEADLINE' => self::getDayStartDateTime(), // day start
			// '<=DEADLINE' => new DateTime(), // current time
			//'=STATUS' => [\CTasks::STATE_PENDING, \CTasks::STATE_IN_PROGRESS]
		];

		$merge = array_merge($defaultFilter, $parameters['filter'] ?: []); 

		$dbTasks = TaskTable::getList([
			'order' => $parameters['order'],
			'filter' => $merge,
			'select' => $select,
			'count_total' => true,
            'limit' => $parameters['limit'],
            'offset' => $parameters['offset']
		]);

        $taskList = $dbTasks->fetchAll();

        $this->arResult["ROWS_COUNT"] = $dbTasks->getCount();
        $this->oPageNavigation->setRecordCount($dbTasks->getCount());

        $this->enableNextPage = $this->oPageNavigation->getCurrentPage() < $this->oPageNavigation->getPageCount();

		foreach($taskList ?:[] as $task)
		{
            $taskUrl = 'https://' . SITE_SERVER_NAME . "/company/personal/user/" . $task['A_RESPONSIBLE_ID'] . "/tasks/task/view/" . $task['ID'] . "/";
			$arRows[$task['ID']] = [
				'data' => [
					'TITLE' => '<a href="' . $taskUrl . '">' . $task['TITLE'] . "</a>",
					'CHANGED_DATE' => $task['CHANGED_DATE']->toString(),
					'DEADLINE' => !empty($task['DEADLINE']) ? $task['DEADLINE']->toString() : $this->getHtmlWithoutDedline(),
		            'ORIGINATOR_NAME' => $this->getHtmlUser(['ID' => $task['A_CREATOR_ID'], 'NAME' => $task['A_CREATOR_NAME'], 'LAST_NAME' => $task['A_CREATOR_LAST_NAME'], 'PERSONAL_PHOTO' => $task['A_CREATOR_PERSONAL_PHOTO']]),
		            'RESPONSIBLE_NAME' => $this->getHtmlUser(['ID' => $task['A_RESPONSIBLE_ID'], 'NAME' => $task['A_RESPONSIBLE_NAME'], 'LAST_NAME' => $task['A_RESPONSIBLE_LAST_NAME'], 'PERSONAL_PHOTO' => $task['A_RESPONSIBLE_PERSONAL_PHOTO']]),
                    'STATUS' => Loc::getMessage('TASKS_STATUS_' . $task['STATUS']),
		            'GROUP_NAME' => $task['A_GROUP_ID'] > 0 ? $task['A_GROUP_NAME'] : "",
		        ]
			];
		}

        return $this->sortView($arRows, $parameters['order']);
    }

    private function sortView($arRow, $order)
    {
        usort($arRow, function ($a, $b) use ($order)
        {
            $field = array_key_first($order);
            $order = current($order);

            if(in_array($field, ['CHANGED_DATE', 'DEADLINE']))
            {
                $valueA = strtotime(strip_tags($a['data'][$field]));
                $valueB = strtotime(strip_tags($b['data'][$field]));
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

    private static function formatDate($date): string
    {
        if (
            !$date
            || !($date instanceof \Bitrix\Main\Type\DateTime)
        )
        {
            return '-';
        }

        return \FormatDate('x', $date->toUserTime()->getTimestamp(), (time() + \CTimeZone::getOffset()));
    }

    private static function getDayStartDateTime(): DateTime
	{
		$now = new Bitrix\Tasks\Util\Type\DateTime();
		$structure = $now->getTimeStruct();
		$now->add('-T'.($structure['SECOND'] + 60 * $structure['MINUTE'] + 3600 * $structure['HOUR']).'S');

		return $now;
	}

    private function getHtmlUser($user)
    {
        return '<div class="tasks-grid-username-wrapper">
            <a class="tasks-grid-username" href="https://' . SITE_SERVER_NAME .'/company/personal/user/' . $user['ID'] . '/">
                <span class="tasks-grid-avatar ui-icon ui-icon-common-user">
                    ' . (!empty($user['PERSONAL_PHOTO']) ? '<i style="background-image: url(' . CFile::GetPath($user['PERSONAL_PHOTO']) . ')"></i>' : '<i></i>') . '
                </span>
                <span class="tasks-grid-username-inner">' . trim($user['NAME'] . " " . $user['LAST_NAME']) . '</span>
            </a>
        </div>';
    }

    private function getHtmlWithoutDedline()
    {
        return '<div class="main-grid-labels">
            <span class="ui-label ui-label-light ui-label-fill ui-label-link">
                <span class="ui-label-inner">
                    <span class="bxt-tasks-grid-deadline">Без срока</span>
                </span>
            </span>
        </div>';
    }
}