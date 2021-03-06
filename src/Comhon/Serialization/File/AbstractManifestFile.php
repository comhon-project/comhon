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

use Comhon\Object\UniqueObject;
use Comhon\Exception\Serialization\SerializationException;
use Comhon\Interfacer\Interfacer;
use Comhon\Object\Config\Config;
use Comhon\Serialization\SerializationFile;
use Comhon\Exception\ArgumentException;
use Comhon\Exception\ComhonException;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\Serialization\ManifestSerializationException;
use Comhon\Exception\Model\NotDefinedModelException;

abstract class AbstractManifestFile extends SerializationFile {
	
	/**
	 * @var \Comhon\Serialization\File\JsonFile
	 */
	private static $instance;
	
	protected $format;
	
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
	 * 
	 * @param string $format must belong to enumeration [xml, json]
	 * @param boolean $pretty if true, manifest is pretty printed
	 * @throws ArgumentException
	 */
	public function __construct($format = null, $pretty = true) {
		if (!is_null($format)) {
			$this->interfacer = Interfacer::getInstance($format, true);
			$this->format = $format;
			$this->interfacer->setSerialContext(true);
			$this->interfacer->setPrivateContext(true);
			$this->interfacer->setFlagValuesAsUpdated(false);
			$this->interfacer->setMergeType(Interfacer::OVERWRITE);
		}
		$this->pretty = $pretty;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\ValidatedSerializationUnit::getModelName()
	 */
	public static function getModelName() {
		return null;
	}
	
	/**
	 * 
	 * @return \Comhon\Interfacer\AssocArrayInterfacer|\Comhon\Interfacer\XMLInterfacer
	 */
	protected static function _initInterfacer() {
		$interfacer = Interfacer::getInstance(Config::getInstance()->getManifestFormat(), true);
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
		throw new ComhonException('must be overrided');
	}
	
	/**
	 * get model name that objects must respect.
	 * object saved/loaded/deleted must be returned model name.
	 */
	abstract protected function _getModelName();
	
	/**
	 * 
	 * @param UniqueObject $object
	 * @throws ManifestSerializationException
	 */
	private function _verifyNamespacePrefix(UniqueObject $object) {
		try {
			list($fullyQualifiedNamePrefix) = ModelManager::getInstance()->splitModelName($object->getId());
			if ($fullyQualifiedNamePrefix == 'Comhon') {
				throw new ManifestSerializationException(
					'manifest with \'Comhon\' prefix cannot be serialized or deleted',
					403
				);
			}
		} catch (NotDefinedModelException $e) {
			throw new ManifestSerializationException(
				"manifest prefix not defined in config file autoload for model '{$object->getId()}'",
				403
			);
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationFile::_saveObject()
	 */
	protected function _saveObject(UniqueObject $object, $operation = null) {
		if (!$object->isA($this->_getModelName())) {
			throw new SerializationException("object model must be a {$this->_getModelName()}', {$object->getModel()->getName()} given");
		}
		if ($object->getValue('version') !== '3.0') {
			throw new ManifestSerializationException("only manifest with version 3.0 may be saved");
		}
		if ($object->issetValue('extends')) {
			foreach ($object->getValue('extends') as $extends) {
				try {
					$modelName = $extends[0] == '\\' ? substr($extends, 1) : $object->getId(). '\\' . $extends;
					ModelManager::getInstance()->getInstanceModel($modelName);
				} catch (NotDefinedModelException $e) {
					throw new ManifestSerializationException("invalid extends value '$extends'. model '$modelName' is not defined");
				}
			}
		}
		$this->_verifyNamespacePrefix($object);
		return parent::_saveObject($object, $operation);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationFile::_loadObject()
	 */
	protected function _loadObject(UniqueObject $object, $propertiesFilter = null) {
		if (!$object->isA($this->_getModelName())) {
			throw new SerializationException("object model must be a '{$this->_getModelName()}', {$object->getModel()->getName()} given");
		}
		try {
			return parent::_loadObject($object, $propertiesFilter);
		} catch (NotDefinedModelException $e) {
			return false;
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationFile::_deleteObject()
	 */
	protected function _deleteObject(UniqueObject $object) {
		if (!$object->isA($this->_getModelName())) {
			throw new SerializationException("object model must be a '{$this->_getModelName()}', {$object->getModel()->getName()} given");
		}
		$this->_verifyNamespacePrefix($object);
		return parent::_deleteObject($object);
	}
	
}