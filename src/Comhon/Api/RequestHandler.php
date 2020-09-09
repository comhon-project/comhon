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
use Comhon\Model\ModelComhonObject;
use Comhon\Model\ModelArray;
use Comhon\Exception\Serialization\ConstraintException;
use GuzzleHttp\Psr7\ServerRequest;
use function GuzzleHttp\Psr7\parse_header;

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
	
	private static $serverRequest;
	
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
	
	/**
	 * get server request
	 * 
	 * @return \Psr\Http\Message\ServerRequestInterface
	 */
	public static function getServerRequest() {
		if (is_null(self::$serverRequest)) {
			self::$serverRequest = ServerRequest::fromGlobals();
		}
		
		return self::$serverRequest;
	}
	
	/**
	 * get server request path with urldecoded characters and removed duplicated slash.
	 * 
	 * @param boolean $removeLastSlash if true, remove the last slash if exists.
	 * @return string
	 */
	public static function getFilteredServerRequestPath($removeLastSlash = false) {
		return self::_getFilteredPath(self::getServerRequest()->getUri()->getPath(), $removeLastSlash);
	}
	
	/**
	 * get path with urldecoded characters and removed duplicated slash.
	 *
	 * @param boolean $removeLastSlash if true, remove the last slash if exists.
	 * @return string
	 */
	private static function _getFilteredPath($path, $removeLastSlash = false) {
		$path = urldecode(preg_replace('#//++#', '/', $path));
		return $removeLastSlash ? rtrim($path, '/') : $path;
	}
	
	/**
	 * handle client request and build response
	 * 
	 * @param string $basePath
	 * @param string[] $requestableModels each models that may be requested indexed by API model names.
	 *                                an API model name must match regex '^[a-z\-]+$' (there is no verification).
	 *                                example : [
	 *                                  'man' => 'Object\Person\Man',
	 *                                  'woman' => 'Object\Person\Woman',
	 *                                  'man-test' => 'Test\Person\Man',
	 *                                ].
	 *                                if provided, URI must contain an API model name,
	 *                                otherwise it must contain a fully qualified model name (with namespace).
	 * @return \Comhon\Api\Response
	 */
	public static function handle($basePath, array $requestableModels = null) {
		$handler = new self();
		return $handler->_handle(self::getServerRequest(), $basePath, $requestableModels);
	}
	
	/**
	 * 
	 * @param \GuzzleHttp\Psr7\ServerRequest $serverRequest
	 * @param string $basePath
	 * @param string[] $requestableModels
	 * @throws \Exception
	 * @return \Comhon\Api\Response
	 */
	protected function _handle(ServerRequest $serverRequest, $basePath, $requestableModels) {
		try {
			$this->_setRessourceArray($serverRequest, $basePath);
			if ($this->resource[0] == 'models' && count($this->resource) == 1) {
				$response = $this->_getRequestableModels($serverRequest, $requestableModels);
			} elseif ($this->resource[0] == 'patterns' && count($this->resource) == 1) {
				$response = $this->_getPatterns($serverRequest);
			}else {
				$this->_setRessourceInfos($serverRequest->getMethod(), $requestableModels);
				$response = $this->_handleMethod($serverRequest);
			}
		} catch (ResponseException $e) {
			$response = $e->getResponse();
		} catch (ManifestSerializationException $e) {
			$response = $this->_buildResponse(403, ['code' => $e->getCode(), 'message' => $e->getMessage()]);
		} catch (ConstraintException $e) {
			$response = $this->_buildResponse(400, ['code' => $e->getCode(), 'message' => $e->getMessage()]);
		} catch (\Exception $e) {
			$response = $this->_buildResponse(500);
		}
		
		return $response;
	}
	
	/**
	 * set resource array according request route.
	 * check if route is valid and may be handled.
	 *
	 * @param \GuzzleHttp\Psr7\ServerRequest $serverRequest
	 * @param string $basePath
	 */
	private function _setRessourceArray(ServerRequest $serverRequest, $basePath) {
		$path = self::_getFilteredPath($serverRequest->getUri()->getPath(), true);
		$basePath = self::_getFilteredPath('/'.$basePath, true);
		
		if ($path === $basePath) {// health check send response code 200
			throw new ResponseException(200, 'API root path');
		} elseif (strpos($path, $basePath.'/') !== 0) {
			throw new ResponseException(404, 'not handled route');
		}
		$this->resource = explode('/', substr($path, strlen($basePath) + 1));
	}
	
	/**
	 * set resource informations according request route.
	 * check if route is valid and may be handled.
	 * 
	 * @param string $method
	 * @param string[] $requestableModels
	 */
	private function _setRessourceInfos($method, array $requestableModels = null) {
		if ($this->resource[0] === 'count' && ($method === 'GET' || $method === 'HEAD' || $method === 'OPTIONS')) {
			array_shift($this->resource);
			$this->isCountRequest = true;
		}
		if (!array_key_exists(0, $this->resource)|| count($this->resource) > 2) {
			throw new ResponseException(404, 'invalid route');
		}
		try {
			if (!is_null($requestableModels)) {
				$resourceName = strtolower($this->resource[0]);
				if (!array_key_exists($resourceName, $requestableModels)) {
					throw new ResponseException(404, "resource api model name '{$this->resource[0]}' doesn't exist");
				}
				$modelName = $requestableModels[$resourceName];
			} else {
				$modelName = $this->resource[0];
			}
			$this->requestedModel = ModelManager::getInstance()->getInstanceModel($modelName);
		} catch (NotDefinedModelException $e) {
			throw new ResponseException(404, "resource model '{$modelName}' doesn't exist");
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
	 * get models list that may be requested
	 * 
	 * @param \GuzzleHttp\Psr7\ServerRequest $serverRequest
	 * @param string[] $requestableModels
	 * @return \Comhon\Api\Response
	 */
	private function _getRequestableModels(ServerRequest $serverRequest, array $requestableModels = null) {
		return $this->_getAssocArrayResponse($serverRequest, $requestableModels);
	}
	
	/**
	 * get patterns list that may be used
	 * 
	 * @param \GuzzleHttp\Psr7\ServerRequest $serverRequest
	 * @return \Comhon\Api\Response
	 */
	private function _getPatterns(ServerRequest $serverRequest) {
		$path = Config::getInstance()->getRegexListPath();
		$regexs = file_exists($path) ? json_decode(file_get_contents($path), true) : null;
		return $this->_getAssocArrayResponse($serverRequest, $regexs);
	}
	
	/**
	 * get response according provided assoc array of strings
	 * 
	 * @param \GuzzleHttp\Psr7\ServerRequest $serverRequest
	 * @param string[] $assocArray
	 * @return \Comhon\Api\Response
	 */
	private function _getAssocArrayResponse(ServerRequest $serverRequest, array $assocArray = null) {
		if (is_null($assocArray)) {
			throw new ResponseException(404, 'not handled route');
		}
		$method = $serverRequest->getMethod();
		
		if ($method == 'OPTIONS') {
			$response = new Response();
			$response->addHeader('Allow', implode(', ', ['GET', 'HEAD', 'OPTIONS']));
			return $response;
		}
		if ($method != 'GET' && $method != 'HEAD') {
			throw new MethodNotAllowedException(
				"method $method not allowed",
				['Allow' => implode(', ', ['GET', 'HEAD', 'OPTIONS'])]
			);
		}
		$response = new Response();
		$interfacer = self::getInterfacerFromAcceptHeader($serverRequest);
		
		if ($interfacer instanceof XMLInterfacer) {
			$root = $interfacer->createArrayNode('root');
			foreach ($assocArray as $apiName => $modelName) {
				$interfacer->addAssociativeValue($root, $modelName, $apiName, 'node');
			}
			$response->setContent($root);
		} elseif ($interfacer instanceof AssocArrayInterfacer) {
			$response->setContent($assocArray);
		} else {
			throw new ComhonException('not handled Content-Type : '.get_class($interfacer));
		}
		if ($method == 'HEAD') {
			$send = $response->getSend();
			if (isset($send[1]['Content-Type'])) {
				$response->addHeader('Content-Type', $send[1]['Content-Type']);
			}
			$response->addHeader('Content-Length', strlen($send[2]));
			$response->setContent(null);
		}
		return $response;
	}
	
	/**
	 * handle request according request method
	 *
	 * @param \GuzzleHttp\Psr7\ServerRequest $serverRequest
	 * @throws \Exception
	 * @return \Comhon\Api\Response
	 */
	private function _handleMethod($serverRequest) {
		$this->_verifyAllowedMethod($serverRequest->getMethod());
		
		switch ($serverRequest->getMethod()) {
			case 'GET':
				return $this->_get($serverRequest);
			case 'HEAD':
				return $this->_head($serverRequest);
			case 'POST':
				return $this->_post($serverRequest);
			case 'PUT':
				return $this->_put($serverRequest);
			case 'DELETE':
				return $this->_delete();
			case 'OPTIONS':
				return $this->_options($serverRequest);
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
	 * @param \GuzzleHttp\Psr7\ServerRequest $serverRequest
	 * @param array $queryParams
	 * @param string[] $filterProperties
	 * @return \Comhon\Request\ComplexRequester
	 */
	private function _getComplexRequester(ServerRequest $serverRequest, &$queryParams, $filterProperties = null) {
		$interfacer = self::getInterfacerFromContentTypeHeader($serverRequest, false);
		if (is_null($interfacer)) {
			$request = $this->_setRequestFromQuery($queryParams, $filterProperties);
		} else {
			$this->_verifyAllowedRequest();
			$requestModel = ModelManager::getInstance()->getInstanceModel('Comhon\Request');
			$request = self::importBody($serverRequest, $requestModel, $interfacer);
			$this->_verifyRequestModel($request);
		}
		
		return ComplexRequester::build($request);
	}
	
	/**
	 *
	 * @param array $queryParams
	 * @param string[] $filterProperties
	 * @return \Comhon\Request\ComplexRequester
	 */
	private function _setRequestFromQuery(&$queryParams, $filterProperties = null) {
		$requestModel = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Complex');
		$request = $requestModel->getObjectInstance(false);
		$tree = $request->initValue('tree', false);
		$tree->setId(1);
		$tree->setValue('model', $this->requestedModel->getName());
		$request->setValue('properties', $filterProperties);
		
		// limit and offset
		if (isset($queryParams[self::RANGE])) {
			if (!isset($queryParams[self::ORDER])) {
				throw new DependsValuesException(self::RANGE, self::ORDER);
			}
			$range = new Range();
			if (!$range->satisfy($queryParams[self::RANGE])) {
				throw new ImportException(new NotSatisfiedRestrictionException($queryParams[self::RANGE], $range), self::RANGE);
			}
			list($first, $last) = explode('-', $queryParams[self::RANGE]);
			$limit = 1 + $last - $first;
			$request->setValue('offset', (integer) $first);
			$request->setValue('limit',  $limit);
			
			unset($queryParams[self::RANGE]);
		}
		
		// values order
		if (isset($queryParams[self::ORDER])) {
			$orderArray = json_decode($queryParams[self::ORDER], true);
			if (!is_array($orderArray)) {
				throw new UnexpectedValueTypeException($queryParams[self::ORDER], 'json', self::ORDER);
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
			unset($queryParams[self::ORDER]);
		}
		$this->_setFilter($request, $queryParams);
		
		return $request;
	}
	
	/**
	 * add filter to apply on given request
	 *
	 * @param \Comhon\Object\UniqueObject $request
	 * @param array $queryParams
	 */
	private function _setFilter(UniqueObject $request, &$queryParams) {
		$i = 0;
		$clauseType = Clause::CONJUNCTION;
		if (isset($queryParams[self::CLAUSE])) {
			$clauseType = $queryParams[self::CLAUSE];
			unset($queryParams[self::CLAUSE]);
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
		
		foreach ($queryParams as $propertyName => $value) {
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
	 * @param array $queryParams
	 * @return \Comhon\Object\ComhonArray
	 */
	private function _getFilterProperties(&$queryParams) {
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Request')->getProperty('properties')->getModel();
		if (isset($queryParams[self::PROPERTIES])) {
			if (!is_array($queryParams[self::PROPERTIES])) {
				throw new UnexpectedValueTypeException($queryParams[self::PROPERTIES], 'array', self::PROPERTIES);
			}
			$filterProperties = $model->import($queryParams[self::PROPERTIES], new AssocArrayInterfacer());
			unset($queryParams[self::PROPERTIES]);
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
	 * @param \GuzzleHttp\Psr7\ServerRequest $serverRequest
	 * @return \Comhon\Api\Response
	 */
	private function _get(ServerRequest $serverRequest) {
		return $this->isCountRequest ? $this->_getCount($serverRequest) : $this->_getResources($serverRequest);
	}
	
	/**
	 * 
	 * @param \GuzzleHttp\Psr7\ServerRequest $serverRequest
	 * @return \Comhon\Api\Response
	 */
	private function _getResources(ServerRequest $serverRequest) {
		if (count($this->resource) > 2) {
			return $this->_buildResponse(404, 'invalid route', ['Content-Type' => 'text/plain']);
		}
		try {
			// query parameters will be modified during query bulding by removing some parameters
			$queryParams = $serverRequest->getQueryParams();
			$filterProperties = $this->_getFilterProperties($queryParams);
			
			$requester = !is_null($this->uniqueResourceId)
				? $this->_getSimpleRequester($this->uniqueResourceId, $filterProperties)
				: $this->_getComplexRequester($serverRequest, $queryParams, $filterProperties);
		} catch (ResponseException $e) {
			throw $e;
		} catch (ComhonException $e) {
			throw new MalformedRequestException(['code' => $e->getCode(), 'message' => $e->getMessage()]);
		}

		$object = $requester->execute();
		if (is_null($object)) {
			throw new NotFoundException($this->requestedModel, $this->uniqueResourceId);
		}
		$interfacer = self::getInterfacerFromAcceptHeader($serverRequest);
		$interfacer->setValidate(false);
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
	 * @param \GuzzleHttp\Psr7\ServerRequest $serverRequest
	 * @return \Comhon\Api\Response
	 */
	private function _getCount(ServerRequest $serverRequest) {
		if (count($this->resource) != 1) {
			return $this->_buildResponse(404, 'invalid route', ['Content-Type' => 'text/plain']);
		}
		
		$interfacer = self::getInterfacerFromContentTypeHeader($serverRequest, false);
		if (is_null($interfacer)) {
			$requestModel = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Complex');
			$request = $requestModel->getObjectInstance(false);
			$tree = $request->initValue('tree', false);
			$tree->setId(1);
			$tree->setValue('model', $this->requestedModel->getName());
			
			// query parameters will be modified during query bulding by removing some parameters
			$queryParams = $serverRequest->getQueryParams();
			$this->_setFilter($request, $queryParams);
		} else {
			$this->_verifyAllowedRequest();
			$requestModel = ModelManager::getInstance()->getInstanceModel('Comhon\Request');
			$request = self::importBody($serverRequest, $requestModel, $interfacer);
			$this->_verifyRequestModel($request);
		}
		
		$requester = ComplexRequester::build($request);
		return $this->_buildResponse(200, $requester->count(), ['Content-Type' => 'text/plain']);
	}
	
	/**
	 *
	 * @param \GuzzleHttp\Psr7\ServerRequest $serverRequest
	 * @return \Comhon\Api\Response
	 */
	private function _head(ServerRequest $serverRequest) {
		$response = $this->_get($serverRequest);
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
	 * @param \GuzzleHttp\Psr7\ServerRequest $serverRequest
	 * @throws ComhonException
	 * @return \Comhon\Api\Response
	 */
	private function _post(ServerRequest $serverRequest) {
		if (count($this->resource) != 1) {
			throw new ComhonException('unique id not verified or invalid options');
		}
		$interfacer = self::getInterfacerFromContentTypeHeader($serverRequest);
		$object = self::importBody($serverRequest, $this->requestedModel, $interfacer);
		
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
	 * @param \GuzzleHttp\Psr7\ServerRequest $serverRequest
	 * @return \Comhon\Api\Response
	 */
	private function _put(ServerRequest $serverRequest) {
		if (is_null($this->uniqueResourceId) || count($this->resource) != 2) {
			throw new ComhonException('unique id not verified or invalid options');
		}
		$idPropertiesNames = array_keys($this->requestedModel->getIdProperties());
		if (is_null($this->requestedModel->loadObject($this->uniqueResourceId, $idPropertiesNames, true))) {
			throw new NotFoundException($this->requestedModel, $this->uniqueResourceId);
		}
		$interfacer = self::getInterfacerFromContentTypeHeader($serverRequest);
		$object = self::importBody($serverRequest, $this->requestedModel, $interfacer);
		
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
	 * get interfacer according provided content type
	 *
	 * @param string|null $contentType
	 * @param boolean $throw if true, and content type is not managed, an exception is thrown
	 * @return \Comhon\Interfacer\Interfacer|null
	 */
	public static function getInterfacerFromContentType($contentType, $throw = false) {
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
	 * get interfacer according content type header
	 *
	 * @param \GuzzleHttp\Psr7\ServerRequest $serverRequest
	 * @param boolean $default if true and Content-Type not specified, return default interfacer otherwise return null
	 * @return \Comhon\Interfacer\Interfacer|null
	 */
	public static function getInterfacerFromContentTypeHeader(ServerRequest $serverRequest, $default = true) {
		return $serverRequest->hasHeader('Content-Type')
			? self::getInterfacerFromContentType(current($serverRequest->getHeader('Content-Type')), true)
			: ($default ? new AssocArrayInterfacer() : null);
	}
	
	/**
	 * get interfacer according provided accept header
	 *
	 * @param \GuzzleHttp\Psr7\ServerRequest $serverRequest
	 * @return \Comhon\Interfacer\Interfacer
	 */
	public static function getInterfacerFromAcceptHeader(ServerRequest $serverRequest) {
		$accept = parse_header($serverRequest->getHeader('Accept'));
		if (count($accept) == 0) {
			return new AssocArrayInterfacer();
		} else {
			usort($accept, [RequestHandler::class, "_sortHeaderByQuality"]);
			foreach ($accept as $contentTypeNode) {
				if (!is_null($interfacer = self::getInterfacerFromContentType(current($contentTypeNode)))) {
					return $interfacer;
				}
			}
			return new AssocArrayInterfacer();
		}
	}
	
	private static function _sortHeaderByQuality($a, $b) {
		$aq = isset($a['q']) ? (float) $a['q'] : 1;
		$bq = isset($b['q']) ? (float) $b['q'] : 1;
		
		if ($aq == $bq) {
			return 0;
		}
		return ($aq > $bq) ? -1 : 1;
	}
	
	/**
	 * import given body and build comhon object according given model
	 * 
	 * @param \GuzzleHttp\Psr7\ServerRequest $serverRequest
	 * @param \Comhon\Model\Model|\Comhon\Model\ModelArray $model
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws MalformedRequestException
	 * @return \Comhon\Object\AbstractComhonObject
	 */
	public static function importBody(ServerRequest $serverRequest, ModelComhonObject $model, Interfacer $interfacer) {
		$interfacedObject = $interfacer->fromString($serverRequest->getBody()->getContents());
		if ($model instanceof ModelArray) {
		    if (!$interfacer->isArrayNodeValue($interfacedObject, $model->isAssociative())) {
		        throw new MalformedRequestException('invalid body');
		    }
		} elseif (!$interfacer->isNodeValue($interfacedObject)) {
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
	 * @param \GuzzleHttp\Psr7\ServerRequest $serverRequest
	 * @return \Comhon\Api\Response
	 */
	private function _options(ServerRequest $serverRequest) {
		$content = null;
		$options = $this->requestedModel->getOptions();
		if (!is_null($options)) {
			$content = self::getInterfacerFromAcceptHeader($serverRequest)->export($options);
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
