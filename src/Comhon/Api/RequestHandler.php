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
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Model\Model;
use Comhon\Model\StringCastableModelInterface;
use Comhon\Exception\ComhonException;
use Comhon\Serialization\SerializationUnit;
use Comhon\Logic\Literal;
use Comhon\Logic\Clause;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Request\ComplexRequester;
use Comhon\Request\SimpleRequester;
use Comhon\Object\ComhonArray;
use Comhon\Request\LiteralBinder;
use Comhon\Object\UniqueObject;
use Comhon\Exception\Model\NotDefinedModelException;
use Comhon\Exception\HTTP\ResponseException;
use Comhon\Exception\Literal\NotAllowedLiteralException;
use Comhon\Exception\HTTP\NotFoundException;
use Comhon\Exception\HTTP\MalformedRequestException;
use Comhon\Exception\Value\UnexpectedValueTypeException;
use Comhon\Exception\Value\NotSatisfiedRestrictionException;
use Comhon\Exception\Object\DependsValuesException;
use Comhon\Model\Restriction\Range;
use Comhon\Exception\Model\NoIdPropertyException;
use Comhon\Exception\Model\PropertyVisibilityException;
use Comhon\Interfacer\Interfacer;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Exception\HTTP\ConflictException;
use Comhon\Exception\HTTP\MethodNotAllowedException;
use Comhon\Exception\Value\InvalidCompositeIdException;
use Comhon\Exception\Serialization\ManifestSerializationException;
use Comhon\Object\Config\Config;

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
	
	/**
	 *
	 * @var string[]
	 */
	private $resource;
	
	/**
	 *
	 * @var \Comhon\Model\Model
	 */
	private $requestedModel;
	
	/**
	 * unique resource id (only if route has a unique resource id)
	 * 
	 * @var string
	 */
	private $uniqueResourceId;
	
	/**
	 *
	 * @var boolean
	 */
	private $isCountRequest = false;
	
	public static function handle($basePath) {
		$handler = new self();
		return $handler->_handle($basePath, $_SERVER, $_GET, apache_request_headers(), file_get_contents('php://input'));
	}
	
	/**
	 * 
	 * @param string $basePath
	 * @param string[] $server
	 * @param array $get
	 * @param string[] $headers
	 * @throws \Exception
	 * @return boolean|\Comhon\Api\Response
	 */
	protected function _handle($basePath, $server, $get, $headers, $body) {
		try {
			$this->_setRessource($basePath, $server, $headers);
			$response = $this->_handleMethod($server, $get, $headers, $body);
		} catch (ResponseException $e) {
			$response = $e->getResponse();
		} catch (ManifestSerializationException $e) {
			$response = $this->_buildResponse(403, ['code' => $e->getCode(), 'message' => $e->getMessage()]);
		}catch (\Exception $e) {
			$response = $this->_buildResponse(500);
		}
		
		return $response;
	}
	
	/**
	 * set route array according request route.
	 * check if route is valid and may be handled.
	 * add namespace to model if header namespace is specified.
	 * 
	 * @param string $basePath
	 * @param string[] $server
	 * @param string[] $headers
	 */
	private function _setRessource($basePath, $server, $headers) {
		$route = substr(preg_replace('~/+~', '/', $server['REQUEST_URI'].'/'), 0, -1);
		$basePath = preg_replace('~/+~', '/', '/'.$basePath.'/');
		
		if (strpos($route, '?') !== false) {
			$route = strstr($route, '?', true);
		}
		if (strpos($route, $basePath) === 0) {
			$route = substr($route, strlen($basePath));
		} elseif ($route === substr($basePath, 0, -1)) {
			// health check send response code 200
			throw new ResponseException(200);
		} else {
			throw new ResponseException(404, 'not handled route');
		}
		$this->resource = explode('/', urldecode($route));
		$method = $server['REQUEST_METHOD'];
		if ($this->resource[0] === 'count' && ($method === 'GET' || $method === 'HEAD' || $method === 'OPTIONS')) {
			array_shift($this->resource);
			$this->isCountRequest = true;
		}
		if (!array_key_exists(0, $this->resource)|| count($this->resource) > 2) {
			throw new ResponseException(404, 'invalid route');
		}
		if (isset($headers['namespace']) && !empty($headers['namespace'])) {
			$this->resource[0] = $headers['namespace'] . '\\' . $this->resource[0];
		}
		try {
			$this->requestedModel = ModelManager::getInstance()->getInstanceModel($this->resource[0]);
		} catch (NotDefinedModelException $e) {
			throw new ResponseException(404, "resource model '{$this->resource[0]}' doesn't exist");
		}
		if (!$this->requestedModel->hasSerialization()) {
			throw new ResponseException(404, "resource model '{$this->requestedModel->getName()}' is not requestable");
		}
		if (isset($this->resource[1])) {
			try {
				$this->uniqueResourceId = $this->_getFormatedId($this->requestedModel, $this->resource[1]);
			} catch (NoIdPropertyException $e) {
				throw new ResponseException(404, ['code' => $e->getCode(), 'message' => 'invalid route, '.$e->getMessage()]);
			} catch (PropertyVisibilityException $e) {
				throw new ResponseException(404, ['code' => $e->getCode(), 'message' => 'invalid route, '.$e->getMessage()]);
			} catch (ComhonException $e) {
				throw new MalformedRequestException(['code' => $e->getCode(), 'message' => $e->getMessage()]);
			}
		}
	}
	
	/**
	 *
	 * @param string[] $server
	 * @param array $get
	 * @param string[] $headers
	 * @param string $body
	 * @throws \Exception
	 * @return boolean|\Comhon\Api\Response
	 */
	private function _handleMethod($server, $get, $headers, $body) {
		$this->_verifyAllowedMethod($server['REQUEST_METHOD']);
		
		switch ($server['REQUEST_METHOD']) {
			case 'GET':
				return $this->_get($get, $headers, $body);
			case 'HEAD':
				return $this->_head($get, $headers, $body);
			case 'POST':
				return $this->_post($headers, $body);
			case 'PUT':
				return $this->_put($headers, $body);
			case 'DELETE':
				return $this->_delete();
			case 'OPTIONS':
				return $this->_options($headers);
			default:
				return $this->_buildResponse(501);
		}
	}
	
	/**
	 * verify if method is allowed for requested resource. if method is not allowed, an exception is thrown.
	 * 
	 * @param string $method
	 * @return boolean
	 */
	private function _verifyAllowedMethod($method) {
		$allowedMethods = $this->_getAllowedMethods();
		
		if (empty($allowedMethods)) {
			throw new ResponseException(404, 'invalid route');
		}
		if (!in_array($method, $allowedMethods)) {
			throw new MethodNotAllowedException(
				"method $method not allowed",
				['Allow' => implode(', ', $allowedMethods)]
			);
		}
	}
	
	/**
	 * 
	 * @param array $get
	 * @param string[] $headers
	 * @param string $body
	 * @param string[] $filterProperties
	 * @return \Comhon\Request\ComplexRequester
	 */
	private function _getComplexRequester(&$get, $headers, $body, $filterProperties = null) {
		$interfacer = $this->_getInterfacerFromContentTypeHeader($headers, false);
		if (is_null($interfacer)) {
			$request = $this->_setRequestFromQuery($get, $headers, $body, $filterProperties);
		} else {
			$this->_verifyAllowedRequest();
			$requestModel = ModelManager::getInstance()->getInstanceModel('Comhon\Request');
			$request = $this->_importBody($body, $requestModel, $interfacer);
			$this->_verifyRequestModel($request);
		}
		
		return ComplexRequester::build($request);
	}
	
	/**
	 *
	 * @param array $get
	 * @param string[] $headers
	 * @param string $body
	 * @param string[] $filterProperties
	 * @return \Comhon\Request\ComplexRequester
	 */
	private function _setRequestFromQuery(&$get, $headers, $body, $filterProperties = null) {
		$requestModel = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Complex');
		$request = $requestModel->getObjectInstance(false);
		$tree = $request->initValue('tree', false);
		$tree->setId(1);
		$tree->setValue('model', $this->requestedModel->getName());
		$request->setValue('properties', $filterProperties);
		
		// limit and offset
		if (isset($get[self::RANGE])) {
			if (!isset($get[self::ORDER])) {
				throw new DependsValuesException(self::RANGE, self::ORDER);
			}
			$range = new Range();
			if (!$range->satisfy($get[self::RANGE])) {
				throw new ImportException(new NotSatisfiedRestrictionException($get[self::RANGE], $range), self::RANGE);
			}
			list($first, $last) = explode('-', $get[self::RANGE]);
			$limit = 1 + $last - $first;
			$request->setValue('offset', (integer) $first);
			$request->setValue('limit',  $limit);
			
			unset($get[self::RANGE]);
		}
		
		// values order
		if (isset($get[self::ORDER])) {
			$orderArray = json_decode($get[self::ORDER], true);
			if (!is_array($orderArray)) {
				throw new UnexpectedValueTypeException($get[self::ORDER], 'json', self::ORDER);
			}
			try {
				$order = $requestModel->getProperty('order')->getModel()->import(
					$orderArray,
					new AssocArrayInterfacer()
				);
			} catch (\Exception $e) {
				throw new ImportException($e, self::ORDER);
			}
			$request->setValue('order', $order);
			unset($get[self::ORDER]);
		}
		$this->_setFilter($request, $get);
		
		return $request;
	}
	
	/**
	 * add filter to apply on given request
	 *
	 * @param \Comhon\Object\UniqueObject $request
	 * @param array $get
	 */
	private function _setFilter(UniqueObject $request, &$get) {
		$i = 0;
		$clauseType = Clause::CONJUNCTION;
		if (isset($get[self::CLAUSE])) {
			$clauseType = $get[self::CLAUSE];
			unset($get[self::CLAUSE]);
		}
		$clause = ModelManager::getInstance()->getInstanceModel('Comhon\Logic\Simple\Clause')->getObjectInstance(false);
		try {
			$clause->setValue('type', $clauseType);
		} catch (\Exception $e) {
			throw new ImportException($e, self::CLAUSE);
		}
		$clause->setId($i++);
		$simpleCollection = $request->initValue('simple_collection');
		$elements = $clause->initValue('elements', false);
		
		foreach ($get as $propertyName => $value) {
			$isArrayFilter = is_array($value);
			$property = $this->requestedModel->getProperty($propertyName, true);
			$literal = LiteralBinder::getLiteralInstance($property, $isArrayFilter);
			$propertyModel = $property->getLiteralModel();
			if (is_null($literal)) {
				throw new NotAllowedLiteralException($this->requestedModel, $property);
			}
			if ($propertyModel instanceof StringCastableModelInterface) {
				if ($isArrayFilter) {
					foreach ($value as &$element) {
						$element = $propertyModel->castValue($element, $propertyName);
					}
				} else {
					$value = $propertyModel->castValue($value, $propertyName);
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
				$literal->setValue('value', $value);
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
	
	/**
	 * 
	 * @param mixed $id
	 * @param \Comhon\Object\ComhonArray $filterProperties
	 * @return \Comhon\Request\SimpleRequester
	 */
	private function _getSimpleRequester($id, ComhonArray $filterProperties) {
		return SimpleRequester::build($this->requestedModel->getName(), $id, $filterProperties->getValues());
	}
	
	
	/**
	 * get properties to export
	 * 
	 * @param array $get
	 * @return \Comhon\Object\ComhonArray
	 */
	private function _getFilterProperties(&$get) {
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Request')->getProperty('properties')->getModel();
		if (isset($get[self::PROPERTIES])) {
			if (!is_array($get[self::PROPERTIES])) {
				throw new UnexpectedValueTypeException($get[self::PROPERTIES], 'array', self::PROPERTIES);
			}
			$filterProperties = $model->import($get[self::PROPERTIES], new AssocArrayInterfacer());
			unset($get[self::PROPERTIES]);
		} else {
			$filterProperties = $model->getObjectInstance();
		}
		
		return $filterProperties;
	}
	
	/**
	 * verify if route model and request model are the same
	 * 
	 * @param UniqueObject $request
	 * @throws MalformedRequestException
	 */
	private function _verifyRequestModel(UniqueObject $request) {
		if ($request->isA('Comhon\Request\Complex')) {
			$modelName = $request->getValue('tree')->getValue('model');
		} elseif ($request->isA('Comhon\Request\Intermediate')) {
			$modelName = $request->getValue('root')->getValue('model');
		}
		if ($modelName !== $this->requestedModel->getName()) {
			throw new MalformedRequestException(
				'request model name is different than route model : '.$modelName.' != '.$this->requestedModel->getName()
			);
		}
	}
	
	
	
	/**
	 * verify if complex/intermediate request are allowed for requested model
	 *
	 * @throws MalformedRequestException
	 */
	private function _verifyAllowedRequest() {
		$options = $this->requestedModel->getOptions();
		$allow = !is_null($options) && $options->issetValue('collection') && $options->getValue('collection')->issetValue('allow_complex_request')
			? $options->getValue('collection')->getValue('allow_complex_request')
			: Config::getInstance()->getValue('allow_complex_request');
		
		if ($allow === false) {
			throw new MalformedRequestException(
				'complex or intermediate request not allowed for model '.$this->requestedModel->getName()
			);
		}
	}
	
	/**
	 * 
	 * @param array $get
	 * @param string[] $headers
	 * @param string $body
	 * @return \Comhon\Api\Response
	 */
	private function _get(&$get, $headers, $body) {
		return $this->isCountRequest ? $this->_getCount($get, $headers, $body) : $this->_getResources($get, $headers, $body);
	}
	
	/**
	 * 
	 * @param array $get
	 * @param string[] $headers
	 * @param string $body
	 * @return \Comhon\Api\Response
	 */
	private function _getResources(&$get, $headers, $body) {
		if (count($this->resource) > 2) {
			return $this->_buildResponse(404, 'invalid route', ['Content-Type' => 'text/plain']);
		}
		try {
			$filterProperties = $this->_getFilterProperties($get);
			
			$requester = !is_null($this->uniqueResourceId)
				? $this->_getSimpleRequester($this->uniqueResourceId, $filterProperties)
				: $this->_getComplexRequester($get, $headers, $body, $filterProperties);
		} catch (MalformedRequestException $e) {
			throw $e;
		} catch (ComhonException $e) {
			throw new MalformedRequestException(['code' => $e->getCode(), 'message' => $e->getMessage()]);
		}

		$object = $requester->execute();
		if (is_null($object)) {
			throw new NotFoundException($this->requestedModel, $this->uniqueResourceId);
		}
		$interfacer = $this->_getInterfacerFromAcceptHeader($headers);
		$interfacer->setPropertiesFilter($filterProperties->getValues(), $object->getUniqueModel()->getName());
		
		if ($object instanceof ComhonArray) {
			$interfacedObject = $interfacer->export($object);
		} else {
			// export through original model to export potential inheritance key
			$interfacedObject = $this->requestedModel->export($object, $interfacer);
		}
		return $this->_buildResponse(200, $interfacedObject);
	}
	
	/**
	 * 
	 * @param array $get
	 * @param string[] $headers
	 * @param string $body
	 * @return \Comhon\Api\Response
	 */
	private function _getCount(&$get, $headers, $body) {
		if (count($this->resource) != 1) {
			return $this->_buildResponse(404, 'invalid route', ['Content-Type' => 'text/plain']);
		}
		
		$interfacer = $this->_getInterfacerFromContentTypeHeader($headers, false);
		if (is_null($interfacer)) {
			$requestModel = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Complex');
			$request = $requestModel->getObjectInstance(false);
			$tree = $request->initValue('tree', false);
			$tree->setId(1);
			$tree->setValue('model', $this->requestedModel->getName());
			
			$this->_setFilter($request, $get);
		} else {
			$this->_verifyAllowedRequest();
			$requestModel = ModelManager::getInstance()->getInstanceModel('Comhon\Request');
			$request = $this->_importBody($body, $requestModel, $interfacer);
			$this->_verifyRequestModel($request);
		}
		
		$requester = ComplexRequester::build($request);
		return $this->_buildResponse(200, $requester->count(), ['Content-Type' => 'text/plain']);
	}
	
	/**
	 *
	 * @param array $get
	 * @param string[] $headers
	 * @param string $body
	 * @return \Comhon\Api\Response
	 */
	private function _head(&$get, $headers, $body) {
		$response = $this->_get($get, $headers, $body);
		$send = $response->getSend();
		if (isset($send[1]['Content-Type'])) {
			$response->addHeader('Content-Type', $send[1]['Content-Type']);
		}
		$response->addHeader('Content-Length', strlen($send[2]));
		$response->setContent(null);
		return $response;
	}
	
	/**
	 * 
	 * @param string[] $headers
	 * @param string $body
	 * @throws ComhonException
	 * @return \Comhon\Api\Response
	 */
	private function _post($headers, $body) {
		if (count($this->resource) != 1) {
			throw new ComhonException('unique id not verified or invalid options');
		}
		$interfacer = $this->_getInterfacerFromContentTypeHeader($headers);
		$object = $this->_importBody($body, $this->requestedModel, $interfacer);
		
		if ($object->getModel()->hasIdProperties()) {
			if ($object->hasCompleteId()) {
				$serializationUnit = $this->requestedModel->getSerialization()->getSerializationUnit();
				if ($serializationUnit->hasIncrementalId($this->requestedModel)) {
					throw new MalformedRequestException("id must not be set to create resource '{$this->requestedModel->getName()}'");
				}
				if (!is_null($this->requestedModel->loadObject($object->getId(), array_keys($this->requestedModel->getIdProperties()), true))) {
					throw new ConflictException("resource with id '{$object->getId()}' already exists");
				}
			} else {
				$serializationUnit = $this->requestedModel->getSerialization()->getSerializationUnit();
				if (!$serializationUnit->hasIncrementalId($this->requestedModel)) {
					throw new MalformedRequestException("id must be set to create resource '{$this->requestedModel->getName()}'");
				}
			}
		}
		if ($object->save(SerializationUnit::CREATE) === 0) {
			throw new ResponseException(500, 'unknown error, object not created');
		}
		
		$object->load(null, true);
		return $this->_buildResponse(201, $interfacer->export($object));
	}
	
	/**
	 * 
	 * @param string[] $headers
	 * @param string $body
	 * @return \Comhon\Api\Response
	 */
	private function _put($headers, $body) {
		if (is_null($this->uniqueResourceId) || count($this->resource) != 2) {
			throw new ComhonException('unique id not verified or invalid options');
		}
		$idPropertiesNames = array_keys($this->requestedModel->getIdProperties());
		if (is_null($this->requestedModel->loadObject($this->uniqueResourceId, $idPropertiesNames, true))) {
			throw new NotFoundException($this->requestedModel, $this->uniqueResourceId);
		}
		$interfacer = $this->_getInterfacerFromContentTypeHeader($headers);
		$object = $this->_importBody($body, $this->requestedModel, $interfacer);
		
		if ($object->hasEmptyId()) {
			$object->setId($this->uniqueResourceId);
		} elseif ($object->getId() !== $this->uniqueResourceId) {
			throw new MalformedRequestException('conflict on route id and body id');
		}
		$updated = $object->save(SerializationUnit::UPDATE);
		
		if ($updated == 0) {
			throw new NotFoundException($this->requestedModel, $this->uniqueResourceId);
		}
		$object->load(null, true);
		return $this->_buildResponse(200, $interfacer->export($object));
	}
	
	/**
	 *
	 * @return \Comhon\Api\Response
	 */
	private function _delete() {
		if (is_null($this->uniqueResourceId) || count($this->resource) != 2) {
			throw new ComhonException('unique id not verified or invalid options');
		}
		$object = $this->requestedModel->loadObject(
			$this->uniqueResourceId, 
			array_keys($this->requestedModel->getIdProperties()),
			true
		);
		
		if (is_null($object)) {
			throw new NotFoundException($this->requestedModel, $this->uniqueResourceId);
		}
		$object->delete();
		
		return $this->_buildResponse(204);
	}
	
	/**
	 *
	 * @param string|null $contentType
	 * @param boolean $throw
	 * @return \Comhon\Interfacer\Interfacer|null
	 */
	public function _getInterfacerFromContentType($contentType, $throw = false) {
		switch ($contentType) {
			case 'application/json':
				return new AssocArrayInterfacer();
			case 'application/xml':
				return new XMLInterfacer();
			default:
				if ($throw) {
					throw new ResponseException(415, [
						'message' => "Content-Type {$contentType} unsupported",
						'supported' => ['application/json', 'application/xml']
					]);
				}
				return null;
		}
	}
	
	/**
	 *
	 * @param string[] $headers
	 * @param boolean $default if true and Content-Type not specified, return default interfacer otherwise return null
	 * @return \Comhon\Interfacer\Interfacer|null
	 */
	public function _getInterfacerFromContentTypeHeader($headers, $default = true) {
		return array_key_exists('Content-Type', $headers) 
			? $this->_getInterfacerFromContentType($headers['Content-Type'], true)
			: ($default ? new AssocArrayInterfacer() : null);
	}
	
	/**
	 *
	 * @param string[] $headers
	 * @return \Comhon\Interfacer\Interfacer
	 */
	public function _getInterfacerFromAcceptHeader($headers) {
		$accept = $this->_getHeaderMultiple('Accept', $headers);
		if (count($accept) == 0) {
			return new AssocArrayInterfacer();
		} else {
			foreach ($accept as $contentType) {
				if (!is_null($interfacer = $this->_getInterfacerFromContentType($contentType))) {
					return $interfacer;
				}
			}
			return new AssocArrayInterfacer();
		}
	}
	
	/**
	 * get header values (header that may define several values) sort by quality DESC
	 * 
	 * @param string $name
	 * @param string[] $headers
	 */
	public function _getHeaderMultiple($name, $headers) {
		$headerValues = [];
		if (array_key_exists($name, $headers)) {
			$values = explode(',', $headers[$name]);
			$indexedValues = ['1' => []];
			foreach ($values as $value) {
				$qualityValue = explode(';q=', trim($value));
				if (count($qualityValue) == 1) {
					$indexedValues[1][] = $qualityValue[0];
				} else {
					$quality = $qualityValue[1];
					if (!isset($indexedValues[$quality])) {
						$indexedValues[$quality] = [];
					}
					$indexedValues[$quality][] = $qualityValue[0];
				}
			}
			krsort($indexedValues);
			
			foreach ($indexedValues as $values) {
				foreach ($values as $value) {
					$headerValues[] = $value;
				}
			}
		}
		return $headerValues;
	}
	
	/**
	 * 
	 * @param string $body
	 * @param \Comhon\Model\Model $model
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws MalformedRequestException
	 * @return \Comhon\Object\UniqueObject
	 */
	public function _importBody($body, Model $model, Interfacer $interfacer) {
		$interfacedObject = $interfacer->fromString($body);
		if (!$interfacer->isNodeValue($interfacedObject)) {
			throw new MalformedRequestException('invalid body');
		}
		try {
			return $interfacer->import($interfacedObject, $model);
		} catch (ComhonException $e) {
			throw new MalformedRequestException(['code' => $e->getCode(), 'message' => $e->getMessage()]);
		}
	}
	
	/**
	 * get formated route. cast id if needed.
	 * 
	 * @param Model $model
	 * @param string $id
	 * @throws ResponseException
	 * @return mixed
	 */
	private function _getFormatedId(Model $model, $id) {
		if (!$model->hasIdProperties()) {
			throw new NoIdPropertyException($model);
		}
		if ($model->hasPrivateIdProperty()) {
			foreach ($model->getIdProperties() as $property) {
				if ($property->isPrivate()) {
					throw new PropertyVisibilityException($property, $model);
				}
			}
		}
		if ($model->hasUniqueIdProperty()) {
			$idModel = $model->getUniqueIdProperty()->getModel();
			if ($idModel instanceof StringCastableModelInterface) {
				$id = $idModel->castvalue($id, $model->getUniqueIdProperty()->getName());
			}
		} elseif (!$model->isCompleteId($id)) {
			throw new InvalidCompositeIdException($id);
		}
		return $id;
	}
	
	
	
	/**
	 * 
	 * @param string[] $headers
	 * @return \Comhon\Api\Response
	 */
	private function _options($headers) {
		$content = null;
		$options = $this->requestedModel->getOptions();
		if (!is_null($options)) {
			$content = $this->_getInterfacerFromContentTypeHeader($headers)->export($options);
		}
		return $this->_buildResponse(200, $content, ['Allow' => implode(', ', $this->_getAllowedMethods())]);
	}
	
	/**
	 *
	 * @param string[] $headers
	 * @return \Comhon\Api\Response
	 */
	private function _getAllowedMethods() {
		$methods = null;
		$options = $this->requestedModel->getOptions();
		if (!is_null($options)) {
			$node = is_null($this->uniqueResourceId) ? $options->getValue('collection') : $options->getValue('unique');
			if (!is_null($node) && $node->issetValue('allowed_methods')) {
				$methods = $node->getValue('allowed_methods')->getValues();
			}
		}
		if (is_null($methods)) {
			if (!is_null($this->uniqueResourceId)) { // request unique value with id
				$methods = !$this->requestedModel->hasIdProperties() || $this->requestedModel->hasPrivateIdProperty()
					? ['OPTIONS'] 
					: ($this->requestedModel->isAbstract() 
						? ['GET', 'HEAD', 'DELETE', 'OPTIONS']
						: ['GET', 'HEAD', 'PUT', 'DELETE', 'OPTIONS']);
			} else { // request collection
				$methods = is_null($this->requestedModel->getSqlTableUnit()) 
					? ($this->requestedModel->isAbstract()
						? ['OPTIONS']
						: ['POST', 'OPTIONS'])
					: ($this->requestedModel->isAbstract() 
						? ['GET', 'HEAD', 'OPTIONS']
						: ['GET', 'HEAD', 'POST', 'OPTIONS']);
			}
		}
		
		return $methods;
	}
	
	/**
	 * 
	 * @param integer $code
	 * @param string $content
	 * @param string[] $headers
	 */
	private function _buildResponse($code, $content = null, array $headers = []) {
		$response = new Response();
		$response->setCode($code);
		foreach ($headers as $name => $value) {
			$response->addHeader($name, $value);
		}
		$response->setContent($content);
		
		return $response;
	}
}
