<?
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

$siteID = isset($_REQUEST['site'])? mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site']), 0, 2) : '';
if($siteID !== '')
{
	define('SITE_ID', $siteID);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Main\UI\Filter\DateType;

Main\Localization\Loc::loadMessages(__FILE__);

if(!Main\Loader::includeModule('crm'))
{
	$result = array('ERROR' => Main\Localization\Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));
}
elseif(!(\CCrmContact::CheckReadPermission() && check_bitrix_sessid()))
{
	$result = array('ERROR' => Main\Localization\Loc::getMessage('CRM_ACCESS_DENIED'));
}
else
{
	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
	if($action === '')
	{
		$action = 'list';
	}

	$filterFlags = Crm\Filter\ContactSettings::FLAG_NONE;
	$enableOutmodedFields = Crm\Settings\ContactSettings::getCurrent()->areOutmodedRequisitesEnabled();
	if($enableOutmodedFields)
	{
		$filterFlags |= Crm\Filter\ContactSettings::FLAG_ENABLE_ADDRESS;
	}

	$filter = \Bitrix\Crm\Filter\Factory::createEntityFilter(
		new \Bitrix\Crm\Filter\ContactSettings(
			array(
				'ID' => isset($_REQUEST['filter_id']) ? $_REQUEST['filter_id'] : 'CRM_CONTACT_LIST_ORDER_V12',
				'flags' => $filterFlags
			)
		)
	);

	if($action === 'field')
	{
		$fieldID = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
		if($fieldID != 'DATE_ORDER')
			$field = $filter->getField($fieldID);
		if($field)
		{
			$result = Main\UI\Filter\FieldAdapter::adapt($field->toArray());
		}
		else
		{
			if($fieldID == 'DATE_ORDER')
			{
				$custom = [
					'id' => 'DATE_ORDER', 'name' => Main\Localization\Loc::getMessage('CRM_FILTER_FIELD_DATE_ORDER'), 'type' => 'date', 'selected' => true, 'default' => true, 'exclude' => array(
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
				$result = Main\UI\Filter\FieldAdapter::adapt($custom);
			}else
				$result = array('ERROR' => Main\Localization\Loc::getMessage('CRM_FILTER_FIELD_NOT_FOUND'));
		}
	}
	elseif($action === 'list')
	{
		$result = array();
		foreach($filter->getFields() as $field)
		{
			$result[] = Main\UI\Filter\FieldAdapter::adapt($field->toArray(array('lightweight' => true)));
		}
		$custom = [
			'id' => 'DATE_ORDER', 'name' => Main\Localization\Loc::getMessage('CRM_FILTER_FIELD_DATE_ORDER'), 'type' => 'date', 'selected' => true, 'default' => true, 'exclude' => array(
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
		$result[] = Main\UI\Filter\FieldAdapter::adapt($custom);
	}
	else
	{
		$result = array('ERROR' => Main\Localization\Loc::getMessage('CRM_FILTER_ACTION_NOT_SUPPORTED'));
	}
}

$response = Main\Context::getCurrent()->getResponse()->copyHeadersTo(new Main\Engine\Response\Json($result));
Main\Application::getInstance()->end(0, $response);