<?
namespace ApiFor1C;

use \Bitrix\Main\Diag\Debug, \Bitrix\Main\Loader;
/**
 * Класс для обработки входящих запросов
 */
class ApiProvider {
    protected 
		/**
	     * [$messages Переменная для хранения ответов, ошибок и т.д.]
	     * @var array
	     */
    	$messages = [
            "empty_method" => "не передан метод",
            "empty_data" => "не переданы данные",
            "undefined_method" => "отсутствует метод",
        ],
	    /**
		 * [$result Переменная для хранения результатов]
		 * @var array
		 */    
    	$result,
    	/**
    	 * Запрос
    	 */
    	$request;
    public 
    	/**
    	 * Переменная для хранения ошибок
    	 */
    	$error1C,
    	/**
    	 * ID Инфоблоков каталога и торговых предложений
    	 * @var [array]
    	 */
    	$itemIblocks = IBLOCKS_CATALOG;

    /**
     * [__construct]
     * @param [type] $request [Запрос]
     * @param [type] $method  [Метод запроса]
     */
    public function __construct($request, $method)
    {
        Loader::includeModule("iblock");
        Loader::includeModule("sale");

        $this->result["error"] = [];

        if(empty($request))
        {
            $this->result["error"][] = $this->messages["empty_data"];
        }
        elseif(empty($method))
        {
            $this->result["error"][] = $this->messages["empty_method"];
        }

        $this->request = $request;

        $this->$method();

        if($this->result["update_cache"] == true){
            foreach ($this->itemIblocks as $iblock){
                \CIBlock::clearIblockTagCache( $iblock );
            }
        }
        $this->set1CError();

        return;
    }

    /**
     * [getResult Получить результат]
     * @return [array] [результат]
     */
    public function getResult()
    {
        return $this->result;
    } 

    /**
     * [getLastError Получить последнюю ошибку]
     * @return [array] [Ошибки]
     */
    public function getLastError()
    {
        return $this->error1C;
    } 

    /**
     * [register_new_user Тестирование запроса]
     * @return [type] [description]
     */
    public function register_new_user()
    {
        $return = json_encode(array("STATUS"=>"OK"));
        $this->result = $return;
    } 

    /**
     * [search_counterpart Тестирование запроса]
     * @return [type] [description]
     */
    public function search_counterpart()
    {
        $return = json_encode(array("PERSON_TYPE"=>"I","FIO"=>"Фамилия Имя Отчество"));
        $this->result = $return;
    } 

    /**
     * [add_counterpart Тестирование запроса]
     */
    public function add_counterpart()
    {
        $return = json_encode(array("STATUS"=>"УИН_1С"));
        $this->result = $return;
    }

    /**
     * [__call Вызов несуществующего метода]
     * @param  [type] $name   [название метода]
     * @param  array  $params [параметры метода]
     */
    public function __call($name,array $params)
    {
        $this->result["error"][] = $this->messages["undefined_method"];
    }

    /**
     * [set1CError Обработка ошибок]
     * @return [json] [массив ошибок]
     */
    public function set1CError()
    {
        if(!empty($this->result["error"]))
        {
            $this->error1C = ["error" => implode(" | ",$this->result["error"])];
            unset($this->result["error"]);
        }
        else{
            $this->error1C = ["error" => null];
        }
        $this->error1C = json_encode($this->error1C);
    }
}
?>