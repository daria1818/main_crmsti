<?
/**
 * xGuard Framework
 * @package xGuard * @subpackage main
 * @copyright 2014 xGuard
 */

namespace xGuard\Main\Export;

use \xGuard\Main;

/**
 * Base entity
 */
class Yandex extends \xGuard\Main
{
	private $userTmpCreated = false;
	public $application			= false;
	public $user				= false;
	public $arParams			= false;
	public $arResult			= false;
	public $db					= false;
	public $time				= 0;
	public $date				= 0;
	public $dateTime			= 0;
	public $timeStart			= 0;
	public $timeEnd				= 0;
	protected $isBufferStarted	= false;
	protected $arraySpecial		= array();
	protected $arCurrencyAllowed= array();
	protected $useBuffer		= true;
	protected $xmlFile			= false;
	protected $xmlFilePath		= '/bitrix/catalog_export/export.php';
	protected $xmlFileTempPath	= '/bitrix/catalog_export/temp.php';
	protected $xmlFileOpenFlag	= 'w+';
	protected $arOfferSelect	= array();
	protected $arSectionSelect	= array();
	protected $arSections		= '';
	protected $arOffers			= '';
	protected $arSiteServers	= array();
	protected $baseCurrency		= false;
	protected $arCurrency		= array();
	protected $price			= array();
	protected $rur				= '';
	protected $dbCatalogList	= false;
	protected $arAvailGroups	= false;
	protected $arCatalogList	= false;
	protected $arIBlock			= false;
	protected $arItem			= false;
	protected $charset			= 'UTF-8';
	
	public function __construct($options=array())
	{
        parent::__construct($options);
        $this->IncludeModule('sale');
        $this->IncludeModule('catalog');
	}
	
/*core*/
	/*
	*
	* Log to file
	* @params $text string
	*
	*/
	/*public*/
	public function Log($text, $file = "debug.dbg", $path = "/upload/debug/")
	{
		$text		= is_array($text) || is_object($text) ? print_r((array)$text,true) : $text;
		$logPath	= $_SERVER['DOCUMENT_ROOT'].$path;
		
		CheckDirPath($logPath,true);
		
		$log_file		= $logPath.$file;
		$info			= debug_backtrace();
		$info			= $info[0];
		$info['file']	= substr($info['file'],strlen($_SERVER['DOCUMENT_ROOT'])); 
		$str			= implode(':',array(date('Y.m.d H:i:s'),$info['file'],$info['line']))."\r\n".$text."\r\n";
		
		file_put_contents($log_file,$str,FILE_APPEND);
	}
	
	public function SetTempUser()
	{
		if (!$this->user || !(($this->user instanceof \CUser) && ('CUser' == get_class($this->user))))
		{
			$this->userTmpCreated = true;
			
			if ($this->user)
			{
				$this->userTmp = $this->user;
			}
			
			$this->user = new \CUser();
		}
	}
	
	public function GetTempUser()
	{
		if ($this->bTmpUserCreated)
		{
			if ($this->userTmp)
			{
				$this->user = &$this->userTmp;
			}
		}
	}
	
	public function CheckXML2XSD()
	{
		if(!class_exists('DOMDocument')||!isset($this->arParams['SCHEME_PATH']))
		{
			return false;
		}
		
		$scheme	= $this->arParams['SCHEME_PATH'];
		
		if(!file_exists($scheme))
		{
			$scheme = $_SERVER['DOCUMENT_ROOT'].$scheme;
			$scheme = str_replace('//','/',$scheme);
			
			if(!file_exists($scheme))
			{
				return false;
			}
		}
		
		$file	= $this->xmlFilePath;
		
		if(!file_exists($file))
		{
			$file = $_SERVER['DOCUMENT_ROOT'].$file;
			$file = str_replace('//','/',$file);
			
			if(!file_exists($file))
			{
				return false;
			}
		}
		
		$file = str_replace($_SERVER['DOCUMENT_ROOT'],'',$file);
		
		$scheme = str_replace('//','/',$scheme);
		$file	= str_replace('//','/',$file);
		$xml	= new DOMDocument();
		
		$xml->loadXML(file_get_contents('http://'.$_SERVER['SERVER_NAME'].$file));/*allow_url_fopen = Off*/
		
		return $xml->schemaValidateSource(file_get_contents($scheme));
	}
	
