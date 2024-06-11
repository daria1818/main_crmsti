<?php


namespace Pwd\Events;


class Crm
{

    public static function addReports(&$items)
    {
        $items[] = [
            'ID' => 'MREPORTS',
            'MENU_ID' => 'menu_crm_m-reports',
            'NAME' => 'Отчет по линейкам',
            'TITLE' => 'Отчет по линейкам',
            'URL' => '/crm/m-reports/',
        ];
    }

}