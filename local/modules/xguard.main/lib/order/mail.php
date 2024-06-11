<?
/**
 * xGuard Framework
 * @package xGuard * @subpackage main
 * @copyright 2014 xGuard
 */

namespace xGuard\Main\Order;

use \xGuard\Main;

/**
 * Base entity
 */

IncludeModuleLangFile(__FILE__);

class Mail extends \xGuard\Main
{
    public function __construct($options)
    {
        parent::__construct($options);
        $this->IncludeModule('sale');
        $this->IncludeModule('catalog');
    }
    
    public static function Send($options=array())
    {
        $options = array_replace_recursive(
            array(
                'SOAP'  => array(
                    'WSDL'    => '',
                    'OPTIONS' => array(
                        'soap_version'  => SOAP_1_2,
                        "trace"         => 1,
                        "exceptions"    => 1,
                    ),
                ),
                'SITE'      => $_SERVER['SERVER_NAME'],
                'RESOURCE'  => $_SERVER['REQUEST_URI'],
                'NAME'      => '',
                'TYPE'      => '',
                'FILES'     => '',
                'SUBJECT'   => '',
                'RECIPIENT' => '',
                'BCC'       => '',
                'MESSAGE'   => '',
                'MAIL'      => array(
                    'GETLIST'   => array(
                        'ORDER'     => array(

                        ),
                        'FILTER'    => array(

                        ),
                        'GROUP'     => false,
                        'LIMIT'     => false,
                        'SELECT'    => array(

                        ),
                    ),
                    'ENCODE_SUBJECT'         => 'utf-8',
                    'ENCODE_MESSAGE'         => 'utf-8',
                ),
            ),
            $options
        );
        try
        {
            ini_set("soap.wsdl_cache_enabled", 0);

            $client = new \SoapClient(
                $options['SOAP']['WSDL'],
                $options['SOAP']['OPTIONS']
            );

            $client->sendMail(
                array(
                    'messageList' => array(
                        'message'   => array($options),
                    ),
                )
            );

            ini_set("soap.wsdl_cache_enabled", 1);
        }
        catch(\Exception $e)
        {
            static::Log($e->getMessage(),$e->getCode());
        }
    }

    public static function PrepareItemsTableForMail($options=array())
    {
        $strOrderList   = '
            <table border="1" cellspacing="0" cellpadding="5">
                <tr>';
                                
        foreach($options['HEADER']['COLUMN'] as $arHeader):
            $strOrderList .= '
                    <td bgcolor="'.(isset($arHeader['BGCOLOR'])?$arHeader['BGCOLOR']:'#a0a0a0').'">'.$arHeader['VALUE'].'</td>
            '; 
        endforeach;
        
        $strOrderList .= '
                </tr>
        ';
        
        foreach ($options['ITEMS'] as $arItem):
            $strOrderList .= '
                </tr>
            ';
            $measureText = (isset($arItem["MEASURE_TEXT"]) && strlen($arItem["MEASURE_TEXT"])) ? $arItem["MEASURE_TEXT"] : \GetMessage("XGUARD_BASKET_SHT");

            foreach($options['HEADER']['COLUMN'] as $key=>$arBody):
                if(isset($arItem[$key])):
                    $value = $arItem[$key];
                elseif(isset($arItem['PROPS'][$key])&&is_array($arItem['PROPS'][$key])):
                    $value = $arItem['PROPS'][$key]['VALUE'];
                else:
                    $value = '';
                endif;
                
                $strOrderList .= '<td>'.$value.'</td>';
            endforeach;
            
            $strOrderList .= '</tr>';
        endforeach;

        $strOrderList .= '</table>';

        return $strOrderList;
    }
    
    public static function PrepareItemsListForMail($options=array())
    {
        $options['MAX_ROW'] = isset($options['MAX_ROW'])?+$options['MAX_ROW']:3;
        
        $strOrderList   = '
            <table border="0" cellspacing="10" cellpadding="0" style="background-color:#ffffff;width:100%;">
                <tr>
        ';
        
        $row=0;
        
        foreach ($options['ITEMS'] as $arItem):
            if($options['MAX_ROW']==$row):
                $strOrderList .= '
                    </tr>
                    <tr>
                ';
                $row=0;
            endif;
            
            $strOrderList .= '<td valign="top" style="border:1px solid #09b68f;width: 30%;">';

            $strOrderList .= GetMessage('XGUARD_TEMPLATE_PREPARE_ITEMS_LIST_FOR_MAIL',$arItem);

            $strOrderList .= '</td>';
            $row++;
        endforeach;
        
        while($options['MAX_ROW']!==$row):
            $strOrderList .= '<td valign="top"></td>';

            $row++;
        endwhile;
        
        $strOrderList .= '</tr></table>';

        return $strOrderList;
    }
}
?>