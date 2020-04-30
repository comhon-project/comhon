<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Utils\Project;

use Comhon\Object\Config\Config;
use Comhon\Utils\Model as ModelUtils;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Utils\InteractiveScript;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\StdObjectInterfacer;

abstract class InteractiveProjectScript extends InteractiveScript {
	
	/**
	 * in interactive mode, ask to user if script execution must continue on not for an invalid model.
	 * if user answer no, an exception is thrown with provided error message.
	 * 
	 * @param string $message
	 * @param string $modelName
	 * @param string $propertyName
	 */
	protected function displayContinueInvalidModel($message, $modelName, $propertyName = null) {
		$msgModel = "model '$modelName'";
		$msgPropertyOrModel = (is_null($propertyName) ? '' : "property '$propertyName' on ").$msgModel;
		
		$error = "Something goes wrong with {$msgPropertyOrModel} :".PHP_EOL.$message;
		$stopOrContinue = "You can stop or continue without $msgModel";
		$continue = $msgModel." is ignored";
		
		$this->displayContinue($error, $stopOrContinue, $continue);
	}
	
	/**
	 * in interactive mode, notice user that a model is currently processed
	 * 
	 * @param string $modelName
	 * @param string $part
	 */
	protected function displayProcessingModel($modelName, $part = null) {
		if (empty($part)) {
			$this->displayMessage("\033[0;93mProcessing model \033[1;33m'{$modelName}'\033[0m");
		} else {
			$this->displayMessage("\033[0;93mProcessing \033[1;33m{$part}\033[0;93m of model \033[1;33m'{$modelName}'\033[0m");
		}
	}
	
	/**
	 * get all validated project model names.
	 * if interactive mode is activated : on each invalid model, user will choose if he want to continue or stop script execution.
	 * if interactive mode is desactivated : an exception is thrown with error message
	 * 
	 * @param string $ContinueFilterModelName if provided, the continue will be displayed only on this model
	 *                                        even if there are errors on others models
	 * @param boolean $recursive if provided and $ContinueFilterModelName set to true, 
	 *                           the continue will be displayed on model and all models with same namespace
	 */
	protected function getValidatedProjectModelNames($ContinueFilterModelName = null, $recursive = false) {
		$notValid = [];
		$projectModelNames = ModelUtils::getValidatedProjectModelNames(null, true, $notValid);
		
		if (!is_null($ContinueFilterModelName) && !$recursive) {
			if (array_key_exists($ContinueFilterModelName, $notValid)) {
				$this->displayContinueInvalidModel($notValid[$ContinueFilterModelName], $ContinueFilterModelName);
			}
		} else {
			foreach ($notValid as $modelName => $message) {
				if (is_null($ContinueFilterModelName) || strpos($modelName, $ContinueFilterModelName) === 0) {
					$this->displayContinueInvalidModel($message, $modelName);
				}
			}
		}
		
		return $projectModelNames;
	}
	
	/**
	 * return array of filtered model names. model names are stored in array keys.
	 * if $recursive is false, returned array will contain only $filterModelName
	 * otherwise it will contain $filterModelName and all model with same namespace
	 * 
	 * @param string[] $projectModelNames
	 * @param string $filterModelName
	 * @param boolean $recursive
	 * @return NULL[]
	 */
	protected function getFilterModelNames($projectModelNames, $filterModelName, $recursive) {
		if (in_array($filterModelName, $projectModelNames)) {
			$filterModelNames = [$filterModelName => null];
		} else {
			$filterModelNames = [];
		}
		if ($recursive) {
			foreach ($projectModelNames as $projectModelName) {
				if (strpos($projectModelName, $filterModelName) === 0) {
					$filterModelNames[$projectModelName] = null;
				}
			}
		}
		
		return $filterModelNames;
	}
	
	/**
	 * get interfacer according manifest format
	 *
	 * @throws \Exception
	 * @return \Comhon\Interfacer\Interfacer
	 */
	protected function getInterfacer() {
		switch (Config::getInstance()->getManifestFormat()) {
			case 'xml':
				return new XMLInterfacer();
				break;
			case 'json':
				return new StdObjectInterfacer();
				break;
			default:
				throw new \Exception('manifest format not managed : '.Config::getInstance()->getManifestFormat());
		}
	}
    
}
