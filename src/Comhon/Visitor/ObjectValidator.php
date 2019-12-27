<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Visitor;

use Comhon\Object\Collection\ObjectCollectionInterfacer;
use Comhon\Exception\ComhonException;
use Comhon\Exception\Visitor\VisitException;
use Comhon\Exception\Interfacer\NotReferencedValueException;
use Comhon\Object\UniqueObject;
use Comhon\Exception\Value\MissingIdForeignValueException;

/**
 * verify if all objects are loaded (check recursively all contained objects)
 */
class ObjectValidator extends Visitor {
	
	/**
	 *
	 * @var string
	 */
	const VERIF_REFERENCES = 'verif_references';
	
	/**
	 *
	 * @var string
	 */
	const VERIF_FOREIGN_ID = 'verif_foreign_id';
	
	/**
	 *
	 * @var boolean
	 */
	private $verifRef = false;
	
	/**
	 *
	 * @var boolean
	 */
	private $verifForeignId = false;
	
	/**
	 *
	 * @var \Comhon\Object\Collection\ObjectCollectionInterfacer
	 */
	private $collection;
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Visitor\Visitor::_getMandatoryParameters()
	 */
	protected function _getMandatoryParameters() {
		return [];
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Visitor\Visitor::_init()
	 */
	protected function _init($object) {
		$this->verifRef = isset($this->params[self::VERIF_REFERENCES]) ? $this->params[self::VERIF_REFERENCES] : false;
		$this->verifForeignId = isset($this->params[self::VERIF_FOREIGN_ID]) ? $this->params[self::VERIF_FOREIGN_ID] : false;
		$object->validate();
		if ($this->verifRef) {
			$this->collection = new ObjectCollectionInterfacer();
			$this->collection->addObject($object, false);
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Visitor\Visitor::_visit()
	 */
	protected function _visit($parentObject, $key, $propertyNameStack, $isForeign) {
		$object = $parentObject->getValue($key);
		if ($object instanceof UniqueObject) {
			if ($this->verifRef) {
				$this->collection->addObject($object, $isForeign);
			}
			if (!$isForeign) {
				$object->validate();
			} elseif ($this->verifForeignId && !$object->hasCompleteId()) {
				throw new MissingIdForeignValueException();
			}
		}
		return true;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Visitor\Visitor::_postVisit()
	 */
	protected function _postVisit($parentObject, $key, $propertyNameStack, $isForeign) {}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Visitor\Visitor::_finalize()
	 */
	protected function _finalize($object) {
		if ($this->verifRef) {
			$objects = $this->collection->getNotReferencedObjects();
			if (!empty($objects)) {
				$objectFinder = new ObjectFinder();
				foreach ($objects as $obj) {
					$statck = $objectFinder->execute(
						$object,
						[
							ObjectFinder::ID => $obj->getId(),
							ObjectFinder::MODEL => $obj->getModel(),
							ObjectFinder::SEARCH_FOREIGN => true
						]
					);
					if (is_null($statck)) {
						throw new ComhonException('value should not be null');
					}
					throw new VisitException(new NotReferencedValueException($obj), $statck);
				}
			}
		}
	}
}