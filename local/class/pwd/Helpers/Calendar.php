<?php


namespace Pwd\Helpers;


use Bitrix\Calendar\Ui\CalendarFilter;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use \Bitrix\Calendar\UserSettings;
use Bitrix\Rest\PlacementTable;
use CAccess;
use CCalendarLocation;
use CCalendarRestService;
use CCalendarSect;
use CCalendarSync;
use CCalendarType;
use CComponentEngine;
use CDavExchangeCalendar;
use CIBlock;
use \Bitrix\Calendar\Integration\Bitrix24\Limitation;
use CUserCounter;

class Calendar extends \CCalendar
{

    public static
        $id = false,
        $instance,
        $CALENDAR_MAX_DATE,
        $CALENDAR_MIN_DATE,
        $type,
        $arTypes,
        $ownerId = 0,
        $settings,
        $siteId,
        $userSettings = [],
        $pathToUser,
        $bOwner,
        $userId,
        $curUserId,
        $userMeetingSection,
        $meetingSections = [],
        $crmSections = [],
        $offset,
        $arTimezoneOffsets = [],
        $perm = [],
        $isArchivedGroup = false,
        $userNameTemplate = "#NAME# #LAST_NAME#",
        $bSuperpose,
        $bExtranet,
        $bIntranet,
        $bWebservice,
        $arSPTypes = [],
        $showTasks,
        $viewTaskPath = '',
        $editTaskPath = '',
        $actionUrl,
        $path = '',
        $outerUrl,
        $accessNames = [],
        $bSocNet,
        $bAnonym,
        $allowReserveMeeting = true,
        $SectionsControlsDOMId = 'sidebar',
        $arAccessTask = [],
        $ownerNames = [],
        $meetingRoomList,
        $cachePath = "calendar/",
        $cacheTime = 2592000, // 30 days by default
        $bCache = true,
        $readOnly,
        $pathesForSite = false,
        $pathes = [], // links for several sites
        $userManagers = [],
        $arUserDepartment = [],
        $bAMPM = false,
        $bWideDate = false,
        $arExchEnabledCache = [],
        $silentErrorMode = false,
        $weekStart,
        $bCurUserSocNetAdmin,
        $serverPath,
        $pathesList = array('path_to_user','path_to_user_calendar','path_to_group','path_to_group_calendar','path_to_vr','path_to_rm'),
        $pathesListEx = null,
        $isGoogleApiEnabled = null,
        $errors = [],
        $timezones = [];

