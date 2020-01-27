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
use Comhon\Model\Singleton\ModelManager;

class ManifestFile extends AbstractManifestFile {
	
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
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\File\AbstractManifestFile::_getModelName()
	 */
	protected function _getModelName() {
		return 'Comhon\Manifest\File';
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\File\AbstractManifestFile::_getPath()
	 */
	protected function _getPath(UniqueObject $object) {
		list($fullyQualifiedNamePrefix, $fullyQualifiedNameSuffix) = ModelManager::getInstance()->splitModelName($object->getId());
		
		if (is_null($this->format)) {
			return ModelManager::getInstance()->getManifestPath($fullyQualifiedNamePrefix, $fullyQualifiedNameSuffix);
		} else {
			$path_afe = ModelManager::getInstance()->getManifestPath($fullyQualifiedNamePrefix, $fullyQualifiedNameSuffix);
			return dirname($path_afe).DIRECTORY_SEPARATOR.'manifest.'.$this->format;
		}
	}

}