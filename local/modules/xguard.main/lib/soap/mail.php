<?
/**
 * xGuard Framework
 * @package xGuard
 * @subpackage main
 * @copyright 2014 xGuard
 */

namespace xGuard\Main\Soap;

use \xGuard\Main;

/**
 * Base entity
 */
class Mail extends \xGuard\Main\Soap\All
{
    //protected function GetWsdl()
    //{

    //}
}

class MailHandler extends \xGuard\Main\Soap\AllHandler
{
    protected $arParams = array();
    protected $options  = array();

    public function PrepareData($data=array())
    {
        $vars = \xGuard\Main\Soap\Mail::GetInstance()->GetVars(array('arParams'));
        $this->arParams = $vars['arParams'];
        $data = (array)$data;
        $data = $this->RecFinder(array('data'=>array('key'=>key($data),'value'=>current($data)),'options'=>array('key'=>'key','value'=>'value','map'=>'Map','item'=>'item')));

        return $data;
    }

    public function SendMail($data=array())
    {
        unlink($_SERVER['DOCUMENT_ROOT'].'/upload/debug/MailHandler.html');debugfile('<pre>','MailHandler.html');
        $this->options=&$options;

        $options['MAILS'] = $this->PrepareData($data);

        $options['MAILS']['messageList']['message'] = !isset($options['MAILS']['messageList']['message'][0])?array($options['MAILS']['messageList']['message']):$options['MAILS']['messageList']['message'];

        foreach($options['MAILS']['messageList']['message'] as $arMail):
            \xGuard\Main\Soap\All::GetInstance()->SetRecParams($arMail);
            debugfile($arMail,'MailHandler.html');
            $options['FIELDS'] = $arMail;
            $options['MAIL']   = isset($arMail['MAIL']) && is_array($arMail['MAIL']) ? $arMail['MAIL'] : array();

            $options['MAIL']['ITEM'] = array();
            $options = $this->GetVars($options);
            $options = $this->GetMailTemplate($options);

            $options = empty($options['MAIL']['ITEM'])?$this->GetTemplate($options):$options;

            $options = $this->PrepareMail($options);
            $options = $this->Send($options);
        endforeach;

        return array(
            'status'    => $options['STATUS'],
            'error'     => $options['ERROR']
        );
    }

    private function GetVars($options=array())
    {
        $options['FIELDS']["RECIPIENT"]          = !empty($options['FIELDS']['RECIPIENT'])?$options['FIELDS']['RECIPIENT']:\COption::GetOptionString("main", "email_from");
        $options['FIELDS']["EMAIL"]              = $options['FIELDS']["RECIPIENT"];
        $options['FIELDS']["SITE_NAME"]          = !empty($options['FIELDS']['SITE'])?$options['FIELDS']['SITE']:\COption::GetOptionString("main", "site_name");
        $options['FIELDS']["SENDER"]             = !empty($options['FIELDS']['SENDER'])?$options['FIELDS']['SENDER']:\COption::GetOptionString("main", "email_from");
        $options['FIELDS']['FILE']               = array();
        $options['FIELDS']['HEADERS']            = array();
        $options['FIELDS']['MULTIPART']          = array();
        $options['FIELDS']['BOUNDARY']           = '--' . md5(uniqid(time()));

        $options['MAIL']['TRANSLIT']            = !empty($options['MAIL']['TRANSLIT'])?$options['MAIL']['TRANSLIT']:array();
        $options['MAIL']['ENCODE_SUBJECT']      = !empty($options['MAIL']['ENCODE_SUBJECT'])?$options['MAIL']['ENCODE_SUBJECT']:'windows-1251';
        $options['MAIL']['ENCODE_MESSAGE']      = !empty($options['MAIL']['ENCODE_MESSAGE'])?$options['MAIL']['ENCODE_MESSAGE']:'windows-1251';
        $options['MAIL']['ENCODE_FILENAME']     = !empty($options['MAIL']['ENCODE_FILENAME'])?$options['MAIL']['ENCODE_FILENAME']:array('FROM'=>'windows-1251','TO'=>'utf-8');

        return $options;
    }

