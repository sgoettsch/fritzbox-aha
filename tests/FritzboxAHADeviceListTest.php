<?php

declare(strict_types=1);

namespace sgoettsch\FritzboxAHATest;

use GuzzleHttp\Psr7\Response;
use \sgoettsch\FritzboxAHA\FritzboxAHA;

class FritzboxAHADeviceListTest extends \PHPUnit\Framework\TestCase
{
    protected array $data;

    public function testSuccessfulLogin(): void
    {
        $client = new ClientFake();

        $responseLoginInit = new Response(200, [], file_get_contents(__DIR__ . '/responses/loginInit.xml'));
        $responseSuccessfulLogin = new Response(200, [], file_get_contents(__DIR__ . '/responses/loginSuccess.xml'));
        $responseDeviceList = new Response(200, [], file_get_contents(__DIR__ . '/responses/deviceList.xml'));
        $client->appendResponse([$responseLoginInit, $responseSuccessfulLogin, $responseDeviceList]);

        $aha = new FritzboxAHA($client);

        $aha->login("fritz.box", "success", 'pass');

        $devices = $aha->getAllDevices();

        $this->assertEquals([
            [
                'name' => 'Wohnzimmer',
                'aid' => '11111 1234567',
                'type' => '320',
            ],
        ], $devices);
    }
}
