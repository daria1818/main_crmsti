<?php

namespace xGuard\Main\Catalog\Product;

/**
 * Class Price
 *
 * @package xGuard\Main\Catalog\Product
 */
class Price
{

    /**
     * @var array
     */
    protected static $prices = [];

    /**
     * @var array
     */
    protected static $offers = [];

    /**
     * @var int
     */
    protected static $discount;

    /**
     * @param array|null $prices
     */
    public static function setPrices(array $prices = null)
    {
        static::$prices = $prices ?? [];
        static::$discount = null;
    }

    /**
     * @param array|null $offers
     */
    public static function setOffers(array $offers = null)
    {
        static::$offers = $offers ?? [];
        static::$discount = null;
    }

    /**
     * @return int
     */
    public static function getDiscount(): int
    {
        if (null !== static::$discount) {
            return static::$discount;
        }

        $min = time();
        $minPrice = [];
        $basePrice = ['VALUE' => 1];

        foreach (static::$offers as $offer):
            $basePrice = $offer['PRICES'][PRICE_4];

            foreach ($offer['PRICES'] as $keyPrice => $price):
                if ($min > +$price['VALUE']) {
                    $minPrice = $price;
                    $min = $price['VALUE'];

                    $minPrice['KEY'] = $keyPrice;
                }
            endforeach;
        endforeach;

        return (int)round((1 - (+$minPrice['VALUE'] / +$basePrice['VALUE'])) * 100);
    }

    /**
     * @return string
     */
    public static function resetDiscount()
    {
        static::$discount = null;
    }

    /**
     * @return string
     */
    public static function getDiscountFormat(): string
    {
        return '-'.static::getDiscount().'%';
    }

    /**
     * @return bool
     */
    public static function isDiscount(): bool
    {
        return !empty(static::getDiscount());
    }
}