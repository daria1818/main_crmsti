<?
namespace ApiFor1C;
class ToolsApi {

    public static function convertCodeToId($codes, $property, $iblocks = [])
    {
        if(count($codes) < 1 || empty($property) || empty($iblocks))
        {
            return [];
        }

        $arSelect = Array("ID", "PROPERTY_{$property}");
        $arFilter = Array("PROPERTY_{$property}" => $codes);

        $idItems = [];
        foreach ($iblocks as $iblock){
            $arFilter["IBLOCK_ID"] = $iblock;

            $res = \CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
            while($arItem = $res->Fetch()) {
                $idItems[$arItem["PROPERTY_{$property}_VALUE"]] = $arItem["ID"];
            }
        }

        return $idItems;
    }

    public static function getQuantityItems($items = [])
    {
        $arFilter = [];
        $productCurrentQuantity = [];
        if(!empty($items))
        {
            $arFilter["ID"] = $items;
        }
        $resAllProduct = CCatalogProduct::GetList(array(), $arFilter);
        while ($product = $resAllProduct->GetNext()) {
            $productCurrentQuantity[$product["ID"]] = $product["QUANTITY"];
        }
        return $productCurrentQuantity;
    }
    
    public static function getCashbackItems($items = NULL, $iblock = NULL)
    {
        $arItemsAll = [];
        $idKey = [];
        foreach ($items as $item)
        {
            $idKey[$item] = [];
        }
        $arProps = array("CODE" => array("REFUND_PERCENT_AUTO"));
        \CIBlockElement::GetPropertyValuesArray($idKey, CATALOG_IBLOCK, ["ID" => $items], $arProps);
        \CIBlockElement::GetPropertyValuesArray($idKey, OFFERS_IBLOCK, ["ID" => $items], $arProps);

        foreach ($idKey as $key => $item)
        {
            $arItemsAll[$key] = $item["REFUND_PERCENT_AUTO"]["VALUE"];
        }
        return $arItemsAll;
    }

    public static function getShopsQuantityItems($items = NULL, $iblock = NULL)
    {

        $arItemsAll = array();
        $idKey = [];
        foreach ($items as $item)
        {
            $idKey[$item] = [];
        }
        $arPropsOffers = array("CODE" => array("COUNT_IN_SHOPS",));
        \CIBlockElement::GetPropertyValuesArray($idKey, $iblock, ["ID" => $items], $arPropsOffers);
        foreach ($idKey as $key => $item)
        {
            $arItemsAll[$key] = [];
            if(is_array($item["COUNT_IN_SHOPS"]["VALUE"])) {
                foreach ($item["COUNT_IN_SHOPS"]["VALUE"] as $keyShop => $valueShop)
                {
                    $arItemsAll[$key][$valueShop] = $item["COUNT_IN_SHOPS"]["DESCRIPTION"][$keyShop];
                }
            }
        }
        return $arItemsAll;
    }

    public static function getPriceItems($items = NULL, $iblock = NULL)
    {
        $result = [];
        $arFilter = [];
        if(!empty($items))
        {
            $arFilter["PRODUCT_ID"] = $items;
        }
        if(!empty($iblock))
        {
            $arFilter["IBLOCK_ID"] = $iblock;
        }

        $db_res = CPrice::GetList(
            array(),
            $arFilter  //TODO тип цены
        );
        while ($ar_res = $db_res->Fetch())
        {
            $result[$ar_res["PRODUCT_ID"]] = $ar_res["PRICE"];
        }

        return $result;
    }

    public static function setPriceItems($items = NULL)
    {
        foreach ($items as $id => $pricet)
        {
            CPrice::Update($id, array("PRICE" => $pricet)); //TODO тип цены
        }
    }

    public static function getEmptyCode($itemsCode = [], $itemsSite = [])
    {
        $emptyCode = [];
        if(count($itemsCode) != count($itemsSite))
        {
            foreach ($itemsCode as $code)
            {
                if(empty($itemsSite[$code])){
                    $emptyCode[] = $code;
                }
            }
        }
        return $emptyCode;
    }

    public static function convertDataCodeToId($data = [], $codeToId = [])
    {
        $idValue = [];
        foreach ($data as $key => $item)
        {
            if(!empty($codeToId[$key]))
            {
                $idValue[$codeToId[$key]] = $item;
            }
        }
        return $idValue;
    }
}
?>