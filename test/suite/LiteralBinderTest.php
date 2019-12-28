<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Request\LiteralBinder;

class LiteralBinderTest extends TestCase
{
	
	/**
	 * @dataProvider isAllowedLiteralData
	 */
	public function testIsAllowedLiteral($modelName, $propertyName, $literalModelNames)
	{
		$model = ModelManager::getInstance()->getInstanceModel($modelName);
		$property = $model->getProperty($propertyName, true);
		
		foreach ($literalModelNames as $literalModelName => $isAllowed) {
			$literal = ModelManager::getInstance()->getInstanceModel($literalModelName)->getObjectInstance(false);
			$this->assertEquals($isAllowed, LiteralBinder::isAllowedLiteral($property, $literal));
		}
	}
	
	public function isAllowedLiteralData()
	{
		return [
			[
				'Test\Test',
				'booleanValue',
				[
					'Comhon\Logic\Simple\Literal\Boolean' => true,
					'Comhon\Logic\Simple\Literal\Null' => true,
					'Comhon\Logic\Simple\Literal\String' => false,
					'Comhon\Logic\Simple\Literal\Set\Numeric\Integer' => false,
				],
			],
			[
				'Test\TestDb',
				'integer',
				[
					'Comhon\Logic\Simple\Literal\Numeric\Integer' => true,
					'Comhon\Logic\Simple\Literal\Set\Numeric\Integer' => true,
					'Comhon\Logic\Simple\Literal\Null' => true,
					'Comhon\Logic\Simple\Literal\Numeric\Float' => false,
				],
			],
			[
				'Test\Test',
				'floatValue',
				[
					'Comhon\Logic\Simple\Literal\Numeric\Float' => true,
					'Comhon\Logic\Simple\Literal\Set\Numeric\Float' => true,
					'Comhon\Logic\Simple\Literal\Set\Numeric\Integer' => true,
					'Comhon\Logic\Simple\Literal\Null' => true,
					'Comhon\Logic\Simple\Literal\Boolean' => false,
				],
			],
			[
				'Test\Test',
				'stringValue',
				[
					'Comhon\Logic\Simple\Literal\String'     => true,
					'Comhon\Logic\Simple\Literal\Set\String' => true,
					'Comhon\Logic\Simple\Literal\Null'       => true,
					'Comhon\Logic\Simple\Literal\Boolean'    => false,
				],
			],
			[
				'Test\Test',
				'indexValue',
				[
					'Comhon\Logic\Simple\Literal\Numeric\Integer' => true,
					'Comhon\Logic\Simple\Literal\Set\Numeric\Integer' => true,
					'Comhon\Logic\Simple\Literal\Null' => true,
					'Comhon\Logic\Simple\Literal\Numeric\Float' => false,
				],
			],
			[
				'Test\Test',
				'percentageValue',
				[
					'Comhon\Logic\Simple\Literal\Numeric\Float' => true,
					'Comhon\Logic\Simple\Literal\Set\Numeric\Float' => true,
					'Comhon\Logic\Simple\Literal\Set\Numeric\Integer' => true,
					'Comhon\Logic\Simple\Literal\Null' => true,
					'Comhon\Logic\Simple\Literal\Boolean' => false,
				],
			],
			[
				'Test\Test',
				'dateValue',
				[
					'Comhon\Logic\Simple\Literal\String'     => true,
					'Comhon\Logic\Simple\Literal\Set\String' => true,
					'Comhon\Logic\Simple\Literal\Null'       => true,
					'Comhon\Logic\Simple\Literal\Boolean'    => false,
				],
			],
			[
				'Test\Person',
				'birthPlace',
				[
					'Comhon\Logic\Simple\Literal\Numeric\Integer' => true,
					'Comhon\Logic\Simple\Literal\Set\Numeric\Integer' => true,
					'Comhon\Logic\Simple\Literal\Null' => true,
					'Comhon\Logic\Simple\Literal\Numeric\Float' => false,
				],
			],
			[
				'Test\Person',
				'children',
				[
					'Comhon\Logic\Simple\Literal\Numeric\Integer' => false,
					'Comhon\Logic\Simple\Literal\Set\String' => false,
					'Comhon\Logic\Simple\Literal\Null' => false,
					'Comhon\Logic\Simple\Literal\Numeric\Float' => false,
				],
			],
			[
				'Test\ChildTestDb',
				'parentTestDb',
				[
					'Comhon\Logic\Simple\Literal\String' => true,
					'Comhon\Logic\Simple\Literal\Set\String' => true,
					'Comhon\Logic\Simple\Literal\Null' => true,
					'Comhon\Logic\Simple\Literal\Numeric\Float' => false,
				],
			],
		];
	}
	
	/**
	 * @dataProvider getLiteralInstanceData
	 */
	public function testgetLiteralInstance($modelName, $propertyName, $isSet, $literalModelName)
	{
		$model = ModelManager::getInstance()->getInstanceModel($modelName);
		$property = $model->getProperty($propertyName, true);
		
		$literal = LiteralBinder::getLiteralInstance($property, $isSet);
		if (is_null($literalModelName)) {
			$this->assertEquals(null, $literal);
		} else {
			$this->assertEquals($literal->getModel()->getName(), $literalModelName);
		}
	}
	
	public function getLiteralInstanceData()
	{
		return [
			[
				'Test\Test',
				'stringValue',
				false, 
				'Comhon\Logic\Simple\Literal\String'
			],
			[
				'Test\Test',
				'stringValue',
				true,
				'Comhon\Logic\Simple\Literal\Set\String'
			],
			[
				'Test\TestDb',
				'integer',
				false,
				'Comhon\Logic\Simple\Literal\Numeric\Integer'
			],
			[
				'Test\TestDb',
				'integer',
				true,
				'Comhon\Logic\Simple\Literal\Set\Numeric\Integer'
			],
			[
				'Test\Test',
				'floatValue',
				false,
				'Comhon\Logic\Simple\Literal\Numeric\Float'
			],
			[
				'Test\Test',
				'floatValue',
				true,
				'Comhon\Logic\Simple\Literal\Set\Numeric\Float'
			],
			[
				'Test\Test',
				'booleanValue',
				false,
				'Comhon\Logic\Simple\Literal\Boolean'
			],
			[
				'Test\Test',
				'booleanValue',
				true,
				null
			],
			[
				'Test\Person',
				'children',
				false,
				null
			],
			[
				'Test\Person',
				'children',
				true,
				null
			],
			[
				'Test\Person',
				'birthPlace',
				false,
				'Comhon\Logic\Simple\Literal\Numeric\Integer'
			],
			[
				'Test\Person',
				'birthPlace',
				true,
				'Comhon\Logic\Simple\Literal\Set\Numeric\Integer'
			],
			[
				'Test\ChildTestDb',
				'parentTestDb',
				false,
				'Comhon\Logic\Simple\Literal\String'
			],
			[
				'Test\ChildTestDb',
				'parentTestDb',
				true,
				'Comhon\Logic\Simple\Literal\Set\String'
			]
		];
	}
}
