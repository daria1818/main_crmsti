<?
use Bitrix\Main;
use Bitrix\Crm\Service;
use Bitrix\Main\DI;
use Bitrix\Crm\Filter;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Display\Options;

if (\Bitrix\Main\Loader::includeModule('crm'))
{
    $filterFactory = new class extends Filter\Factory
    {
        public function getDataProvider(Main\Filter\EntitySettings $settings): Main\Filter\DataProvider
        {
            if ($settings instanceof Filter\OrderSettings)
            {
                return new class($settings) extends Filter\OrderDataProvider
                {
                    public function prepareFields()
                    {
                        $result = parent::prepareFields();
                        $custom = [
                            'LID' => $this->createField(
                                'LID',
                                ['default' => true, 'type' => 'list', 'partial' => true, 'name' => 'Сайт']
                            ),
                        ];
                        return array_merge($result, $custom);
                    }

                    public function prepareFieldData($fieldID)
                    {
                        if($fieldID == 'LID')
                        {
                            $arItems = [];
                            $rsList = CSite::GetList($by="sort", $order="asc", ["ACTIVE" => "Y"]);
                            while ($arSite = $rsList->Fetch())
                            {
                                $arItems[$arSite['ID']] = $arSite['NAME'];
                            }
                            return array(
                                'params' => array('multiple' => 'N'),
                                'items' => $arItems
                            );
                        }
                        $result = parent::prepareFieldData($fieldID);
                        return $result;
                    }
                };
            }
            
            return parent::getDataProvider($settings);
        }
    };

    DI\ServiceLocator::getInstance()->addInstance(
        'crm.filter.factory',
        $filterFactory
    );
}

if (\Bitrix\Main\Loader::includeModule('iblock'))
{
    $dmElement = new class extends \Bitrix\Iblock\Controller\DefaultElement
    {
        protected function getDefaultPreFilters()
        {
            return [];
        }

        public static function getAllowedList()
        {
            return ['XML_ID'];
        }

        public static function getElementEntityAllowedList()
        {
            return [
                'ID',
                'NAME',
                'XML_ID',
            ];
        }
    };

    DI\ServiceLocator::getInstance()->addInstance(
        'iblock.element.DM.rest.controller',
        $dmElement
    );

    $dmOffersElement = new class extends \Bitrix\Iblock\Controller\DefaultElement
    {
        protected function getDefaultPreFilters()
        {
            return [];
        }

        public static function getAllowedList()
        {
            return ['XML_ID'];
        }

        public static function getElementEntityAllowedList()
        {
            return [
                'ID',
                'NAME',
                'XML_ID',
            ];
        }
    };

    DI\ServiceLocator::getInstance()->addInstance(
        'iblock.element.DMOffers.rest.controller',
        $dmOffersElement
    );
}
?>