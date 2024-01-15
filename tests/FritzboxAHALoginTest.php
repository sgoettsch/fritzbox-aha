<?php
declare(strict_types = 1);

namespace sgoettsch\FritzboxAHATest;

use GuzzleHttp\Psr7\Response;
use \sgoettsch\FritzboxAHA\FritzboxAHA;

class FritzboxAHALoginTest extends \PHPUnit\Framework\TestCase
{
    protected array $data;

    public function testSuccessfulLogin(): void
    {
        $client = new ClientFake();

        $responseLoginInit = new Response(200, [], file_get_contents(__DIR__.'/responses/loginInit.xml'));
        $responseSuccessfulLogin = new Response(200, [], file_get_contents(__DIR__.'/responses/loginSuccess.xml'));
        $client->appendResponse([$responseLoginInit, $responseSuccessfulLogin]);

        $aha = new FritzboxAHA($client);

        $aha->login("fritz.box", "success", 'pass');

        $this->assertEquals("bbfac33ab1e65841", $aha->getSid());
    }
}
