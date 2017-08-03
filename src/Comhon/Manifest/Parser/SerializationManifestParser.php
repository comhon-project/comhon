<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Manifest\Parser;

use Comhon\Model\Model;
use Comhon\Model\MainModel;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Interfacer\Interfacer;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Exception\ManifestException;
use Comhon\Exception\ComhonException;

abstract class SerializationManifestParser {
	
	/** @var mixed */
	protected $manifest;
	
	/** @var \Comhon\Model\MainModel */
	protected $model;
	
	/** @var \Comhon\Interfacer\Interfacer */
	protected $interfacer;

	/**
	 * get serialization informations of property
	 * 
	 * @param string $propertyName
	 */
	abstract public function getPropertySerializationInfos($propertyName);
	
	/**
	 * get serialization settings
	 * 
	 * @return \Comhon\Object\ObjectUnique
	 */
	abstract protected function _getSerializationSettings();
	
	/**
	 * get inheritance key
	 * 
	 * @return string
	 */
	abstract public function getInheritanceKey();
	
	/**
	 * @param \Comhon\Model\MainModel $model
	 * @param mixed $manifest
	 */
	final public function __construct(MainModel $model, $manifest) {
		$this->interfacer = $this->_getInterfacer($manifest);
		$this->model      = $model;
		$this->manifest   = $manifest;
		
		$this->interfacer->setSerialContext(true);
		$this->interfacer->setPrivateContext(true);
	}
	
	/**
	 * get serialization manifest parser instance
	 * 
	 * @param \Comhon\Model\MainModel $model
	 * @param string $serializationManifestPath_afe
	 * @throws \Exception
	 * @return \Comhon\Manifest\Parser\SerializationManifestParser
	 */
	public static function getInstance(MainModel $model, $serializationManifestPath_afe) {
		switch (mb_strtolower(pathinfo($serializationManifestPath_afe, PATHINFO_EXTENSION))) {
			case 'xml':
				$interfacer = new XMLInterfacer();
				break;
			case 'json':
				$interfacer = new AssocArrayInterfacer();
				break;
			default:
				throw new ManifestException('extension not recognized for manifest file : '.$serializationManifestPath_afe);
		}
		return self::_getInstanceWithInterfacer($model, $serializationManifestPath_afe, $interfacer);
		
	}
	
	/**
	 * get serialization settings
	 * 
	 * @param \Comhon\Model\MainModel $model
	 * @throws \Exception
	 * @return \Comhon\Object\ObjectUnique
	 */
	final public function getSerializationSettings(MainModel $model) {
		if ($this->model !== $model) {
			throw new ComhonException('not same models');
		}
		return $this->model->hasLoadedSerialization()
			? $this->model->getSerialization()->getSettings() : $this->_getSerializationSettings();
	}
	
	/**
	 * get interfacer able to interpret manifest
	 * 
	 * @param mixed $manifest
	 * @return \Comhon\Interfacer\Interfacer
	 */
	public function _getInterfacer($manifest) {
		if (is_array($manifest)) {
			return new AssocArrayInterfacer();
		}
		if ($manifest instanceof \stdClass) {
			return new StdObjectInterfacer();
		}
		if ($manifest instanceof \DOMElement) {
			return new XMLInterfacer();
		}
		throw new ManifestException('not recognized manifest format');
	}
	
	/**
	 * get manifest parser instance
	 *
	 * @param \Comhon\Model\MainModel $model
	 * @param string $serializationManifestPath_afe
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 * @return SerializationManifestParser
	 */
	private static function _getInstanceWithInterfacer(MainModel $model, $serializationManifestPath_afe, Interfacer $interfacer) {
		$manifest = $interfacer->read($serializationManifestPath_afe);
		
		if ($manifest === false || is_null($manifest)) {
			throw new ManifestException("serialization manifest file not found or malformed '$serializationManifestPath_afe'");
		}
		
		if (!$interfacer->hasValue($manifest, 'version')) {
			throw new ManifestException("serialization manifest '$serializationManifestPath_afe' doesn't have version");
		}
		$version = (string) $interfacer->getValue($manifest, 'version');
		switch ($version) {
			case '2.0': return new V_2_0\SerializationManifestParser($model, $manifest);
			default:    throw new ManifestException("version $version not recognized for manifest $serializationManifestPath_afe");
		}
	}
}