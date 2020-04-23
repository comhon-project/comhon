<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\ComhonObject;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;

class InheritedSerializationTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testParentSerializationInstance()
	{
		// start from Test\GreatGrandParent\GrandParent\ParentOne\Child
		$currentModel = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child');
		$this->assertInstanceOf(ComhonObject::class, $currentModel->getSerializationSettings());
		$this->assertSame($currentModel->getSerializationSettings(), $currentModel->getParent()->getSerializationSettings());
		$this->assertEquals(
			'{"dir_path":".\/data\/inherited_serialization","file_name":"file.json"}',
			json_encode($currentModel->getSerializationSettings()->getValues())
		);
		
		$currentModel = $currentModel->getParent();
		$this->assertInstanceOf(ComhonObject::class, $currentModel->getSerializationSettings());
		$this->assertSame($currentModel->getSerializationSettings(), $currentModel->getParent()->getSerializationSettings());
		
		$currentModel = $currentModel->getParent();
		$this->assertInstanceOf(ComhonObject::class, $currentModel->getSerializationSettings());
		$this->assertNull($currentModel->getParent()->getSerializationSettings());
		
		// start from Test\GreatGrandParent\GrandParent\ParentTwo\Child
		$currentModel = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo\Child');
		$childSerialization = $currentModel->getSerializationSettings();
		
		$this->assertInstanceOf(ComhonObject::class, $currentModel->getSerializationSettings());
		$this->assertNotSame($childSerialization, $currentModel->getParent()->getSerializationSettings());
		
		$currentModel = $currentModel->getParent();
		$this->assertInstanceOf(ComhonObject::class, $currentModel->getSerializationSettings());
		$this->assertEquals(
			'{"dir_path":".\/data\/inherited_serialization_two","file_name":"file.json"}',
			json_encode($currentModel->getSerializationSettings()->getValues())
		);
		
		$currentModel = $currentModel->getParent();
		$this->assertSame($childSerialization, $currentModel->getSerializationSettings());
		$this->assertInstanceOf(ComhonObject::class, $currentModel->getSerializationSettings());
		$this->assertNull($currentModel->getParent()->getSerializationSettings());
	}
	
	public function testInheritanceKey()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent');
		$this->assertEquals('grand_parent', $model->getSerialization()->getInheritanceKey());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne');
		$this->assertEquals('grand_parent', $model->getSerialization()->getInheritanceKey());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child');
		$this->assertEquals('grand_parent', $model->getSerialization()->getInheritanceKey());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo\Child');
		$this->assertEquals('grand_parent', $model->getSerialization()->getInheritanceKey());
	}
	
	public function testSerializationAllowed()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent');
		$this->assertTrue($model->isSerializable());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne');
		$this->assertFalse($model->isSerializable());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child');
		$this->assertTrue($model->isSerializable());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo');
		$this->assertTrue($model->isSerializable());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo\Child');
		$this->assertTrue($model->isSerializable());
	}
	
	public function testLocalModelSerialization()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child\ChildLocalSerializable');
		$this->assertTrue($model->isSerializable());
		
		$this->assertEquals('local_child_one', $model->getProperty('localChildOne')->getSerializationName());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child\ChildLocalNotSerializable');
		$this->assertFalse($model->isSerializable());
	}
	
	public function testFirstSharedIdParentMatch()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child');
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentOne', $model->getFirstSharedIdParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentOne', $model->getFirstSharedIdParentMatch(true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getFirstSharedIdParentMatch(null, true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getFirstSharedIdParentMatch(true, true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentOne', $model->getFirstSharedIdParentMatch(true, false)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getFirstSharedIdParentMatch(false, false)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getFirstSharedIdParentMatch(false)->getName());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo\Child');
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getFirstSharedIdParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getFirstSharedIdParentMatch(true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getFirstSharedIdParentMatch(null, true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getFirstSharedIdParentMatch(true, true)->getName());
		$this->assertNull($model->getFirstSharedIdParentMatch(true, false));
		$this->assertEquals('Test\GreatGrandParent', $model->getFirstSharedIdParentMatch(false, false)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getFirstSharedIdParentMatch(false)->getName());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParentTwo\Parent\Child');
		$this->assertEquals('Test\GreatGrandParent\GrandParentTwo\Parent', $model->getFirstSharedIdParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParentTwo\Parent', $model->getFirstSharedIdParentMatch(true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParentTwo\Parent', $model->getFirstSharedIdParentMatch(null, false)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParentTwo\Parent', $model->getFirstSharedIdParentMatch(true, false)->getName());
		$this->assertNull($model->getFirstSharedIdParentMatch(false));
		$this->assertNull($model->getFirstSharedIdParentMatch(false, true));
		$this->assertNull($model->getFirstSharedIdParentMatch(false, false));
		$this->assertNull($model->getFirstSharedIdParentMatch(true, true));
		$this->assertNull($model->getFirstSharedIdParentMatch(null, true));
	}
	
	public function testLastSharedIdParentMatch()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child');
		$this->assertEquals('Test\GreatGrandParent', $model->getLastSharedIdParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastSharedIdParentMatch(true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastSharedIdParentMatch(null, true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastSharedIdParentMatch(true, true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentOne', $model->getLastSharedIdParentMatch(true, false)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getLastSharedIdParentMatch(false, false)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getLastSharedIdParentMatch(false)->getName());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo\Child');
		$this->assertEquals('Test\GreatGrandParent', $model->getLastSharedIdParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastSharedIdParentMatch(true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastSharedIdParentMatch(null, true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastSharedIdParentMatch(true, true)->getName());
		$this->assertNull($model->getLastSharedIdParentMatch(true, false));
		$this->assertEquals('Test\GreatGrandParent', $model->getLastSharedIdParentMatch(false, false)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getLastSharedIdParentMatch(false)->getName());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParentTwo\Parent\Child');
		$this->assertEquals('Test\GreatGrandParent', $model->getLastSharedIdParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getLastSharedIdParentMatch(true)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getLastSharedIdParentMatch(null, false)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getLastSharedIdParentMatch(true, false)->getName());
		$this->assertNull($model->getLastSharedIdParentMatch(false));
		$this->assertNull($model->getLastSharedIdParentMatch(false, true));
		$this->assertNull($model->getLastSharedIdParentMatch(false, false));
		$this->assertNull($model->getLastSharedIdParentMatch(true, true));
		$this->assertNull($model->getLastSharedIdParentMatch(null, true));
	}
	
}
