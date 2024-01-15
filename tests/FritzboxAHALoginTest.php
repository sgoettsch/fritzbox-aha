<?php
declare(strict_types = 1);

namespace sgoettsch\FritzboxAHATest;

use GuzzleHttp\Psr7\Response;
use \sgoettsch\FritzboxAHA\FritzboxAHA;

include_once __DIR__.'/../vendor/autoload.php';

class FritzboxAHALoginTest extends \PHPUnit\Framework\TestCase
{
    protected array $data;

    public function testSuccessfulLogin(): void
    {
        $client = new ClientFake();

        $responseLoginInit = new Response(200, [], '<?xml version="1.0" encoding="utf-8"?><SessionInfo><SID>0000000000000000</SID><Challenge>1234567z</Challenge><BlockTime>0</BlockTime><Rights></Rights><Users><User>Success</User><User last="1">Failed</User></Users></SessionInfo>');
        $responseSuccessfulLogin = new Response(200, [], '<?xml version="1.0" encoding="utf-8"?><SessionInfo><SID>bbfac33ab1e65841</SID><Challenge>1234567z</Challenge><BlockTime>0</BlockTime><Rights><Name>Dial</Name><Access>2</Access><Name>App</Name><Access>2</Access><Name>HomeAuto</Name><Access>2</Access><Name>BoxAdmin</Name><Access>2</Access><Name>Phone</Name><Access>2</Access></Rights><Users><User>Sebastian</User><User last="1">success</User></Users></SessionInfo>');
        $client->appendResponse([$responseLoginInit, $responseSuccessfulLogin]);

        $aha = new FritzboxAHA($client);

        $aha->login("fritz.box", "success", 'pass');

        $this->assertEquals("bbfac33ab1e65841", $aha->getSid());
    }
}
