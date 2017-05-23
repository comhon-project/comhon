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

	private $mFunction;

	const COUNT = 'COUNT';
	const SUM   = 'SUM';
	const AVG   = 'AVG';
	const MIN   = 'MIN';
	const MAX   = 'MAX';
	
	protected static $sAcceptedFunctions = [
			self::COUNT => null,
			self::SUM   => null,
			self::AVG   => null,
			self::MIN   => null,
			self::MAX   => null
	];
	
	public function __construct($pFunction, $pTable, $pColumn, $pOperator, $pValue) {
		$this->mFunction = $pFunction;
		parent::__construct($pTable, $pColumn, $pOperator, $pValue);
	}
	
	protected function _verifLiteral() {
		if (!array_key_exists($this->mOperator, self::$sAcceptedOperators)) {
			throw new \Exception('operator \''.$this->mOperator.'\' doesn\'t exists');
		}
		if (!array_key_exists($this->mFunction, self::$sAcceptedFunctions)) {
			throw new \Exception('function \''.$this->mFunction.'\' doesn\'t exists');
		}
		if (!is_int($this->mValue)) {
			throw new \Exception('having literal must have an integer value');
		}
	}
	
	/**
	 * @param array $pValues
	 * @return string
	 */
	public function export(&$pValues) {
		$lColumnTable = is_null($this->mColumn) ? '*'
			: ((($this->mTable instanceof TableNode) ? $this->mTable->getExportName() : $this->mTable) . '.' . $this->mColumn);
		return sprintf('%s(%s) %s %s', $this->mFunction, $lColumnTable, $this->mOperator, $this->mValue);
	}
	
	private static function _verifStdObject($pStdObject) {
		if (!is_object($pStdObject) || !isset($pStdObject->function) || !isset($pStdObject->operator) ||!isset($pStdObject->value)) {
			throw new \Exception('malformed stdObject literal : '.json_encode($pStdObject));
		}
	}
	
	/**
	 * @param stdClass $pStdObject
	 * @param string|TableNode $pTable not necessary if proeprty 'node' is specified in $pStdObject
	 * @param Model $pModel not necessary if function is COUNT
	 * @param boolean $pAllowPrivateProperties
	 * @throws \Exception
	 * @return Literal
	 */
	public static function stdObjectToHavingLiteral($pStdObject, $pTable = null, $pModel = null, $pAllowPrivateProperties = true) {
		self::_verifStdObject($pStdObject);
		
		if ($pStdObject->function == HavingLiteral::COUNT) {
			$lColumn = null;
		} else if (isset($pStdObject->property)) {
			if (is_null($pModel)) {
				throw new \Exception('model can\'t be null if function is different than COUNT');
			}
			$lProperty = $pModel->getProperty($pStdObject->property, true);
			if (!$pAllowPrivateProperties && $lProperty->isPrivate()) {
				throw new \Exception("having literal contain private property '{$lProperty->getName()}'");
			}
			$lColumn = $lProperty->getSerializationName();
		} else {
			throw new \Exception('malformed stdObject literal : '.json_encode($pStdObject));
		}
		
		if (isset($pStdObject->node)) {
			$lTable = $pStdObject->node;
		} else if (!is_null($pTable)) {
			$lTable = $pTable;
		} else {
			throw new \Exception('literal dosen\'t have property \'node\' and table is not specified in parameter : '.json_encode($pStdObject));
		}
		
		$lLiteral  = new HavingLiteral($pStdObject->function, $lTable, $lColumn, $pStdObject->operator, $pStdObject->value);
		return $lLiteral;
	}
	
}