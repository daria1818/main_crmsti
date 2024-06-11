<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$catalogId = "catalogId";

$auth = base64_encode("login:pass");
$context = stream_context_create([
    "http" => [
        "header" => "Authorization: Basic $auth"
    ]
]);
$strUpdate = file_get_contents("http://..../export.xml", false, $context );

$xml = new SimpleXMLElement($strUpdate);


$catalogs = [];
foreach ($xml->ПакетПредложений as $catalog)
{
    $products = [];
    foreach ($catalog->Предложения->Предложение as $item)
    {
        $shopsCount = [];
        foreach ($item->Склад as $sklad){
            $shopsCount[(string)$sklad["ИдСклада"]]=
                [
                    "store_id" => (string)$sklad["ИдСклада"],
                    "quantity" => trim((string)$sklad["КоличествоНаСкладе"])
                ];
        }

        $products[] =
            [
                "id" => (string)$item->Ид,
                "store" => $shopsCount,
                "price" => (string)$item->Цены->Цена->ЦенаЗаЕдиницу,
            ];
    }


    if($catalog->Ид == $catalogId)
    {
        CModule::IncludeModule("iblock");
        $updQuantity = new Api_1C\Update\Product();
        $updQuantity->typeUpdateShops =  "site_and_shops_export";
        $resultUpd = $updQuantity->update($products, ["quantity", "shops", "price"]);
        echo "<pre>".print_r($resultUpd, true)."</pre>";
    }
}