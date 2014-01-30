<?php

namespace Asakusuma\SugarWrapper;

use Asakusuma\SugarWrapper\Rest;

/**
 * SugarCRM REST Testing Class
 *
 * @package     SugarCRM
 * @category    Libraries
 * @author  Clifford W. Hansen <clifford@nighthawk.co.za>
 * @license MIT License
 * @link    http://github.com/asakusuma/SugarCRM-REST-API-Wrapper-Class/
 */
class RestTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Asakusuma\SugarWrapper\Rest
     */
    protected $api;

    /**
     *
     * @var \Alexsoft\Curl
     */
    protected $curl;

    public function setUp()
    {
        parent::setUp();

        $this->api = new Rest;

        $this->curl = $this->getMockBuilder('\Alexsoft\Curl')
                ->disableOriginalConstructor()
                ->setMethods(array('POST', 'addData'))
                ->getMock();
    }

    public function testEmptyErrorReturnsFalse()
    {
        $this->assertFalse($this->api->get_error());
    }

    public function testSettersReturnSelf()
    {
        $this->assertEquals($this->api, $this->api->setUrl('http://localhost/'));
        $this->assertEquals($this->api, $this->api->setUsername('apiuser'));
        $this->assertEquals($this->api, $this->api->setPassword('passwd'));
        $this->assertEquals($this->api, $this->api->setCurl($this->curl));
    }

    public function testGetCurlReturnsNewCurl()
    {
        $this->api->setUrl('http://localhost');

        $this->assertInstanceOf('\Alexsoft\Curl', $this->api->getCurl());
    }

    /**
     * @param string $originalString String to be checked
     * @param string $expectedResult What we expect our result to be
     *
     * @dataProvider providerTestIsValidId
     */
    public function testIsValidId($originalString, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->api->is_valid_id($originalString));
    }

    public function providerTestIsValidId()
    {
        return array(
            array('', false),
            array('$%', false),
            array(923562, false),
            array('9152255a-5516-f5fb-2440-52ea362ee9a6', true),
            array('9152255a5516f5fb244052ea362ee9a6', true),
        );
    }

    public function testConnectSetsValues()
    {
        $this->curl->expects($this->once())
                ->method('addData')
                ->with(
                    $this->equalTo(
                        array(
                            'method' => 'login',
                            'input_type' => 'JSON',
                            'response_type' => 'JSON',
                            'rest_data' => json_encode(
                                array(
                                    'user_auth' => array(
                                        'user_name' => 'apiuser',
                                        'password' => '5f4dcc3b5aa765d61d8327deb882cf99'
                                    ),
                                    'name_value_list' => array(
                                        array(
                                            'name' => 'notifyonsave',
                                            'value' => 'true'
                                        )
                                    )
                                )
                            )
                        )
                    )
                );

        $this->api->setCurl($this->curl);

        $this->assertFalse($this->api->connect('http://localhost', 'apiuser', 'password'));
    }

    public function testConnectReturnsFalseOnError()
    {
        $expected = array(
            'name'        => 'Unknown Error',
            'number'      => -1,
            'description' => 'We are having technical difficulties. We apologize for the inconvenience.',
        );

        $this->api->setCurl($this->curl);

        $this->assertFalse($this->api->connect());
        $this->assertEquals($expected, $this->api->get_error());
    }

    public function testConnectReturnsTrueOnConnect()
    {
        $this->curl->expects($this->at(1))
                ->method('POST')
                ->will(
                    $this->returnValue(
                        array(
                            'body' => '{"id":"mh1262ekdep3klo8urgotb9kf2","module_name":"Users","name_value_list":{"user_id":{"name":"user_id","value":"39940602-3328-311c-084b-52e9f7549144"},"user_name":{"name":"user_name","value":"apiuser"},"user_language":{"name":"user_language","value":"en_us"},"user_currency_id":{"name":"user_currency_id","value":"-99"},"user_currency_name":{"name":"user_currency_name","value":"US Dollars"}}}'
                        )
                    )
                );

        $this->api->setCurl($this->curl);

        $this->assertFalse($this->api->is_logged_in());

        $this->assertTrue($this->api->connect());

        $this->assertTrue($this->api->is_logged_in());
    }

    public function testCountRecordsReturnsValue()
    {
        $this->curl->expects($this->at(1))
                ->method('POST')
                ->will(
                    $this->returnValue(
                        array(
                            'body' => '{"id":"mh1262ekdep3klo8urgotb9kf2","result_count":100}'
                        )
                    )
                );

        $this->api->setCurl($this->curl);

        $this->assertEquals(100, $this->api->count_records('', ''));
    }

    public function testCountRecordsReturnsFalse()
    {
        $this->curl->expects($this->at(1))
                ->method('POST')
                ->will(
                    $this->returnValue(
                        array(
                            'body' => '{"id":"mh1262ekdep3klo8urgotb9kf2"}'
                        )
                    )
                );

        $this->api->setCurl($this->curl);

        $this->assertFalse($this->api->count_records('', ''));
    }

    public function testGetWithRelatedFailsIfNoFields()
    {
        $this->assertFalse($this->api->get_with_related('test', array()));
    }

    public function testGetWithRelatedFailsIfNoModuleSection()
    {
        $this->assertFalse($this->api->get_with_related('test', array('ola')));
    }

    public function testGetReturnsAccount()
    {
        $expected = array(
            array(
                'id'   => '1',
                'name' => 'Test',
            )
        );

        $this->curl->expects($this->at(1))
                ->method('POST')
                ->will(
                    $this->returnValue(
                        array(
                            'body' => '{"result_count":1,"next_offset":1,"entry_list":[{"id":"1","module_name":"Accounts","name_value_list":{"id":{"name":"id","value":"1"},"name":{"name":"name","value":"Test"}}}],"relationship_list":[]}'
                        )
                    )
                );

        $this->api->setCurl($this->curl);

        $this->assertEquals($expected, $this->api->get('Accounts', array('id', 'name')));
    }

    public function testGetReturnsAccountWithRelated()
    {
        $expected = array(
            'result_count' => 1,
            'next_offset' => 1,
            'entry_list' => array(
                array(
                    'id'   => '1',
                    'module_name' => 'Accounts',
                    'name_value_list' => array(
                        'name' => array('name'=>'name', 'value'=>'Test'),
                        'id' => array('name'=>'id', 'value'=>'1'),
                    ),
                ),
            ),
            'relationship_list' => array(),
        );

        $this->curl->expects($this->at(1))
                ->method('POST')
                ->will(
                    $this->returnValue(
                        array(
                            'body' => '{"result_count":1,"next_offset":1,"entry_list":[{"id":"1","module_name":"Accounts","name_value_list":{"id":{"name":"id","value":"1"},"name":{"name":"name","value":"Test"}}}],"relationship_list":[]}'
                        )
                    )
                );

        $this->api->setCurl($this->curl);

        $this->assertEquals($expected, $this->api->get_with_related('Accounts', array('Accounts' => array('id', 'name'),'User' => array('id', 'name'))));
    }

    public function testSetReturnsId()
    {
        $expected = array('id'=>'mh1262ekdep3klo8urgotb9kf2');
        $this->curl->expects($this->at(1))
                ->method('POST')
                ->will(
                    $this->returnValue(
                        array(
                            'body' => '{"id":"'.$expected['id'].'"}'
                        )
                    )
                );

        $this->api->setCurl($this->curl);

        $this->assertEquals($expected, $this->api->set('Accounts', array('id'=>$expected['id'])));
    }

    public function testGetAvailableModules()
    {
        $expected = array(
            'modules' => array(
                'Home',
                'Accounts',
                'Contacts',
            ),
        );
        $this->curl->expects($this->at(1))
                ->method('POST')
                ->will(
                    $this->returnValue(
                        array(
                            'body' => json_encode($expected)
                        )
                    )
                );
        $this->curl->expects($this->once())
                ->method('addData')
                ->with(
                    $this->equalTo(
                        array(
                            'method' => 'get_available_modules',
                            'input_type' => 'JSON',
                            'response_type' => 'JSON',
                            'rest_data' => json_encode(array('session' => null))
                        )
                    )
                );

        $this->api->setCurl($this->curl);

        $this->assertEquals($expected, $this->api->get_available_modules());
    }
}
