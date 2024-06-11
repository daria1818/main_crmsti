<?php /** @noinspection AutoloadingIssuesInspection */

declare(strict_types=1);

namespace Pwd\Components;

use Bitrix\Main\Engine\Contract\Controllerable;
use Narrowspark\HttpStatus\Exception\UnauthorizedException;
use Pwd\Entity\FormQrGenerationTable;
use spaceonfire\BitrixTools\CacheMap\UserGroupCacheMap;
use spaceonfire\BitrixTools\Components\BaseComponent;
use spaceonfire\BitrixTools\Components\Property\ComponentPropertiesTrait;
use Bitrix\Main\Type\DateTime;
use Throwable;
use Da\QrCode\QrCode;

/**
 * Class QrGenerationFormComponent
 * @package Pwd\Components
 * @property-read bool $isModerator
 * @property-read int $assigned
 * @property-read int $contactId
 * @property-read int $task
 * @property-read bool $generationMode
 * @property-read array $row
 * @property-read array $params
 * @property-read array $specEnums
 * @property-read string $specCode
 * @property-read string $clinicNameCode
 * @property-read array $empl
 */
class QrGenerationFormComponent extends BaseComponent implements Controllerable
{
    use ComponentPropertiesTrait;

    /**
     * @var string[]
     */
    protected $needModules = ['iblock', 'crm', 'tasks'];

    /**
     * @var bool
     */
    public $isModerator;

    public $assigned;

    public $contactId;

    public $task;

    public $generationMode = true;

    public $row;

    public $params;

    public $specEnums;

    public $specCode;

    public $clinicNameCode;

    public $empl;


