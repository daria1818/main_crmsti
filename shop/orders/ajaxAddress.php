<?
include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use \Bitrix\Main\Loader;
use \Bitrix\Location\Entity\Address;
use \Bitrix\Crm\EntityAddress;

Loader::includeModule("sale");
Loader::includeModule("location");
Loader::includeModule("main");
Loader::includeModule("crm");

if($_SERVER['REQUEST_METHOD'] === 'POST')
{
	$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
	$list = $request->getPostList();

	$typeContact = \CCrmOwnerType::Contact;
	$typeCompany = \CCrmOwnerType::Company;
	$reqisite = \CCrmOwnerType::Requisite;
	$typeDelivery = \Bitrix\Crm\EntityAddressType::Delivery;

	if($list['ORDER_ID'] || $list['CLIENT_ID'] && $list['CLIENT_TYPE'])
	{
		if(!empty($list['ADDRESS']))
		{
			$address_string = $list['ADDRESS'];
		}
		elseif($list['ORDER_ID'] > 0)
		{
			$order = \Bitrix\Crm\Order\Order::load($list['ORDER_ID']);
			$properties = $order->getPropertyCollection();
			$ar = $properties->getArray();
			$address_string = '';
			foreach($ar['properties'] ?:[] as $prop){
			    if($prop['CODE'] == "ADDRESS"){
			        $address_string = reset($prop['VALUE']);
			    }
			}
		}

		if($list['ORDER_ID'])
		{
			$entity = \Bitrix\Crm\Binding\OrderContactCompanyTable::getList(['filter' => ['ORDER_ID' => $list['ORDER_ID']]])->fetch();
		}
		elseif($list['CLIENT_ID'] && $list['CLIENT_TYPE'])
		{
			$entity = [
				'ENTITY_ID' => $list['CLIENT_ID'],
				'ENTITY_TYPE_ID' => ($list['CLIENT_TYPE'] == 'COMPANY' ? $typeCompany : $typeContact),
			];
		}		

		if($entity['ENTITY_TYPE_ID'] == $typeCompany)
		{
			$company = \Bitrix\Crm\CompanyTable::getList(['filter' => ['ID' => $entity['ENTITY_ID']], 'select' => ['*']])->fetch();
		}

		if($entity['ENTITY_TYPE_ID'] == $typeContact)
		{
			$contact = \Bitrix\Crm\ContactTable::getList(['filter' => ['ID' => $entity['ENTITY_ID']], 'select' => ['*']])->fetch();
		}
		
		$row = getRequisite($entity);

		if($row['ID'])
		{
			$address = \Bitrix\Crm\EntityRequisite::getAddresses($row['ID']);
			if(isset($address[$typeDelivery]) && $address_string != $address[$typeDelivery]['ADDRESS_2'] && $address_string != $address[$typeDelivery]['ADDRESS_1'])
			{
				echo json_encode([
					'address_old' => (!empty($address[$typeDelivery]['ADDRESS_2']) ? $address[$typeDelivery]['ADDRESS_2'] : $address[$typeDelivery]['ADDRESS_1']),
					'address_new' => $address_string,
					'name' => ($entity['ENTITY_TYPE_ID'] == $typeCompany ? $company['TITLE'] : trim($contact['NAME'] . " " . $contact['LAST_NAME'])),
					'LOC_ADDR_ID' => $address[$typeDelivery]['LOC_ADDR_ID'],
					'ENTITY_ID' => $row['ID'],
					'ANCHOR_ID' => $entity['ENTITY_ID'],
            		'ANCHOR_TYPE_ID' => $entity['ENTITY_TYPE_ID']
				]);
			}

			if(!isset($address[$typeDelivery]))
			{
				registerDeliveryAddress($entity, $typeDelivery, $address_string);
			}
		}
		else
		{
			registerDeliveryAddress($entity, $typeDelivery, $address_string);
		}
	}

	if($list['YES'] == 'Y')
	{
		if($list['LOC_ADDR_ID'])
		{
			$fields = [
	            'LOC_ADDR_ID' => $list['LOC_ADDR_ID'],
	            'ADDRESS_2' => $list['ADDRESS_NEW'],
	            'ENTITY_TYPE_ID' => $reqisite,
	            'ENTITY_ID' => $list['ENTITY_ID'],
	            'TYPE_ID' => $typeDelivery,
	            'ANCHOR_ID' => $list['ANCHOR_ID'],
	            'ANCHOR_TYPE_ID' => $list['ANCHOR_TYPE_ID']
	        ];
	        
	        $locationAddress = Address::load($list['LOC_ADDR_ID']);
	        foreach($locationAddress->getAllFieldsValues() as $type => $value){
	            $locationAddress->setFieldValue($type, $list['ADDRESS_NEW']);
	        }
	        $locationAddress->save();

	        $update = \Bitrix\Crm\AddressTable::upsert($fields);
		}
		else
		{
			registerDeliveryAddress(['ENTITY_TYPE_ID' => $list['ANCHOR_TYPE_ID'], 'ENTITY_ID' => $list['ANCHOR_ID']], $typeDelivery, $list['ADDRESS_NEW']);
		}
	}
}

function registerDeliveryAddress($entity, $typeDelivery, $address_string)
{
    EntityAddress::register(
        $entity['ENTITY_TYPE_ID'],
        $entity['ENTITY_ID'],
        $typeDelivery,
        array(
            'ADDRESS_2' => $address_string
        ),
        [
            'updateLocationAddress' => true
        ]
    );
}

function getRequisite($entity)
{
	$row = [];
	$req = new \Bitrix\Crm\EntityRequisite();

	$activeReq = $req->loadSettings($entity['ENTITY_TYPE_ID'], $entity['ENTITY_ID']);

	if($activeReq['REQUISITE_ID_SELECTED'] > 0)
		return ['ID' => $activeReq['REQUISITE_ID_SELECTED']];

	$rs = $req->getList([
		"filter" => [
			"ENTITY_ID" => $entity['ENTITY_ID'], 
			"ENTITY_TYPE_ID" => $entity['ENTITY_TYPE_ID'], 
		],
		'select' => ['ID']
	]);
	$row = $rs->fetch();

	return $row;
}