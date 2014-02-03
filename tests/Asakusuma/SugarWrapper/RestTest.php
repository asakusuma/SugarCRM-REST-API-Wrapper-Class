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
     * @var \Alexsoft\Curl|\Mockery\MockInterface
     */
    protected $curl;

    protected function setUpConnect()
    {
        $this->curl->shouldReceive('addData')
            ->once()
            ->with(
                array(
                    'method' => 'login',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode(
                        array(
                            'user_auth' => array(
                                'user_name' => 'apiuser',
                                'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                            ),
                            'name_value_list' => array(
                                array(
                                    'name' => 'notifyonsave',
                                    'value' => 'true'
                                )
                            )
                        )
                    ),
                )
            )
            ->andReturnNull();

        $this->curl->shouldReceive('post')
            ->once()
            ->andReturn(
                array(
                    'body' => json_encode(
                        array(
                            'id'              => 'mh1262ekdep3klo8urgotb9kf2',
                            'module_name'     => 'Users',
                            'name_value_list' => array(
                                'user_id'            => array(
                                    'name'  => 'user_id',
                                    'value' => '39940602-3328-311c-084b-52e9f7549144'
                                ),
                                'user_name'          => array(
                                    'name'  => 'user_name',
                                    'value' => 'apiuser'
                                ),
                                'user_language'      => array(
                                    'name'  => 'user_language',
                                    'value' => 'en_us'
                                ),
                                'user_currency_id'   => array(
                                    'name'  => 'user_currency_id',
                                    'value' => '-99'
                                ),
                                'user_currency_name' => array(
                                    'name'  => 'user_currency_name',
                                    'value' => 'US Dollars'
                                ),
                            ),
                        )
                    ),
                )
            );

        $this->assertTrue(
            $this->api->connect(
                'http://localhost',
                'apiuser',
                'password'
            )
        );
    }

    public function setUp()
    {
        parent::setUp();

        $this->api = new Rest;

        $this->curl = \Mockery::mock('\Alexsoft\Curl');
    }

    public function tearDown()
    {
        $this->curl->shouldReceive('addData')
            ->with(
                array(
                    'method' => 'logout',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode(
                        array(
                            'session' => 'mh1262ekdep3klo8urgotb9kf2',
                        )
                    ),
                )
            )
            ->andReturnNull();

        parent::tearDown();
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

        $actual = $this->api->getCurl();

        $this->assertInstanceOf('\Alexsoft\Curl', $actual);
        $this->assertNotEquals($this->curl, $actual);
        $this->assertEquals($actual, $this->api->getCurl());
    }

    /**
     * @param string $originalString String to be checked
     * @param string $expectedResult What we expect our result to be
     *
     * @dataProvider providerTestIsValidId
     */
    public function testIsValidId($originalString, $expectedResult)
    {
        $this->assertEquals(
            $expectedResult,
            $this->api->is_valid_id($originalString)
        );
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

    public function testConnectReturnsFalseOnErrorNoError()
    {
        $this->api->setCurl($this->curl);

        $expected = array(
            'name'        => 'Unknown Error',
            'number'      => -1,
            'description' => 'We are having technical difficulties. We apologize for the inconvenience.',
        );

        $data = array(
            'username' => 'apiuser',
            'password' => 'password',
        );

        $this->curl->shouldReceive('addData')
            ->once()
            ->with(
                array(
                    'method' => 'login',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode(
                        array(
                            'user_auth' => array(
                                'user_name' => $data['username'],
                                'password' => md5($data['password']),
                            ),
                            'name_value_list' => array(
                                array(
                                    'name' => 'notifyonsave',
                                    'value' => 'true'
                                )
                            )
                        )
                    ),
                )
            )
            ->andReturnNull();

        $this->curl->shouldReceive('post')
            ->once()
            ->andReturnNull();

        $this->assertFalse(
            $this->api->connect(
                'http://localhost',
                $data['username'],
                $data['password']
            )
        );
        $this->assertEquals($expected, $this->api->get_error());
    }

    public function testConnectReturnsFalseOnErrorWithError()
    {
        $data = array(
            'username' => 'apiuser',
            'password' => 'password',
        );

        $expected = array(
            'body' => json_encode(
                array(
                    'name'        => 'Auth Error',
                    'number'      => 403,
                    'description' => 'User login failed.',
                )
            )
        );

        $this->curl->shouldReceive('addData')
            ->once()
            ->with(
                array(
                    'method' => 'login',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode(
                        array(
                            'user_auth' => array(
                                'user_name' => $data['username'],
                                'password' => md5($data['password']),
                            ),
                            'name_value_list' => array(
                                array(
                                    'name' => 'notifyonsave',
                                    'value' => 'true'
                                )
                            )
                        )
                    ),
                )
            )
            ->andReturnNull();

        $this->curl->shouldReceive('post')
            ->once()
            ->andReturn($expected);

        $this->api->setCurl($this->curl);

        $this->assertFalse(
            $this->api->connect(
                'http://localhost',
                $data['username'],
                $data['password']
            )
        );
    }

    public function testConnectReturnsTrueOnConnect()
    {
        $this->api->setCurl($this->curl);
        $this->setUpConnect();
        $this->assertTrue($this->api->is_logged_in());
    }

    /**
     * @group test
     */
    public function testCountRecordsReturnsValue()
    {
        $this->api->setCurl($this->curl);

        $this->setUpConnect();

        $this->curl->shouldReceive('addData')
            ->once()
            ->with(
                array(
                    'method' => 'get_entries_count',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode(
                        array(
                            'session' => 'mh1262ekdep3klo8urgotb9kf2',
                            'module_name' => 'User',
                            'query' => '',
                            'deleted' => 0
                        )
                    ),
                )
            )
            ->andReturnNull();

        $this->curl->shouldReceive('post')
            ->once()
            ->andReturn(
                array(
                    'body' => json_encode(
                        array(
                            'id'           => 'mh1262ekdep3klo8urgotb9kf2',
                            'result_count' => 100,
                        )
                    )
                )
            );

        $actual = $this->api->count_records('User', '');

        $this->assertEquals(100, $actual);
    }

    public function testCountRecordsReturnsFalse()
    {
        $this->api->setCurl($this->curl);

        $this->setUpConnect();

        $this->curl->shouldReceive('addData')
            ->once()
            ->with(
                array(
                    'method' => 'get_entries_count',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode(
                        array(
                            'session' => 'mh1262ekdep3klo8urgotb9kf2',
                            'module_name' => 'User',
                            'query' => '',
                            'deleted' => 0
                        )
                    ),
                )
            )
            ->andReturnNull();

        $this->curl->shouldReceive('post')
            ->once()
            ->andReturn(
                array(
                    'body' => json_encode(
                        array(
                            'id'           => 'mh1262ekdep3klo8urgotb9kf2',
                        )
                    )
                )
            );

        $this->assertFalse($this->api->count_records('User', ''));
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
        $this->api->setCurl($this->curl);

        $this->setUpConnect();

        $expected = array(
            array(
                'id'   => '1',
                'name' => 'Test',
            )
        );

        $this->curl->shouldReceive('addData')
            ->once()
            ->with(
                array(
                    'method' => 'get_entry_list',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode(
                        array(
                            'session' => 'mh1262ekdep3klo8urgotb9kf2',
                            'module_name' => 'Accounts',
                            'query' => null,
                            'order_by' => null,
                            'offset' => 0,
                            'select_fields' => array('id','name'),
                            'link_name_to_fields_array' => array(),
                            'max_results' => 20,
                            'deleted' => false
                        )
                    ),
                )
            )
            ->andReturnNull();

        $this->curl->shouldReceive('post')
            ->once()
            ->andReturn(
                array(
                    'body' => json_encode(
                        array(
                            'result_count' => 1,
                            'next_offset' => 1,
                            'entry_list' => array(
                                array(
                                    'id' => 1,
                                    'module_name' => 'Accounts',
                                    'name_value_list' => array(
                                        'id' => array(
                                            'name' => 'id',
                                            'value' => '1',
                                        ),
                                        'name' => array(
                                            'name' => 'name',
                                            'value' => 'Test',
                                        ),
                                    )
                                )
                            ),
                            'relationship_list' => array(),
                        )
                    )
                )
            );

        $this->api->setCurl($this->curl);

        $this->assertEquals($expected, $this->api->get('Accounts', array('id', 'name')));
    }

    public function testGetReturnsAccountWithRelated()
    {
        $this->api->setCurl($this->curl);

        $this->setUpConnect();

        $expected = array(
            array(
                'id'   => '1',
                'name' => 'Test',
            )
        );

        $this->curl->shouldReceive('addData')
            ->once()
            ->with(
                array(
                    'method' => 'get_entry_list',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode(
                        array(
                            'session' => 'mh1262ekdep3klo8urgotb9kf2',
                            'module_name' => 'Accounts',
                            'query' => null,
                            'order_by' => null,
                            'offset' => 0,
                            'select_fields' => array('id','name'),
                            'link_name_to_fields_array' => array(
                                array(
                                    'name' => 'user',
                                    'value' => array(
                                        'id',
                                        'name'
                                    )
                                )
                            ),
                            'max_results' => 20,
                            'deleted' => false
                        )
                    ),
                )
            )
            ->andReturnNull();

        $this->curl->shouldReceive('post')
            ->once()
            ->andReturn(
                array(
                    'body' => json_encode(
                        array(
                            'result_count' => 1,
                            'next_offset' => 1,
                            'entry_list' => array(
                                array(
                                    'id' => 1,
                                    'module_name' => 'Accounts',
                                    'name_value_list' => array(
                                        'id' => array(
                                            'name' => 'id',
                                            'value' => '1',
                                        ),
                                        'name' => array(
                                            'name' => 'name',
                                            'value' => 'Test',
                                        ),
                                    )
                                )
                            ),
                            'relationship_list' => array(
                                array(
                                    array(
                                        'name' => 'user',
                                        'records' => array(),
                                    )
                                ),
                            ),
                        )
                    )
                )
            );

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
            'relationship_list' => array(
                array(
                    array(
                        'name' => 'user',
                        'records' => array(),
                    )
                ),
            ),
        );

        $this->assertEquals($expected, $this->api->get_with_related('Accounts', array('Accounts' => array('id', 'name'),'User' => array('id', 'name'))));
    }

    public function testSetReturnsId()
    {
        $this->api->setCurl($this->curl);

        $this->setUpConnect();

        $expected = array('id'=>'mh1262ekdep3klo8urgotb9kf2');

        $this->curl->shouldReceive('addData')
            ->once()
            ->with(
                array(
                    'method' => 'set_entry',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode(
                        array(
                            'session' => 'mh1262ekdep3klo8urgotb9kf2',
                            'module_name' => 'Accounts',
                            'name_value_list' => array(
                                'id' => 'mh1262ekdep3klo8urgotb9kf2'
                            )
                        )
                    ),
                )
            )
            ->andReturnNull();

        $this->curl->shouldReceive('post')
            ->once()
            ->andReturn(
                array(
                    'body' => json_encode(
                        array(
                            'id' => 'mh1262ekdep3klo8urgotb9kf2'
                        )
                    )
                )
            );

        $this->assertEquals($expected, $this->api->set('Accounts', array('id'=>$expected['id'])));
    }

    public function testGetAvailableModules()
    {
        $this->api->setCurl($this->curl);

        $this->setUpConnect();

        $expected = array(
            'modules' => array(
                'Home',
                'Accounts',
                'Contacts',
            ),
        );

        $this->curl->shouldReceive('addData')
            ->once()
            ->with(
                array(
                    'method' => 'get_available_modules',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode(
                        array(
                            'session' => 'mh1262ekdep3klo8urgotb9kf2',
                        )
                    ),
                )
            )
            ->andReturnNull();

        $this->curl->shouldReceive('post')
            ->once()
            ->andReturn(
                array(
                    'body' => json_encode(
                        array(
                            'modules' => array(
                                'Home',
                                'Accounts',
                                'Contacts',
                            )
                        )
                    )
                )
            );

        $this->assertEquals($expected, $this->api->get_available_modules());
    }

    public function testSetRelationship()
    {
        $this->api->setCurl($this->curl);
        $this->setUpConnect();

        $expected = array(
            'created' => 0,
            'failed'  => 0,
            'deleted' => 0,
        );

        $this->curl->shouldReceive('addData')
            ->once()
            ->with(
                array(
                    'method' => 'set_relationship',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode(
                        array(
                            'session'         => 'mh1262ekdep3klo8urgotb9kf2',
                            'module_name'     =>'Accounts',
                            'module_id'       => 1,
                            'link_field_name' => 'id',
                            'related_ids'     => array('account_id'),
                            'name_value_list' => array(),
                            'delete'          => false,
                        )
                    ),
                )
            )
            ->andReturnNull();

        $this->curl->shouldReceive('post')
            ->once()
            ->andReturn(
                array(
                    'body' => json_encode(
                        $expected
                    )
                )
            );

        $this->assertEquals(
            $expected,
            $this->api->set_relationship(
                'Accounts',
                1,
                'id',
                'account_id',
                false
            )
        );
    }

    public function testSearchByModuleReturnsNoResults()
    {
        $this->api->setCurl($this->curl);
        $this->setUpConnect();

        $expected = array(
            'entry_list' => array(
                array(
                    'name'    => 'Accounts',
                    'records' => array(),
                ),
            ),
        );

        $this->curl->shouldReceive('addData')
            ->once()
            ->with(
                array(
                    'method' => 'search_by_module',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode(
                        array(
                            'session'       => 'mh1262ekdep3klo8urgotb9kf2',
                            'search_string' =>'Test',
                            'modules'       => array('Accounts'),
                            'offset'        => 0,
                            'max_results'   => 100,
                        )
                    ),
                )
            )
            ->andReturnNull();

        $this->curl->shouldReceive('post')
            ->once()
            ->andReturn(
                array(
                    'body' => json_encode(
                        $expected
                    )
                )
            );

        $this->assertEquals(
            $expected,
            $this->api->search_by_module('Test', array('Accounts'), 0, 100)
        );
    }

    public function testSetNoteAttachmentError()
    {
        $this->api->setCurl($this->curl);
        $this->setUpConnect();

        $fileContents = '{\rtf1\ansi{\fonttbl\f0\fswiss Helvetica;}\f0\pard
This is some {\b bold} text.\par
}';

        $binaryContents = base64_encode($fileContents);

        $expected = array(
            'id' => '-1',
        );

        $this->curl->shouldReceive('addData')
            ->once()
            ->with(
                array(
                    'method' => 'set_note_attachment',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode(
                        array(
                            'session' => 'mh1262ekdep3klo8urgotb9kf2',
                            'note'    => array(
                                'id'                  => 1,
                                'file'                => $binaryContents,
                                'filename'            => 'testfile.rtf',
                                'related_module_name' => 'Cases',
                            ),
                        )
                    ),
                )
            )
            ->andReturnNull();

        $this->curl->shouldReceive('post')
            ->once()
            ->andReturn(
                array(
                    'body' => json_encode(
                        $expected
                    )
                )
            );



        $this->assertEquals(
            $expected,
            $this->api->set_note_attachment(1, $binaryContents, 'testfile.rtf')
        );
    }

    public function testSetNoteAttachmentReturnsId()
    {
        $this->api->setCurl($this->curl);
        $this->setUpConnect();

        $fileContents = '{\rtf1\ansi{\fonttbl\f0\fswiss Helvetica;}\f0\pard
This is some {\b bold} text.\par
}';

        $binaryContents = base64_encode($fileContents);

        $expected = array(
            'id' => 'b500568b-a7b6-afb1-2298-52ef3f5a5d74',
        );

        $this->curl->shouldReceive('addData')
            ->once()
            ->with(
                array(
                    'method' => 'set_note_attachment',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode(
                        array(
                            'session' => 'mh1262ekdep3klo8urgotb9kf2',
                            'note'    => array(
                                'id'                  => 1,
                                'file'                => $binaryContents,
                                'filename'            => 'testfile.rtf',
                                'related_module_name' => 'Cases',
                            ),
                        )
                    ),
                )
            )
            ->andReturnNull();

        $this->curl->shouldReceive('post')
            ->once()
            ->andReturn(
                array(
                    'body' => json_encode(
                        $expected
                    )
                )
            );



        $this->assertEquals(
            $expected,
            $this->api->set_note_attachment(1, $binaryContents, 'testfile.rtf')
        );
    }

    public function testGetNoteAttachmentReturnsFile()
    {
        $this->api->setCurl($this->curl);
        $this->setUpConnect();

        $expected = array(
            'note_attachment' => array(
                'filename' => 'testfile.rtf',
                'file'     => 'e1xydGYxXGFuc2l7XGZvbnR0YmxcZjBcZnN3aXNzIEhlbHZldGljYTt9XGYwXHBhcmQKVGhpcyBpcyBzb21lIHtcYiBib2xkfSB0ZXh0LlxwYXIKfQ==',
            )
        );

        $this->curl->shouldReceive('addData')
            ->once()
            ->with(
                array(
                    'method' => 'get_note_attachment',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode(
                        array(
                            'session' => 'mh1262ekdep3klo8urgotb9kf2',
                            'id'      => '1',
                        )
                    ),
                )
            )
            ->andReturnNull();

        $this->curl->shouldReceive('post')
            ->once()
            ->andReturn(
                array(
                    'body' => json_encode(
                        $expected
                    )
                )
            );

        $this->assertEquals(
            $expected,
            $this->api->get_note_attachment('1')
        );
    }

    public function testGetNoteAttachmentInvalidIDReturnsFalse()
    {
        $this->api->setCurl($this->curl);
        $this->setUpConnect();

        $this->assertFalse($this->api->get_note_attachment(1));
    }
}
