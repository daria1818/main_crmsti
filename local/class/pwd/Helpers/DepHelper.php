<?php

namespace Pwd\Helpers;

final class DepHelper
{
    public static function getDep(): array
    {
        global $USER;
        $dep = \CIntranetUtils::GetUserDepartments($USER->GetId());
        $depData = \CIntranetUtils::GetDepartmentsData($dep);

        $deps = [];
        foreach($depData as $dep){
            $deps[] = $dep;
        }

        return $deps;
    }
}
