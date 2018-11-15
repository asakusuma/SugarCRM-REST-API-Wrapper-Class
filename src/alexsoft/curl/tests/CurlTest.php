<?php
use Alexsoft\Curl;

class CurlTest extends PHPUnit_Framework_TestCase
{

    public function testIsCurl()
    {
        $curl = new Curl('http://github.com/alexsoft/curl');

        $this->assertInstanceOf('Alexsoft\Curl', $curl);

        $this->assertInstanceOf('Alexsoft\Curl', $curl->addData(array('data' => 'allalone')));

        $this->assertInstanceOf(
            'Alexsoft\Curl',
            $curl->addHeaders(array('header1' => 'php', 'header2' => 'javascript'))
        );

        $this->assertInstanceOf('Alexsoft\Curl', $curl->addCookies(array('cookie1' => 'ci', 'cookie2' => 'travis')));

        $this->assertArrayHasKey('statusCode', $curl->get());
    }

}