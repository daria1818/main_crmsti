<?
/**
 * xGuard Framework
 * @package xGuard
 * @subpackage main
 * @copyright 2014 xGuard
 */

namespace xGuard\Main;

use xGuard;

/**
 * Base entity
 */
class Mail extends \xGuard\Main
{
    public function Send($options = array())
    {
        if(empty($this->arParams['webFormId']) && empty($this->arParams['resultId']))
        {
            return false;
        };

        $this->IncludeModule("form");
        $this->IncludeModule("iblock");

        $this->arResult['RESULT'] = array(
            'FORM'  => \CFormResult::GetByID($this->arParams['resultId'])->Fetch(),
            'DATA'  => \CFormResult::GetDataByID($this->arParams['resultId']),
        );

        $arFields = $this->arResult['RESULT']['FORM'];

        $options['MAIL']                                = !is_array($options['MAIL'])?array():$options['MAIL'];
        $options['MAIL']['FROM_TITLE']                  = !empty($options['MAIL']['FROM_TITLE'])?$options['MAIL']['FROM_TITLE']:'INFO';
        $options['EVENT_MESSAGE']                       = !is_array($options['EVENT_MESSAGE'])?array():$options['EVENT_MESSAGE'];
        $options['EVENT_MESSAGE']['GETLIST']            = !is_array($options['EVENT_MESSAGE']['GETLIST'])?array():$options['EVENT_MESSAGE']['GETLIST'];
        $options['EVENT_MESSAGE']['GETLIST']['BY']      = !empty($options['EVENT_MESSAGE']['GETLIST']['BY'])?$options['EVENT_MESSAGE']['GETLIST']['BY']:'id';
        $options['EVENT_MESSAGE']['GETLIST']['ORDER']   = !empty($options['EVENT_MESSAGE']['GETLIST']['ORDER'])?$options['EVENT_MESSAGE']['GETLIST']['ORDER']:'asc';
        $options['EVENT_MESSAGE']['GETLIST']['FILTER']  = !is_array($options['EVENT_MESSAGE']['GETLIST']['FILTER'])||!count($options['EVENT_MESSAGE']['GETLIST']['FILTER'])?array('LID' => SITE_ID, 'TYPE_ID' => ''):$options['EVENT_MESSAGE']['GETLIST']['FILTER'];

        $body = \CEventMessage::GetList(
            $options['EVENT_MESSAGE']['GETLIST']['BY'],
            $options['EVENT_MESSAGE']['GETLIST']['ORDER'],
            $options['EVENT_MESSAGE']['GETLIST']['FILTER']
        )->Fetch();

        if (!is_array($body)) {
            return false;
        }

        $arFields["EMAIL_TO"]           = !empty($options['MAIL']["EMAIL_TO"])?$options['MAIL']["EMAIL_TO"]:\COption::GetOptionString("main", "email_from");
        $arFields["SITE_NAME"]          = !empty($options['MAIL']["SITE_NAME"])?$options['MAIL']["EMAIL_TO"]:\COption::GetOptionString("main", "site_name");
        $arFields["DEFAULT_EMAIL_FROM"] = !empty($options['MAIL']["DEFAULT_EMAIL_FROM"])?$options['MAIL']["DEFAULT_EMAIL_FROM"]:\COption::GetOptionString("main", "email_from");

        foreach ($this->arResult['RESULT']['DATA'] as $key=>$value)
        {
            if($value[0]['FIELD_TYPE']!=='file'):
                $arFields[$key] = $value[0]['USER_TEXT'];
                $arFields[$key] = !empty($arFields[$key])?$arFields[$key]:$value[0]['VALUE'];
            else:
                foreach($value as $file):
                    $arFields[$key][] = \CFile::GetFileArray($file['USER_FILE_ID']);
                endforeach;
            endif;
        }

        $body["BODY_TYPE"]  = ($body["BODY_TYPE"] == "html");
        $body["MESSAGE"]    = \CAllEvent::ReplaceTemplate($body["MESSAGE"], $arFields, $body["BODY_TYPE"]);
        $body["SUBJECT"]    = \CAllEvent::ReplaceTemplate($body["SUBJECT"], $arFields, $body["BODY_TYPE"]);
        $body["EMAIL_FROM"] = \CAllEvent::ReplaceTemplate($body["EMAIL_FROM"], $arFields, $body["BODY_TYPE"]);
        $body["EMAIL_TO"]   = \CAllEvent::ReplaceTemplate($body["EMAIL_TO"], $arFields, $body["BODY_TYPE"]);

        $headers = $file = $headers = $multipart = array();
        $subject = '=?UTF-8?B?' . base64_encode($body['SUBJECT']) . '?=';
        $boundary = '--' . md5(uniqid(time()));

        $arFields['FILES'] = is_array($arFields['FILES'])?$arFields['FILES']:(is_array($arFields['FILE']))?$arFields['FILE']:array();

        foreach ($arFields['FILES'] as $fileName)
        {
            if(!file_exists($_SERVER['DOCUMENT_ROOT'].$fileName['SRC'])):
                continue;
            endif;

            $fileTitle = \CUtil::translit($fileName['ORIGINAL_NAME'], 'ru', array("replace_space" => "-", "replace_other" => "."));
            $file[] = '--' . $boundary;
            $file[] = 'Content-Type: application/octet-stream';
            $file[] = 'Content-Transfer-Encoding: base64';
            $file[] = 'Content-Disposition: attachment; filename="' . $fileTitle . '"' . "\r\n";
            $file[] = base64_encode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$fileName['SRC']));
        }

        $headers[] = 'From: '.$options['MAIL']['FROM_TITLE'].'<' . $body['EMAIL_FROM'] . '>';
        $headers[] = 'Return-path: <' . $body['EMAIL_FROM'] . '>';
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
        $multipart[] = '--' . $boundary;
        $multipart[] = 'Content-Type: text/html; charset="UTF-8"';
        $multipart[] = 'Content-Transfer-Encoding: quoted-printable' . "\r\n";
        $multipart[] = quoted_printable_encode($body['MESSAGE']) . "\r\n";
        $multipart[] = !empty($file)?(implode("\r\n", $file) . '--' . $boundary . '--'):'';
debugfile([$body,$subject,$multipart,$headers],'mail.log');
        return @mail($body['EMAIL_TO'], $subject, implode("\r\n", $multipart), implode("\r\n", $headers));
    }
}
?>