<?php

/*
 * This file is part of Chaching.
 *
 * (c) 2020 BACKBONE, s.r.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chaching;

use BadMethodCallException;
use Chaching\Exceptions\InvalidOptionsException;


/**
 * Class Chaching
 * @package Chaching
 */
class Chaching
{
    const EDITION = 'basic';
    const VERSION = '0.21.0';

    const CARDPAY = 'cardpay';
    const SPOROPAY = 'sporopay';
    const TATRAPAY = 'tatrapay';
    const TRUSTPAY = 'trustpay';
    const EPLATBY = 'eplatby';
    const ECARD = 'ecard';
    const PAYPAL = 'paypal';
    const GPWEBPAY = 'gpwebpay';
    const ITERMINAL = 'iterminal';
    const HOMECREDIT = 'homecredit';

    const PRODUCTION = 'production';
    const SANDBOX = 'sandbox';

    /**
     * @var string[]
     */
    private $_payment_drivers = [];

    /**
     * @var array
     */
    private static $_3rd_party_payment_drivers = [];

    /**
     * Create object to work with payments via specified driver.
     *
     * @param int $driver driver handle
     * @param array $authorization basic authentication information
     *                                        to service
     * @param array $options
     */
    public function __construct($driver, array $authorization, array $options = [])
    {
        $this->_payment_drivers = $this->getNativelySupportedPaymentDrivers();

        if (!is_string($driver)){
            throw new InvalidOptionsException(sprintf(
                "Driver '%s' should be string.",
                $driver
            ));
        }

        if (!isset($this->_payment_drivers[$driver])){

            if (!isset(self::$_3rd_party_payment_drivers[$driver]))
                throw new InvalidOptionsException(sprintf(
                    "Invalid driver '%s' in use. Valid drivers are '%s'.",
                    $driver, implode("', '", array_keys($this->_payment_drivers))
                ));

            //using 3rd party driver here
            $driver = self::$_3rd_party_payment_drivers[$driver];

        }else{

            $driver = '\\Chaching\\Drivers\\' . $this->_payment_drivers[$driver];

        }

        if (!class_exists($driver))
            throw new InvalidOptionsException(sprintf(
                "[internal] Requested driver '%s' does not appear to have " .
                "class definition associated. Valid drivers are %s",
                $driver, implode("', '", array_keys($this->_payment_drivers))
            ));

        $this->driver = new $driver($authorization, $options);
    }

    public static function supportDriver($class, $handle)
    {
        if (!class_exists($class))
            throw new \Exception('Class not found: '.$class);

        if (!is_a($class, Driver::class, true)){
            throw new \Exception("'{$class}' should be extended from ".Driver::class);
        }

        self::$_3rd_party_payment_drivers[$handle] = $class;
    }

    /**
     * Defines the natively supported payment drivers by this package.
     * Gets loaded into the payment drivers during initialization
     * @return string[]
     */
    final public function getNativelySupportedPaymentDrivers()
    {
        return [
            self::SPOROPAY => 'SLSPSporoPay',
            self::CARDPAY => 'TBCardPay',
            self::TATRAPAY => 'TBTatraPay',
            self::TRUSTPAY => 'TrustPay',
            self::EPLATBY => 'VUBePlatby',
            self::ECARD => 'VUBeCard',
            self::PAYPAL => 'PayPal',
            self::GPWEBPAY => 'GPwebpay',
            self::ITERMINAL => 'PBiTerminal',
            self::HOMECREDIT => 'HomeCredit'
        ];
    }

    public function __call($method, $arguments)
    {
        if (method_exists($this->driver, $method))
            return call_user_func_array(
                [$this->driver, $method], $arguments
            );

        throw new BadMethodCallException(sprintf(
            "Method %s not implemented in driver", $method
        ));
    }

    public function request($attributes)
    {
        return $this->driver->request((array)$attributes);
    }

    public function response($attributes)
    {
        return $this->driver->response((array)$attributes);
    }
}
