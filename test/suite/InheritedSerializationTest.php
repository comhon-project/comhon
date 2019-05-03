<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\ComhonObject;

class InheritedSerializationTest extends TestCase
{
	
	public function testParentSerializationInstance()
	{
		// start from Test\GreatGrandParent\GrandParent\ParentOne\Child
		$currentModel = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child');
		$this->assertInstanceOf(ComhonObject::class, $currentModel->getSerializationSettings());
		$this->assertSame($currentModel->getSerializationSettings(), $currentModel->getParent()->getSerializationSettings());
		$this->assertEquals(
			'{"saticPath":".\/data\/inherited_serialization","staticName":"file.json"}',
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
			'{"saticPath":".\/data\/inherited_serialization_two","staticName":"file.json"}',
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
		$this->assertEquals('child_one', $model->getSerialization()->getInheritanceKey());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo\Child');
		$this->assertEquals(null, $model->getSerialization()->getInheritanceKey());
	}
	
	public function testSerializationAllowed()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent');
		$this->assertTrue($model->getSerialization()->isSerializationAllowed());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne');
		$this->assertFalse($model->getSerialization()->isSerializationAllowed());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child');
		$this->assertTrue($model->getSerialization()->isSerializationAllowed());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo');
		$this->assertTrue($model->getSerialization()->isSerializationAllowed());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo\Child');
		$this->assertTrue($model->getSerialization()->isSerializationAllowed());
	}
	
	public function testFirstParentMatch()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child');
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentOne', $model->getFirstParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentOne', $model->getFirstParentMatch(true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getFirstParentMatch(null, true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getFirstParentMatch(true, true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentOne', $model->getFirstParentMatch(true, false)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getFirstParentMatch(false, false)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getFirstParentMatch(false)->getName());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo\Child');
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentTwo', $model->getFirstParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getFirstParentMatch(true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentTwo', $model->getFirstParentMatch(null, true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getFirstParentMatch(true, true)->getName());
		$this->assertNull($model->getFirstParentMatch(true, false));
		$this->assertEquals('Test\GreatGrandParent', $model->getFirstParentMatch(false, false)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentTwo', $model->getFirstParentMatch(false)->getName());
	}
	
	public function testLastParentMatch()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child');
		$this->assertEquals('Test\GreatGrandParent', $model->getLastParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastParentMatch(true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastParentMatch(null, true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastParentMatch(true, true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentOne', $model->getLastParentMatch(true, false)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getLastParentMatch(false, false)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getLastParentMatch(false)->getName());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo\Child');
		$this->assertEquals('Test\GreatGrandParent', $model->getLastParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastParentMatch(true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastParentMatch(null, true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastParentMatch(true, true)->getName());
		$this->assertNull($model->getLastParentMatch(true, false));
		$this->assertEquals('Test\GreatGrandParent', $model->getLastParentMatch(false, false)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getLastParentMatch(false)->getName());
	}
	
}
