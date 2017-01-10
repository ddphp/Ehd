<?php
namespace Ehd;

use Prezent\Soap\Client\SoapClient as SoapClientService;

class SoapClient
{
    private static $soapClient = [];

    /**
     * @param $wsdl
     * @return SoapClientService
     */
    public static function get($wsdl)
    {
        if (!isset(self::$soapClient[$wsdl])) {
            self::$soapClient[$wsdl] = app(SoapClientService::class, [$wsdl]);
        }

        return self::$soapClient[$wsdl];
    }
}