<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Api;

use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Serialization\SqlTable;
use Comhon\Model\ModelInteger;
use Comhon\Model\Model;
use Comhon\Model\StringCastableModelInterface;
use Comhon\Exception\ComhonException;
use Comhon\Serialization\SerializationUnit;
use Comhon\Logic\Literal;
use Comhon\Logic\Clause;
use Comhon\Exception\HTTP\HTTPException;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Request\ComplexLoadRequest;
use Comhon\Request\SimpleLoadRequest;
use Comhon\Object\ComhonArray;
use Comhon\Request\LiteralBinder;
use Comhon\Object\UniqueObject;
use Comhon\Exception\Model\UndefinedPropertyException;
use Comhon\Exception\Model\NotDefinedModelException;

class RequestHandler {
	
	/**
	 *
	 * @var string
	 */
	const PROPERTIES = '__properties__';
	
	/**
	 *
	 * @var string
	 */
	const ORDER = '__order__';
	
	/**
	 *
	 * @var string
	 */
	const RANGE = '__range__';
	
	/**
	 *
	 * @var string
	 */
	const CLAUSE = '__clause__';
	
	public static function handle($basePath) {
		$handler = new self();
		return $handler->_handle($basePath, $_SERVER, $_GET, apache_request_headers());
	}
	
	/**
	 * 
	 * @param string $basePath
	 * @param string[] $server
	 * @param array $get
	 * @param string[] $headers
	 * @throws HTTPException
	 * @throws \Exception
	 * @return boolean|\Comhon\Api\Response
	 */
	protected function _handle($basePath, $server, $get, $headers) {
		$route = substr(preg_replace('~/+~', '/', $server['REQUEST_URI'].'/'), 0, -1);
		$basePath = preg_replace('~/+~', '/', '/'.$basePath.'/');
		
		if (strpos($route, '?') !== false) {
			$route = strstr($route, '?', true);
		}
		if (strpos($route, $basePath) === 0) {
			$route = substr($route, strlen($basePath));
		} elseif ($route === substr($basePath, 0, -1)) {
			// health check return 200
			return new Response();
		}else {
			// route not handled
			$response = new Response();
			$response->setCode(404);
			return $response;
		}
		$explodedRoute = explode('/', urldecode($route));
		$modelNameIndex = $server['REQUEST_METHOD'] === 'GET' && $explodedRoute[0] === 'count'? 1 : 0;
		
		if (!array_key_exists($modelNameIndex, $explodedRoute)) {
			$response = new Response();
			$response->setCode(404);
			$response->addHeader('Content-Type', 'text/plain');
			$response->setContent("invalid route");
			return $response;
		}
		if (isset($headers['namespace']) && !empty($headers['namespace'])) {
			$explodedRoute[$modelNameIndex] = $headers['namespace'] . '\\' . $explodedRoute[$modelNameIndex];
		}
		try {
			ModelManager::getInstance()->getInstanceModel($explodedRoute[$modelNameIndex]);
		} catch (NotDefinedModelException $e) {
			$response = new Response();
			$response->setCode(404);
			$response->addHeader('Content-Type', 'text/plain');
			$response->setContent("resource model '{$explodedRoute[$modelNameIndex]}' doesn't exist");
			return $response;
		}
		try {
			switch ($server['REQUEST_METHOD']) {
				case 'GET':
					if ($explodedRoute[0] === 'count') {
						$response = $this->_getCount($explodedRoute, $get);
					} else {
						$response = $this->_get($explodedRoute, $get);
					}
					break;
				case 'POST':
					$response = $this->_post($explodedRoute);
					break;
				case 'PUT':
					$response = $this->_put($explodedRoute);
					break;
				case 'DELETE':
					$response = $this->_delete($explodedRoute);
					break;
				case 'OPTIONS':
					$response = new Response();
					break;
				default:
					throw new HTTPException('method not handled', 501);
					break;
			}
		} catch (UndefinedPropertyException $e) {
			$response = new Response();
			$response->setCode(400);
			$response->addHeader('Content-Type', 'text/plain');
			$response->setContent($e->getMessage());
		} catch (HTTPException $e) {
			throw $e;
			trigger_error($e->getCode() . ': ' . $e->getMessage());
			http_response_code($e->getCode());
		} catch (\Exception $e) {
			throw $e;
			trigger_error($e->getCode() . ': ' . $e->getMessage());
			http_response_code(500);
		}
		
		return $response;
	}
	
