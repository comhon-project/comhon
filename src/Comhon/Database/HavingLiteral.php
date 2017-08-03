<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Database;

use Comhon\Exception\Literal\MalformedLiteralException;
use Comhon\Exception\PropertyVisibilityException;
use Comhon\Exception\ComhonException;

class HavingLiteral extends DbLiteral {

	/** @var string */
	const COUNT = 'COUNT';
	
	/** @var string */
	const SUM   = 'SUM';
	
	/** @var string */
	const AVG   = 'AVG';
	
	/** @var string */
	const MIN   = 'MIN';
	
	/** @var string */
	const MAX   = 'MAX';
	
	/** @var array  all allowed functions */
	protected static $allowedFunctions = [
			self::COUNT => null,
			self::SUM   => null,
			self::AVG   => null,
			self::MIN   => null,
			self::MAX   => null
	];
	
	/** @var string */
	private $function;
	
	/** @var integer */
	private $value;
	
	/**
	 * 
	 * @param string $function [self::COUNT, self::SUM, self::AVG, self::MIN, self::MAX]
	 * @param TableNode|string $table table name or table object linked to literal
	 * @param string $column
	 * @param string $operator
	 * @param integer $value
	 */
	public function __construct($function, $table, $column, $operator, $value) {
		parent::__construct($table, $column, $operator);
		
		if (!array_key_exists($function, self::$allowedFunctions)) {
			throw new ComhonException("function '$function' not allowed");
		}
		
		$this->function = $function;
		$this->value = $value;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Logic\Formula::export()
	 */
	public function export(&$values) {
		$columnTable = is_null($this->column) ? '*'
			: ((($this->table instanceof TableNode) ? $this->table->getExportName() : $this->table) . '.' . $this->column);
		return sprintf('%s(%s) %s %s', $this->function, $columnTable, $this->operator, $this->value);
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Logic\Formula::exportDebug()
	 */
	public function exportDebug() {
		$array = [];
		return $this->export($array);
	}
	
	/**
	 * verify if given object has expected format
	 * 
	 * @param \stdClass $stdObject
	 * @throws \Exception
	 */
	private static function _verifStdObject($stdObject) {
		if (
			!is_object($stdObject) 
			|| !isset($stdObject->operator)
			|| !array_key_exists($stdObject->operator, self::$allowedOperators)
			|| !isset($stdObject->value)
			|| !is_int($stdObject->value)
			|| !isset($stdObject->function)
			|| !array_key_exists($stdObject->function, self::$allowedFunctions)
			|| (($stdObject->function != self::COUNT) && !isset($stdObject->property))
		) {
			throw new MalformedLiteralException($stdObject);
		}
	}
	
	/**
	 * build HavingLiteral instance
	 * 
	 * @param \stdClass $stdObject
	 * @param TableNode|string $table not necessary if property 'node' is specified in $stdObject
	 * @param \Comhon\Model\Model $model not necessary if function is self::COUNT
	 * @param boolean $allowPrivateProperties
	 * @throws \Exception
	 * @return HavingLiteral
	 */
	public static function stdObjectToHavingLiteral($stdObject, $table = null, $model = null, $allowPrivateProperties = true) {
		self::_verifStdObject($stdObject);
		
		if ($stdObject->function == self::COUNT) {
			$column = null;
		} else {
			if (is_null($model)) {
				throw new ComhonException('model can\'t be null if function is different than COUNT');
			}
			$property = $model->getProperty($stdObject->property, true);
			if (!$allowPrivateProperties && $property->isPrivate()) {
				throw new PropertyVisibilityException($property->getName());
			}
			$column = $property->getSerializationName();
		}
		
		if (isset($stdObject->node)) {
			$table = $stdObject->node;
		} else if (is_null($table)) {
			throw new ComhonException('literal dosen\'t have property \'node\' and table is not specified in parameter : '.json_encode($stdObject));
		}
		
		$literal  = new HavingLiteral($stdObject->function, $table, $column, $stdObject->operator, $stdObject->value);
		return $literal;
	}
	
}