	public function SetTruncateText($text='', $length=0, $postfix='...')
	{
		if(strlen($text) > $length)
		{
			return rtrim(substr($text, 0, $length), ".").$postfix;
		}
		else
		{
			return $text;
		}
	}

	/*public*/
	/*protected*/
	/*
	*
	* Start map of object`s init
	* @params void
	*
	*/
	protected function Init()
	{
		return true;
	}
	
	protected function StartExport()
	{
		$this->dbCatalogList = \CCatalog::GetList(array(), array("YANDEX_EXPORT" => "Y", "PRODUCT_IBLOCK_ID" => 0,'LID'=>SITE_ID), false, false, array('IBLOCK_ID'));
		
		while ($this->arCatalogList = $this->dbCatalogList->Fetch())
		{
			$this->arCatalogList['IBLOCK_ID']	= (int)$this->arCatalogList['IBLOCK_ID'];
			$this->arIBlock						= \CIBlock::GetArrayByID($this->arCatalogList['IBLOCK_ID']);
			
			if(empty($this->arIBlock) || !is_array($this->arIBlock))
			{
				continue;
			}
			
			if($this->arIBlock['ACTIVE'] !== 'Y')
			{
				continue;
			}
			
			$boolRights = false;
			
			if($this->arIBlock['RIGHTS_MODE'] !== 'E')
			{
				$arRights = \CIBlock::GetGroupPermissions($this->arCatalogList['IBLOCK_ID']);
				
				if(!empty($arRights) && isset($arRights[2]))
				{
					if($arRights[2] >= 'R')
					{
						$boolRights = true;
					}
				}
			}
			else
			{
				$obRights	= new \CIBlockRights($this->arCatalogList['IBLOCK_ID']);
				$arRights	= $obRights->GetGroups(array('section_read', 'element_read'));
				
				if (!empty($arRights) && in_array('G2',$arRights))
				{
					$boolRights = true;
				}
			}
			
			if (!$boolRights)
			{
				continue;
			}
			
			$this->GetSections();
			$this->GetItems();
		}
	}
	
	protected function EndExport()
	{
		return true;
	}
	
	protected function GetSections()
	{
		$this->StartBuffer();
		$filter	= array("IBLOCK_ID"=>$this->arCatalogList["IBLOCK_ID"], "ACTIVE"=>"Y", "GLOBAL_ACTIVE"=>"Y");
		$nsSections	= \CIBlockSection::GetList(array("left_margin"=>"asc"), $filter, false, $this->arSectionSelect, false);
		
		$this->arAvailGroups = array();
		echo '<categories>';
		while ($arSection = $nsSections->Fetch())
		{
			echo '<category id="',$arSection["ID"],'"',(IntVal($arSection["IBLOCK_SECTION_ID"])>0?' parentId="'.$arSection["IBLOCK_SECTION_ID"].'"':''),'>',$this->SetText2Xml($arSection["NAME"], true),'</category>',"\n";
			
			$arSection["ID"] = (int)$arSection["ID"];
			
			$this->arAvailGroups[$arSection["ID"]] = $arSection;
		}
		echo '</categories>';
		$this->EndBuffer();
	}
	