    /**
     * @inheritDoc
     */
    protected function getParamsTypes(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function canShowExceptionMessage(Throwable $exception): bool
    {
        if ($exception instanceof UnauthorizedException) {
            return true;
        }

        return parent::canShowExceptionMessage($exception);
    }

    /**
     * @inheritDoc
     */
    protected function init(): void
    {
        parent::init();
    }

    public function getData()
    {
        $eventAdminGroup = UserGroupCacheMap::getId('MODERATOR_OF_FORM');
        $this->isModerator = \CSite::InGroup([$eventAdminGroup]);

        $param = $this->request->getQuery("param") ?? $this->request->getPostList()->toArray()['hash'];
        if ($param || $this->request->isAjaxRequest()) $this->generationMode = false;

        $rsData = \CUserTypeEntity::GetList(array("ID" => "ASC"), array('LANG' => 'ru', 'ENTITY_ID' => 'CRM_CONTACT'));
        while ($field = $rsData->Fetch()) {
            if (($this->specCode) && ($this->clinicNameCode)) break;
            if ($field['EDIT_FORM_LABEL'] === 'Специализация') {
                $this->specCode = $field['FIELD_NAME'];
                $specId = $field['ID'];
                continue;
            }
            if ($field['EDIT_FORM_LABEL'] === 'Название клиники') {
                $this->clinicNameCode = $field['FIELD_NAME'];
                continue;
            }
        }

        if (!$this->generationMode) {
            $row = FormQrGenerationTable::getList([
                'select' => ['*'],
                'filter' => [
                    'UF_HASH' => $param
                ]
            ])->fetch();
            $this->row = $row;
            $this->assigned = $this->row['UF_RESPONSIBLE'];
            $params = $row['UF_PARAM'];
            if ($params) {
                $this->params = unserialize($params);
            } else die;

            $enums = [];
            $obEnum = new \CUserFieldEnum;
            $rsEnum = $obEnum->GetList(array(), array("USER_FIELD_ID" => $specId));
            while ($arEnum = $rsEnum->Fetch()) {
                $this->specEnums[] = $arEnum;
            }
        } else {
            $emp = '';
            $oDep = \CIntranetUtils::GetStructure();
            foreach ($oDep['DATA'] as $dep) {
                if ($dep['NAME'] === 'Бренд менеджеры'
                    || $dep['NAME'] === 'Клиентский отдел') {
                    foreach ($dep['EMPLOYEES'] as $depEmp) {
                        $emp = $emp . "$depEmp | ";
                    }

                }
            }

            $rsUsers = \CUser::GetList(($by = "personal_country"), ($order = "desc"), ['ID' => $emp]);
            $empFull = [];
            while ($user = $rsUsers->Fetch()) {
                $this->empl[] = ['ID' => $user['ID'], 'NAME' => $user['NAME'], 'LAST_NAME' => $user['LAST_NAME']];
            }

            $rsUsers = \CUser::GetList(($by = "personal_country"), ($order = "desc"), ['NAME' => 'Андрей', 'LAST_NAME' => 'Табаков'])->fetch();
            $user = ['ID' => $rsUsers['ID'], 'NAME' => $rsUsers['NAME'], 'LAST_NAME' => $rsUsers['LAST_NAME']];

            $this->empl[] = $user;
        }
    }

    /**
     * @inheritDoc
     */
    protected function executeProlog(): void
    {
        $this->getData();
    }

    /**
     * @inheritDoc
     */
    protected function executeMain(): void
    {
        $this->setResultCacheKeys([]);
    }

    /**
     * @inheritDoc
     */
    public function configureActions(): array
    {
        return [
            'addPage' => [
                'prefilters' => [
                    new \Bitrix\Main\Engine\ActionFilter\Authentication(),
                    new \Bitrix\Main\Engine\ActionFilter\HttpMethod([
                        \Bitrix\Main\Engine\ActionFilter\HttpMethod::METHOD_POST
                    ])
                ],
            ],
            'makeContact' => [
				'prefilters' => ''
			],
        ];
    }

    public function addFile($dir, $html)
    {
        $page = '';
        if (@file_put_contents(explode('local', __DIR__)[0] . '/' . $dir . '.php', $html)) $page = '/' . $dir . '.php';
        return $page;
    }

    public function addContact($firstName, $secondName, $phone, $email, $spec, $clinicName): void
    {
        $dbResult = \CCrmFieldMulti::GetList(
            [],
            [
                'ENTITY_ID' => 'CONTACT',
                'TYPE_ID' => 'EMAIL',
                'VALUE' => $email
            ]
        );
        $fields = $dbResult->Fetch();
        if ($fields) {
            $this->contactId = $fields['ELEMENT_ID'];
            return;
        }

        $arFields = array(
            "NAME" => $firstName,
            "LAST_NAME" => $secondName,
            "POST" => "",
            "OPENED" => "N",
            "EXPORT" => "Y",
            'FM' => array(
                'EMAIL' => array(
                    'n0' => array('VALUE' => $email, 'VALUE_TYPE' => 'WORK')
                ),
                'PHONE' => array(
                    'n0' => array('VALUE' => $phone, 'VALUE_TYPE' => 'WORK')
                )
            ),
            "ASSIGNED_BY_ID" => $this->assigned,
            $this->specCode => $spec,
            $this->clinicNameCode => $clinicName,
        );

		//$USER->Authorize(1);
        $oContact = new \CAllCrmContact();
        $iContactID = $oContact->Add($arFields);
		file_put_contents(__DIR__.'/1.txt', print_r($iContactID,true), FILE_APPEND);
file_put_contents(__DIR__.'/1.txt', print_r($iContactID,true), FILE_APPEND);
        $this->contactId = $iContactID;
		//$USER->Logout();
    }

    public function addTask($firstName, $secondName): void
    {
        $arFields = Array(
            "TITLE" => "CRM: Новый контакт " . $firstName . " " . $secondName,
            "DESCRIPTION" => "Контакт с формы",
            "RESPONSIBLE_ID" => $this->assigned,
            "GROUP_ID" => 3
        );

        $obTask = new \CTasks;
        $ID = $obTask->Add($arFields);
        $this->task = $ID;
    }

    public function addContactTaskConnect(): void
    {
        global $DB;
        $DB->PrepareFields("b_uts_tasks_task");
        $arFields = array(
            "VALUE_ID" => $this->task,
            "UF_CRM_TASK" => "'" . serialize(['C_' . $this->contactId]) . "'",
            "UF_TASK_WEBDAV_FILES" => "'" . serialize([]) . "'",
        );
        $DB->Insert("b_uts_tasks_task", $arFields, $err_mess . __LINE__);
    }

    public function makeContactAction()
    {
        try {
            $this->includeModules();
            $this->init();
            $this->getData();
            global $USER;
        } catch (Throwable $e) {
            return [];
        }

        $request = $this->request->getPostList()->toArray();
        $break = false;

		\CEventLog::Add([
		 	"SEVERITY" => "INFO",
			 "AUDIT_TYPE_ID" => "CONTACT_FORM",
			 "MODULE_ID" => "main",
			 "ITEM_ID" => 123,
			 "DESCRIPTION" => json_encode($request),
		]);

        if (!$break) {
            $this->addContact($request['first_name'], $request['second_name'], $request['phone'], $request['email'], $request['spec'], $request['clinic_name']);
            $this->addTask($request['first_name'], $request['second_name']);
            if ($this->contactId && $this->task) $this->addContactTaskConnect();
        }

        return [
            'request' => $request,
            'break' => $break,
            'contact' => $this->contactId,
            'task' => $this->task,
            'generationMode' => $this->generationMode,
            'ajax' => $this->request->isAjaxRequest(),
        ];
    }

    public function addPageAction()
    {
        try {
            $this->includeModules();
            $this->init();
            global $USER;
        } catch (Throwable $e) {
            return [];
        }

        $request = $this->request->getPostList()->toArray();
        $title = $request['title'];
        $manager = $request['manager'];
        $html = $request['html'];
        $date = $request['date'];
        $event = $request['event'];
        $city = $request['city'];
        $teacher = $request['teacher'];
        $titleTag = $request['title'] ?? "stionline.ru - интернет магазин для стоматологов и зубных техников";

        $dateNow = new DateTime();

        $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $url = explode('?', $url);
        $url = $url[0];

        $param = $paramOrig = [
            'date' => $date,
            'event' => $event,
            'city' => $city,
            'teacher' => $teacher,
            'titleTag' => $titleTag,
            'html' => $html,
            'title' => $title,
            'responsible' => $manager,
        ];

        $param = serialize($param);
        $paramHash = md5(serialize($param));
        $data = $url . "/contact-form/?param=" . $paramHash . "";
        $qrCode = (new QrCode($data))
            ->setSize(250)
            ->useForegroundColor(0, 0, 0);
        $qrGen = $qrCode->writeDataUri();
        $qrImg = '<img src="' . $qrCode->writeDataUri() . '" alt="QR Code" />';

        FormQrGenerationTable::add([
            'UF_TITLE' => $title,
            'UF_DESC' => $html,
            'UF_PARAM' => $param,
            'UF_DATE' => $dateNow,
            'UF_PAGE' => $data,
            'UF_PARAMS' => $param,
            'UF_HASH' => $paramHash,
            'UF_RESPONSIBLE' => $manager,
            'UF_QR' => $qrGen,
            'UF_CREATOR' => $USER->GetID(),
        ]);

        return [
            'src' => $data,
            'qrSrc' => $qrGen,
            'param' => $paramOrig,
        ];
    }
}
