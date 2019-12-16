<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Model;

use Comhon\Interfacer\Interfacer;
use Comhon\Model\Restriction\Restriction;
use Comhon\Exception\Value\NotSatisfiedRestrictionException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\Value\UnexpectedRestrictedArrayException;
use Comhon\Object\Collection\ObjectCollectionInterfacer;
use Comhon\Model\Restriction\NotNull;

class ModelRestrictedArray extends ModelArray {
	
	/** @var \Comhon\Model\Restriction\Restriction[] */
	private $restrictions = [];
	
	/**
	 * 
	 * @param \Comhon\Model\ModelUnique $model
	 * @param \Comhon\Model\Restriction\Restriction[] $restrictions
	 * @param boolean $isAssociative
	 * @param string $elementName
	 * @throws \Exception
	 */
	public function __construct(ModelUnique $model, array $restrictions, $isAssociative, $elementName) {
		parent::__construct($model, $isAssociative, $elementName);
		
		foreach ($restrictions as $restriction) {
			if (!$restriction->isAllowedModel($this->model)) {
				throw new ComhonException('restriction doesn\'t allow specified model'.get_class($this->model));
			}
			$this->restrictions[get_class($restriction)] = $restriction;
		}
	}
	
	/**
	 * get restrictions
	 * 
	 * @return \Comhon\Model\Restriction\Restriction[]
	 */
	public function getRestrictions() {
		return $this->restrictions;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelArray::_import()
	 */
	protected function _import($interfacedObject, Interfacer $interfacer, $isFirstLevel, ObjectCollectionInterfacer $objectCollectionInterfacer) {
		$objectArray = parent::_import($interfacedObject, $interfacer, $isFirstLevel, $objectCollectionInterfacer);
		if (!is_null($objectArray)) {
			foreach ($objectArray->getValues() as $value) {
				foreach ($this->restrictions as $restriction) {
					if (!$restriction->satisfy($value)) {
						throw new NotSatisfiedRestrictionException($value, $restriction);
					}
				}
			}
		}
		return $objectArray;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelArray::verifValue()
	 */
	public function verifValue($value) {
		parent::verifValue($value);
		if ($value->getModel() !== $this) {
			if (!($value->getModel() instanceof ModelRestrictedArray)) {
				throw new UnexpectedRestrictedArrayException($value, $this);
			}
			if (!Restriction::compare($this->restrictions, $value->getModel()->getRestrictions())) {
				throw new UnexpectedRestrictedArrayException($value, $this);
			}
		}
		return true;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelArray::verifElementValue()
	 */
	public function verifElementValue($value) {
		parent::verifElementValue($value);
		if (is_null($value)) {
			if (isset($this->restrictions[NotNull::class])) {
				throw new NotSatisfiedRestrictionException($value, $this->restrictions[NotNull::class]);
			}
		} elseif (!is_null($restriction = Restriction::getFirstNotSatisifed($this->restrictions, $value))) {
			throw new NotSatisfiedRestrictionException($value, $restriction);
		}
		return true;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelContainer::isEqual()
	 */
	public function isEqual(AbstractModel $model) {
		return parent::isEqual($model) && 
			($model instanceof ModelRestrictedArray) &&
			Restriction::compare($this->restrictions, $model->getRestrictions());
	}
	
}