    function Init($params)
    {
        global $USER, $APPLICATION;
        $access = new CAccess();
        $access->UpdateCodes();
        if (!$USER || !is_object($USER))
            $USER = new \CUser;
        // Owner params
        self::$siteId = isset($params['siteId']) ? $params['siteId'] : SITE_ID;
        self::$type = $params['type'];
        self::$arTypes = CCalendarType::GetList();
        self::$bIntranet = Calendar::IsIntranetEnabled();
        self::$bSocNet = self::IsSocNet();
        self::$userId = (isset($params['userId']) && $params['userId'] > 0) ? intval($params['userId']) : Calendar::GetCurUserId(true);
        self::$bOwner = self::$type == 'user' || self::$type == 'group';
        self::$settings = self::GetSettings();
        self::$userSettings = UserSettings::get();
        self::$pathesForSite = self::GetPathes(self::$siteId);
        self::$pathToUser = self::$pathesForSite['path_to_user'];
        self::$bSuperpose = $params['allowSuperpose'] != false && self::$bSocNet;
        self::$bAnonym = !$USER || !$USER->IsAuthorized();
        self::$userNameTemplate = self::$settings['user_name_template'];
        self::$bAMPM = IsAmPmMode();
        self::$bWideDate = mb_strpos(FORMAT_DATETIME, 'MMMM') !== false;
        self::$id = $this->GetId();

        if (isset($params['SectionControlsDOMId']))
            self::$SectionsControlsDOMId = $params['SectionControlsDOMId'];

        if (self::$bOwner && isset($params['ownerId']) && $params['ownerId'] > 0)
            self::$ownerId = intval($params['ownerId']);

        self::$showTasks = (self::$type == 'user' || self::$type == 'group')
            && $params['showTasks'] !== false
            && $params['viewTaskPath']
            && Loader::includeModule('tasks')
            && self::$userSettings['showTasks'] != 'N';

        if (self::$showTasks)
        {
            self::$viewTaskPath = $params['viewTaskPath'];
            self::$editTaskPath = $params['editTaskPath'];
        }

        self::GetPermissions(array(
            'type' => self::$type,
            'bOwner' => self::$bOwner,
            'userId' => self::$userId,
            'ownerId' => self::$ownerId
        ));

        // Cache params
        if (isset($params['cachePath']))
            self::$cachePath = $params['cachePath'];
        if (isset($params['cacheTime']))
            self::$cacheTime = $params['cacheTime'];
        self::$bCache = self::$cacheTime > 0;

        // Urls
        $page = preg_replace(
            array(
                "/EVENT_ID=.*?\&/i",
                "/EVENT_DATE=.*?\&/i",
                "/CHOOSE_MR=.*?\&/i",
                "/action=.*?\&/i",
                "/bx_event_calendar_request=.*?\&/i",
                "/clear_cache=.*?\&/i",
                "/bitrix_include_areas=.*?\&/i",
                "/bitrix_show_mode=.*?\&/i",
                "/back_url_admin=.*?\&/i",
                "/IFRAME=.*?\&/i",
                "/IFRAME_TYPE=.*?\&/i"
            ),
            "", $params['pageUrl'].'&'
        );
        $page = preg_replace(array("/^(.*?)\&$/i","/^(.*?)\?$/i"), "\$1", $page);
        self::$actionUrl = $page;

        if (self::$bOwner && !empty(self::$ownerId))
            self::$path = self::GetPath(self::$type, self::$ownerId, true);
        else
            self::$path = Calendar::GetServerPath().$page;

        self::$outerUrl = $APPLICATION->GetCurPageParam('', array("action", "bx_event_calendar_request", "clear_cache", "bitrix_include_areas", "bitrix_show_mode", "back_url_admin", "SEF_APPLICATION_CUR_PAGE_URL", "EVENT_ID", "EVENT_DATE", "CHOOSE_MR"), false);

        // *** Meeting room params ***
        $RMiblockId = self::$settings['rm_iblock_id'];
        self::$allowReserveMeeting = $params["allowResMeeting"] && $RMiblockId > 0;

        if(self::$allowReserveMeeting && !$USER->IsAdmin() && (CIBlock::GetPermission($RMiblockId) < "R"))
            self::$allowReserveMeeting = false;
    }