	/**
	 *
	 * @param string $modelName
	 * @param array $get
	 * @param string[] $filterProperties
	 * @throws HTTPException
	 * @return \Comhon\Object\ComhonArray
	 */
	private function _getResources($modelName, &$get, $filterProperties = null) {
		$model = ModelManager::getInstance()->getInstanceModel($modelName);
		$requestModel = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Complex');
		$request = $requestModel->getObjectInstance(false);
		$tree = $request->initValue('tree', false);
		$tree->setId(1);
		$tree->setValue('model', $modelName);
		$request->setValue('properties', $filterProperties);
		
		// limit and offset
		if (isset($get[self::RANGE])) {
			if (!isset($get[self::ORDER])) { // TODO and model doesn't have default order
				throw new HTTPException(self::ORDER." is required with ".self::RANGE, 400);
			}
			$range = explode('-', $get[self::RANGE]);
			if (count($range) !== 2) {
				throw new HTTPException("malformed range '{$get[self::RANGE]}'", 400);
			}
			if (!ctype_digit($range[0]) || !ctype_digit($range[0])) {
				throw new HTTPException("malformed range '{$get[self::RANGE]}'", 400);
			}
			
			$range[0] = (integer) $range[0];
			$range[1] = (integer) $range[1];
			
			$request->setValue('offset', $range[0]);
			$request->setValue('limit', ($range[1] - $range[0]) + 1);
			
			unset($get[self::RANGE]);
		}
		
		// values order
		if (isset($get[self::ORDER])) {
			$order = $requestModel->getProperty('order')->getModel()->import(
				json_decode($get[self::ORDER], true),
				new AssocArrayInterfacer()
			);
			$request->setValue('order', $order);
			unset($get[self::ORDER]);
		}
		
		// modify $params object by adding filter to apply
		$this->_setParamsFilter($request, $get, $model);
		
		return ComplexLoadRequest::build($request)->execute();
	}
	
	/**
	 *
	 * @param string $modelName
	 * @param array $get
	 * @throws HTTPException
	 * @return int
	 */
	private function _getResourcesCount($modelName, &$get) {
		$model = ModelManager::getInstance()->getInstanceModel($modelName);
		$requestModel = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Complex');
		$request = $requestModel->getObjectInstance(false);
		$tree = $request->initValue('tree', false);
		$tree->setId(1);
		$tree->setValue('model', $modelName);
		
		// modify $params object by adding filter to apply
		$this->_setParamsFilter($request, $get, $model);
		
		return ComplexLoadRequest::build($request)->count();
	}
	
	/**
	 * modify $params object by adding filter to apply
	 *
	 * @param \Comhon\Object\UniqueObject $request
	 * @param array $get
	 * @param \Comhon\Model\Model $model
	 * @throws HTTPException
	 */
	private function _setParamsFilter(UniqueObject $request, &$get, Model $model) {
		$i = 0;
		$clauseType = Clause::CONJUNCTION;
		if (isset($get[self::CLAUSE])) {
			$clauseType = $get[self::CLAUSE];
			unset($get[self::CLAUSE]);
		}
		if ($clauseType == Clause::CONJUNCTION) {
			$clause = ModelManager::getInstance()->getInstanceModel('Comhon\Logic\Simple\Clause\Conjunction')->getObjectInstance(false);
		} elseif ($clauseType == Clause::DISJUNCTION) {
			$clause = ModelManager::getInstance()->getInstanceModel('Comhon\Logic\Simple\Clause\Disjunction')->getObjectInstance(false);
		} else {
			throw new HTTPException("Not supported clause type $clauseType", 400);
		}
		$clause->setId($i++);
		$simpleCollection = $request->initValue('simpleCollection');
		$elements = $clause->initValue('elements', false);
		
		foreach ($get as $propertyName => $value) {
			$isArrayFilter = is_array($value);
			$property = $model->getProperty($propertyName, true);
			$literal = LiteralBinder::getLiteralInstance($property, $isArrayFilter);
			$propertyModel = $property->getLiteralModel();
			if (is_null($literal)) {
				throw new HTTPException("Not supported property $propertyName", 400);
			}
			if ($propertyModel instanceof StringCastableModelInterface) {
				if ($isArrayFilter) {
					foreach ($value as &$element) {
						$element = $propertyModel->castValue($element);
					}
				} else {
					$value = $propertyModel->castValue($value);
				}
			}
			if ($isArrayFilter) {
				$literal->setValue('operator', Literal::IN);
				$values = $literal->initValue('values', false);
				foreach ($value as $element) {
					$values->pushValue($element);
				}
			} else {
				$literal->setValue('operator', Literal::EQUAL);
				$values = $literal->setValue('value', $value);
			}
			$literal->setId($i++);
			$literal->setValue('node', $request->getValue('tree'));
			$literal->setValue('property', $propertyName);
			$elements->pushValue($literal);
			$simpleCollection->pushValue($literal);
		}
		
		if (count($elements->getValues()) == 1) {
			$request->setValue('filter', $elements->getValue(0));
		} elseif (count($elements->getValues()) > 1) {
			$simpleCollection->pushValue($clause);
			$request->setValue('filter', $clause);
		}
	}
	
