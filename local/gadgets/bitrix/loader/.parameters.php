<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("iblock"))
{
    ShowMessage(GetMessage("IBLOCK_ERROR"));
    return false;
}

//  получение списка инфоблоков
$dbIBlocks = CIBlock::GetList(
    array(
        "name"  =>  "asc",
    ),
    array(
        "ACTIVE"    =>  "Y",
    ),
    true
);

while ($arIBlocks = $dbIBlocks->GetNext())
{
    $iblocks[$arIBlocks["ID"]] = "[" . $arIBlocks["ID"] . "] " . $arIBlocks["NAME"] . " (" . $arIBlocks["ELEMENT_CNT"] . ")";
    $last = $arIBlocks["ID"];
}

//  Получение списка свойств
$dbProperties = CIBlockProperty::GetList(
    array(
        "NAME"  =>  "ASC"
    ),
    array(
        "ACTIVE"    =>  "Y",
        "IBLOCK_ID" =>  $arAllCurrentValues["IBLOCK_ID"]["VALUE"]
    )
);
while ($arProperties = $dbProperties->GetNext())
{
    $properties[$arProperties["CODE"]] = $arProperties["NAME"];
}

$arParameters = Array(
    "PARAMETERS"=> Array(

    ),
    "USER_PARAMETERS" => Array(
        "IBLOCK_ID" => Array(
            "NAME"  => "Инфоблок",
            "TYPE"  => "LIST",
            "VALUES"    =>  $iblocks,
            "REFRESH"   =>  "Y"
        ),
        "PROPERTIES"    =>  array(
            "NAME"  =>  "Свойство",
            "TYPE"  =>  "LIST",
            "MULTIPLE"  =>  "N",
            "VALUES"    =>    $properties
        ),
    ),
);
?>