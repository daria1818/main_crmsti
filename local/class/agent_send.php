<?
function AgentSgReport()
{
	if (CModule::IncludeModule("support")){
		
		/* Получаем всех "Участников согласования" на  этапе "Согласование документа"*/

		$arResult = array();
		$arResultDocs = array();
		$arProc = array(4495, 4500, 4496,);
		$arProcStatus = array(4486);
		$arFilter = array("IBLOCK_ID" => 109, "PROPERTY_MW_PROC_CURRENT" => $arProc, "!PROPERTY_MW_STATUS_CURRENT" => $arProcStatus,);
		$res = CIBlockElement::GetList(array(), $arFilter);

		while ($ob = $res->GetNextElement()){
			$arResult['fields'] = $ob->GetFields();
			$arResult['prop'] = $ob->GetProperties();
			$arResultDocs[$arResult['fields']['ID']] = $arResult['prop']['MW_USERS_PARTICIP']['VALUE'];
		}

		/* Проверяем "Историю согласования" на наличие статуса "Согласовано" от всех "Участников согласования" */

		foreach ($arResultDocs as $IdDocs => $IdUsers) {
			$arFilter = array("IBLOCK_ID" => 111, "PROPERTY_MW_COM_DOC" => $IdDocs, "PROPERTY_MW_COM_PROC" => 4495, "PROPERTY_MW_COM_STATUS" => 4487,);
			$res = CIBlockElement::GetList(array('timestamp_x' => 'ask'), $arFilter);
			while ($ob = $res->GetNextElement()) {
				$fields = $ob->GetFields();
				$props = $ob->GetProperties();
				$date_utv = $fields["TIMESTAMP_X"];
			}
			$now_date = date('d.m.Y');
			$premium_date = date("d.m.Y H:i:s", strtotime("+3 days", strtotime($date_utv)));
			
			$premium_date_unix = strtotime($premium_date);
			$now_date_unix = strtotime($now_date);
			
			foreach ($IdUsers as $IdUser) {
				$arFilter = array("IBLOCK_ID" => 111, "PROPERTY_MW_COM_DOC" => $IdDocs, "PROPERTY_MW_COM_PROC" => 4500, "PROPERTY_MW_COM_STATUS" => 4485, "PROPERTY_MW_COM_USER" => $IdUser,);
				$res = CIBlockElement::GetList(array('timestamp_x' => 'desc'), $arFilter);
				
				$cnt = CIBlockElement::GetList(
					array(),
					$arFilter,
					array(),
					false,
					array('ID', 'NAME')
				);
				
				if($cnt > 0){
					
				}else{
					if($now_date_unix > $premium_date_unix) {
						
						$filter = array('ID' => $IdUser);
						$rsUsers = CUser::GetList(($by="id"), ($order="desc"), $filter);
						$userEmails = array();
						while ($arUser = $rsUsers->GetNext()) {
							$userEmails[] = $arUser['EMAIL'];
						}

						$userEmails = implode(',', $userEmails);

						$params = array(
							'TITLE' => "Согласование документа №{$IdDocs}",
							'MESSAGE_TEXT' => 'Документ ожидает согласования более 3-х дней',
							'EMAILS' => $userEmails
						);

						$eventName = "WORKFLOW_NOTIFY";

						$arFields = array(
							'FROM_EMAIL' => 'noreply@niioz.ru',
							//'EMAIL_TO' => 'ga@a-daru.ru, ga@no-ri.ru',
							'EMAIL_TO' => $params['EMAILS'],
							'TITLE' => $params['TITLE'],
							'MESSAGE_TEXT' => $params['MESSAGE_TEXT'],
						);

						$arrSite = 's1';

						$event = new CEvent;
						$event->SendImmediate($eventName, $arrSite, $arFields, "N", 95);
					}
				}
			}
		}
	}
	return "AgentSgReport();";
}

function AgentPdReport()
{
	if (CModule::IncludeModule("support")){
		
		/* Получаем всех "Подписантов" на  этапе "Подписание документа"*/

		$arResult = array();
		$arResultDocs = array();
		$arProc = array(4497);
		$arFilter = array("IBLOCK_ID" => 109, "PROPERTY_MW_PROC_CURRENT" => $arProc,);
		$res = CIBlockElement::GetList(array(), $arFilter);

		while ($ob = $res->GetNextElement()){
			$arResult['fields'] = $ob->GetFields();
			$arResult['prop'] = $ob->GetProperties();
			$arResultDocs[$arResult['fields']['ID']] = $arResult['prop']['MW_PODPISANT']['VALUE'];
		}

		/* Проверяем "Историю подписания" на наличие статуса "Подписано"*/

		foreach ($arResultDocs as $IdDocs => $IdUsers) {
			$arFilter = array("IBLOCK_ID" => 111, "PROPERTY_MW_COM_DOC" => $IdDocs, "PROPERTY_MW_COM_PROC" => 4496, "PROPERTY_MW_COM_STATUS" => 4485,);
			$res = CIBlockElement::GetList(array('timestamp_x' => 'ask'), $arFilter);
			while ($ob = $res->GetNextElement()) {
				$fields = $ob->GetFields();
				$props = $ob->GetProperties();
				$date_utv = $fields["TIMESTAMP_X"];
			}
			$now_date = date('d.m.Y');
			$premium_date = date("d.m.Y H:i:s", strtotime("+3 days", strtotime($date_utv)));
			
			$premium_date_unix = strtotime($premium_date);
			$now_date_unix = strtotime($now_date);
			
			$arFilter = array("IBLOCK_ID" => 111, "PROPERTY_MW_COM_DOC" => $IdDocs, "PROPERTY_MW_COM_PROC" => 4497, "PROPERTY_MW_COM_STATUS" => 4492, "PROPERTY_MW_COM_USER" => $IdUsers,);
			$res = CIBlockElement::GetList(array('timestamp_x' => 'desc'), $arFilter);
			
			$cnt = CIBlockElement::GetList(
				array(),
				$arFilter,
				array(),
				false,
				array('ID', 'NAME')
			);
			
			if($cnt > 0){
				
			}else{
				if($now_date_unix > $premium_date_unix) {
					
					$filter = array('ID' => $IdUsers);
					$rsUsers = CUser::GetList(($by="id"), ($order="desc"), $filter);
					$userEmails = array();
					while ($arUser = $rsUsers->GetNext()) {
						$userEmails[] = $arUser['EMAIL'];
					}

					$userEmails = implode(',', $userEmails);

					$params = array(
						'TITLE' => "Подписание документа №{$IdDocs}",
						'MESSAGE_TEXT' => 'Документ ожидает подписания более 3-х дней',
						'EMAILS' => $userEmails
					);

					$eventName = "WORKFLOW_NOTIFY";

					$arFields = array(
						'FROM_EMAIL' => 'noreply@niioz.ru',
						//'EMAIL_TO' => 'ga@a-daru.ru, ga@no-ri.ru',
						'EMAIL_TO' => $params['EMAILS'],
						'TITLE' => $params['TITLE'],
						'MESSAGE_TEXT' => $params['MESSAGE_TEXT'],
					);

					$arrSite = 's1';

					$event = new CEvent;
					$event->SendImmediate($eventName, $arrSite, $arFields, "N", 95);
				}
			}
		}
	}
	return "AgentPdReport();";
}
