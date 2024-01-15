<?php
declare(strict_types = 1);

namespace sgoettsch\FritzboxAHATest;

use GuzzleHttp\Psr7\Response;
use \sgoettsch\FritzboxAHA\FritzboxAHA;

include_once __DIR__.'/../vendor/autoload.php';

class FritzboxAHAChallengeTest extends \PHPUnit\Framework\TestCase
{
    protected array $data;

    public function testChallengeGenerator(): void
    {
        $aha = new FritzboxAHA();
        $aha->setPassword('äbc');
        $challenge = $aha->getChallengeResponse('1234567z');

        $this->assertEquals("1234567z-9e224a41eeefa284df7bb0f26c2913e2", $challenge);
    }
}
