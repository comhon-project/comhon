<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Serialization\File;

use Comhon\Utils\Utils;
use Comhon\Object\UniqueObject;
use Comhon\Exception\Serialization\SerializationException;
use Comhon\Exception\ArgumentException;
use Comhon\Model\Model;
use Comhon\Interfacer\Interfacer;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\Config\Config;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Serialization\SerializationFile;

class ManifestFile extends SerializationFile {
	
	/**
	 * @var \Comhon\Serialization\File\JsonFile
	 */
	private static $instance;
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::getInstance()
	 *
	 * @return \Comhon\Serialization\File\JsonFile
	 */
	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * get serialization unit type
	 *
	 * @return string|null
	 */
	public static function getType() {
		return null;
	}
	
	/**
	 * 
	 * @return \Comhon\Interfacer\AssocArrayInterfacer|\Comhon\Interfacer\XMLInterfacer
	 */
	protected static function _initInterfacer() {
		$interfacer = Config::getInstance()->getManifestFormat() == 'json' 
			? new AssocArrayInterfacer() : new XMLInterfacer();
		$interfacer->setSerialContext(true);
		$interfacer->setPrivateContext(true);
		$interfacer->setFlagValuesAsUpdated(false);
		$interfacer->setMergeType(Interfacer::OVERWRITE);
		return $interfacer;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationFile::_getPath()
	 */
	protected function _getPath(UniqueObject $object) {
		list($fullyQualifiedNamePrefix, $fullyQualifiedNameSuffix) = ModelManager::getInstance()->splitModelName($object->getId());
		return ModelManager::getInstance()->getManifestPath($fullyQualifiedNamePrefix, $fullyQualifiedNameSuffix);
	}

	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::_saveObject()
	 */
	protected function _saveObject(UniqueObject $object, $operation = null) {
		if ($object->getModel()->getName() !== 'Comhon\Manifest\File') {
			throw new SerializationException("object model must be 'Comhon\Manifest\File', {$object->getModel()->getName()} given");
		}
		return parent::_saveObject($object, $operation);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::_loadObject()
	 */
	protected function _loadObject(UniqueObject $object, $propertiesFilter = null) {
		if ($object->getModel()->getName() !== 'Comhon\Manifest\File') {
			throw new SerializationException("object model must be 'Comhon\Manifest\File', {$object->getModel()->getName()} given");
		}
		return parent::_loadObject($object, $propertiesFilter);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::_deleteObject()
	 */
	protected function _deleteObject(UniqueObject $object) {
		if ($object->getModel()->getName() !== 'Comhon\Manifest\File') {
			throw new SerializationException("object model must be 'Comhon\Manifest\File', {$object->getModel()->getName()} given");
		}
		return parent::_deleteObject($object);
	}
	
}