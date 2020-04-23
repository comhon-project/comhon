<?php

use PHPUnit\Framework\TestCase;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Utils\Cli;

class AskTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
		$separator = DIRECTORY_SEPARATOR;
		Cli::$STDIN = fopen(__DIR__.$separator.'data'.$separator.'Ask'.$separator.'stdin.txt', 'r');
		Cli::$STDOUT = fopen(__DIR__.$separator.'data'.$separator.'Ask'.$separator.'stdout_actual.txt', 'w');
	}
	
	public static function  tearDownAfterClass() {
		fclose(Cli::$STDIN);
		fclose(Cli::$STDOUT);
		Cli::$STDIN = STDIN;
		Cli::$STDOUT = STDOUT;
	}
	
	/**
	 * @dataProvider askData
	 */
	public function testAskResponses($question, $default, $filter, $filterType, $expected)
	{
		$response = Cli::ask($question, $default, $filter, $filterType);
		$this->assertEquals($expected, $response);
	}
	
	public function askData()
	{
		return [
			[ // simple
				'0 - how are you ?',
				null,
				null,
				null,
				'find !',
			],
			[ // simple, use default 
				'1 - how are you ?',
				'i\'m ok !',
				null,
				null,
				'i\'m ok !',
			],
			[ // simple, do not use default 
				'2 - how are you ?',
				'not find !',
				null,
				null,
				'find and you ?',
			],
			[ // filter value, invalid inputs then valid
				'3 - are you ok ?',
				null,
				['yes', 'no'],
				Cli::FILTER_VALUE,
				'yes',
			],
			[ // filter value, first time valid, first element
				'4 - are you ok ?',
				null,
				['yes', 'no'],
				Cli::FILTER_VALUE,
				'yes',
			],
			[ // filter value, first time valid, last element
				'5 - are you ok ?',
				null,
				['yes', 'no', 'perhaps'],
				Cli::FILTER_VALUE,
				'perhaps',
			],
			[ // filter value, use default
				'6 - are you ok ?',
				'yes',
				['yes', 'no'],
				Cli::FILTER_VALUE,
				'yes',
			],
			[ // filter value, invalid input then use default
				'7 - are you ok ?',
				'no',
				['yes', 'no'],
				Cli::FILTER_VALUE,
				'no',
			],
			[ // filter value, do not use default
				'8 - are you ok ?',
				'yes',
				['yes', 'no'],
				Cli::FILTER_VALUE,
				'no',
			],
			[ // filter value, invalid input then do not use default
				'9 - are you ok ?',
				'no',
				['yes', 'no'],
				Cli::FILTER_VALUE,
				'yes',
			],
			[ // filter key, invalid inputs then valid
				'10 - are you ok ?',
				null,
				['yes', 'no'],
				Cli::FILTER_KEY,
				'0',
			],
			[ // filter key, first time valid, first element
				'11 - are you ok ?',
				null,
				['yes', 'no'],
				Cli::FILTER_KEY,
				'0',
			],
			[ // filter key, first time valid, last element
				'12 - are you ok ?',
				null,
				['yes', 'no', 'perhaps'],
				Cli::FILTER_KEY,
				'2',
			],
			[ // filter key, use default
				'13 - are you ok ?',
				'0',
				['yes', 'no'],
				Cli::FILTER_KEY,
				'0',
			],
			[ // filter key, invalid input then use default
				'14 - are you ok ?',
				'1',
				['yes', 'no'],
				Cli::FILTER_KEY,
				'1',
			],
			[ // filter key, do not use default
				'15 - are you ok ?',
				'0',
				['yes', 'no'],
				Cli::FILTER_KEY,
				'1',
			],
			[ // filter key, invalid input then do not use default
				'16 - are you ok ?',
				'1',
				['yes', 'no'],
				Cli::FILTER_KEY,
				'0',
			],
			[ // filter multi, invalid inputs then valid
				'17 - resolve equation x^2 = 16 ?',
				null,
				['0', '-4', '4'],
				Cli::FILTER_MULTI,
				['0'],
			],
			[ // filter multi, first time valid, first element
				'18 - resolve equation x^2 = 16 ?',
				null,
				['0', '-4', '4'],
				Cli::FILTER_MULTI,
				['0'],
			],
			[ // filter multi, first time valid, two last elements
				'19 - resolve equation x^2 = 16 ?',
				null,
				['0', '-4', '4'],
				Cli::FILTER_MULTI,
				['1', '2'],
			],
			[ // filter multi, use default
				'20 - resolve equation x^2 = 16 ?',
				['0'],
				['0', '-4', '4'],
				Cli::FILTER_MULTI,
				['0'],
			],
			[ // filter multi, invalid input then use default
				'21 - resolve equation x^2 = 16 ?',
				['1', '2'],
				['0', '-4', '4'],
				Cli::FILTER_MULTI,
				['1', '2'],
			],
			[ // filter multi, do not use default
				'22 - resolve equation x^2 = 16 ?',
				['0'],
				['0', '-4', '4'],
				Cli::FILTER_MULTI,
				['1', '2'],
			],
			[ // filter multi, invalid input then do not use default
				'23 - resolve equation x^2 = 16 ?',
				['1', '2'],
				['0', '-4', '4'],
				Cli::FILTER_MULTI,
				['0'],
			],
			[ // filter regex, invalid inputs then valid
				'24 - which comhon model would you process ?',
				null,
				['/^\w+(\\\\\\w+)*$/', '/^Test.+House$/'],
				Cli::FILTER_REGEX,
				'Test\House',
			],
			[ // filter regex, first time valid
				'25 - which comhon model would you process ?',
				null,
				['/^\w+(\\\\\\w+)*$/'],
				Cli::FILTER_REGEX,
				'Test\House',
			],
			[ // filter regex, several regex, first time valid only first regex
				'26 - which comhon model would you process ?',
				null,
				['/^\w+(\\\\\\w+)*$/', '/^Test.+House$/'],
				Cli::FILTER_REGEX,
				'Test\Person',
			],
			[ // filter regex, several regex, first time valid all regex
				'27 - which comhon model would you process ?',
				null,
				['/^\w+(\\\\\\w+)*$/', '/^Test.+Person$/'],
				Cli::FILTER_REGEX,
				'Test\Person',
			],
			[ // filter regex, use default
				'28 - which comhon model would you process ?',
				'Test\House',
				['/^\w+(\\\\\\w+)*$/'],
				Cli::FILTER_REGEX,
				'Test\House',
			],
			[ // filter regex, invalid input then use default
				'29 - which comhon model would you process ?',
				'Test\Person',
				['/^\w+(\\\\\\w+)*$/'],
				Cli::FILTER_REGEX,
				'Test\Person',
			],
			[ // filter regex, do not use default
				'30 - which comhon model would you process ?',
				'Test\House',
				['/^\w+(\\\\\\w+)*$/'],
				Cli::FILTER_REGEX,
				'Test\Person',
			],
			[ // filter regex, invalid input then do not use default
				'31 - which comhon model would you process ?',
				'Test\Person',
				['/^\w+(\\\\\\w+)*$/'],
				Cli::FILTER_REGEX,
				'Test\House',
			],
		];
	}
	
	
	
	/**
	 * @dataProvider askInvalidData
	 */
	public function testAskInvalidParameters($question, $default, $filter, $filterType, $errorMessage)
	{
		$this->expectExceptionMessage($errorMessage);
		Cli::ask($question, $default, $filter, $filterType);
	}
	
	
	public function askInvalidData()
	{
		return [
			[ // not string question
				[],
				null,
				null,
				null,
				'question must be not empty string',
			],
			[ // empty string question
				'',
				null,
				null,
				null,
				'question must be not empty string',
			],
			[ // empty array filter
				'are my parameters invalid ?',
				null,
				[],
				Cli::FILTER_VALUE,
				'filter must be null or not empty array',
			],
			[ // not string array responses value with filter regex
				'are my parameters invalid ?',
				'my_default',
				['yes', ['yes', 'no']],
				Cli::FILTER_VALUE,
				'provided filter responses must be an array of strings',
			],
			[ // invalid filter type
				'are my parameters invalid ?',
				'yes',
				['yes', 'no'],
				'invalid type',
				'invalid filter type \'invalid type\'',
			],
			[ // default value not in filter value
				'are my parameters invalid ?',
				'perhaps',
				['yes', 'no'],
				Cli::FILTER_VALUE,
				'default response \'perhaps\' not found in responses array',
			],
			[ // default value not in filter kry
				'are my parameters invalid ?',
				'perhaps',
				['yes', 'no'],
				Cli::FILTER_KEY,
				'default response key \'perhaps\' not found in responses array',
			],
			[ // default value not in filter multi
				'are my parameters invalid ?',
				[0, 1, 'my_key'],
				['yes', 'no'],
				Cli::FILTER_MULTI,
				'default response key \'my_key\' not found in responses array',
			],
			[ // not string default value without filter
				'are my parameters invalid ?',
				['my_default'],
				null,
				Cli::FILTER_VALUE, // not used
				'without filter, provided default response must be a string',
			],
			[ // not string default value with filter value
				'are my parameters invalid ?',
				['my_default'],
				['yes', 'no'],
				Cli::FILTER_VALUE,
				'with filter Cli::FILTER_VALUE, provided default response must be a string',
			],
			[ // not string or integer default value with filter key
				'are my parameters invalid ?',
				[0, 1, 'my_key'],
				['yes', 'no'],
				Cli::FILTER_KEY,
				'with filter Cli::FILTER_KEY, provided default response must be a string or an integer',
			],
			[ // not array default value with filter multi
				'are my parameters invalid ?',
				'my_key',
				['yes', 'no'],
				Cli::FILTER_MULTI,
				'with filter Cli::FILTER_MULTI, provided default response must be a not empty array',
			],
			[ // not string or int array default value with filter multi
				'are my parameters invalid ?',
				[0, ['yes', 'no']],
				['yes', 'no'],
				Cli::FILTER_MULTI,
				'with filter Cli::FILTER_MULTI, provided default response must be an array of strings or integers'
			],
			[ // not string default value with filter regex
				'are my parameters invalid ?',
				['my_default'],
				['yes', 'no'],
				Cli::FILTER_REGEX,
				'with filter Cli::FILTER_REGEX, provided default response must be a string',
			],
		];
	}
	
	/**
	 * @depends testAskResponses
	 */
	public function testAskQuestions() {
		$separator = DIRECTORY_SEPARATOR;
		$expected = __DIR__.$separator.'data'.$separator.'Ask'.$separator.'stdout_expected.txt';
		$actual = __DIR__.$separator.'data'.$separator.'Ask'.$separator.'stdout_actual.txt';
		$this->assertFileEquals($expected, $actual);
	}
}
