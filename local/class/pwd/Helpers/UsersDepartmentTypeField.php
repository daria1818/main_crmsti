<?php
namespace PWD\Helpers;

use \Bitrix\Main,
    \Bitrix\Main\Localization\Loc,
    \Bitrix\Main\UserField,
    \Bitrix\Main\UserTable,
    \Bitrix\Iblock\SectionTable;

class UsersDepartmentTypeField
{
    /**
     * Метод возвращает массив описания собственного типа свойств
     * @return array
     */
    public static function GetUserTypeDescription()
    {
        return array(
            'PROPERTY_TYPE' => 'N',
            'USER_TYPE' => 'FILTERUSERIDS',
            'DESCRIPTION' => 'PWD: Привязка к пользователям определенного департамента',
            'GetPropertyFieldHtml' => [__CLASS__, 'GetPropertyFieldHtml'],
            'PrepareSettings' => [__CLASS__, 'PrepareSettings'],
            'GetSettingsHTML' => [__CLASS__, 'GetSettingsHTML'],
        );
    }

    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        $options = '<option>- не выбрано -</option>';
        $arUsers = self::getUsers($arProperty['USER_TYPE_SETTINGS']['SECTION_ID']);
        foreach($arUsers as $user){
            if($value['VALUE'] === $user['ID']){
                $selected = 'selected';
            }
            $options .= '<option '.$selected.' value="'.$user['ID'].'">'.$user['LAST_NAME'].' '.$user['NAME'].' '.$user['SECOND_NAME']
                .'</option>';
            unset($selected);
        }
        $html = '<select name="'.$strHTMLControlName['VALUE'].'">'.$options.'</select>';
        return $html;
    }

    public static function getUsers($department_id)
    {
        $dbUser= UserTable::getList([
            'select'=>['ID','LOGIN','NAME', 'LAST_NAME', 'SECOND_NAME'],
            'filter'=>['UF_DEPARTMENT'=>$department_id],
        ]);
        return $dbUser->FetchAll();
    }
    public static function getSection(){
        $rsSection = SectionTable::getList([
            'filter' => [
                'IBLOCK_ID' => 46,
                'DEPTH_LEVEL' => 2,
            ],
            'select' =>  ['ID','CODE','NAME'],
        ]);
        return $rsSection->FetchAll();
    }
    // Функция вызывается при выводе формы метаданных (настроек) свойства
    // @param bool $bVarsFromForm - флаг отправки формы
    // @return string - HTML для вывода
    public static function GetSettingsHTML($arUserField = false, $arHtmlControl, $bVarsFromForm) {
        $departments = self::getSection();
        foreach($departments as $department){
            if($department['ID'] === $arUserField['USER_TYPE_SETTINGS']['SECTION_ID']){
                $selected = 'selected';
            }
            $options .= '<option '.$selected.' value="'.$department['ID'].'">'.$department['NAME'].'</option>';
            unset($selected);
        }
        return '<tr>
        <td>Департамент:</td>
        <td><select name="'.$arHtmlControl['NAME'].'[SECTION_ID]">'.$options.'</select></td>
        </tr>';
    }
    public static function PrepareSettings($arFields)
    {
        return $arFields['USER_TYPE_SETTINGS'];
    }
    /**
     * Обязательный метод для определения типа поля таблицы в БД при создании свойства
     * @param $arUserField
     * @return string
     */
    public static function GetDBColumnType($arUserField)
    {
        global $DB;
        switch(strtolower($DB->type))
        {
            case 'mysql':
                return 'int(18)';
            case "oracle":
                return 'number(18)';
            case "mssql":
                return 'int';
        }
        return 'int';
    }
}

