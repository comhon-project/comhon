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

abstract class SerializationManifestParser {
	
	protected $manifest;
	protected $model;
	protected $interfacer;

	public abstract function getPropertySerializationInfos($propertyName);
	
	protected abstract function _getSerializationSettings();
	public abstract function getInheritanceKey();
	
	/**
	 * @param Model $model
	 * @param string $manifest
	 */
	public final function __construct(MainModel $model, $manifest) {
		$this->interfacer = $this->_getInterfacer($manifest);
		$this->model      = $model;
		$this->manifest   = $manifest;
		
		$this->interfacer->setSerialContext(true);
		$this->interfacer->setPrivateContext(true);
	}
	
	/**
	 * 
	 * @param Model $model
	 * @param string $serializationManifestPath_afe
	 * @throws \Exception
	 * @return SerializationManifestParser
	 */
	public static function getInstance(Model $model, $serializationManifestPath_afe) {
		switch (mb_strtolower(pathinfo($serializationManifestPath_afe, PATHINFO_EXTENSION))) {
			case 'xml':
				$interfacer = new XMLInterfacer();
				break;
			case 'json':
				$interfacer = new AssocArrayInterfacer();
				break;
			default:
				throw new \Exception('extension not recognized for manifest file : '.$serializationManifestPath_afe);
		}
		return self::getVersionnedInstance($model, $serializationManifestPath_afe, $interfacer);
		
	}
	
	public final function getSerializationSettings(MainModel $model) {
		if ($this->model !== $model) {
			throw new \Exception('not same models');
		}
		return $this->model->hasLoadedSerialization()
			? $this->model->getSerialization()->getSettings() : $this->_getSerializationSettings();
	}
	
	/**
	 * get interfacer able to interpret manifest
	 * @param [] $manifest
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
		throw new \Exception('not recognized manifest format');
	}
	
	/**
	 *
	 * @param Model $model
	 * @param string $serializationManifestPath_afe
	 * @param Interfacer $interfacer
	 * @throws \Exception
	 * @return ManifestParser
	 */
	public static function getVersionnedInstance($model, $serializationManifestPath_afe, Interfacer $interfacer) {
		$manifest = $interfacer->read($serializationManifestPath_afe);
		
		if ($manifest === false || is_null($manifest)) {
			throw new \Exception("serialization manifest file not found or malformed '$serializationManifestPath_afe'");
		}
		
		if (!$interfacer->hasValue($manifest, 'version')) {
			throw new \Exception("serialization manifest '$serializationManifestPath_afe' doesn't have version");
		}
		$version = (string) $interfacer->getValue($manifest, 'version');
		switch ($version) {
			case '2.0': return new V_2_0\SerializationManifestParser($model, $manifest);
			default:    throw new \Exception("version $version not recognized for manifest $serializationManifestPath_afe");
		}
	}
}