	private function _getResource($modelName, $id, ComhonArray $filterProperties) {
		$model = ModelManager::getInstance()->getInstanceModel($modelName);
		$idProperties = $model->getIdProperties();
		if (count($idProperties) !== 1) {
			throw new HTTPException('id property must be unique', 400);
		}
		if (current($idProperties)->getModel() instanceof ModelInteger) {
			if (!ctype_digit($id)) {
				throw new HTTPException('id must be an integer', 400);
			}
			$id = (integer) $id;
		}
		
		$request = SimpleLoadRequest::build($modelName, $id, $filterProperties->getValues());
		$object = $request->execute();
		
		if (is_null($object)) {
			throw new HTTPException("resource not found", 404);
		}
		
		return $object;
	}
	
	
	/**
	 * get properties to export
	 * 
	 * @param array $get
	 * @return \Comhon\Object\ComhonArray
	 */
	private function _getFilterProperties(&$get) {
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Request');
		$filterProperties = isset($get[self::PROPERTIES]) 
			? $model->getProperty('properties')->getModel()->import($get[self::PROPERTIES], new AssocArrayInterfacer())
			: $model->getProperty('properties')->getModel()->getObjectInstance();
		unset($get[self::PROPERTIES]);
		
		return $filterProperties;
	}
	
	/**
	 * 
	 * @param string[] $explodedRoute
	 * @param array $get
	 * @return \Comhon\Api\Response
	 */
	private function _get($explodedRoute, &$get) {
		$filterProperties = $this->_getFilterProperties($get);
		$modelName = $explodedRoute[0];
		
		$object = isset($explodedRoute[1])
			? $this->_getResource($modelName, $explodedRoute[1], $filterProperties)
			: $this->_getResources($modelName, $get, $filterProperties);

		$interfacer = new StdObjectInterfacer();
		$interfacer->setPropertiesFilter($filterProperties->getValues(), $object->getUniqueModel()->getName());
		
		if ($object instanceof ComhonArray) {
			$interfacedObject = $interfacer->export($object);
		} else {
			// export through original model to export potential inheritance key
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			$interfacedObject = $model->export($object, $interfacer);
		}
		$response = new Response();
		$response->setCode(200);
		$response->addHeader('Content-Type', 'application/json');
		$response->setContent($interfacer->toString($interfacedObject));
		
		return $response;
	}
	
	/**
	 * 
	 * @param string[] $explodedRoute
	 * @param array $get
	 * @throws HTTPException
	 * @return \Comhon\Api\Response
	 */
	private function _getCount($explodedRoute, &$get) {
		if (!isset($explodedRoute[1])) {
			throw new HTTPException('missing resource name', 400);
		}
		$response = new Response();
		$response->setCode(200);
		$response->addHeader('Content-Type', 'text/plain');
		$response->setContent($this->_getResourcesCount($explodedRoute[1], $get));
		
		return $response;
	}
	
