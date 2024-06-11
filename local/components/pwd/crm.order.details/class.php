<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();


use Bitrix\Main;
use Bitrix\Crm\Order;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\EntityPropertyValueCollection;
use Bitrix\Sale\Services;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Sale\Internals\Input\Manager;

if (!Main\Loader::includeModule('crm')) {
    ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
    return;
}

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass('bitrix:crm.order.details');

class CCrmOrderDetailsComponentAddConnections extends CCrmOrderDetailsComponent
{
    public static $replaceCode = [
        'ADDRESS_REGISTER' => EntityAddress::Registered,
        'COMPANY_REG_ADDRESS' => EntityAddress::Registered,
        'ADDRESS_DELIVERY' => EntityAddress::Delivery,
        'DELIVERY_ADDRESS' => EntityAddress::Delivery,
    ];

    public static $actualField = [
        'POSTAL_CODE',
        'COUNTRY',
        'PROVINCE',
        'REGION',
        'CITY',
        'ADDRESS_1',
        'ADDRESS_2',
    ];

    public static $addressData = [];

    public function getAddressCompany(?int $companyID): void
    {
        $entityRequest = new EntityRequisite();
        $entityCompanyID = $entityRequest->getList([
            "filter" => [
                "ENTITY_ID" => $companyID,
                "ENTITY_TYPE_ID" => CCrmOwnerType::Company,
            ],
        ])->fetch()['ID'];

        if (!$entityCompanyID) {
            self::$replaceCode = [];
            return;
        }

        $address = Bitrix\Crm\EntityRequisite::getAddresses($entityCompanyID);
        foreach ($address ?: [] as $type => $value) {
            $value = array_intersect_key($value, array_flip(self::$actualField));
            $value = array_diff($value, ['']);
            $value = array_merge(array_flip(self::$actualField), $value);

            foreach (self::$replaceCode as $code => &$addressType) {
                if($type == $addressType){
                    $addressType = implode(', ', $value);
                }
            }
            unset($addressType);
        }

    }

    /**
     * @param EntityPropertyValueCollection $entityPropertyValueCollection
     * @return array
     */
    public function getPropertyEntityData(EntityPropertyValueCollection $entityPropertyValueCollection)
    {
        $properties = [];
        $propertyCollection = $entityPropertyValueCollection;

        $this->getAddressCompany($this->request->get('company_id'));

        /**@var Bitrix\Sale\PropertyValue $property */
        foreach ($propertyCollection as $property) {
            $code = null;
            $propertyData = $property->getProperty();

            if ((int)$propertyData['ID'] > 0) {
                $code = (int)$propertyData['ID'];
            } elseif (is_array($property->getValue()) || $property->getValue() <> '') {
                $code = 'n' . $property->getId();
            }

            if (empty($code)) {
                continue;
            }

            $simplePropertyTypes = ['STRING', 'NUMBER', 'ENUM', 'DATE', 'Y/N'];
            if (!in_array($property->getType(), $simplePropertyTypes, true)) {
                $params = $property->getProperty();
                $name = "PROPERTY_{$code}";
                $params['ONCHANGE'] = "BX.onCustomEvent('CrmOrderPropertySetCustom', ['{$name}']);";

                if ($property->getType() === 'LOCATION') {
                    $params['IS_SEARCH_LINE'] = true;
                }

                $html = Manager::getEditHtml(
                    $name,
                    $params,
                    $property->getValue()
                );

                $properties["{$name}_EDIT_HTML"] = $html;
                $properties["{$name}_VIEW_HTML"] = $property->getValue() ? $property->getViewHtml() : "";
                $properties["{$name}_EMPTY_HTML"] = Loc::getMessage('CRM_ORDER_NOT_SELECTED');
            }

            $properties['PROPERTY_' . $code] = (self::$replaceCode[$propertyData['CODE']]) ?: $property->getValue();
        }

        return $properties;
    }

    public function prepareConfiguration()
    {
        parent::prepareConfiguration();
        foreach($this->arResult['ENTITY_CONFIG'] as &$item){
            if($item['name'] != 'main')
                continue;
            array_unshift($item['elements'], ['name' => 'LID']);
            break;
        }
        return $this->arResult['ENTITY_CONFIG'];
    }

    protected function getEventTabParams(): array
    {
        $result = parent::getEventTabParams();
        $document = new \Bitrix\Crm\Integration\DocumentGeneratorManager();
        $url = $document->getDocumentDetailUrl(
            $this->arResult['ENTITY_INFO']['ENTITY_TYPE_ID'],
            $this->arResult['ENTITY_INFO']['ENTITY_ID'],
            null,
            $this->arResult['SITE_ID'] == 'dm' ? 18 : 2
        );
        $this->arResult['TABS'][] = [
            'id' => 'bill',
            'name' => 'Счёт',
            'tariffLock' => "BX.DocumentGenerator.Document.onBeforeCreate('" . htmlspecialchars_decode($url->getUri()) . "',{},'/bitrix/components/bitrix/crm.document.view/templates/.default/images/document_view.svg','crm')"
        ];
        return $result;
    }
}