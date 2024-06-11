<?php
use \Pwd\Entity\CheckInTable,
    \Bitrix\Main\Mail\Event;

class sopdu {
		function copyright($currentYeare, $copyright){
			if($currentYeare == date('Y')){
				$yeare = date('Y');
			} else {
				$yeare = $currentYeare.' - '.date('Y');
			}
			$result = '&copy;'.$yeare.'&nbsp;'.$copyright;
			return $result;
		}
		function phone($path){
			$phone = file_get_contents($path);
			$result = preg_replace('/[^0-9]/', '', $phone);
			return 'tel:+'.$result;
		}
		function phone_text($phone){
			return 'tel:+'.preg_replace('/[^0-9]/', '', $phone);
		}
		public function dump($value){
			$filePath = $_SERVER["DOCUMENT_ROOT"].'/ilsDump.txt';
			$file = fopen($filePath, "w");
			fwrite($file, print_r($value, 1));
			#fclose(); // не удалять - надо тестировать
			return;
		}
		function clearData($value = ''){
			$value = trim($value);
			$value = stripcslashes($value);
			$value = strip_tags($value);
			$value = htmlspecialchars($value);
			return $value;
		}
		/*
		public function dump($value){
			$filePath = $_SERVER["DOCUMENT_ROOT"].'/ilsDump.txt';
			$file = fopen($filePath, "w");
			fwrite($file, print_r($value, 1));
			#fclose();
			return;
		}
		*/
		public function translit($s){
			$s = (string)$s;
			$s = strip_tags($s);
			$s = str_replace(array("\n", "\r"), " ", $s);
			$s = preg_replace("/\s+/", ' ', $s);
			$s = trim($s);
			$s = function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s);
			$s = strtr($s, array('а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'j', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ы' => 'y', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya', 'ъ' => '', 'ь' => '', ' ' => '_'));
			$s = preg_replace("/[^0-9a-z-_ ]/i", "", $s);
			$s = str_replace(" ", "-", $s);
			return $s;
		}
		public function Decode($str) {
			$encode = mb_detect_encoding($str);
			if($encode == 'UTF-8') {
				$decode = $str;
			} else {
				$decode = iconv($encode, 'UTF-8', $str);
			}
			return $decode;
		}
		public function Log($data, $title = ''){
			define('DEBUG_FILE_NAME', date('Y-m-d').'.log');
			if(!DEBUG_FILE_NAME){ return false; }
			$log = "\n------------------------\n";
			$log .= date("Y.m.d G:i:s")."\n";
			#$log .= $this->GetUser()."\n";
			$log .= (strlen($title) > 0 ? $title : 'DEBUG')."\n";
			$log .= print_r($data, 1);
			$log .= "\n------------------------\n";
			file_put_contents(__DIR__."/".DEBUG_FILE_NAME, $log, FILE_APPEND);
			#file_put_contents($_SERVER["DOCUMENT_ROOT"]."/ils_log/".DEBUG_FILE_NAME, $log, FILE_APPEND);
			return;
		}
		public static function generationPass() {
			$num = 8;
			$array = [
				'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','r','s','t','u','v','x','y','z',
				'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','R','S','T','U','V','X','Y','Z',
				'1','2','3','4','5','6','7','8','9','0'
			];
			$pass = '';
			for ($i = 0; $i < $num; $i++){
				$ind = rand(0, count($array) - 1);
				$pass .= $array[$ind];
			}
			return $pass;
		}
		
		public function mailLoginEmail(&$arFields){
			$arFields["LOGIN"] = $arFields["EMAIL"];
			return $arFields;
		}

        /**
         * Првоеряем изменение статуса и высылаем сообщение
         * @param $arFields
         * @return mixed
         * @throws \Bitrix\Main\ArgumentException
         * @throws \Bitrix\Main\ObjectPropertyException
         * @throws \Bitrix\Main\SystemException
         */
		public function changeStatusAward(&$arFields){
		    if($arFields['IBLOCK_ID'] != CONST_IBLOCK_ID_CHECKIN){
		        return $arFields;
            }

            $arData = CheckInTable::getList([
                'filter' => [
                    'ACTIVE' => 'Y',
                    'ID'     => $arFields['ID']
                ],
                'select' => [
                    'USER'  => 'PROPERTY_SIMPLE.USER',
                    'STATE' => 'PROPERTY_SIMPLE.STATE',
                ],
            ])->fetch();

            $iProperty = CheckInTable::getPropertyIdByCode('STATE');
            list('VALUE' => $iNewValue) = $arFields['PROPERTY_VALUES'][$iProperty][0];

            $arUser =  \CUser::GetByID($arData['USER'])->fetch();

            if($iNewValue != $arData['STATE'] && !empty($arUser['EMAIL'])) {
                Event::send(array(
                    "EVENT_NAME" => 'CHECK_IN_CHANGE_STATUS',
                    "LID" => "s1",
                    "C_FIELDS" => [
                        'EMAIL' => $arUser['EMAIL']
                    ]
                ));
            }
            return $arFields;
        }
        /**
         * Ответ на вопрос в разделе
         * @param $arFields
         * @return mixed
         * @throws \Bitrix\Main\ArgumentException
         * @throws \Bitrix\Main\ObjectPropertyException
         * @throws \Bitrix\Main\SystemException
         */
		public function answerQuestion(&$arFields){
		    if($arFields['IBLOCK_ID'] != CONST_IBLOCK_ID_QUESTIONS_TO_DOCTOR){
		        return $arFields;
            }
            if(empty(trim($arFields['DETAIL_TEXT']))){
                return $arFields;
            }

            //Достаем ID пользователя ,

            //TODO::переделать если будет несоклько св-в

            $arPropUser = array_shift(array_shift($arFields['PROPERTY_VALUES']));
            $iUserID = $arPropUser['VALUE'];

            $arUser =  \CUser::GetByID($iUserID)->fetch();

            if(!empty($arUser['EMAIL'])) {
                Event::send(array(
                    "EVENT_NAME" => 'ASK_QUESTION_USER',
                    "LID" => "s1",
                    "C_FIELDS" => [
                        'QUESTION' => $arFields['PREVIEW_TEXT'],
                        'ANSWER'   => $arFields['DETAIL_TEXT'],
                        'EMAIL' => $arUser['EMAIL']
                    ]
                ));
            }
            return $arFields;
        }


		public function mailRegistrationEmail(&$arFields){
			if (intval($arFields["ID"])>0){
				$toSend = Array();
				$toSend["PASSWORD"] = $arFields["CONFIRM_PASSWORD"];
				$toSend["EMAIL"] = $arFields["EMAIL"];
				$toSend["USER_ID"] = $arFields["ID"];
				$toSend["USER_IP"] = $arFields["USER_IP"];
				$toSend["USER_HOST"] = $arFields["USER_HOST"];
				$toSend["LOGIN"] = $arFields["LOGIN"];
				$toSend["NAME"] = (trim ($arFields["NAME"]) == "")? $toSend["NAME"] = htmlspecialchars('<Не указано>'): $arFields["NAME"];
				$toSend["LAST_NAME"] = (trim ($arFields["LAST_NAME"]) == "")? $toSend["LAST_NAME"] = htmlspecialchars('<Не указано>'): $arFields["LAST_NAME"];
//				CEvent::SendImmediate ("sopdu_NEW_USER", SITE_ID, $toSend); // Письмо о регистрации на сайте
			}
			//return $arFields;
			return;
		}
		public function mailRefPass(&$arFields){
			if(intval($arFields["ID"])>0){
				$toSent = Array();
				
				
				
			}
		}

        /**
         * читаемы размеры файлов
         * @param $size
         * @param string $unit
         * @return string
         */
        public static function humanFileSize($size,$unit="") {
            if( (!$unit && $size >= 1<<30) || $unit == "гб")
                return number_format($size/(1<<30),2)." гб";
            if( (!$unit && $size >= 1<<20) || $unit == "мб")
                return number_format($size/(1<<20),2)." мб";
            if( (!$unit && $size >= 1<<10) || $unit == "кб")
                return number_format($size/(1<<10),2)." кб";
            return number_format($size)." б";
        }
        /**
         * @param $iIBlockID
         * @param $sXmlId
         * @return int|null
         */
		public static function getEnumIdByXmlID($iIBlockID, $sXmlId): ?int
        {
		    $arResult = CIBlockPropertyEnum::GetList([],
                [
                    'IBLOCK_ID' => $iIBlockID,
                    'XML_ID'   => $sXmlId,
                ])->fetch();

		    return $arResult['ID'] ?? false;
        }

        /**
         * Добавляем watermark
         * @param $photo
         */
        public static function getImgWithWatermark($photo){
            $arImgFilter = [
                [
                    "name"           => "watermark",
                    "position"       => "tl",        // (доступные варианты tl|tc|tr|ml|mc|mr|bl|bc|br или topleft|topcenter|topright|centerleft|center|centerright|bottomleft|bottomcenter|bottomright)
                    "type"           => "image",     // (доступные варианты "image|text")
                    "size"           => "small",      // ( доступные варианты big|medium|small|real; real доступен только для type=image )
                    'alpha_level'    =>   1000,
                    "file"           => $_SERVER["DOCUMENT_ROOT"] . SITE_TEMPLATE_PATH . "/img/print-watermark.png",  // (абсолютный путь до картинки с водяным знаком)
                    "fill" => "exact",
                ]
            ];
            $arSizeORIG = getimagesize($_SERVER['DOCUMENT_ROOT'].CFile::GetPath($photo));
            $widthBIG =  intval($arSizeORIG[0])-1; //echo  $widthBIG;
            $heightBIG =  intval($arSizeORIG[0])-1; //echo $heightBIG;
            $thumbBIG = CFile::ResizeImageGet(
                $photo,
                [
                    'width' => $widthBIG,
                    'height' => $heightBIG
                ],
                BX_RESIZE_IMAGE_PROPORTIONAL,
                true,
                $arImgFilter
            );
            return $thumbBIG['src'];
        }
	}
	$sopdu = new sopdu();
?>
