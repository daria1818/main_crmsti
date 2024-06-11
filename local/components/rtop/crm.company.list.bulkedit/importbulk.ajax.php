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
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Crm;

global $APPLICATION;
Header('Content-Type: text/html; charset='.LANG_CHARSET);
Main\Loader::includeModule('crm');

if(!empty($_FILES))
{
	if($_FILES['importCompanyCSV']['error'] == 0)
	{
		$fileID = CFile::SaveFile($_FILES['importCompanyCSV'], "import");

		$path = $_SERVER['DOCUMENT_ROOT'] . CFile::GetPath($fileID);
		$text = file_get_contents($path);
		
		$data = explode("\n", $text);
		unset($data[0]);

		foreach ($data as $kk => $vv)
		{
			$vv = trim($vv);
			if(empty($vv))
				continue;
			$explode = (preg_match("/,\"/", $vv) ? ",\"" : ";");
            $tmp = explode($explode, $vv);
            $data[$kk] = $tmp;
       	}

       	if(!empty($data))
       	{
       		$names = array_column($data, '0');
       		
       		foreach($names as &$name)
       		{
       			$name = trim(str_replace('"""', '"', $name));
       			$name = trim(str_replace('""', '"', $name));
       			if($name[0] == '"')
       				$name = substr($name, 1, strlen($name)-1);
       		}
       		
       		$companies = \Bitrix\Crm\CompanyTable::getList([
       			'filter' => [
       				'=TITLE' => $names
       			],
       			'select' => ['ID', 'TITLE']
       		])->fetchAll();

       		if(!empty($companies))
       		{
       			$IDS = array_column($companies, 'ID');
       			$titles = array_column($companies, 'TITLE');
       			$diff = array_values(array_diff($names, $titles));
       		}		
       	}       	

       	CFile::Delete($fileID);

       	echo json_encode([
       		'url' => empty($IDS) ? '' : '/crm/company/bulk_edit/?CUSTOM_FILTER_IDS=' . implode(',', $IDS),
       		'diff' => $diff
       	]);
	}
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
die();