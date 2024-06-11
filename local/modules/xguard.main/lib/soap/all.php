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
class All extends \xGuard\Main
{
    protected $arService        = array();
    protected $arDefaultService = array();

    protected function GetWsdlBody()
    {
        $this->GetWsdlTypes();
        $this->GetWsdlMessage();
        $this->GetWsdlPort();
        $this->GetWsdlBinding();
        $this->GetWsdlService();
    }

    protected function GetWsdlService()
    {
        echo '
            <wsdl:service name="',$this->arService['SERVICE']['NAME'],'">
                <wsdl:port name="',$this->arService['SERVICE']['PORT']['NAME'],'" binding="typens:',$this->arService['SERVICE']['PORT']['BINDING'],'">
                    <soap:address location="',$this->arService['SERVICE']['PORT']['SOAP']['LOCATION'],'" />
                </wsdl:port>
            </wsdl:service>';
    }

    protected function GetWsdlBinding()
    {
        echo '<wsdl:binding name="',$this->arService['BINDING']['NAME'],'" type="typens:',$this->arService['BINDING']['TYPE'],'">
                <soap:binding style="',$this->arService['BINDING']['SOAP']['STYLE'],'" transport="',$this->arService['BINDING']['SOAP']['TRANSPORT'],'" />';

        foreach($this->arService['BINDING']['OPERATION'] as $nameOperation=>$arOperation):
            echo '<wsdl:operation name="',$nameOperation,'">
                    <soap:operation soapAction="',$arOperation['SOAP']['SOAPACTION'],'" />
                    <wsdl:input>
                        <soap:body use="',$arOperation['INPUT']['SOAP']['USE'],'" namespace="',$arOperation['INPUT']['SOAP']['NAMESPACE'],'" encodingStyle="',$arOperation['INPUT']['SOAP']['ENCODINGSTYLE'],'"/>
                    </wsdl:input>
                    <wsdl:output>
                        <soap:body use="',$arOperation['OUTPUT']['SOAP']['USE'],'" namespace="',$arOperation['OUTPUT']['SOAP']['NAMESPACE'],'" encodingStyle="',$arOperation['OUTPUT']['SOAP']['ENCODINGSTYLE'],'"/>
                    </wsdl:output>
                </wsdl:operation>';
        endforeach;

        echo '</wsdl:binding>';
    }

    protected function GetWsdlPort()
    {
        echo '<wsdl:portType name="',$this->arService['PORTTYPE']['NAME'],'">';

        foreach($this->arService['PORTTYPE']['OPERATION'] as $nameOperation=>$arOperation):
            echo '
                <wsdl:operation name="',$nameOperation,'">
                    <wsdl:input message="typens:',$arOperation['INPUT']['MESSAGE'],'" />
                    <wsdl:output message="typens:',$arOperation['OUTPUT']['MESSAGE'],'" />
                </wsdl:operation>';
        endforeach;

        echo '</wsdl:portType>';
    }

    protected function GetWsdlMessage()
    {
        foreach($this->arService['MESSAGE'] as $nameMessage=>$arMessage):
            echo '
                <wsdl:message name="',$nameMessage,'">
                    <wsdl:part name="',$arMessage['PART']['NAME'],'" element="typens:',$arMessage['PART']['ELEMENT'],'"/>
                </wsdl:message>';
        endforeach;
    }

    protected function GetWsdlTypes()
    {
        echo '
            <wsdl:types>
                <xsd:schema
                       xmlns:xsd="',$this->arService['TYPES']['XSD']['xmlns:xsd'],'"
                       elementFormDefault="',$this->arService['TYPES']['XSD']['elementFormDefault'],'"
                       targetNamespace="',$this->arService['TYPES']['XSD']['targetNamespace'],'">',"\r\n";
        foreach($this->arService['TYPES']['XSD']['COMPLEXTYPE'] as $nameComplexType=>$arComplexType):
            echo '
                <xsd:complexType name="',$nameComplexType,'">
                    <xsd:sequence>';
            foreach($arComplexType['SEQUENCE']['ELEMENT'] as $arElement):
                echo '<xsd:element',
                (!empty($arElement['NAME'])?' name="'.$arElement['NAME'].'"':''),
                (!empty($arElement['TYPE'])?' type="'.$arElement['TYPE'].'"':''),
                (!empty($arElement['MINOCCURS'])?' minOccurs="'.$arElement['MINOCCURS'].'"':''),
                (!empty($arElement['MAXOCCURS'])?' maxOccurs="'.$arElement['MAXOCCURS'].'"':''),
                '/>';
            endforeach;
            echo '</xsd:sequence>
                </xsd:complexType>';
        endforeach;

        foreach($this->arService['TYPES']['XSD']['ELEMENT'] as $nameElement=>$arElement):
            echo '
                <xsd:element name="',$nameElement,'">';
            foreach($arElement['ELEMENT'] as $element):
                echo '
                <xsd:complexType>
                    <xsd:sequence>
                            <xsd:element name="',$element['NAME'],'" type="',$element['TYPE'],'"/>
                        </xsd:sequence>
                </xsd:complexType>';
            endforeach;

            echo '</xsd:element>';
        endforeach;

        echo '
            </xsd:schema>
        </wsdl:types>';
    }

