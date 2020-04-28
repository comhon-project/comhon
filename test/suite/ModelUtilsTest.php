<?php

use PHPUnit\Framework\TestCase;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Utils\Model;
use Comhon\Model\Singleton\ModelManager;

class ModelUtilsTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testGetValidatedProjectModelNamesWithoutFilter()
	{
		ModelManager::resetSingleton();
		
		$notValid = [];
		$modelNames = Model::getValidatedProjectModelNames(null, true, $notValid);
		$expected = json_decode(file_get_contents(__DIR__.'/data/ModelUtils/models.json'));
		$this->assertEquals($expected, $modelNames);
		
		$suffix = Config::getInstance()->getManifestFormat();
		$expected = json_decode(file_get_contents(__DIR__."/data/ModelUtils/not_valid_models_{$suffix}.json"), true);
		$this->assertEquals($expected, $notValid);
	}
	
	public function testGetValidatedProjectModelNamesWithFilter()
	{
		$notValid = [];
		$modelNames = Model::getValidatedProjectModelNames(__DIR__.'/../manifests/test/manifest/Body', true, $notValid);
		$expected = [
			'Test\Body',
			'Test\Body\Art',
			'Test\Body\Tatoo',
			'Test\Body\Piercing',
			'Test\Body\Man',
			'Test\Body\ManJson',
			'Test\Body\Woman',
			'Test\Body\ManJsonExtended',
		];
		$this->assertEquals($expected, $modelNames);
		$this->assertEquals([], $notValid);
	}
	
	public function testGetValidatedProjectModelNamesWhitoutLocalTypesWithFilter()
	{
		$notValid = [];
		$modelNames = Model::getValidatedProjectModelNames(__DIR__.'/../manifests/test/manifest/Body', false, $notValid);
		$expected = [
			'Test\Body',
			'Test\Body\Man',
			'Test\Body\ManJson',
			'Test\Body\Woman',
			'Test\Body\ManJsonExtended',
		];
		$this->assertEquals($expected, $modelNames);
		$this->assertEquals([], $notValid);
	}
	
	public function testGetValidatedProjectModelNamesWithNotExistingDir()
	{
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage("directory '/home/jean-philippe/ReposGit/comhon/test/suite/../manifests/test/manifest/my_dir' doesn't exist");
		Model::getValidatedProjectModelNames(__DIR__.'/../manifests/test/manifest/my_dir');
	}
	
	public function testGetValidatedProjectModelNamesWithFilterNotInAutoload()
	{
		$notValid = [];
		$modelNames = Model::getValidatedProjectModelNames(__DIR__, true, $notValid);
		$this->assertEquals([], $modelNames);
		$this->assertEquals([], $notValid);
	}
	
	public function testGetValidatedProjectModelNamesWithDirectoryHigherThanAutoload()
	{
		ModelManager::resetSingleton();
		
		// must act like there is no filter
		$notValid = [];
		$modelNames = Model::getValidatedProjectModelNames(__DIR__.'/..', true, $notValid);
		$expected = json_decode(file_get_contents(__DIR__.'/data/ModelUtils/models.json'));
		$this->assertEquals($expected, $modelNames);
		
		$suffix = Config::getInstance()->getManifestFormat();
		$expected = json_decode(file_get_contents(__DIR__."/data/ModelUtils/not_valid_models_{$suffix}.json"), true);
		$this->assertEquals($expected, $notValid);
	}
	
	
	public function testSortModelNames()
	{
		$modelNames = [
			'Test\Body\Man',
			'Test\Body',
			'Test\Body\Piercing',
			'Test\Body\Art',
			'Test\Body\Tatoo',
			'Test\Body\ManJson',
			'Test\Body\Woman',
			'Test\Body\Piercing',
			'Test\Body\ManJsonExtended',
			'Test\Body\Man',
		];
		Model::sortModelNamesByInheritance($modelNames);
		
		$expected = [
			'Test\Body',
			'Test\Body\Man',
			'Test\Body\Man',
			'Test\Body\Art',
			'Test\Body\Piercing',
			'Test\Body\Piercing',
			'Test\Body\Tatoo',
			'Test\Body\ManJson',
			'Test\Body\Woman',
			'Test\Body\ManJsonExtended',
		];
		$this->assertEquals($expected, $modelNames);
	}
}
