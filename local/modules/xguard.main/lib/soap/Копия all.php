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
            <service name="',$this->arService['SERVICE']['NAME'],'">
                <port name="',$this->arService['SERVICE']['PORT']['NAME'],'" binding="typens:',$this->arService['SERVICE']['PORT']['BINDING'],'">
                    <soap:address location="',$this->arService['SERVICE']['PORT']['SOAP']['LOCATION'],'" />
                </port>
            </service>';
    }

    protected function GetWsdlBinding()
    {
        echo '<binding name="',$this->arService['BINDING']['NAME'],'" type="typens:',$this->arService['BINDING']['TYPE'],'">
                <soap:binding style="',$this->arService['BINDING']['SOAP']['STYLE'],'" transport="',$this->arService['BINDING']['SOAP']['TRANSPORT'],'" />';

        foreach($this->arService['BINDING']['OPERATION'] as $nameOperation=>$arOperation):
            echo '<operation name="',$nameOperation,'">
                    <soap:operation soapAction="',$arOperation['SOAP']['SOAPACTION'],'" />
                    <input>
                        <soap:body use="',$arOperation['INPUT']['SOAP']['USE'],'" namespace="',$arOperation['INPUT']['SOAP']['NAMESPACE'],'" encodingStyle="',$arOperation['INPUT']['SOAP']['ENCODINGSTYLE'],'"/>
                    </input>
                    <output>
                        <soap:body use="',$arOperation['OUTPUT']['SOAP']['USE'],'" namespace="',$arOperation['OUTPUT']['SOAP']['NAMESPACE'],'" encodingStyle="',$arOperation['OUTPUT']['SOAP']['ENCODINGSTYLE'],'"/>
                    </output>
                </operation>';
        endforeach;

        echo '</binding>';
    }

    protected function GetWsdlPort()
    {
        echo '<portType name="',$this->arService['PORTTYPE']['NAME'],'">';

        foreach($this->arService['PORTTYPE']['OPERATION'] as $nameOperation=>$arOperation):
            echo '
                <operation name="',$nameOperation,'">
                    <input message="typens:',$arOperation['INPUT']['MESSAGE'],'" />
                    <output message="typens:',$arOperation['OUTPUT']['MESSAGE'],'" />
                </operation>';
        endforeach;

        echo '</portType>';
    }

    protected function GetWsdlMessage()
    {
        foreach($this->arService['MESSAGE'] as $nameMessage=>$arMessage):
            echo '
                <message name="',$nameMessage,'">
                    <part name="',$arMessage['PART']['NAME'],'" element="typens:',$arMessage['PART']['ELEMENT'],'"/>
                </message>';
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
        echo '<definitions ';

        foreach($this->arService['DEFINITIONS'] as $name=>$value):
            echo $name,'="',$value,'" ';
        endforeach;

        echo '>';

        $this->GetWsdlBody();

        echo '</definitions>';
    }

    protected function GetWsdlParams()
    {
        $this->arService['DEFINITIONS'] = array(
            'xmlns:typens'  => empty($this->arParams['SOAP']['DEFINITIONS']['TYPENS'])?'urn:'.$_SERVER['SERVER_NAME'].'':$this->arParams['SOAP']['DEFINITIONS']['TYPENS'],
            'xmlns:xsd'     => empty($this->arParams['SOAP']['DEFINITIONS']['XSD'])?'http://www.w3.org/2001/XMLSchema':$this->arParams['SOAP']['DEFINITIONS']['XSD'],
            'xmlns:soap'    => empty($this->arParams['SOAP']['DEFINITIONS']['SOAP'])?'http://schemas.xmlsoap.org/wsdl/soap/':$this->arParams['SOAP']['DEFINITIONS']['SOAP'],
            'xmlns:soapenc' => empty($this->arParams['SOAP']['DEFINITIONS']['SOAPENC'])?'http://schemas.xmlsoap.org/soap/encoding/':$this->arParams['SOAP']['DEFINITIONS']['SOAPENC'],
            'xmlns:wsdl'    => empty($this->arParams['SOAP']['DEFINITIONS']['WSDL'])?'http://schemas.xmlsoap.org/wsdl/':$this->arParams['SOAP']['DEFINITIONS']['WSDL'],
            'xmlns'         => empty($this->arParams['SOAP']['DEFINITIONS']['XMLNS'])?'http://schemas.xmlsoap.org/wsdl/':$this->arParams['SOAP']['DEFINITIONS']['XMLNS'],
            'xmlns:mime'    => empty($this->arParams['SOAP']['DEFINITIONS']['MIME'])?'http://schemas.xmlsoap.org/wsdl/mime/':$this->arParams['SOAP']['DEFINITIONS']['MIME'],
            'xmlns:soap12'  => empty($this->arParams['SOAP']['DEFINITIONS']['SOAP12'])?'http://schemas.xmlsoap.org/wsdl/soap12/':$this->arParams['SOAP']['DEFINITIONS']['SOAP12'],
            'xmlns:http'    => empty($this->arParams['SOAP']['DEFINITIONS']['HTTP'])?'http://schemas.xmlsoap.org/wsdl/http/':$this->arParams['SOAP']['DEFINITIONS']['HTTP'],
            'name'          => empty($this->arParams['SOAP']['DEFINITIONS']['NAME'])?'TestWsdl':$this->arParams['SOAP']['DEFINITIONS']['NAME'],
        );

        $this->arService['SERVICE'] = array(
            'NAME'  => empty($this->arParams['SOAP']['SERVICE']['NAME'])?'TestServices':$this->arParams['SOAP']['SERVICE']['NAME'],
            'PORT'  => array(
                'NAME'      => empty($this->arParams['SOAP']['SERVICE']['PORT']['NAME'])?'TestServicesPort':$this->arParams['SOAP']['SERVICE']['PORT']['NAME'],
                'BINDING'   => empty($this->arParams['SOAP']['SERVICE']['PORT']['BINDING'])?'TestServicesBinding':$this->arParams['SOAP']['SERVICE']['PORT']['BINDING'],
                'SOAP'      => array(
                    'LOCATION'  => empty($this->arParams['SOAP']['SERVICE']['PORT']['SOAP']['LOCATION'])?'http://'.$_SERVER['SERVER_NAME'].':80/'.$_SERVER['SCRIPT_PATH'].'/test.php':$this->arParams['SOAP']['SERVICE']['PORT']['SOAP']['LOCATION'],
                ),
            ),
        );

        $this->arService['BINDING'] = array(
            'NAME'  => empty($this->arParams['SOAP']['SERVICE']['PORT']['BINDING'])?'TestServices':$this->arParams['SOAP']['SERVICE']['PORT']['BINDING'],
            'TYPE'  => empty($this->arParams['SOAP']['SERVICE']['PORT']['BINDING'])?'TestServices':$this->arParams['SOAP']['SERVICE']['PORT']['BINDING'],
            'TYPE'  => $this->arParams['SOAP']['PORTTYPE']['NAME'],
            'SOAP'  => array(
                'STYLE'     => empty($this->arParams['SOAP']['BINDING']['SOAP']['STYLE'])?'document':$this->arParams['SOAP']['BINDING']['SOAP']['STYLE'],
                'TRANSPORT' => empty($this->arParams['SOAP']['BINDING']['SOAP']['TRANSPORT'])?'http://schemas.xmlsoap.org/soap/http':$this->arParams['SOAP']['BINDING']['SOAP']['TRANSPORT'],
            ),
            'OPERATION' => is_array($this->arParams['SOAP']['BINDING']['OPERATION'])?$this->arParams['SOAP']['BINDING']['OPERATION']:array(
                'Test'  => array(
                    'SOAP'  => array(
                        'SOAPACTION'    => '',
                    ),
                    'INPUT' => array(
                        'SOAP'  =>  array(
                            'USE'           => 'literal',
                                'NAMESPACE'     => $this->arService['DEFINITIONS']['xmlns:typens'],
                            'ENCODINGSTYLE' => '',
                        ),
                    ),
                    'OUTPUT'    => array(
                        'SOAP'  =>  array(
                            'USE'   => 'literal',
                        ),
                    ),
                ),
            ),
        );

        $this->arService['PORTTYPE'] = array(
            'NAME'  => empty($this->arParams['SOAP']['PORTTYPE']['NAME'])?$this->arService['SERVICE']['PORT']['NAME'].'Type':$this->arParams['SOAP']['PORTTYPE']['NAME'],
            'OPERATION' => is_array($this->arParams['SOAP']['PORTTYPE']['OPERATION'])?$this->arParams['SOAP']['PORTTYPE']['OPERATION']:array(
                'Test'  => array(
                    'INPUT' => array(
                        'MESSAGE'  => 'TestRequest',
                    ),
                    'OUTPUT'    => array(
                        'MESSAGE'  =>  'TestResponse',
                    ),
                ),
            ),
        );

        $arKeys=array_keys($this->arService['PORTTYPE']['OPERATION']);
        $this->arService['MESSAGE'] = is_array($this->arParams['SOAP']['MESSAGE'])?$this->arParams['SOAP']['MESSAGE']:array(
            $arKeys[0].'Request'  => array(
                'PART' => array(
                    'NAME'      =>  'Request',
                    'ELEMENT'   =>  'Request',
                ),
            ),
            $arKeys[0].'Response'  => array(
                'PART' => array(
                    'NAME'      =>  'Response',
                    'ELEMENT'   =>  'Response',
                ),
            ),
        );

        $this->arService['TYPES'] = array(
            'XSD'    => array(
                'xmlns:tns'             => empty($this->arParams['SOAP']['TYPES']['XSD']['TNS'])?'http://schemas.xmlsoap.org/wsdl/':$this->arParams['SOAP']['TYPES']['XSD']['TNS'],
                'xmlns'                 => empty($this->arParams['SOAP']['TYPES']['XSD']['XMLNS'])?'http://www.w3.org/2001/XMLSchema':$this->arParams['SOAP']['TYPES']['XSD']['XMLNS'],
                'xmlns:xsd'              => empty($this->arParams['SOAP']['TYPES']['XSD']['XSD'])?'http://www.w3.org/2001/XMLSchema':$this->arParams['SOAP']['TYPES']['XSD']['XSD'],
                'elementFormDefault'    => empty($this->arParams['SOAP']['TYPES']['XSD']['ELEMENTFORMDEFAULT'])?'qualified':$this->arParams['SOAP']['TYPES']['XSD']['ELEMENTFORMDEFAULT'],
                'targetNamespace'       => empty($this->arParams['SOAP']['TYPES']['XSD']['TARGETNAMESPACE'])?'http://'.$_SERVER['SERVER_NAME'].'/':$this->arParams['SOAP']['TYPES']['XSD']['TARGETNAMESPACE'],
                'COMPLEXTYPE'           => is_array($this->arParams['SOAP']['TYPES']['XSD']['COMPLEXTYPE'])?$this->arParams['SOAP']['TYPES']['XSD']['COMPLEXTYPE']:array(
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
                ),
                'ELEMENT'               => is_array($this->arParams['SOAP']['TYPES']['ELEMENT'])?$this->arParams['SOAP']['TYPES']['ELEMENT']:array(
                    'Response'  =>    array(
                        'ELEMENT'   => array(
                            array(
                                'NAME'  => 'status',
                                'TYPE'  => 'xsd:boolean',
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
        );
    }
}

class AllHandler
{
    public function Test()
    {
        return array('status'=>'true');
    }
}
?>