	protected function GetItems()
	{
		$filter		= array("IBLOCK_ID"=>$this->arCatalogList["IBLOCK_ID"], "ACTIVE"=>"Y", "ACTIVE_DATE"=>"Y");
		
		if(isset($this->arParams['ITEMS']))
		{
			if(isset($this->arParams['ITEMS']['SELECT']))
			{
				if(is_array($this->arParams['ITEMS']['SELECT']))
				{
					$filter = array_merge($filter,$this->arParams['ITEMS']['SELECT']);
				}
			}
		}
		
		$nsItems	= \CIBlockElement::GetList(array(), $filter, false, false, $this->arOfferSelect);
		
		$this->StartOffers();
		$sum=0;
		while ($rsItem = $nsItems->GetNextElement())
		{
			$this->arItem = $rsItem->GetFields();
			$this->arItem['PROPERTIES'] = $rsItem->GetProperties();
			
			if(!isset($this->arParams['SERVER_NAME']))
			{
				if (!isset($this->arSiteServers[$this->arItem['LID']]))
				{
					$nsSite = \CSite::GetList(($b="sort"), ($o="asc"), array("LID" => $this->arItem["LID"]));
					
					if($arSite = $nsSite->Fetch())
					{
						$this->arItem["SERVER_NAME"] = $arSite["SERVER_NAME"];
					}
					
					if(strlen($this->arItem["SERVER_NAME"])<=0 && defined("SITE_SERVER_NAME"))
					{
						$this->arItem["SERVER_NAME"] = SITE_SERVER_NAME;
					}
					
					if(strlen($this->arItem["SERVER_NAME"])<=0)
					{
						$this->arItem["SERVER_NAME"] = \COption::GetOptionString("main", "server_name", "");
					}

					$arSiteServers[$this->arItem['LID']] = $this->arItem['SERVER_NAME'];
				}
				else
				{
					$this->arItem['SERVER_NAME'] = $this->arSiteServers[$this->arItem['LID']];
				}
				$this->arParams['SERVER_NAME'] = $this->arItem['SERVER_NAME'];
			}
			else
			{
				$this->arItem['SERVER_NAME'] = $this->arParams['SERVER_NAME'];
			}
			
			if (empty($this->arItem['DETAIL_PAGE_URL']))
			{
				$this->arItem['DETAIL_PAGE_URL'] = '/';
			}
			else
			{
				$this->arItem['DETAIL_PAGE_URL'] = str_replace(' ', '%20', $this->arItem['DETAIL_PAGE_URL']);
			}
			
			if (empty($this->arItem['~DETAIL_PAGE_URL']))
			{
				$this->arItem['~DETAIL_PAGE_URL'] = '/';
			}
			else
			{
				$this->arItem['~DETAIL_PAGE_URL'] = str_replace(' ', '%20', $this->arItem['~DETAIL_PAGE_URL']);
			}
			
			$this->GetOffer();
			$sum++;
		}
		// debugfile($sum,'filter');
		$this->EndOffers();
	}
	
	protected function GetItemQuantity()
	{
		$this->arItem['CATALOG_QUANTITY']		= '';
		$this->arItem['CATALOG_QUANTITY_TRACE']	= 'N';
		
		$nsProducts = \CCatalogProduct::GetList(
			array(),
			array('ID' => $this->arItem['ID']),
			false,
			false,
			array('ID', 'QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO')
		);
		
		if ($arProduct = $nsProducts->Fetch())
		{
			$this->arItem['CATALOG_QUANTITY']		= $arProduct['QUANTITY'];
			$this->arItem['CATALOG_QUANTITY_TRACE']	= $arProduct['QUANTITY_TRACE'];
		}

		$quantity       = doubleval($this->arItem["CATALOG_QUANTITY"]);
		$quantityTrace  = $this->arItem["CATALOG_QUANTITY_TRACE"];
		
		$this->arItem['AVAILABLE'] = ' available="true"';
		
		if ($quantity <= 0/* && $quantityTrace == "Y"*/)
		{
			$this->arItem['AVAILABLE']	= ' available="false"';
		}
		
		return true;
	}
	
	protected function GetOptimalPrice()
	{
		$this->price['MIN']				= 0;
		$this->price['MIN_RUR']			= 0;
		$this->price['MIN_GROUP']		= 0;
		$this->price['MIN_CURRENCY']	= "";

		$arPrice = \CCatalogProduct::GetOptimalPrice($this->arItem['ID'], 1, array(2), 'N', array(), $this->arIBlock['LID']);
		$this->arResult['PRICES'][] = $arPrice;
		if($arPrice)
		{
			$this->price['MIN']				= isset($arPrice['DISCOUNT_PRICE'])?$arPrice['DISCOUNT_PRICE']:$arPrice['PRICE']['PRICE'];
			$this->price['MIN_CURRENCY']	= isset($arPrice['RESULT_PRICE']['CURRENCY'])?$arPrice['RESULT_PRICE']['CURRENCY']:$arPrice['PRICE']['CURRENCY'];
			
			if ($this->baseCurrency !== $this->rur)
			{
				$this->price['MIN_RUR'] = \CCurrencyRates::ConvertCurrency($this->price['MIN'], $this->baseCurrency, $this->rur);
			}
			else
			{
				$this->price['MIN_RUR'] = $this->price['MIN'];
			}
			
			$this->price['MIN_GROUP'] = $arPrice['PRICE']['CATALOG_GROUP_ID'];
		}
		
		return ($this->price['MIN'] <= 0);
	}
	
