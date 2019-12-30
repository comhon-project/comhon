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

use Comhon\Exception\Model\PropertyVisibilityException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\Literal\MultiplePropertyLiteralException;
use Comhon\Object\UniqueObject;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\ArgumentException;

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
	 * build HavingLiteral instance
	 * 
	 * @param \Comhon\Object\UniqueObject $havingLiteral
	 * @param TableNode|string $table
	 * @param \Comhon\Model\Model $model not necessary if function is self::COUNT
	 * @param boolean $allowPrivateProperties
	 * @throws \Exception
	 * @return HavingLiteral
	 */
	public static function buildHaving(UniqueObject $havingLiteral, $table, $model = null, $allowPrivateProperties = true) {
		$literalModel = ModelManager::getInstance()->getInstanceModel('Comhon\Logic\Having\Literal');
		if (!$havingLiteral->getModel()->isInheritedFrom($literalModel)) {
			throw new ArgumentException($havingLiteral, $literalModel->getObjectInstance(false)->getComhonClass(), 1);
		}
		$havingLiteral->validate();
		
		if ($havingLiteral->getModel()->getName() == 'Comhon\Logic\Having\Literal\Count') {
			$literal  = new HavingLiteral(HavingLiteral::COUNT, $table, null, $havingLiteral->getValue('operator'), $havingLiteral->getValue('value'));
		} else {
			if (is_null($model)) {
				throw new ComhonException('model can\'t be null if function is different than COUNT');
			}
			$property = $model->getProperty($havingLiteral->getValue('property'), true);
			if ($property->hasMultipleSerializationNames()) {
				throw new MultiplePropertyLiteralException($property);
			}
			if (!$allowPrivateProperties && $property->isPrivate()) {
				throw new PropertyVisibilityException($property);
			}
			$column = $property->getSerializationName();
			$literal  = new HavingLiteral($havingLiteral->getValue('function'), $table, $column, $havingLiteral->getValue('operator'), $havingLiteral->getValue('value'));
		}
		
		return $literal;
	}
	
}