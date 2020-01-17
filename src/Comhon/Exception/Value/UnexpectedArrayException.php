<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Value;

use Comhon\Exception\ConstantException;
use Comhon\Model\ModelArray;
use Comhon\Object\ComhonArray;
use Comhon\Model\Model;

class UnexpectedArrayException extends UnexpectedValueTypeException {
	
	/** @var \Comhon\Model\ModelArray */
	private $modelArray;
	
	/** @var integer */
	private $depth;
	
	/**
	 * 
	 * @param \Comhon\Object\ComhonArray $objectArray
	 * @param \Comhon\Model\ModelArray $modelArray
	 * @param integer $depth
	 */
	public function __construct(ComhonArray $objectArray, ModelArray $modelArray, $depth) {
		$this->modelArray = $modelArray;
		$this->depth = $depth;
		$objectModel = $objectArray->getModel();
		for ($i = $depth; $i > 0; $i--) {
			$objectModel = $objectModel->getModel();
		}
		
		if (!($objectModel instanceof ModelArray)) {
			$this->message = "model must be a ".ModelArray::class.", model '{$objectModel->getName()}' given. ";
		} elseif ($modelArray->isAssociative() !== $objectModel->isAssociative()) {
			$part = $modelArray->isAssociative() ? 'must be' : 'must not be';
			$this->message = "ModelArray $part associative. ";
		} elseif ($modelArray->getElementName() !== $objectModel->getElementName()) {
			$this->message = "ModelArray element name must be '{$modelArray->getElementName()}', '{$objectModel->getElementName()}' given. ";
		} elseif ($modelArray->isNotNullElement() !== $objectModel->isNotNullElement()) {
			$part = $modelArray->isNotNullElement() ? 'must have' : 'must not have';
			$this->message = "ModelArray $part not null element. ";
		} elseif ($modelArray->isIsolatedElement() !== $objectModel->isIsolatedElement()) {
			$part = $modelArray->isNotNullElement() ? 'must be' : 'must not be';
			$this->message = "ModelArray $part isolated element. ";
		} elseif (!($objectModel->getModel() instanceof Model) || !$objectModel->getModel()->isInheritedFrom($modelArray->getModel())) {
			$trustModelName = $modelArray->getModel()->getName();
			$objectModelName = $objectModel->getModel() instanceof ModelArray 
				? ModelArray::class : "'".$objectModel->getModel()->getName()."'";
			$this->message = "model must be a '$trustModelName', model $objectModelName given. ";
		} else {
			$expectedRestriction = '';
			foreach ($modelArray->getArrayRestrictions() as $restriction) {
				$expectedRestriction .= ' - ' . $restriction->toString() . '(on comhon array)' . PHP_EOL;
			}
			foreach ($modelArray->getElementRestrictions() as $restriction) {
				$expectedRestriction .= ' - ' . $restriction->toString() . '(on elements)' . PHP_EOL;
			}
			$actualRestriction = '';
			if ($objectArray->getModel() instanceof modelArray ) {
				foreach ($objectArray->getModel()->getArrayRestrictions() as $restriction) {
					$actualRestriction .= ' - ' . $restriction->toString() . '(on comhon array)' . PHP_EOL;
				}
				foreach ($objectArray->getModel()->getElementRestrictions() as $restriction) {
					$actualRestriction .= ' - ' . $restriction->toString() . '(on elements)' . PHP_EOL;
				}
			}
			$class = get_class($objectArray);
			
			$this->message = "value $class must have restrictions :". PHP_EOL 
				. $expectedRestriction
				. "restrictions given : " . PHP_EOL 
				. $actualRestriction;
		}
		if ($depth > 0) {
			$this->message .= 'array depth : '.$this->depth;
		}
		$this->code = ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION;
	}
	
	/**
	 * 
	 * @return \Comhon\Model\ModelArray
	 */
	public function getModelArray() {
		return $this->modelArray;
	}
	
	/**
	 * 
	 * @return integer
	 */
	public function getDepth() {
		return $this->depth;
	}
	
}