	protected function StartOffers()
	{
		return true;
	}
	
	protected function EndOffers()
	{
		return true;
	}
	
	protected function GetOffer()
	{
		if($this->CheckOffer())
		{
			return true;
		}
		
		$this->StartOffer();
		
		foreach($this->arParams['MODEL'] as $key=>$value)
		{
			$function = 'Get'.$key;
			if(method_exists($this,$function))
			{
				if(!$this->$function($value))
				{
					return false;
				}
			}
		}
		
		$this->EndOffer();
	}
	
	protected function CheckOffer()
	{
		$this->GetItemQuantity();
		
		if($this->GetOptimalPrice())
		{
			return true;
		}
	}
	
	protected function StartOffer()
	{
		return true;
	}
	
	protected function EndOffer()
	{
		return true;
	}
	
	/*
	*
	* Set parametrs from $this->arParams
	* @params void
	*
	*/
	protected function SetParams()
	{
		if(!$this->IncludeModule("iblock") || !$this->IncludeModule("catalog") || !$this->IncludeModule("sale"))
		{
			return false;
		}
		
		$this->arOfferSelect	= array("ID", "LID", "IBLOCK_ID", "IBLOCK_SECTION_ID", "ACTIVE", "NAME", "PREVIEW_PICTURE", "PREVIEW_TEXT", "DETAIL_TEXT", "PREVIEW_TEXT_TYPE", "DETAIL_PICTURE", "LANG_DIR", "DETAIL_PAGE_URL");
		
		if(isset($this->arParams['ARRAY_OFFER_SELECT']))
		{
			if(is_array($this->arParams['ARRAY_OFFER_SELECT']))
			{
				$this->arOfferSelect = array_merge($this->arOfferSelect,$this->arParams['ARRAY_OFFER_SELECT']);
			}
		}
		
		$this->arSectionSelect	= array("ID", "IBLOCK_ID", 'IBLOCK_SECTION_ID', "SECTION_ID", "ACTIVE", "NAME","CODE","UF_*");
		
		if(isset($this->arParams['ARRAY_SECTION_SELECT']))
		{
			if(is_array($this->arParams['ARRAY_SECTION_SELECT']))
			{
				$this->arSectionSelect = array_merge($this->arSectionSelect,$this->arParams['ARRAY_SECTION_SELECT']);
			}
		}
		
		if(isset($this->arParams['MODEL_PATH']))
		{
			if(!empty($this->arParams['MODEL_PATH']))
			{
				$this->xmlFilePath = $this->arParams['MODEL_PATH'];
			}
		}

        if(isset($this->arParams['MODEL_TEMP_PATH']))
        {
            if(!empty($this->arParams['MODEL_TEMP_PATH']))
            {
                $this->xmlFileTempPath = $this->arParams['MODEL_TEMP_PATH'];
            }
        }
		
		$this->arSections		= "";
		$this->arOffers			= "";
		$this->arSiteServers	= array();
		$this->baseCurrency		= \CCurrency::GetBaseCurrency();
		
		if($this->arCurrency = \CCurrency::GetByID('RUR'))
		{
			$this->rur = 'RUR';
		}
		else
		{
			$this->rur = 'RUB';
		}
		
		if(!$this->useBuffer)
		{
			header('Content-Type: text/xml; charset='.$this->GetCharset());
		}
		else
		{
			//echo CURRENT_LANG;
		}
		
		$this->SetAdditionalParams();
	}
	/*
	*
	* Start Buffer
	* @params void
	*
	*/
	protected function StartBuffer()
	{
		if(!$this->useBuffer)
		{
			return false;
		}
		
		if($this->isBufferStarted)
		{
			ob_end_clean();
		}
		
		$this->isBufferStarted = true;
		ob_start();
	}
	/*
	*
	* End Buffer
	* @params void
	*
	*/	
	protected function EndBuffer()
	{
		if(!$this->useBuffer)
		{
			return false;
		}
		
		$this->isBufferStarted = false;
		
		$this->SaveToFile(ob_get_contents());
		ob_end_clean();
	}
	/*
	*
	* Open/Close/Write xml file
	* @params $text - string
	*
	*/
	protected function SaveToFile($text=false)
	{
		if(!$this->xmlFile)
		{
			$this->xmlFileTempPath = $this->xmlFileTempPath;
			$this->xmlFilePath = $_SERVER['DOCUMENT_ROOT'].$this->xmlFilePath;
			$this->xmlFile = fopen($this->xmlFileTempPath,$this->xmlFileOpenFlag);
			
			if($this->GetCharset()=='UTF-8')
			{
				// fwrite($this->xmlFile, "\xEF\xBB\xBF", 3);
			}
			
			return true;
		}

		if(!$this->xmlFile)
		{
			$this->Log($this->arParams);
			return false;
		}
		
		if($text)
		{
			// $text=$this->application->ConvertCharset($text, LANG_CHARSET, 'windows-1251');
			fwrite($this->xmlFile,$text);
		}
		else
		{
			fclose($this->xmlFile);
            @rename($this->xmlFileTempPath,$this->xmlFilePath);
            @chmod($this->xmlFilePath, BX_FILE_PERMISSIONS);

            echo 'OK';
		}
		
		return true;
	}
	/*
	*
	* Start Xml
	* @params void
	*
	*/	
	protected function StartXml()
	{
		return true;
	}
	
