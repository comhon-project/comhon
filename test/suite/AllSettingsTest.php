<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\Config\Config;
use Test\Comhon\Data;
use Test\Comhon\Object\Settings;
use Test\Comhon\Object\SettingsLocal;
use Comhon\Object\ComhonObject;

class AllSettingsTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	/**
	 *
	 * @dataProvider allSettingsData
	 */
	public function testAllSettings($modelName, $class, $isMain, $isAbstract, $sharedId)
	{
		$model = ModelManager::getInstance()->getInstanceModel($modelName);
		$this->assertInstanceOf($class, $model->getObjectInstance());
		
		$this->assertEquals($isMain, $model->isMain());
		$this->assertEquals($isAbstract, $model->isAbstract());
		if (!is_null($sharedId)) {
			$this->assertSame(ModelManager::getInstance()->getInstanceModel($sharedId), $model->getSharedIdModel());
		}
	}
	
	public function allSettingsData()
	{
		return [
			[
					'Test\Basic\Id\Main',
					ComhonObject::class,
					true,
					true,
					null
			],
			[
					'Test\Settings',
					Settings::class,
					false,
					false,
					'Test\Basic\Id\Main'
			],
			[
					'Test\Settings\Local',
					SettingsLocal::class,
					true,
					true,
					'Test\Basic\Id\Simple'
			],
			[
					'Test\Settings\LocalTwo',
					ComhonObject::class,
					false,
					false,
					'Test\Basic\Id\Simple'
			]
		];
	}
}
