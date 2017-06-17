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

	/** @var string */
	private $function;

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
	
	/**
	 * 
	 * @param string $function [self::COUNT, self::SUM, self::AVG, self::MIN, self::MAX]
	 * @param TableNode|string $table table name or table object linked to literal
	 * @param string $column
	 * @param string $operator
	 * @param string $value
	 */
	public function __construct($function, $table, $column, $operator, $value) {
		$this->function = $function;
		parent::__construct($table, $column, $operator, $value);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Database\Literal::_verifLiteral()
	 */
	protected function _verifLiteral() {
		if (!array_key_exists($this->operator, self::$allowedOperators)) {
			throw new \Exception('operator \''.$this->operator.'\' doesn\'t exists');
		}
		if (!array_key_exists($this->function, self::$allowedFunctions)) {
			throw new \Exception('function \''.$this->function.'\' doesn\'t exists');
		}
		if (!is_int($this->value)) {
			throw new \Exception('having literal must have an integer value');
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Database\Literal::export()
	 */
	public function export(&$values) {
		$columnTable = is_null($this->column) ? '*'
			: ((($this->table instanceof TableNode) ? $this->table->getExportName() : $this->table) . '.' . $this->column);
		return sprintf('%s(%s) %s %s', $this->function, $columnTable, $this->operator, $this->value);
	}
	
	/**
	 * 
	 * @param \stdClass $stdObject
	 * @throws \Exception
	 */
	private static function _verifStdObject($stdObject) {
		if (!is_object($stdObject) || !isset($stdObject->function) || !isset($stdObject->operator) ||!isset($stdObject->value)) {
			throw new \Exception('malformed stdObject literal : '.json_encode($stdObject));
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