    private function GetMailTemplate($options=array())
    {
        $options['MAIL']['GETLIST']             = is_array($options['MAIL']['GETLIST'])?$options['MAIL']['GETLIST']:array();
        $options['MAIL']['GETLIST']['FIELD']    = !empty($options['MAIL']['GETLIST']['FIELD'])?$options['MAIL']['GETLIST']['FIELD']:'id';
        $options['MAIL']['GETLIST']['ORDER']    = !empty($options['MAIL']['GETLIST']['ORDER'])?$options['MAIL']['GETLIST']['ORDER']:'asc';
        $options['MAIL']['GETLIST']['FILTER']   = is_array($options['MAIL']['GETLIST']['FILTER'])?$options['MAIL']['GETLIST']['FILTER']:array();
        $options['MAIL']['GETLIST']['FILTER']   = array_merge(array('LID'=>'s1','ACTIVE'=>'Y'),$options['MAIL']['GETLIST']['FILTER']);

        if(count($options['MAIL']['GETLIST']['FILTER'])<=2):
            return $options;
        endif;

        $options['MAIL']['ITEM']	= \CEventMessage::GetList(
            $options['MAIL']['GETLIST']['FIELD'],
            $options['MAIL']['GETLIST']['ORDER'],
            $options['MAIL']['GETLIST']['FILTER']
        )->Fetch();

        $options['FIELDS']["DEFAULT_EMAIL_FROM"]= $options['FIELDS']["SENDER"];
        $options['MAIL']['ITEM']["BODY_TYPE"]	= ($options['MAIL']['ITEM']["BODY_TYPE"] == "html");
        $options['FIELDS']["MESSAGE"]	        = \CAllEvent::ReplaceTemplate($options['MAIL']['ITEM']["MESSAGE"], $options['FIELDS'], $options['MAIL']['ITEM']["BODY_TYPE"]);
        $options['FIELDS']["RECIPIENT"]	        = !empty($options['FIELDS']["RECIPIENT"])?$options['FIELDS']["RECIPIENT"]:\CAllEvent::ReplaceTemplate($options['MAIL']['ITEM']["EMAIL_TO"], $options['FIELDS'], $options['MAIL']['ITEM']["BODY_TYPE"]);
        $options['FIELDS']["BCC"]               = !empty($options['FIELDS']["BCC"])?$options['FIELDS']["BCC"]:'';
        $options['FIELDS']["SENDER"]            = \CAllEvent::ReplaceTemplate($options['MAIL']['ITEM']["EMAIL_FROM"], $options['FIELDS'], $options['MAIL']['ITEM']["BODY_TYPE"]);
        $options['FIELDS']["SUBJECT"]	        = '=?'.$options['MAIL']['ENCODE_SUBJECT'].'?B?'.base64_encode(\CAllEvent::ReplaceTemplate($options['MAIL']['ITEM']["SUBJECT"], $options['FIELDS'], $options['MAIL']['ITEM']["BODY_TYPE"])).'?=';
        $options['MAIL']['ITEM']['FILES']['VALUE'] = !is_array($options['FIELDS']["FILES"])?array($options['FIELDS']["FILES"]):$options['FIELDS']["FILES"];

        $options = $this->SetFile($options);

        return $options;
    }

    private function GetTemplate($options=array())
    {
        if(empty($options['FIELDS']['TYPE'])):
            return $options;
        endif;
        \xGuard\Main\Soap\All::GetInstance()->IncludeModule("iblock");

        $obEvent                                = \CIBlockElement::GetList(array(), array('CODE' => $options['FIELDS']['TYPE'],'IBLOCK_ID'=>59))->GetNextElement();

        $options['MAIL']['ITEM']                = $obEvent->GetFields();
        $options['MAIL']['ITEM']['PROPERTIES']  = $obEvent->GetProperties();
        $options['FIELDS']["BCC"]               = $options['MAIL']['ITEM']['PROPERTIES']['BCC']['VALUE'];
        $options['FIELDS']["MESSAGE"]           = \CAllEvent::ReplaceTemplate($options['MAIL']['ITEM']["~DETAIL_TEXT"], $options['FIELDS'], true);
        $options['FIELDS']['SUBJECT']           = '=?'.$options['MAIL']['ENCODE_SUBJECT'].'?B?' . base64_encode($options['MAIL']['ITEM']['NAME']) . '?=';
        $options['MAIL']['ITEM']['FILES']['VALUE']       = is_array($options['MAIL']['ITEM']['PROPERTIES']['FILES']['VALUE'])?$options['MAIL']['ITEM']['PROPERTIES']['FILES']['VALUE']:array($options['MAIL']['ITEM']['PROPERTIES']['FILES']['VALUE']);
        $options['MAIL']['ITEM']['FILES']['DESCRIPTION'] = is_array($options['MAIL']['ITEM']['PROPERTIES']['FILES']['DESCRIPTION'])?$options['MAIL']['ITEM']['PROPERTIES']['FILES']['DESCRIPTION']:array($options['MAIL']['ITEM']['PROPERTIES']['FILES']['DESCRIPTION']);

        $options = $this->SetFile($options);

        return $options;
    }

