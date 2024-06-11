<?php

namespace Pwd\Tools;

use Dadata\DadataClient;

/**
 * Class TrueConfTools
 * @package Pwd\Tools
 */
class DaDataTools
{
    private $token = 'd936df359bf3cff66bcaf1cd5b6b4352399ac811';
    private $secret = '236f0fca07ecb2f79523e44588b948eda288970e';

    /**
     * @var DadataClient
     */
    private $dadata;

    /**
     * Инициализация
     */
    public function init(): void
    {
        $this->dadata = new DadataClient($this->token, $this->secret);
    }

    /**
     * Получаем данные по ИНН
     * @param $inn
     */
    public function getByInn($inn)
    {
        $this->init();
        $response = $this->dadata->findById("party", $inn);
        return $response;
    }

    /**
     * Получаем данные по индексу
     * @param $postalCode
     */
    public function getByPostalCode($postalCode)
    {
        $this->init();
        $response = $this->dadata->suggest("address", $postalCode);
        return $response;
    }

    /**
     * Получаем данные по адресу
     * @param $address
     */
    public function getByAddress($address)
    {
        $this->init();
        $response = $this->dadata->suggest("address", $address);
        return $response;
    }
}