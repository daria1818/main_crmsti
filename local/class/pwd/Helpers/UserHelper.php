<?php

namespace Pwd\Helpers;

use spaceonfire\BitrixTools\CacheMap\UserGroupCacheMap,
    \CSite;

final class UserHelper
{
    private const MODERATOR_OF_FORM = 'MODERATOR_OF_FORM';
    private const BRAND_MANAGER = 'BRAND_MANAGER';
    private const MANAGER_CLIENT_DEPARTMENT = 'MANAGER_CLIENT_DEPARTMENT';

    /**
     * Проверка на права админа
     * @return bool
     */
    public static function isAdmin()
    {
        global $USER;
        return $USER->IsAuthorized() && $USER->IsAdmin();
    }

    /**
     * Получить ID группы
     * @return bool
     */
    public static function getId($code)
    {
        return UserGroupCacheMap::getId($code);
    }

    /**
     * Проверка на права модератора форм
     * @return bool
     */
    public static function isModeratorOfForms()
    {
        global $USER;
        return $USER->IsAuthorized()
            && (
                CSite::InGroup([UserGroupCacheMap::getId(self::MODERATOR_OF_FORM)])
                || $USER->IsAdmin()
            );
    }

    /**
     * Проверка на права доступа к новым отчетам
     * @return bool
     */
    public static function isViewsOfReports()
    {
        global $USER;
        return $USER->IsAuthorized()
            && (
                CSite::InGroup([UserGroupCacheMap::getId(self::BRAND_MANAGER)]) ||
                CSite::InGroup([UserGroupCacheMap::getId(self::MANAGER_CLIENT_DEPARTMENT)]) ||
                $USER->IsAdmin()
            );
    }
}