    public function Show($params = [])
    {
        global $APPLICATION;
        $arType = false;

        foreach (self::$arTypes as $type) {
            if (self::$type == $type['XML_ID'])
                $arType = $type;
        }

        if (!$arType) {
            $APPLICATION->ThrowException('[EC_WRONG_TYPE] ' . Loc::getMessage('EC_WRONG_TYPE'));
            return false;
        }

        if (!\CCalendarType::CanDo('calendar_type_view', self::$type)) {
            $APPLICATION->ThrowException(Loc::getMessage("EC_ACCESS_DENIED"));
            return false;
        }

        $startupEvent = false;
        $showNewEventDialog = false;
        //Show new event dialog
        if (isset($_GET['EVENT_ID'])) {
            if ($_GET['EVENT_ID'] == 'NEW') {
                $showNewEventDialog = true;
            } elseif (mb_substr($_GET['EVENT_ID'], 0, 4) == 'EDIT') {
                $startupEvent = self::GetStartUpEvent(intval(mb_substr($_GET['EVENT_ID'], 4)));
                if ($startupEvent)
                    $startupEvent['EDIT'] = true;
                if ($startupEvent['DT_FROM']) {
                    $ts = self::Timestamp($startupEvent['DT_FROM']);
                    $init_month = date('m', $ts);
                    $init_year = date('Y', $ts);
                }
            } // Show popup event at start
            elseif ($startupEvent = self::GetStartUpEvent($_GET['EVENT_ID'])) {
                $eventFromTs = self::Timestamp($startupEvent['DATE_FROM']);
                $currentDateTs = self::Timestamp($_GET['EVENT_DATE']);

                if ($currentDateTs > $eventFromTs) {
                    $startupEvent['~CURRENT_DATE'] = self::Date($currentDateTs, false);
                    $init_month = date('m', $currentDateTs);
                    $init_year = date('Y', $currentDateTs);
                } else {
                    $init_month = date('m', $eventFromTs);
                    $init_year = date('Y', $eventFromTs);
                }
            }
        }

        if (!$init_month && !$init_year && $params["initDate"] <> '' && mb_strpos($params["initDate"], '.') !== false) {
            $ts = self::Timestamp($params["initDate"]);
            $init_month = date('m', $ts);
            $init_year = date('Y', $ts);
        }

        if (!isset($init_month))
            $init_month = date("m");
        if (!isset($init_year))
            $init_year = date("Y");

        $id = $this->GetId();

        $weekHolidays = [];
        if (isset(self::$settings['week_holidays'])) {
            $days = ['MO' => 0, 'TU' => 1, 'WE' => 2, 'TH' => 3, 'FR' => 4, 'SA' => 5, 'SU' => 6];
            foreach (self::$settings['week_holidays'] as $day)
                $weekHolidays[] = $days[$day];
        } else
            $weekHolidays = [5, 6];

        $yearHolidays = [];
        if (isset(self::$settings['year_holidays'])) {
            foreach (explode(',', self::$settings['year_holidays']) as $date) {
                $date = trim($date);
                $ardate = explode('.', $date);
                if (count($ardate) == 2 && $ardate[0] && $ardate[1])
                    $yearHolidays[] = intval($ardate[0]) . '.' . (intval($ardate[1]) - 1);
            }
        }

        $yearWorkdays = [];
        if (isset(self::$settings['year_workdays'])) {
            foreach (explode(',', self::$settings['year_workdays']) as $date) {
                $date = trim($date);
                $ardate = explode('.', $date);
                if (count($ardate) == 2 && $ardate[0] && $ardate[1])
                    $yearWorkdays[] = intval($ardate[0]) . '.' . (intval($ardate[1]) - 1);
            }
        }

        $bSyncPannel = self::IsPersonal();
        $bExchange = Calendar::IsExchangeEnabled() && self::$type == 'user';
        $bExchangeConnected = $bExchange && CDavExchangeCalendar::IsExchangeEnabledForUser(self::$ownerId);
        $bCalDAV = Calendar::IsCalDAVEnabled() && self::$type == "user";
        $bGoogleApi = Calendar::isGoogleApiEnabled() && self::$type == "user";
        $bWebservice = Calendar::IsWebserviceEnabled();
        $bExtranet = Calendar::IsExtranetEnabled();

        $userTimezoneOffsetUTC = self::GetCurrentOffsetUTC(self::$userId);
        $userTimezoneName = self::GetUserTimezoneName(self::$userId, false);
        $userTimezoneDefault = '';

        // We don't have default timezone for this offset for this user
        // We will ask him but we should suggest some suitable for his offset
        if (!$userTimezoneName) {
            $userTimezoneDefault = self::GetGoodTimezoneForOffset($userTimezoneOffsetUTC);
        }

        $JSConfig = [
            'id' => $id,
            'type' => self::$type,
            'userId' => self::$userId,
            'userName' => self::GetUserName(self::$userId), // deprecated
            'ownerId' => self::$ownerId,
            'user' => [
                'id' => self::$userId,
                'name' => self::GetUserName(self::$userId),
                'url' => self::GetUserUrl(self::$userId),
                'avatar' => self::GetUserAvatarSrc(self::$userId),
                'smallAvatar' => self::GetUserAvatarSrc(self::$userId, ['AVATAR_SIZE' => 18]),
            ],
            'perm' => $arType['PERM'], // Permissions from type
            'permEx' => self::$perm,
            'showTasks' => self::$showTasks,
            'sectionControlsDOMId' => self::$SectionsControlsDOMId,
            'week_holidays' => $weekHolidays,
            'year_holidays' => $yearHolidays,
            'year_workdays' => $yearWorkdays,
            'init_month' => $init_month,
            'init_year' => $init_year,
            'pathToUser' => self::$pathToUser,
            'path' => self::$path,
            'actionUrl' => self::$actionUrl,
            'settings' => self::$settings,
            'userSettings' => self::$userSettings,
            'bAnonym' => self::$bAnonym,
            'bIntranet' => self::$bIntranet,
            'bWebservice' => $bWebservice,
            'bExtranet' => $bExtranet,
            'bSocNet' => self::$bSocNet,
            'bExchange' => $bExchangeConnected,
            'startupEvent' => $startupEvent,
            'workTime' => [self::$settings['work_time_start'], self::$settings['work_time_end']], // Decrecated !!
            'userWorkTime' => [self::$settings['work_time_start'], self::$settings['work_time_end']],
            'meetingRooms' => self::GetMeetingRoomList([
                'RMiblockId' => self::$settings['rm_iblock_id'],
                'pathToMR' => self::$pathesForSite['path_to_rm'],
            ]),
            'allowResMeeting' => self::$allowReserveMeeting,
            'bAMPM' => self::$bAMPM,
            'WDControllerCID' => 'UFWD' . $id,
            'userTimezoneOffsetUTC' => $userTimezoneOffsetUTC,
            'userTimezoneName' => $userTimezoneName,
            'userTimezoneDefault' => $userTimezoneDefault,
            'sectionCustomization' => UserSettings::getSectionCustomization(self::$userId),
            'locationFeatureEnabled' => !self::IsBitrix24() || \Bitrix\Bitrix24\Feature::isFeatureEnabled("calendar_location"),
            'eventWithEmailGuestLimit' => Limitation::getEventWithEmailGuestLimit(),
            'countEventWithEmailGuestAmount' => Limitation::getCountEventWithEmailGuestAmount(),
        ];

        $JSConfig['lastSection'] = CCalendarSect::GetLastUsedSection(self::$type, self::$ownerId, self::$userId);

        if (self::$type == 'user' && self::$userId != self::$ownerId) {
            $JSConfig['ownerUser'] = [
                'id' => self::$ownerId,
                'name' => self::GetUserName(self::$ownerId),
                'url' => self::GetUserUrl(self::$ownerId),
                'avatar' => self::GetUserAvatarSrc(self::$ownerId),
                'smallAvatar' => self::GetUserAvatarSrc(self::$ownerId, ['AVATAR_SIZE' => 18]),
            ];
        }

        $placementParams = false;
        if (Loader::includeModule('rest')) {
            $placementParams = [
                'gridPlacementCode' => CCalendarRestService::PLACEMENT_GRID_VIEW,
                'gridPlacementList' => PlacementTable::getHandlersList(CCalendarRestService::PLACEMENT_GRID_VIEW),
                'serviceUrl' => '/bitrix/components/bitrix/app.layout/lazyload.ajax.php?&site=' . SITE_ID . '&' . bitrix_sessid_get(),
            ];
        }
        $JSConfig['placementParams'] = $placementParams;

        if (self::$type == 'user' && self::$userId == self::$ownerId) {
            $JSConfig['counters'] = [
                'invitation' => CUserCounter::GetValue(self::$userId, 'calendar'),
            ];

            $JSConfig['filterId'] = CalendarFilter::getFilterId(self::$type, self::$ownerId, self::$userId);
        }

        // Access permissons for type
        if (CCalendarType::CanDo('calendar_type_edit_access', self::$type))
            $JSConfig['TYPE_ACCESS'] = $arType['ACCESS'];

        if ($bCalDAV || $bGoogleApi) {
            self::InitExternalCalendarsSyncParams($JSConfig);
        }

        if ($bSyncPannel) {
            $syncInfoParams = [
                'userId' => self::$userId,
                'type' => self::$type,
            ];
            $JSConfig['syncInfo'] = CCalendarSync::GetSyncInfo($syncInfoParams);
            $JSConfig['syncLinks'] = CCalendarSync::GetSyncLinks($syncInfoParams);
            $JSConfig['isSetSyncCaldavSettings'] = CCalendarSync::isSetSyncCaldavSettings($syncInfoParams['type']);

            $JSConfig['displayMobileBanner'] = CCalendarSync::checkMobileBannerDisplay()
                && !$JSConfig['syncInfo']['iphone']['connected']
                && !$JSConfig['syncInfo']['android']['connected'];
        } else {
            $JSConfig['syncInfo'] = false;
        }

        $followedSectionList = UserSettings::getFollowedSectionIdList(self::$userId);
        /* Фикс календаря. Добавленные календари не должны отображаться в личном календаре */
        if(self::$type == 'user'){
            foreach ($followedSectionList as $key=>$value){
                $calendarData = CCalendarSect::GetById($value);
                if($calendarData['CAL_TYPE'] == 'user'){
                    unset($followedSectionList[$key]);
                }
            }
        }

        $arSectionIds = [];
        $hiddenSections = UserSettings::getHiddenSections(self::$userId);

        self::$userMeetingSection = Calendar::GetCurUserMeetingSection();
        //  **** GET SECTIONS ****
        $sections = [];
        $sectionList = self::GetSectionList([
            'ADDITIONAL_IDS' => $followedSectionList,
            'checkPermissions' => true,
            'getPermissions' => true,
            'getImages' => true,
        ]);

        $sectionList = array_merge($sectionList, Calendar::getSectionListAvailableForUser(self::$userId));
        $sectionIdList = [];
        foreach ($sectionList as $i => $section) {
            if (!in_array(intval($section['ID']), $sectionIdList)) {
                $sections[] = $section;
                $sectionIdList[] = intval($section['ID']);
            }
        }

        $readOnly = !self::$perm['edit'] && !self::$perm['section_edit'];

        if (self::$type == 'user' && self::$ownerId != self::$userId)
            $readOnly = true;

        if (self::$bAnonym)
            $readOnly = true;

        $bCreateDefault = !self::$bAnonym;

        if (self::$type == 'user')
            $bCreateDefault = self::$ownerId == self::$userId;

        $additonalMeetingsId = [];
        $groupOrUser = self::$type == 'user' || self::$type == 'group';
        if ($groupOrUser) {
            $noEditAccessedCalendars = true;
        }

        $trackingUsers = [];
        $trackingGroups = [];

        foreach ($sections as $i => $section) {
            $sections[$i]['~IS_MEETING_FOR_OWNER'] = $section['CAL_TYPE'] == 'user' && $section['OWNER_ID'] != self::$userId && Calendar::GetMeetingSection($section['OWNER_ID']) == $section['ID'];

            if (!in_array($section['ID'], $hiddenSections) && $section['ACTIVE'] !== 'N') {
                $arSectionIds[] = $section['ID'];
                // It's superposed calendar of the other user and it's need to show user's meetings
                if ($sections[$i]['~IS_MEETING_FOR_OWNER'])
                    $additonalMeetingsId[] = ['ID' => $section['OWNER_ID'], 'SECTION_ID' => $section['ID']];
            }

            // We check access only for main sections because we can't edit superposed section
            if ($groupOrUser && $sections[$i]['CAL_TYPE'] == self::$type &&
                $sections[$i]['OWNER_ID'] == self::$ownerId) {
                if ($noEditAccessedCalendars && $section['PERM']['edit'])
                    $noEditAccessedCalendars = false;

                if ($readOnly && ($section['PERM']['edit'] || $section['PERM']['edit_section']) && !self::$isArchivedGroup)
                    $readOnly = false;
            }

            if (self::$bSuperpose && in_array($section['ID'], $followedSectionList)) {
                $sections[$i]['SUPERPOSED'] = true;
            }

            if ($bCreateDefault && $section['CAL_TYPE'] == self::$type && $section['OWNER_ID'] == self::$ownerId)
                $bCreateDefault = false;

            if ($sections[$i]['SUPERPOSED']) {
                $type = $sections[$i]['CAL_TYPE'];
                if ($type == 'user') {
                    $path = self::$pathesForSite['path_to_user_calendar'];
                    $path = CComponentEngine::MakePathFromTemplate($path, ["user_id" => $sections[$i]['OWNER_ID']]);
                    $trackingUsers[] = $sections[$i]['OWNER_ID'];
                } elseif ($type == 'group') {
                    $path = self::$pathesForSite['path_to_group_calendar'];
                    $path = CComponentEngine::MakePathFromTemplate($path, ["group_id" => $sections[$i]['OWNER_ID']]);
                    $trackingGroups[] = $sections[$i]['OWNER_ID'];
                } else {
                    $path = self::$pathesForSite['path_to_type_' . $type];
                }
                $sections[$i]['LINK'] = $path;
            }
        }

        if ($groupOrUser && $noEditAccessedCalendars && !$bCreateDefault)
            $readOnly = true;

        self::$readOnly = $readOnly;
        if (!$readOnly && $showNewEventDialog) {
            $JSConfig['showNewEventDialog'] = true;
            $JSConfig['bChooseMR'] = isset($_GET['CHOOSE_MR']) && $_GET['CHOOSE_MR'] == "Y";
        }

        if (!in_array($JSConfig['lastSection'], $arSectionIds)) {
            $JSConfig['lastSection'] = $arSectionIds[0];
        }

        $JSConfig = array_merge($JSConfig, [
            'trackingUsersList' => UserSettings::getTrackingUsers(false, ['userList' => $trackingUsers]),
            'trackingGroupList' => UserSettings::getTrackingGroups(false, ['groupList' => $trackingGroups]),
        ]);

        //  **** GET TASKS ****
        if (self::$showTasks) {
            $JSConfig['viewTaskPath'] = self::$viewTaskPath;
            $JSConfig['editTaskPath'] = self::$editTaskPath;
        }

        // We don't have any section
        if ($bCreateDefault) {
            $fullSectionsList = $groupOrUser ? self::GetSectionList(['checkPermissions' => false, 'getPermissions' => false]) : [];

            // Section exists but it closed to this user (Ref. mantis:#64037)
            if (count($fullSectionsList) > 0) {
                $readOnly = true;
            } else {
                $defCalendar = CCalendarSect::CreateDefault([
                    'type' => Calendar::GetType(),
                    'ownerId' => Calendar::GetOwnerId(),
                ]);
                $arSectionIds[] = $defCalendar['ID'];
                $sections[] = $defCalendar;
                self::$userMeetingSection = $defCalendar['ID'];
            }
        }

        if (\CCalendarType::CanDo('calendar_type_edit', self::$type))
            $JSConfig['new_section_access'] = CCalendarSect::GetDefaultAccess(self::$type, self::$ownerId);

        $colors = ['#86B100', '#0092CC', '#00AFC7', '#DA9100', '#00B38C', '#DE2B24', '#BD7AC9', '#838FA0', '#AB7917', '#E97090'];

        $JSConfig['hiddenSections'] = $hiddenSections;
        $JSConfig['readOnly'] = $readOnly;

        // access
        $JSConfig['accessNames'] = self::GetAccessNames();
        $JSConfig['sectionAccessTasks'] = self::GetAccessTasks('calendar_section');
        $JSConfig['typeAccessTasks'] = self::GetAccessTasks('calendar_type');

        $JSConfig['bSuperpose'] = self::$bSuperpose;
        $JSConfig['additonalMeetingsId'] = $additonalMeetingsId;

        $selectedUserCodes = ['U' . self::$userId];
        if (self::$type == 'user') {
            $selectedUserCodes[] = 'U' . self::$ownerId;
        }

        $additionalParams = [
            'socnetDestination' => Calendar::GetSocNetDestination(false, $selectedUserCodes),
            'locationList' => CCalendarLocation::GetList(),
            'timezoneList' => Calendar::GetTimezoneList(),
            'defaultColorsList' => $colors,
            'formSettings' => [
                'slider_main' => UserSettings::getFormSettings('slider_main'),
            ],
        ];

        // Append Javascript files and CSS files, and some base configs
       \CCalendarSceleton::InitJS(
            $JSConfig,
            [
                'sections' => $sections,
            ],
            $additionalParams
        );
    }

}