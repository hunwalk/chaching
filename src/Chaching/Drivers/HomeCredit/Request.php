<?php

namespace Chaching\Drivers\HomeCredit;

use Chaching\Chaching;
use Chaching\Exceptions\InvalidOptionsException;
use Chaching\Message;

/**
 * Class Request
 * @package Chaching\Drivers\HomeCredit
 */
class Request extends Message
{
    const SANDBOX_BASEURL_CZ = 'https://apicz-test.homecredit.net/verdun-train';

    const SANDBOX_BASEURL_SK = 'https://apisk-test.homecredit.net/verdun-train';

    const PRODUCTION_BASEURL_CZ = 'https://api.homecredit.cz';

    const PRODUCTION_BASEURL_SK = 'https://api.homecredit.sk';

    const SANDBOX_USERNAME = '024243tech';

    const SANDBOX_PASSWORD = '024243tech';

    const AUTHENTICATION_URL = '/authentication/v1/partner/';

    const SERVER_LOCATION_SK = 'sk';

    const SERVER_LOCATION_CZ = 'cz';

    private $_baseUrl;

    private $_server_location;

    private $_access_token;

    public function __construct(array $authorization, array $attributes, array $options = [])
    {
        parent::__construct();

        if (isset($options['server_location'])) {
            //Todo: refactor server locations?
            if (!in_array($options['server_location'], ['sk', 'cz'])) {
                throw new InvalidOptionsException(sprintf(
                        "Server location '%s' is invalid.",
                        $options['server_location'])
                );
            }

            $this->_server_location = $options['server_location'];
        }

        //Todo: maybe even refactor this?
        $this->_baseUrl = $this->get_server_locations()[$this->environment][$this->_server_location];

        $this->_access_token = $this->obtain_access_token();
    }

    public function get_server_locations()
    {
        return [
            Chaching::PRODUCTION => [
                self::SERVER_LOCATION_CZ => self::PRODUCTION_BASEURL_CZ,
                self::SERVER_LOCATION_SK => self::PRODUCTION_BASEURL_SK,
            ],
            Chaching::SANDBOX => [
                self::SERVER_LOCATION_CZ => self::SANDBOX_BASEURL_CZ,
                self::SERVER_LOCATION_SK => self::SANDBOX_BASEURL_SK,
            ]
        ];
    }

    protected function obtain_access_token()
    {
        $url = $this->_baseUrl . self::AUTHENTICATION_URL;
        $ch = curl_init($url);

        $login = [
            'username' => $this->auth[0],
            'password' => $this->auth[1],
        ];

        $login = json_encode($login);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $login);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Charset: utf-8'
        ));

        $result = curl_exec($ch);
        return json_decode( $result,true );
    }
}