	/**
	 * 
	 * @param string[] $explodedRoute
	 * @throws ComhonException
	 * @throws HTTPException
	 * @return \Comhon\Api\Response
	 */
	private function _post($explodedRoute) {
		$post = json_decode(file_get_contents('php://input'), true);
		
		$model  = ModelManager::getInstance()->getInstanceModel($explodedRoute[0]);
		$interfacer = new AssocArrayInterfacer();
		$interfacer->setFlagObjectAsLoaded(false);
		
		/**
		 * @var \Comhon\Object\ComhonObject $object
		 */
		$object = $interfacer->import($post, $model);
		
		if ($object->hasCompleteId()) {
			$serialization = $model->getSqlTableUnit();
			if ($serialization instanceof SqlTable) {
				if ($serialization->hasIncrementalId($model)) {
					throw new ComhonException('id must be empty to create resource '.$model->getName());
				}
			}
			if (!is_null($model->loadObject($object->getId(), array_keys($model->getIdProperties())))) {
				throw new ComhonException('resource already exists');
			}
		}
		if ($object->save(SerializationUnit::CREATE) === 0) {
			throw new HTTPException('malformed request', 400);
		}
		$model->loadAndFillObject($object, null, true);
		
		$response = new Response();
		$response->setCode(201);
		$response->addHeader('Content-Type', 'application/json');
		$response->setContent($interfacer->toString($interfacer->export($object)));
		
		return $response;
	}
	
	/**
	 * 
	 * @param string[] $explodedRoute
	 * @throws HTTPException
	 * @return \Comhon\Api\Response
	 */
	private function _put($explodedRoute) {
		if (!isset($explodedRoute[0]) || !isset($explodedRoute[1])) {
			throw new HTTPException('malformed route', 400);
		}
		$id = $explodedRoute[1];
		
		$post = json_decode(file_get_contents('php://input'), true);
		
		$model  = ModelManager::getInstance()->getInstanceModel($explodedRoute[0]);
		$interfacer = new AssocArrayInterfacer();
		$interfacer->setFlagObjectAsLoaded(false);
		
		$idProperties = $model->getIdProperties();
		if (count($idProperties) == 0) {
			throw new HTTPException('model without id cannot be deleted', 400);
		}
		$idPropertiesNames = array_keys($idProperties);
		if (count($idProperties) == 1) {
			$modelId = $idProperties[$idPropertiesNames[0]]->getModel();
			if ($modelId instanceof StringCastableModelInterface) {
				$id = $modelId->castValue($id);
			}
		}
		
		/**
		 * @var \Comhon\Object\ComhonObject $object
		 */
		try {
			$object = $interfacer->import($post, $model);
		} catch (ImportException $e) {
			throw new HTTPException($e->getMessage(), 400);
		}
		
		if ($object->hasEmptyId()) {
			$object->setId($id);
		}elseif ($object->getId() !== $id) {
			throw new HTTPException('malformed request, ids not compatible', 400);
		}
		if (!$object->hasCompleteId()) {
			throw new HTTPException('malformed request', 400);
		}
		if (is_null($model->loadObject($object->getId(), array_keys($model->getIdProperties())))) {
			throw new HTTPException('not found', 404);
		}
		$object->save(SerializationUnit::UPDATE);
		$model->loadAndFillObject($object, null, true);
		
		$response = new Response();
		$response->setCode(200);
		$response->addHeader('Content-Type', 'application/json');
		$response->setContent($interfacer->toString($interfacer->export($object)));
		
		return $response;
	}
	
	/**
	 * 
	 * @param string[] $explodedRoute
	 * @throws HTTPException
	 * @return \Comhon\Api\Response
	 */
	private function _delete($explodedRoute) {
		if (!isset($explodedRoute[0]) || !isset($explodedRoute[1])) {
			throw new HTTPException('malformed route', 400);
		}
		$modelName = $explodedRoute[0];
		$id = $explodedRoute[1];
		
		$model  = ModelManager::getInstance()->getInstanceModel($modelName);
		
		$idProperties = $model->getIdProperties();
		if (count($idProperties) == 0) {
			throw new HTTPException('model without id cannot be deleted', 400);
		}
		$idPropertiesNames = array_keys($idProperties);
		if (count($idProperties) == 1) {
			$modelId = $idProperties[$idPropertiesNames[0]]->getModel();
			if ($modelId instanceof StringCastableModelInterface) {
				$id = $modelId->castValue($id);
			}
		}
		$object = $model->loadObject($id, $idPropertiesNames);
		if (is_null($object)) {
			throw new HTTPException("{$model->getName()} with id '{$id}' not found", 404);
		}
		$object->_delete();
		
		$response = new Response();
		$response->setCode(204);
		
		return $response;
	}
}