    protected function GetWsdl()
    {
        $this->GetWsdlParams();

        header("Content-Type: text/xml; charset=utf-8");

        echo '<?xml version="1.0" encoding="utf-8"?>';
        echo '<wsdl:definitions ';

        foreach($this->arService['DEFINITIONS'] as $name=>$value):
            echo $name,'="',$value,'" ';
        endforeach;

        echo '>';

        $this->GetWsdlBody();

        echo '</wsdl:definitions>';
    }

    protected function GetWsdlParams()
    {
        $this->arParams['SOAP'] = is_array($this->arParams['SOAP'])?$this->arParams['SOAP']:array();
        $this->arDefaultService = array(
            'DEFINITIONS'   => array(
                'xmlns:typens'  => 'urn:'.$_SERVER['SERVER_NAME'],
                'xmlns:xsd'     => 'http://www.w3.org/2001/XMLSchema',
                'xmlns:soap'    => 'http://schemas.xmlsoap.org/wsdl/soap/',
                'xmlns:soapenc' => 'http://schemas.xmlsoap.org/soap/encoding/',
                'xmlns:wsdl'    => 'http://schemas.xmlsoap.org/wsdl/',
                'xmlns'         => 'http://schemas.xmlsoap.org/wsdl/',
                'xmlns:mime'    => 'http://schemas.xmlsoap.org/wsdl/mime/',
                'xmlns:soap12'  => 'http://schemas.xmlsoap.org/wsdl/soap12/',
                'xmlns:http'    => 'http://schemas.xmlsoap.org/wsdl/http/',
                'name'          => 'TestWsdl',
            ),
            'SERVICE'   => array(
                'NAME'  => 'TestServices',
                'PORT'  => array(
                    'NAME'      => 'TestServicesPort',
                    'BINDING'   => 'TestServicesBinding',
                    'SOAP'      => array(
                        'LOCATION'  => $_SERVER['SCRIPT_URI']==dirname($_SERVER['SCRIPT_URI']).'/'?$_SERVER['SCRIPT_URI']:dirname($_SERVER['SCRIPT_URI']).'/',
                    ),
                ),
            ),
            'BINDING' => array(
                'NAME'  => 'TestServicesBinding',
                'TYPE'  => 'TestServicesPortType',
                'SOAP'  => array(
                    'STYLE'     => 'rpc',
                    'TRANSPORT' => 'http://schemas.xmlsoap.org/soap/http',
                ),
                'OPERATION' => array(
                    'Test'  => array(
                        'SOAP'  => array(
                            'SOAPACTION'    => '',
                        ),
                        'INPUT' => array(
                            'SOAP'  =>  array(
                                'USE'           => 'literal',
                                'NAMESPACE'     => 'urn:'.$_SERVER['SERVER_NAME'],
                                'ENCODINGSTYLE' => '',
                            ),
                        ),
                        'OUTPUT'    => array(
                            'SOAP'  =>  array(
                                'USE'   => 'literal',
                                'NAMESPACE'     => 'urn:'.$_SERVER['SERVER_NAME'],
                                'ENCODINGSTYLE' => '',
                            ),
                        ),
                    ),
                ),
            ),
            'PORTTYPE' => array(
                'NAME'  => 'TestServicesPortType',
                'OPERATION' => array(
                    'Test'  => array(
                        'INPUT' => array(
                            'MESSAGE'  => 'TestRequest',
                        ),
                        'OUTPUT'    => array(
                            'MESSAGE'  =>  'TestResponse',
                        ),
                    ),
                ),
            ),
            'MESSAGE' => array(
                'TestRequest'  => array(
                    'PART' => array(
                        'NAME'      =>  'Request',
                        'ELEMENT'   =>  'Request',
                    ),
                ),
                'TestResponse'  => array(
                    'PART' => array(
                        'NAME'      =>  'Response',
                        'ELEMENT'   =>  'Response',
                    ),
                ),
            ),
            'TYPES' => array(
                'XSD'    => array(
                    'xmlns:tns'             => 'http://schemas.xmlsoap.org/wsdl/',
                    'xmlns'                 => 'http://www.w3.org/2001/XMLSchema',
                    'xmlns:xsd'             => 'http://www.w3.org/2001/XMLSchema',
                    'elementFormDefault'    => 'qualified',
                    'targetNamespace'       => 'urn:'.$_SERVER['SERVER_NAME'],
                    'COMPLEXTYPE'           => array(
                        'Test'  => array(
                            'SEQUENCE'  => array(
                                'ELEMENT'   => array(
                                    array(
                                        'NAME'  => 'value',
                                        'TYPE'  => 'string',
                                        'MINOCCURS'  => '1',
                                        'MAXOCCURS'  => 'unbounded',
                                    ),
                                ),
                            ),
                        ),
                        'TestList'  => array(
                            'SEQUENCE'  => array(
                                'ELEMENT'   => array(
                                    array(
                                        'NAME'      => 'Test',
                                        'TYPE'      => 'Test',
                                        'MINOCCURS' => '1',
                                        'MAXOCCURS' => 'unbounded',
                                    ),
                                ),
                            ),
                        ),
                        'Response'  => array(
                            'SEQUENCE'  => array(
                                'ELEMENT'   => array(
                                    array(
                                        'NAME'  => 'status',
                                        'TYPE'  => 'xsd:boolean',

                                    ),
                                    array(
                                        'NAME'      => 'error',
                                        'TYPE'      => 'xsd:string',
                                    )
                                ),
                            ),
                        ),
                    ),
                    'ELEMENT'   => array(
                        'Response'  =>    array(
                            'ELEMENT'   => array(
                                array(
                                    'NAME'  => 'response',
                                    'TYPE'  => 'Response',
                                ),
                            ),
                        ),
                        'Request'  =>    array(
                            'ELEMENT'   => array(
                                array(
                                    'NAME'  => 'testList',
                                    'TYPE'  => 'TestList',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );

        $this->arService = array_replace_recursive($this->arDefaultService,$this->arParams['SOAP']);
    }

    public function Init()
    {
        $this->SetParams();

        return $this;
    }

    public function GetResult()
    {
        if(isset($_REQUEST['wsdl'])):
            $this->GetWsdl();
        else:
            $this->server = new \SoapServer('http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']).'/?wsdl');

            $this->server->setClass(get_called_class().'Handler');
            $this->server->handle();
        endif;

        return $this;
    }
}

class AllHandler
{
    public function Test()
    {
        return array('status'=>'true');
    }

    protected function RecFinder($options=array())
    {
        if(isset($options['data'][$options['options']['key']])&&isset($options['data'][$options['options']['value']])):
            $options['data'][$options['options']['value']] = is_object($options['data'][$options['options']['value']])?(array)$options['data'][$options['options']['value']]:$options['data'][$options['options']['value']];
            if(is_array($options['data'][$options['options']['value']])):
                if(is_numeric(key($options['data']))):
                    $options['result'] = array($options['data'][$options['options']['key']]=>$options['data'][$options['options']['value']]);
                else:
                    $options['result'][$options['data'][$options['options']['key']]] = static::RecFinder(array('data'=>(array)$options['data'][$options['options']['value']],'options'=>$options['options']));
                endif;
            else:
                $options['result'] = array($options['data'][$options['options']['key']]=>$options['data'][$options['options']['value']]);
            endif;
        else:
            if(key($options['data'])===$options['options']['map']):
                $options['result'] = static::RecFinder(array('data'=>(array)current($options['data']),'options'=>$options['options']));
            else:
                if(is_numeric(key($options['data']))):
                    foreach($options['data'] as $key=>$value):
                        $value = (array)$value;
                        if(isset($value[$options['options']['item']])):
                            $value[$options['options']['item']] = (array)$value[$options['options']['item']];
                            $value[$options['options']['item']][$options['options']['value']] = (array)$value[$options['options']['item']][$options['options']['value']];
                            $value[$options['options']['item']][$options['options']['value']][$options['options']['item']] = (array)$value[$options['options']['item']][$options['options']['value']][$options['options']['item']];
                            $options['result'][$value[$options['options']['item']][$options['options']['key']]][] =  static::RecFinder(array('data'=>(array)$value[$options['options']['item']][$options['options']['value']][$options['options']['item']],'options'=>$options['options']));
                        else:
                            $options['result'] =  is_array($options['result'])?$options['result']:array();
                            $options['result'] = array_merge($options['result'],static::RecFinder(array('data'=>(array)$value,'options'=>$options['options'])));
                        endif;
                    endforeach;
                else:
                    $options['result'] = static::RecFinder(array('data'=>(array)current($options['data']),'options'=>$options['options']));
                endif;
            endif;
        endif;

        return $options['result'];
    }
}
?>