	protected function EndXml()
	{
		return true;
	}

/*core*/
/*set*/
	/*protected*/
/*
*
* Replace special symbols
* @params $arg = array
*
*/
	protected function SetReplaceSpecial($arg)
	{
		if(!count($this->arraySpecial))
		{
			$this->arraySpecial = array("&quot;"=>0, "&amp;"=>0, "&lt;"=>0, "&gt;"=>0);
		}
		
		if (isset($this->arraySpecial[$arg[0]]))
		{
			return $arg[0];
		}
		else
		{
			return " ";
		}
	}
/*
*
* Text 2 Xml
* @params $text - string
* @params $bHSC - htmlspecialcharsbx
* @params $bDblQuote - replace double quote
*
*/
	protected function SetText2Xml($text, $bHSC = false, $bDblQuote = false)
	{
		$bHSC = (true == $bHSC ? true : false);
		$bDblQuote = (true == $bDblQuote ? true : false);

		if ($bHSC)
		{
			$text = htmlspecialcharsbx($text);
			if($bDblQuote)
			{
				$text = str_replace('&quot;', '"', $text);
			}
		}
		
		$text = preg_replace('/[\x01-\x08\x0B-\x0C\x0E-\x1F]/', "", $text);
		$text = str_replace("'", "&apos;", $text);
		if((isset($this->arParams['CHARSET'])&&$this->arParams['CHARSET']!==LANG_CHARSET)||!isset($this->arParams['CHARSET']))
		{
			$text = $this->application->ConvertCharset($text, LANG_CHARSET, 'windows-1251');
		}
		return $text;
	}
	/*protected*/
	/*public*/
/*
*
* Set Currency Allowed
* @params void
*
*/
	public function SetCurrencyAllowed()
	{
		if(!count($this->arraySpecial))
		{
			$this->arCurrencyAllowed = $this->arraySpecial = array('RUR'=>'RUR', 'RUB'=>'RUB', 'USD'=>'USD', 'EUR'=>'EUR', 'UAH'=>'UAH');
		}
		
		if(isset($this->arParams['CURRENCY_ALLOWED']))
		{
			if(is_array($this->arParams['CURRENCY_ALLOWED']))
			{
				$this->arCurrencyAllowed = array_merge($this->arCurrencyAllowed,$this->arParams['CURRENCY_ALLOWED']);
			}
		}
	}
	
	public function SetCharset($a='UTF-8')
	{
		$this->charset=strlen($a)?$a:$this->charset;
		
		return $this;
	}
	
	public function GetCharset()
	{	
		return $this->charset;
	}
	/*public*/
/*set*/
/*get*/
	/*private*/
	/*private*/
	/*protected*/
	/*protected*/
	/*public*/
	/*public*/
	/*get*/
	/*dummy*/
	protected function SetAdditionalParams()
	{
		return true;
	}
	
	protected function InitBefore()
	{
		return true;
	}
	
	protected function InitAfter()
	{
		return true;
	}
	/*dummy*/
}
?>