<?
//namespace Pwd\Components;

use \Bitrix\Main\Loader,
    \Bitrix\Main\Application,
    \Bitrix\Sale\Order,
    Bitrix\Main\UI\Filter\Options,
    \Bitrix\Main\Type\DateTime;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class CrmReport extends CBitrixComponent 
{
    private $_request;

    /**
     * Обертка над глобальной переменной
     * @return CAllMain|CMain
     */
    private function _app()
    {
        global $APPLICATION;
        return $APPLICATION;
    }

    /**
     * Обертка над глобальной переменной
     * @return CAllUser|CUser
     */
    private function _user()
    {
        global $USER;
        return $USER;
    }

    /**
     * Подготовка параметров компонента
     * @param $arParams
     * @return mixed
     */
    public function onPrepareComponentParams($arParams)
    {
        return $arParams;
    }

    public function formatItems($args = [])
    {
        $fields = [
            'ALL' => [
                'COUNT' => '0',
                'SUMM' => '0'
            ],
            'ITEMS' => [
                'F' => [
                    'COUNT' => '0',
                    'SUMM' => '0'
                ],
                'D' => [
                    'COUNT' => '0',
                    'SUMM' => '0'
                ],
            ]
        ];
        $items = array_merge($fields, $args);
        if(!$items['ALL']['COUNT']){
            $items['ALL']['COUNT'] = '0';
            $items['ALL']['SUMM'] = '0';
        }
        if(!$items['ITEMS']['D']['COUNT']){
            $items['ITEMS']['D']['COUNT'] = '0';
            $items['ITEMS']['D']['SUMM'] = '0';
        }
        if(!$items['ITEMS']['F']['COUNT']){
            $items['ITEMS']['F']['COUNT'] = '0';
            $items['ITEMS']['F']['SUMM'] = '0';
        }
        return $items;
    }

    public function getDealsCompany($company_id, $date_from = false, $date_to = false)
    {
        Loader::includeModule('crm');

        $filter['COMPANY_ID'] = $company_id;
        $filter['PERMISSIONS'] = 'N';
        if($date_from){
            $filter['>=DATE_CREATE'] = new DateTime($date_from);
        }
        if($date_to){
            $filter['<=DATE_CREATE'] = new DateTime($date_to);
        }
        $result = CCrmDeal::GetList(['ID' => 'ASC'], $filter, ['OPPORTUNITY', 'STAGE_ID']);
        while($deal = $result->Fetch()){
            $deals['ALL']['COUNT']++;
            $deals['ALL']['SUMM'] += $deal['OPPORTUNITY'];
            if($deal['STAGE_ID'] === 'WON' || $deal['STAGE_ID'] === 'C3:WON'){
                $deals['ITEMS']['F']['SUMM'] += $deal['OPPORTUNITY'];
                $deals['ITEMS']['F']['COUNT']++;
            }
            if($deal['STAGE_ID'] === 'LOSE' || $deal['STAGE_ID'] === 'C3:LOSE'){
                $deals['ITEMS']['D']['SUMM'] += $deal['OPPORTUNITY'];
                $deals['ITEMS']['D']['COUNT']++;
            }
        }
        return $deals;
    }

    public function getOrdersCompany($company_id, $date_from = false, $date_to = false)
    {
        $connection = Application::getConnection();
        $result = $connection->query('SELECT ORDER_ID FROM b_crm_order_contact_company WHERE ENTITY_ID = "'.$company_id.'" ORDER BY id DESC');
        while($orderId = $result->fetch()){
            $filter['ID'] = $orderId;
            if($date_from){
                $filter['>=DATE_INSERT'] = new DateTime($date_from);
            }
            if($date_to){
                $filter['<=DATE_INSERT'] = new DateTime($date_to);
            }
            $dbRes = Order::getList([
              'select' => ['*'],
              'filter' => $filter,
              'order' => ['ID' => 'DESC']
            ]);
            while ($order = $dbRes->fetch()){
                $orders['ALL']['SUMM'] += $order['PRICE'];
                $orders['ALL']['COUNT']++;
                if($order['STATUS_ID'] === 'F'){
                    $orders['ITEMS']['F']['SUMM'] += $order['PRICE'];
                    $orders['ITEMS']['F']['COUNT']++;
                }
                if($order['STATUS_ID'] === 'D'){
                    $orders['ITEMS']['D']['SUMM'] += $order['PRICE'];
                    $orders['ITEMS']['D']['COUNT']++;
                }
            }
        }
        return $orders;
    }

    public function getResult($company_id, $date_from = false, $date_to = false)
    {
        if(!$date_from && !$date_to){
            $filterOption = new Options($this->arParams['GUID']);
            $filterData = $filterOption->getFilter([]);
            $date_from = $filterData['PERIOD_from'];
            $date_to = $filterData['PERIOD_to'];
        }
        $results['ORDERS'] = $this->formatItems($this->getOrdersCompany($company_id, $date_from, $date_to));
        $results['DEALS'] = $this->formatItems($this->getDealsCompany($company_id, $date_from, $date_to));
        return $results;
    }

    public function executeComponent() 
    {
        $this->arResult = $this->getResult($this->arParams['ENTITY_ID']);
        $this->arResult['PATH_AJAX'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname(__FILE__).'/ajax.php');
        $this->includeComponentTemplate();
    }
}