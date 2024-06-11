<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;

\CJSCore::Init(array("jquery"));

class BpConnectComponent extends CBitrixComponent implements Controllerable
{
	/**
	 * Конфигурируем AJAX методы
	 * @return array
	 */
	public function configureActions()
	{
		// Сбрасываем фильтры по-умолчанию (ActionFilter\Authentication и ActionFilter\HttpMethod)
		// Предустановленные фильтры находятся в папке /bitrix/modules/main/lib/engine/actionfilter/
		return [
			'getField' => [ // Ajax-метод
				'prefilters' => [
					new ActionFilter\HttpMethod([
						ActionFilter\HttpMethod::METHOD_POST
					])
				],
			],
			'getFieldValue' => [ // Ajax-метод
				'prefilters' => [
					new ActionFilter\HttpMethod([
						ActionFilter\HttpMethod::METHOD_POST
					])
				],
			],
		];
	}

	/**
	 * Обработка входных параметров
	 *
	 * @param mixed[] $arParams
	 *
	 * @return mixed[] $arParams
	 */
	public function onPrepareComponentParams($arParams)
	{
		return $arParams;
	}

	/**
	 * выполняет логику работы компонента
	 *
	 * @return void
	 */
	public function executeComponent()
	{
		try {
			$this->includeComponentTemplate();
		} catch (Exception $e) {
			ShowError($e->getMessage());
		}
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getFieldAction(): array
	{
		$rsData = \CUserTypeEntity::GetList(array("ID" => "ASC"), array('LANG' => 'ru', 'ENTITY_ID' => 'CRM_DEAL'));
		$field = $rsData->Fetch();
		while ($field = $rsData->Fetch()) {
			if ($field['EDIT_FORM_LABEL'] === 'Тип сделки') {
				break;
			}
		}
		$code = $field['FIELD_NAME'];

		$enums = [];
		$obEnum = new \CUserFieldEnum;
		$rsEnum = $obEnum->GetList(array(), array("USER_FIELD_ID" => $field['ID']));
		while ($arEnum = $rsEnum->Fetch()) {
			if($arEnum['VALUE'] === 'Заказ ИМ'){
				$enum = $arEnum;
			}
		}

		return [
			"field" => $field,
			"enum" => $enum,
			"code" => $code,
		];
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getFieldValueAction(): array
	{
		$deal = $this->request->getPostList()->toArray()['idDeal'];
		$fieldId = $this->request->getPostList()->toArray()['idField'];
		$arUserFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("CRM_DEAL", $deal);
		foreach ($arUserFields as $id => $field){
			if($id == $fieldId){
				$userField = $field;
			}
		}

		return [
			"field" => $userField,
			"fieldId" => $fieldId,
			"allFields" => $arUserFields,
			"request" => $this->request->getPostList()->toArray(),
		];
	}

}

?>
