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
		
		// start from Test\GreatGrandParent\GrandParent\ParentThree\Child
		$currentModel = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentThree\Child');
		$childSerialization = $currentModel->getSerializationSettings();
		
		$this->assertInstanceOf(ComhonObject::class, $currentModel->getSerializationSettings());
		$this->assertNotSame($childSerialization, $currentModel->getParent()->getSerializationSettings());
		
		$currentModel = $currentModel->getParent();
		$this->assertFalse($currentModel->hasSerialization());
		
		$currentModel = $currentModel->getParent();
		$this->assertSame($childSerialization, $currentModel->getSerializationSettings());
		$this->assertInstanceOf(ComhonObject::class, $currentModel->getSerializationSettings());
		$this->assertNull($currentModel->getParent()->getSerializationSettings());
	}
	
	public function testHasSerializationAndInheritanceKey()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent');
		$this->assertTrue($model->hasSerialization());
		$this->assertEquals('grand_parent', $model->getSerialization()->getInheritanceKey());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne');
		$this->assertTrue($model->hasSerialization());
		$this->assertEquals('grand_parent', $model->getSerialization()->getInheritanceKey());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child');
		$this->assertTrue($model->hasSerialization());
		$this->assertEquals('grand_parent', $model->getSerialization()->getInheritanceKey());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo');
		$this->assertTrue($model->hasSerialization());
		$this->assertNull($model->getSerialization()->getInheritanceKey());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo\Child');
		$this->assertTrue($model->hasSerialization());
		$this->assertEquals('grand_parent', $model->getSerialization()->getInheritanceKey());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentThree');
		$this->assertFalse($model->hasSerialization());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentThree\Child');
		$this->assertTrue($model->hasSerialization());
		$this->assertEquals('grand_parent', $model->getSerialization()->getInheritanceKey());
	}
	
	public function testLocalModelSerialization()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child\ChildLocalSerializable');
		$this->assertTrue($model->hasSerialization());
		$this->assertEquals('local_child_one', $model->getProperty('localChildOne')->getSerializationName());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child\ChildLocalNotSerializable');
		$this->assertFalse($model->hasSerialization());
	}
	
	public function testFirstSharedIdParentMatch()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child');
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentOne', $model->getFirstSharedIdParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentOne', $model->getFirstSharedIdParentMatch(true)->getName());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo\Child');
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getFirstSharedIdParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getFirstSharedIdParentMatch(true)->getName());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentThree\Child');
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getFirstSharedIdParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getFirstSharedIdParentMatch(true)->getName());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParentTwo\Parent\Child');
		$this->assertEquals('Test\GreatGrandParent\GrandParentTwo\Parent', $model->getFirstSharedIdParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParentTwo\Parent', $model->getFirstSharedIdParentMatch(true)->getName());
	}
	
	public function testLastSharedIdParentMatch()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child');
		$this->assertEquals('Test\GreatGrandParent', $model->getLastSharedIdParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastSharedIdParentMatch(true)->getName());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo\Child');
		$this->assertEquals('Test\GreatGrandParent', $model->getLastSharedIdParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastSharedIdParentMatch(true)->getName());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentThree\Child');
		$this->assertEquals('Test\GreatGrandParent', $model->getLastSharedIdParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastSharedIdParentMatch(true)->getName());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParentTwo\Parent\Child');
		$this->assertEquals('Test\GreatGrandParent', $model->getLastSharedIdParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getLastSharedIdParentMatch(true)->getName());
	}
	
}
