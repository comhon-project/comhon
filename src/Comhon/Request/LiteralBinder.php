<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Request;

use Comhon\Object\UniqueObject;
use Comhon\Model\Property\Property;
use Comhon\Model\Property\MultipleForeignProperty;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\ModelBoolean;
use Comhon\Model\Property\ForeignProperty;

class LiteralBinder {

	const ALLOWED_STRING_LITERALS = [
		'Comhon\Logic\Simple\Literal\String',
		'Comhon\Logic\Simple\Literal\Set\String',
		'Comhon\Logic\Simple\Literal\Null'
	];
	
	const ALLOWED_FLOAT_LITERALS = [
		'Comhon\Logic\Simple\Literal\Numeric\Float',
		'Comhon\Logic\Simple\Literal\Set\Numeric\Float',
		'Comhon\Logic\Simple\Literal\Numeric\Integer',
		'Comhon\Logic\Simple\Literal\Set\Numeric\Integer',
		'Comhon\Logic\Simple\Literal\Null'
	];
	
	const ALLOWED_INTEGER_LITERALS = [
		'Comhon\Logic\Simple\Literal\Numeric\Integer',
		'Comhon\Logic\Simple\Literal\Set\Numeric\Integer',
		'Comhon\Logic\Simple\Literal\Null'
	];
	
	const ALLOWED_BOOLEAN_LITERALS = [
		'Comhon\Logic\Simple\Literal\Boolean',
		'Comhon\Logic\Simple\Literal\Null'
	];
	
	private static $allowedLiterals = [
			'string'     => self::ALLOWED_STRING_LITERALS,
			'integer'    => self::ALLOWED_INTEGER_LITERALS,
			'float'      => self::ALLOWED_FLOAT_LITERALS,
			'dateTime'   => self::ALLOWED_STRING_LITERALS,
			'index'      => self::ALLOWED_INTEGER_LITERALS,
			'percentage' => self::ALLOWED_FLOAT_LITERALS,
			'boolean'    => self::ALLOWED_BOOLEAN_LITERALS
	];
	
	/**
	 * verify if given literal is allowed on given property.
	 * literals are used when requesting objects.
	 * model of given literal must be a 'Comhon\Logic\Simple\Literal'.
	 *
	 * @param \Comhon\Model\Property\Property $property
	 * @param \Comhon\Object\UniqueObject $literal
	 * @return boolean
	 */
	public static function isAllowedLiteral(Property $property, UniqueObject $literal) {
		$literalModel = $property->getLiteralModel();
		$literalModelName = $literalModel ? $literalModel->getName() : null;
		
		return array_key_exists($literalModelName, self::$allowedLiterals)
			? in_array($literal->getModel()->getName(), self::$allowedLiterals[$literalModelName])
			: false;
	}
	
	/**
	 * get allowed literals that may be applied on given property.
	 * literals are used when requesting objects.
	 *
	 * @param \Comhon\Model\Property\Property $property
	 * @return string[]
	 */
	public static function getAllowedLiterals(Property $property) {
		$literalModel = $property->getLiteralModel();
		$literalModelName = $literalModel ? $literalModel->getName() : null;
		
		return array_key_exists($literalModelName, self::$allowedLiterals)
			? self::$allowedLiterals[$literalModelName]
			: [];
	}
	
	/**
	 * get instance literal associated to given property.
	 * literals are used when requesting objects.
	 *
	 * @param \Comhon\Model\Property\Property $property
	 * @param boolean $isSet determine if returned literal is a literal with a set of values
	 * @return \Comhon\Object\UniqueObject|null
	 */
	public static function getLiteralInstance(Property $property, $isSet = false) {
		$literalModel = $property->getLiteralModel();
		$literalModelName = $literalModel ? $literalModel->getName() : null;
		if (is_null($literalModelName)) {
			return null;
		}
		
		if ($isSet) {
			if ($literalModel instanceof ModelBoolean) {
				return null;
			}
			$index = 1;
		} else {
			$index = 0;
		}
		$modelName = array_key_exists($literalModelName, self::$allowedLiterals)
			? self::$allowedLiterals[$literalModelName][$index]
			: null;
		return is_null($modelName) ? null : ModelManager::getInstance()->getInstanceModel($modelName)->getObjectInstance(false);
	}
	
}