    private function SetFile($options=array())
    {
        foreach ($options['MAIL']['ITEM']['FILES']['VALUE'] as $key=>$fileName)
        {
            $temp   = array_change_key_case(\CFile::GetFileArray($fileName),CASE_UPPER);
            $arFile   = empty($temp)?array_change_key_case(\CFile::MakeFileArray($fileName),CASE_UPPER):$temp;
            $arFile['SRC'] = isset($arFile['SRC'])?$arFile['SRC']:$arFile['TMP_NAME'];
            $arFile['FILE_NAME'] = isset($arFile['FILE_NAME'])?$arFile['FILE_NAME']:basename($arFile['TMP_NAME']);
            $extension = pathinfo($arFile["FILE_NAME"], PATHINFO_EXTENSION);
            $fileTitle  = !empty($options['MAIL']['ITEM']['FILES']['DESCRIPTION'][$key])?$options['MAIL']['ITEM']['FILES']['DESCRIPTION'][$key]:$arFile['FILE_NAME'];
            $fileTitle  = !empty($options['MAIL']['TRANSLIT'])?CUtil::translit($fileTitle, $options['MAIL']['TRANSLIT']['LANG'], $options['MAIL']['TRANSLIT']['LANG']['PARAM']):$fileTitle;
            $options['FILE_TITLE'] = $fileTitle;
            $options['~FILE_TITLE'] = $this->GetValueEncode($fileTitle);
            $options['FIELDS']['FILE'][] = '--' . $options['FIELDS']['BOUNDARY'];
            $options['FIELDS']['FILE'][] = 'Content-Type: application/octet-stream';
            $options['FIELDS']['FILE'][] = 'Content-Transfer-Encoding: base64';
            $options['FIELDS']['FILE'][] = 'Content-Disposition: attachment; filename="' . ($options['~FILE_TITLE']) . (stristr($options['~FILE_TITLE'],$extension) ? '' : '.' . $extension) . '"' . "\r\n";
            $options['FIELDS']['FILE'][] = base64_encode(file_get_contents(file_exists($_SERVER['DOCUMENT_ROOT'].$arFile['SRC'])?$_SERVER['DOCUMENT_ROOT'].$arFile['SRC']:$arFile['SRC']));
        }

        return $options;
    }

    private function PrepareMail($options=array())
    {
        $options['FIELDS']['HEADERS'][] = 'From: ' . $options['FIELDS']["SITE_NAME"] . ' <' . $options['FIELDS']["SENDER"] . '>';
        $options['FIELDS']['HEADERS'][] = 'Return-path: <' . $options['FIELDS']["SENDER"] . '>';
        $options['FIELDS']['HEADERS'][] = 'Bcc: '. $options['FIELDS']["BCC"];
        $options['FIELDS']['HEADERS'][] = 'MIME-Version: 1.0';
        $options['FIELDS']['HEADERS'][] = 'Content-Type: multipart/mixed; boundary="' . $options['FIELDS']['BOUNDARY'] . '"';
        $options['FIELDS']['MULTIPART'][] = '--' . $options['FIELDS']['BOUNDARY'];
        $options['FIELDS']['MULTIPART'][] = 'Content-Type: text/html; charset="'.$options['MAIL']['ENCODE_MESSAGE'].'"';
        $options['FIELDS']['MULTIPART'][] = 'Content-Transfer-Encoding: quoted-printable' . "\r\n";
        $options['FIELDS']['MULTIPART'][] = quoted_printable_encode($options['FIELDS']['MESSAGE']) . "\r\n";
        $options['FIELDS']['MULTIPART'][] = implode("\r\n", $options['FIELDS']['FILE']) . '--' . $options['FIELDS']['BOUNDARY'] . '--';
        debugfile($options['FIELDS'],'MailHandler.html');
        return $options;
    }

    private function Send($options=array())
    {
        $options['STATUS'] = @mail($options['FIELDS']['RECIPIENT'], $options['FIELDS']['SUBJECT'], implode("\r\n", $options['FIELDS']['MULTIPART']), implode("\r\n", $options['FIELDS']['HEADERS']));

        return $options;
    }

    private function GetValueEncode($value)
    {
        return isset($this->options['MAIL']['ENCODE_FILENAME']['FROM'])
        &&
        isset($this->options['MAIL']['ENCODE_FILENAME']['TO'])
        &&
        ($this->options['MAIL']['ENCODE_FILENAME']['FROM']==$this->options['MAIL']['ENCODE_FILENAME']['TO'])
            ?
            $value
            :
            iconv($this->options['MAIL']['ENCODE_FILENAME']['FROM'],$this->options['MAIL']['ENCODE_FILENAME']['TO'],$value);
    }
}
?>
