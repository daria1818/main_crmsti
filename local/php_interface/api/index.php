<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$data1C = json_decode(file_get_contents('php://input'), true);

if ($_GET["test"] == "Y") {

    $data1C = [
        "products" => [
            [
                "id" => "X003",
                "percent" => 5,
            ],
            [
                "id" => "2879",
                "percent" => 10
            ],
        ]
	];
}

if ($_GET["test"] != "Y") {
	Bitrix\Main\Diag\Debug::writeToFile($_REQUEST, "request", $_SERVER["DOCUMENT_ROOT"]."/local/php_interface/api/logs/requestLogs.txt");
	Bitrix\Main\Diag\Debug::writeToFile(file_get_contents('php://input'), "input", $_SERVER["DOCUMENT_ROOT"]."/local/php_interface/api/logs/requestLogs.txt");
}
/**
 * Принимает внешние запросы и перенаправляет в АПИ провайдер 
 */
$provider = new \ApiFor1C\ApiProvider($data1C, $_REQUEST["method"]);
$GLOBALS["APPLICATION"]->RestartBuffer();
/**
 * Возвращает ответ АПИ провайдера
 */
echo $provider->getResult() ? : $provider->getLastError();
die();
?>