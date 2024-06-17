<?php

declare(strict_types=1);

namespace sgoettsch\FritzboxAHATest;

use GuzzleHttp\Psr7\Response;
use sgoettsch\FritzboxAHA\FritzboxAHA;
use sgoettsch\FritzboxAHA\FritzboxAHADevice;

class FritzboxAHAGetDeviceTest extends \PHPUnit\Framework\TestCase
{
    protected array $data;

    public function testGetDevice(): void
    {
        $client = new ClientFake();

        $deviceList = file_get_contents(__DIR__ . '/responses/deviceList.xml');

        $responseLoginInit = new Response(200, [], file_get_contents(__DIR__ . '/responses/loginInit.xml'));
        $responseLoginSuccess = new Response(200, [], file_get_contents(__DIR__ . '/responses/loginSuccess.xml'));
        $responseDeviceList = new Response(200, [], $deviceList);
        $client->appendResponse([$responseLoginInit, $responseLoginSuccess, $responseDeviceList]);

        $aha = new FritzboxAHA($client);

        $aha->login("fritz.box", "success", 'pass');

        $device = $aha->getDevice('11111 1234567');

        $xml = simplexml_load_string($deviceList);

        $testDevice = new FritzboxAHADevice($xml->device);

        $this->assertEquals($testDevice, $device);
    }
}
