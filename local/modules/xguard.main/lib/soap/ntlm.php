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
class SoapClientNTLM extends \SoapClient
{

    /**
     * @var array
     */
    public $options = array();
    /**
     *
     * @param string $wsdl
     * @param array $options
     */

    public function __construct($wsdl, $options = array())
    {
        $this->options = &$options;

        parent::SoapClient($wsdl, $options);
    }

    /**
     * @param string $function_name
     * @param array  $arguments
     * @param array  $options
     * @param string $input_headers
     * @param array  $output_headers
     *
     * @return mixed
     * @throws Main\Exception
     */
    function __soapCall($function_name, $arguments , $options=array(), $input_headers='',&$output_headers)
    {
        try
        {
            return parent::__soapCall($function_name, $arguments , $options, $input_headers,$output_headers);
        }
        catch(\Throwable $e)
        {
            throw new Main\Exception($e->GetMessage(), __LINE__);
        }
    }

    /**
     * @param string $function_name
     * @param array  $arguments
     *
     * @return mixed
     * @throws Main\Exception
     */
    function __call($function_name, $arguments)
    {
        try
        {
            return parent::__call($function_name, $arguments);
        }
        catch(\Throwable $e)
        {
            throw new Main\Exception($e->GetMessage(), __LINE__);
        }
    }

    /**
     * @param string $request
     * @param string $location
     * @param string $action
     * @param int    $version
     *
     * @return mixed|string
     * @throws Main\Exception
     */
    function __doRequest($request, $location, $action, $version)
    {
        $headers = array(
            'Method: POST',
            'User-Agent: PHP-SOAP-CURL',
            'Content-Type: application/soap+xml; charset=utf-8',
            'SOAPAction: "' . $action . '"',
            //'Content-Length: ' . strlen($request),
            'Expect: 100-continue',
            'Connection: Keep-Alive',
            'Accept-Encoding: gzip,deflate'
        );

        $this->__last_request_headers = $headers;
        $this->__last_request = $request;
        $ch = curl_init($location);
        $arParams = array(
            CURLOPT_URL                 => $location,
            CURLOPT_RETURNTRANSFER      => true,
            CURLOPT_POST                => true,
            CURLOPT_POSTFIELDS          => $request,
            CURLOPT_HTTP_VERSION        => CURL_HTTP_VERSION_1_0,
            CURLOPT_FAILONERROR         => false,
            CURLOPT_HTTPAUTH            => CURLAUTH_NTLM,
            //CURLOPT_HTTPAUTH            => CURLAUTH_BASIC,
            CURLOPT_USERPWD             => $this->options['login'] . ':' . $this->options['password'],
            CURLOPT_SSLVERSION          => 3,
            CURLOPT_SSL_VERIFYPEER      => true,
            CURLOPT_SSL_VERIFYHOST      => 2,
            CURLOPT_FRESH_CONNECT       => true,
            CURLOPT_HTTPHEADER          => $headers,
            CURLOPT_VERBOSE             => true,
            CURLOPT_CERTINFO            => true,
            CURLOPT_CONNECTTIMEOUT      => SOAP_NTLM_CONNECTION_TIMEOUT,
            CURLOPT_TIMEOUT             => SOAP_NTLM_TIMEOUT,
        );

        curl_setopt_array($ch,$arParams);

        $response = curl_exec($ch);
        $responseDecode = gzdecode($response);
        $response = !empty($responseDecode) ? $responseDecode:$response;
        $info = curl_getinfo($ch);

        $this->__last_response_headers = $info;
        $this->__last_response = $response;
        //debugfile([SOAP_NTLM_URL_WSDL,$response,$info,$arParams],__FUNCTION__);
        if(!empty($info) && $info['http_code']===200):
            return \is_string($response) ? $response : (string) $response;
        else:
            if($info['http_code']===401):
                throw new Main\Exception('<?xml version="1.0" encoding="UTF-8"?><return><ErrorList><Error>Access Denied</Error></ErrorList></return>', 401);
            else:
                if(curl_errno($ch)!==0):
                    throw new Main\Exception('<?xml version="1.0" encoding="UTF-8"?><return><ErrorList><Error>'.curl_error($ch).'</Error></ErrorList></return>', curl_errno($ch));
                else:
                    throw new Main\Exception('<?xml version="1.0" encoding="UTF-8"?><return><ErrorList><Error>Error #'.strtoupper(base64_encode(__LINE__)).$info['http_code'].'</Error><Error>'.$response.'</Error></ErrorList></return>');
                endif;
            endif;
        endif;
    }
}
