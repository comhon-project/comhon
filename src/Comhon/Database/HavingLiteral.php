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

class HavingLiteral extends Literal {

	private $function;

	const COUNT = 'COUNT';
	const SUM   = 'SUM';
	const AVG   = 'AVG';
	const MIN   = 'MIN';
	const MAX   = 'MAX';
	
	protected static $acceptedFunctions = [
			self::COUNT => null,
			self::SUM   => null,
			self::AVG   => null,
			self::MIN   => null,
			self::MAX   => null
	];
	
	public function __construct($function, $table, $column, $operator, $value) {
		$this->function = $function;
		parent::__construct($table, $column, $operator, $value);
	}
	
	protected function _verifLiteral() {
		if (!array_key_exists($this->operator, self::$acceptedOperators)) {
			throw new \Exception('operator \''.$this->operator.'\' doesn\'t exists');
		}
		if (!array_key_exists($this->function, self::$acceptedFunctions)) {
			throw new \Exception('function \''.$this->function.'\' doesn\'t exists');
		}
		if (!is_int($this->value)) {
			throw new \Exception('having literal must have an integer value');
		}
	}
	
	/**
	 * @param array $values
	 * @return string
	 */
	public function export(&$values) {
		$columnTable = is_null($this->column) ? '*'
			: ((($this->table instanceof TableNode) ? $this->table->getExportName() : $this->table) . '.' . $this->column);
		return sprintf('%s(%s) %s %s', $this->function, $columnTable, $this->operator, $this->value);
	}
	
	private static function _verifStdObject($stdObject) {
		if (!is_object($stdObject) || !isset($stdObject->function) || !isset($stdObject->operator) ||!isset($stdObject->value)) {
			throw new \Exception('malformed stdObject literal : '.json_encode($stdObject));
		}
	}
	
	/**
	 * @param stdClass $stdObject
	 * @param string|TableNode $table not necessary if proeprty 'node' is specified in $stdObject
	 * @param Model $model not necessary if function is COUNT
	 * @param boolean $allowPrivateProperties
	 * @throws \Exception
	 * @return Literal
	 */
	public static function stdObjectToHavingLiteral($stdObject, $table = null, $model = null, $allowPrivateProperties = true) {
		self::_verifStdObject($stdObject);
		
		if ($stdObject->function == HavingLiteral::COUNT) {
			$column = null;
		} else if (isset($stdObject->property)) {
			if (is_null($model)) {
				throw new \Exception('model can\'t be null if function is different than COUNT');
			}
			$property = $model->getProperty($stdObject->property, true);
			if (!$allowPrivateProperties && $property->isPrivate()) {
				throw new \Exception("having literal contain private property '{$property->getName()}'");
			}
			$column = $property->getSerializationName();
		} else {
			throw new \Exception('malformed stdObject literal : '.json_encode($stdObject));
		}
		
		if (isset($stdObject->node)) {
			$table = $stdObject->node;
		} else if (is_null($table)) {
			throw new \Exception('literal dosen\'t have property \'node\' and table is not specified in parameter : '.json_encode($stdObject));
		}
		
		$literal  = new HavingLiteral($stdObject->function, $table, $column, $stdObject->operator, $stdObject->value);
		return $literal;
	}
	
}