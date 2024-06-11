<?
namespace ApiFor1C;

use \Bitrix\Main\Diag\Debug;
/**
 * Класс для отправки запросов в 1С
 */
class Sender {
    protected 
        /**
         * [$messages Переменная для хранения ответов, ошибок и т.д.]
         * @var array
         */
        $messages = [
            "empty_data" => "не переданы данные",
            "empty_method" => "не передан метод",
        ],
        /**
         * [$result Переменная для хранения результатов]
         * @var array
         */
        $result = [];

    private 
        /**
         * [$sendUrl Адрес 1С]
         * @var string
         */
        $sendUrl = "http://185.219.41.171/StiOnline/hs/ExchangeAPI/#method#",
        /**
         * [$login Логин 1С]
         * @var string
         */
        $login = "admin",
        /**
         * [$pass Пароль 1С]
         * @var string
         */
        $pass = "1",
        /**
         * Переменная для включения/отключения логирования
         */
        $debug;
    /**
     * [__construct Конструктор]
     * @param boolean $debug [Флаг логирования]
     */
    public function __construct($debug = false) {
        $this->debug = $debug ? true : false;
    }

    /**
     * [getResult Получения результата запроса]
     * @return [array] [Результат]
     */
    public function getResult() {
        return $this->isJSON($this->result) ? json_decode($this->result, true) : $this->result;
    } 

    /**
     * [isJSON]
     * @param  [type]  $string
     * @return boolean
     */
    private function isJSON($string){
       return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

    /*private function prepareData($data, $url, $description = ""){
        $uri = new \Bitrix\Main\Web\Uri($url);
        $return = array (
            'info' => array (
                '_postman_id' => '5e12b970-a701-40fd-b234-aa12354ae7dc',
                'name' => 'СтиОнлайн',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ),
            'item' => array (
                array (
                    'name' => $uri->getUri(),
                    'request' => array (
                        'auth' => array (
                            'type' => 'basic',
                            'basic' => array (
                                array (
                                    'key' => 'password',
                                    'value' => '1',
                                    'type' => 'string',
                                ), 
                                array (
                                    'key' => 'username',
                                    'value' => 'admin',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'method' => 'POST',
                        'header' => array (),
                        'body' => array (
                            'mode' => 'raw',
                            'raw' => json_encode($data),
                            'options' => array (
                                'raw' => array (
                                    'language' => 'json'
                                ),
                            ),
                        ),
                        'url' => array (
                            'raw' => $uri->getUri(),
                            'protocol' => $uri->getScheme(),
                            'host' => explode(".", $uri->getHost()),
                            'path' => explode("/", substr($uri->getPath(), 1)),
                        ),
                        'description' => $description,
                    ),
                    'response' => array (),
                ),
            ),
            'protocolProfileBehavior' => array (),
        );
        return json_encode($return);
    }*/

    /**
     * [send Отправка запроса]
     * @param  [type] $data   [Данные для отправки]
     * @param  [type] $method [Метод]
     */
    public function send($data, $method) {
        if(empty($data))
        {
            $this->result["error"][] = $this->messages["empty_data"];
            return false;
        }
        if(empty($method))
        {
            $this->result["error"][] = $this->messages["empty_method"];
            return false;
        }
        $sendUrl = str_replace("#method#", $method, $this->sendUrl);
        $result = [];
    	$http = new \Bitrix\Main\Web\HttpClient();
    	$http->setHeader('Content-Type', 'application/json', true);
    	$http->setHeader('Accept', 'application/json', true);
    	$http->setAuthorization($this->login, $this->pass);
    	$postData = json_encode($data);
        if($this->debug){
    	   Debug::writeToFile(array($sendUrl, $postData), '['.date ("d.m.Y H:i:s").'] Sender::send postData');
        }
        $result = $http->post($sendUrl, $postData);
		if($result){
            $this->result = $result;
            if($this->debug){
                Debug::writeToFile($this->getResult(), '['.date ("d.m.Y H:i:s").'] Sender::send');
            }
		}
    }
}
?>