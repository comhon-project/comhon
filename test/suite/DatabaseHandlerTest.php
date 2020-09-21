<?php

use PHPUnit\Framework\TestCase;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Database\DatabaseHandler;
use Comhon\Exception\ComhonException;

class DatabaseHandlerTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testGetInstanceUnknownId()
	{
		$this->expectException(ComhonException::class);
		$this->expectExceptionCode(0);
		$this->expectExceptionMessage("database file with id 'unknown' not found");
		
		DatabaseHandler::getInstanceWithDataBaseId('unknown');
	}
}
