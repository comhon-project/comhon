<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Manifest\Parser\V_3_0;

use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\Manifest\ManifestException;
use Comhon\Exception\Manifest\SerializationManifestIdException;
use Comhon\Model\ModelForeign;
use Comhon\Interfacer\Interfacer;

class SerializationManifestParser extends \Comhon\Manifest\Parser\V_2_0\SerializationManifestParser {
	
	/** @var string */
	const SERIALIZATION_NAME = 'serialization_name';
	
	/** @var string */
	const SERIALIZATION_NAMES = 'serialization_names';
	
	/** @var string */
	const INHERITANCE_KEY = 'inheritance_key';
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\V_2_0\SerializationManifestParser::_buildSerializationSettings()
	 */
	protected function _buildSerializationSettings($serializationNode) {
		if ($this->interfacer->hasValue($serializationNode, 'settings', true)) {
			$model = ModelManager::getInstance()->getInstanceModel('Comhon\Root');
			$serialization = $model->import(
				$this->interfacer->getValue($serializationNode, 'settings', true),
				$this->interfacer
			);
		} elseif ($this->interfacer->hasValue($serializationNode, 'foreign_settings', true)) {
			$model = ModelManager::getInstance()->getInstanceModel('Comhon\Root');
			$foreignModel = new ModelForeign($model);
			$serialization = $foreignModel->import(
				$this->interfacer->getValue($serializationNode, 'foreign_settings', true),
				$this->interfacer
			);
			$serialization->load();
			if (!$serialization->isLoaded()) {
				$node = $this->interfacer->getValue($serializationNode, 'foreign_settings', true);
				$id = $this->interfacer->getValue($node, Interfacer::COMPLEX_ID_KEY);
				$modelName = $this->interfacer->getValue($node, Interfacer::INHERITANCE_KEY);
				throw new SerializationManifestIdException($modelName, $id);
			}
		} elseif (!$this->interfacer->hasValue($serializationNode, static::UNIT_CLASS)) {
			throw new ManifestException('malformed serialization');
		} else {
			$serialization = null;
		}
		return $serialization;
	}
	
}