<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Sale\Location;





function getIblockProps($value, $propData, $arSize = array("WIDTH" => 90, "HEIGHT" => 90), $orderId = 0)
{
	$res = array();

	if ($propData["MULTIPLE"] == "Y")
	{
		$arVal = array();
		if (!is_array($value))
		{
			if (strpos($value, ",") !== false)
				$arVal = explode(",", $value);
			else
				$arVal[] = $value;
		}
		else
			$arVal = $value;

		if (count($arVal) > 0)
		{
			foreach ($arVal as $key => $val)
			{
				if ($propData["PROPERTY_TYPE"] == "F")
					$res[] = getFileData(trim($val), $orderId, $arSize);
				else
					$res[] = array("type" => "value", "value" => $val);
			}
		}
	}
	else
	{
		if ($propData["PROPERTY_TYPE"] == "F")
			$res[] = getFileData($value, $orderId, $arSize);
		else
			$res[] = array("type" => "value", "value" => $value);
	}

	return $res;
}

function getFileData($fileId, $orderId = 0, $arSize = array("WIDTH" => 90, "HEIGHT" => 90))
{
	$res = "";
	$arFile = CFile::GetFileArray($fileId);

	if ($arFile)
	{
		$is_image = CFile::IsImage($arFile["FILE_NAME"], $arFile["CONTENT_TYPE"]);
		if ($is_image)
		{
			$arImgProduct = CFile::ResizeImageGet($arFile, array("width" => $arSize["WIDTH"], "height" => $arSize["HEIGHT"]), BX_RESIZE_IMAGE_PROPORTIONAL, false, false);

			if (is_array($arImgProduct))
				$res = array("type" => "image", "value" => $arImgProduct["src"]);
		}
		else
			$res = array("type" => "file", "value" => "<a href=".$arFile["SRC"].">".$arFile["ORIGINAL_NAME"]."</a>");
	}

	return $res;
}
?>