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
use Comhon\Exception\HTTP\ConflictException;
use Comhon\Exception\HTTP\MethodNotAllowedException;
use Comhon\Exception\Value\InvalidCompositeIdException;
use Comhon\Exception\Serialization\ManifestSerializationException;
use Comhon\Object\Config\Config;
use Comhon\Model\ModelComhonObject;
use Comhon\Model\ModelArray;
use Comhon\Exception\Serialization\ConstraintException;
use Comhon\Exception\Serialization\MissingNotNullException;
use GuzzleHttp\Psr7\ServerRequest;
use function GuzzleHttp\Psr7\parse_header;
use function GuzzleHttp\Psr7\stream_for;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Comhon\Interfacer\XMLInterfacer;

class RequestHandler implements RequestHandlerInterface {
	
	/**
	 *
	 * @var string
	 */
	const PROPERTIES = '-properties';
	
	/**
	 *
	 * @var string
	 */
	const ORDER = '-order';
	
	/**
	 *
	 * @var string
	 */
	const RANGE = '-range';
	
	/**
	 *
	 * @var string
	 */
	const CLAUSE = '-clause';
	
	/**
	 * 
	 * @var \Psr\Http\Message\ServerRequestInterface
	 */
	private static $serverRequest;
	
	/**
	 *
	 * @var string
	 */
	private $requestBasePath;
	
	/**
	 *
	 * @var ApiModelNameHandlerInterface
	 */
	private $apiModelNameHandler;
	
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
	 * get server request from globals
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
	 * get path with urldecoded characters, removed duplicated slash and optionally removed trailing slash.
	 *
	 * @param boolean $removeTrailingSlash if true, remove trailing slash if exists (/my/path/ become /my/path).
	 * @return string
	 */
	public static function filterPath($path, $removeTrailingSlash = false) {
		$path = urldecode(preg_replace('#//++#', '/', $path));
		return $removeTrailingSlash ? rtrim($path, '/') : $path;
	}
	
	/**
	 * 
	 * @param string $basePath The request path must match with given base path otherwise request will not be handled 
	 * 
	 *                         for example if you decide to set base path to '/api' :
	 *                         - a request with path '/' will not be handled
	 *                         - a request with path '/myapi' will not be handled
	 *                         - a request with path '/api' will be handled
	 *                         - a request with path '/api/some/resource' will be handled
	 *                         
	 *                         If you call handle function but request path is not handled according base path,
	 *                         a response whit status code 404 and body 'not handled route' will be returned
	 *                         
	 * @param ApiModelNameHandlerInterface $apiModelNameHandler
	 * @return \Comhon\Api\Response
	 */
	public function __construct($basePath, ApiModelNameHandlerInterface $apiModelNameHandler = null) {
		$this->requestBasePath = $basePath;
		$this->apiModelNameHandler = $apiModelNameHandler;
	}
	
	/**
     * Handles the request from globals and produces a response.
	 * 
	 * @return ResponseInterface
	 */
	public function run(): ResponseInterface {
		return $this->handle(self::getServerRequest());
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Psr\Http\Server\RequestHandlerInterface::handle()
	 */
	public function handle(ServerRequestInterface $serverRequest): ResponseInterface {
		try {
			$this->_setRessourceArray($serverRequest);
			if ($this->resource[0] == 'pattern' && count($this->resource) == 2) {
				$response = $this->_getPattern($serverRequest);
			} elseif ($this->resource[0] == 'models' && count($this->resource) == 1) {
				$response = $this->_getModels($serverRequest);
			}else {
				$this->_setRessourceInfos($serverRequest);
				$response = $this->_handleMethod($serverRequest);
			}
		} catch (ResponseException $e) {
			$response = $e->getResponse();
		} catch (ManifestSerializationException $e) {
			$response = ResponseBuilder::buildSimpleResponse($e->getHttpCode(), [], ['code' => $e->getCode(), 'message' => $e->getMessage()]);
		} catch (ConstraintException $e) {
			$response = ResponseBuilder::buildSimpleResponse(400, [], ['code' => $e->getCode(), 'message' => $e->getMessage()]);
		} catch (MissingNotNullException $e) {
			$response = ResponseBuilder::buildSimpleResponse(400, [], ['code' => $e->getCode(), 'message' => $e->getMessage()]);
		} catch (\Exception $e) {
			$response = new Response(500);
		}
		
		return $response;
	}
	
	/**
	 * set resource array according request route.
	 * check if route is valid and may be handled.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
	 */
	private function _setRessourceArray(ServerRequestInterface $serverRequest) {
		$path = self::filterPath($serverRequest->getUri()->getPath(), true);
		$basePath = self::filterPath('/'.$this->requestBasePath, true);
		
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
	 * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
	 */
	private function _setRessourceInfos(ServerRequestInterface $serverRequest) {
		$method = $serverRequest->getMethod();
		if ($this->resource[0] === 'count' && ($method === 'GET' || $method === 'HEAD' || $method === 'OPTIONS')) {
			array_shift($this->resource);
			$this->isCountRequest = true;
		}
		if (!array_key_exists(0, $this->resource)|| count($this->resource) > 2) {
			throw new ResponseException(404, 'invalid route');
		}
		try {
			if (!is_null($this->apiModelNameHandler) && $this->apiModelNameHandler->useApiModelName()) {
				$modelName = $this->apiModelNameHandler->resolveApiModelName($this->resource[0], $serverRequest);
				if (is_null($modelName)) {
					throw new ResponseException(404, "resource api model name '{$this->resource[0]}' doesn't exist");
				}
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
				// if method is OPTIONS, not need to format id, 
				// it is only used to know if request is a collection or a unique resquest
				// it avoid to respond with an error code that might be inconvenient with browser CORS management
				$this->uniqueResourceId = $this->_getFormatedId($this->requestedModel, $this->resource[1], $method !== 'OPTIONS');
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
	 * get pattern regex according given pattern name in request path URI
	 * 
	 * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
	 * @return \Comhon\Api\Response
	 */
	private function _getPattern(ServerRequestInterface $serverRequest) {
		$method = $serverRequest->getMethod();
		
		if ($method == 'OPTIONS') {
			return new Response(200, ['Allow' => implode(', ', ['GET', 'HEAD', 'OPTIONS'])]);
		}
		if ($method != 'GET' && $method != 'HEAD') {
			throw new MethodNotAllowedException(
				"method $method not allowed",
				['Allow' => implode(', ', ['GET', 'HEAD', 'OPTIONS'])]
			);
		}
		$path = Config::getInstance()->getRegexListPath();
		$regexs = file_exists($path) ? json_decode(file_get_contents($path), true) : null;
		if (is_null($regexs)|| !array_key_exists($this->resource[1], $regexs)) {
			return new Response(404);
		}
		
		return $method == 'GET'
			? new Response(200, ['Content-Type' => 'text/plain'], $regexs[$this->resource[1]])
			: new Response(200, ['Content-Length' => strlen($regexs[$this->resource[1]])]);
	}
	
	/**
	 * get models list
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
	 * @return \Comhon\Api\Response
	 */
	private function _getModels(ServerRequestInterface $serverRequest) {
		if (is_null($this->apiModelNameHandler)) {
			return new Response(404);
		}
		$models = $this->apiModelNameHandler->getApiModels($serverRequest);
		if (is_null($models)) {
			return new Response(404);
		}
		$method = $serverRequest->getMethod();
		if ($method == 'OPTIONS') {
			return new Response(200, ['Allow' => implode(', ', ['GET', 'HEAD', 'OPTIONS'])]);
		}
		if ($method != 'GET' && $method != 'HEAD') {
			throw new MethodNotAllowedException(
				"method $method not allowed",
				['Allow' => implode(', ', ['GET', 'HEAD', 'OPTIONS'])]
			);
		}
		$interfacer = self::getInterfacerFromAcceptHeader($serverRequest);
		if ($interfacer instanceof XMLInterfacer) {
			$root = $interfacer->createArrayNode('root');
			foreach ($models as $model) {
				$modelNode = $interfacer->createArrayNode('model');
				$interfacer->setValue($root, $modelNode);
				if (!isset($model['comhon_model_name'])) {
					throw new ComhonException('invalid models list : "comhon_model_name" is not set');
				}
				$interfacer->setValue($modelNode, $model['comhon_model_name'], 'comhon_model_name');
				if (isset($model['api_model_name'])) {
					$interfacer->setValue($modelNode, $model['api_model_name'], 'api_model_name');
				}
				if (isset($model['extends'])) {
					$extendsNode = $interfacer->createArrayNode('extends');
					$interfacer->setValue($modelNode, $extendsNode);
					foreach ($model['extends'] as $modelName) {
						$interfacer->setValue($extendsNode, $modelName, 'model', true);
					}
				}
			}
			$models = $root;
		}
		
		return $method == 'GET'
			? new Response(200, ['Content-Type' => $interfacer->getMediaType()], $interfacer->toString($models))
			: new Response(200, ['Content-Length' => strlen($interfacer->toString($models))]);
		
	}
	
	/**
	 * handle request according request method
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
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
				return new Response(501);
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
	 * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
	 * @param array $queryParams
	 * @param string[]|null $filterProperties
	 * @return \Comhon\Request\ComplexRequester
	 */
	private function _getComplexRequester(ServerRequestInterface $serverRequest, &$queryParams, $filterProperties = null) {
		$interfacer = self::getInterfacerFromContentTypeHeader($serverRequest, false);
		if (is_null($interfacer)) {
			$request = $this->_setRequestFromQuery($queryParams, $filterProperties);
		} else {
			$this->_verifyAllowedRequest();
			$requestModel = ModelManager::getInstance()->getInstanceModel('Comhon\Request');
			$request = self::_importBody($serverRequest, $requestModel, $interfacer);
			$this->_verifyRequestModel($request);
		}
		
		return ComplexRequester::build($request);
	}
	
	/**
	 *
	 * @param array $queryParams
	 * @param string[]|null $filterProperties
	 * @return \Comhon\Request\ComplexRequester
	 */
	private function _setRequestFromQuery(&$queryParams, $filterProperties = null) {
		$requestModel = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Complex');
		$request = $requestModel->getObjectInstance(false);
		$tree = $request->initValue('tree', false);
		$tree->setId(1);
		$tree->setValue('model', $this->requestedModel->getName());
		if (is_array($filterProperties)) {
			$properties = $request->initValue('properties');
			foreach ($filterProperties as $property) {
				$properties->pushValue($property);
			}
			$request->setValue('properties', $properties);
		}
		
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
	 * @param string[]|null $filterProperties
	 * @return \Comhon\Request\SimpleRequester
	 */
	private function _getSimpleRequester($id, array $filterProperties = null) {
		return SimpleRequester::build($this->requestedModel->getName(), $id, $filterProperties);
	}
	
	
	/**
	 * get properties to export
	 * 
	 * @param array $queryParams
	 * @return string[]|null
	 */
	private function _getFilterProperties(&$queryParams) {
		if (isset($queryParams[self::PROPERTIES])) {
			if (!is_array($queryParams[self::PROPERTIES])) {
				throw new UnexpectedValueTypeException($queryParams[self::PROPERTIES], 'array', self::PROPERTIES);
			}
			$properties = $queryParams[self::PROPERTIES];
			unset($queryParams[self::PROPERTIES]);
			return $properties;
		}
		return null;
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
	 * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
	 * @return \Comhon\Api\Response
	 */
	private function _get(ServerRequestInterface $serverRequest) {
		return $this->isCountRequest ? $this->_getCount($serverRequest) : $this->_getResources($serverRequest);
	}
	
	/**
	 * 
	 * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
	 * @return \Comhon\Api\Response
	 */
	private function _getResources(ServerRequestInterface $serverRequest) {
		if (count($this->resource) > 2) {
			return ResponseBuilder::buildSimpleResponse(404, [], 'invalid route');
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
			if ($this->requestedModel->getName() == 'Comhon\Manifest') {
				$this->throwManifestRedirection($serverRequest);
			}
			throw new NotFoundException($this->requestedModel, $this->uniqueResourceId);
		}
		$interfacer = self::getInterfacerFromAcceptHeader($serverRequest);
		$interfacer->setValidate(false);
		$interfacer->setPropertiesFilter($filterProperties);
		
		// for unique object, export through original model to export potential inheritance key
		$interfacedObject = $object instanceof ComhonArray 
			? $interfacer->export($object) : 
			$this->requestedModel->export($object, $interfacer);
		
		return ResponseBuilder::buildSimpleResponse(
			200, 
			['Content-Type' => $interfacer->getMediaType()], 
			$interfacer->toString($interfacedObject)
		);
	}
	
	/**
	 * throw a redirection HTTP exception if needed.
	 * the redirection is thrown only a "parent" manifest is found
	 * 
	 * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
	 * @throws ResponseException
	 */
	private function throwManifestRedirection(ServerRequestInterface $serverRequest) {
		$infos = ModelManager::getInstance()->searchManifestPath($this->uniqueResourceId);
		if (!is_null($infos[0])) {
			$uri = $serverRequest->getUri()->__toString();
			if (($pos = strrpos($uri, '/')) !== false) {
				$uri = substr($uri, 0, $pos + 1);
			}
			$uri .= urlencode($infos[3]);
			throw new ResponseException(301, $infos[3], ['Location' => $uri]);
		}
	}
	
	/**
	 * 
	 * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
	 * @return \Comhon\Api\Response
	 */
	private function _getCount(ServerRequestInterface $serverRequest) {
		if (count($this->resource) != 1) {
			return ResponseBuilder::buildSimpleResponse(404, [], 'invalid route');
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
			$request = self::_importBody($serverRequest, $requestModel, $interfacer);
			$this->_verifyRequestModel($request);
		}
		
		$requester = ComplexRequester::build($request);
		
		return ResponseBuilder::buildSimpleResponse(200, [], ''.$requester->count());
	}
	
	/**
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
	 * @return \Comhon\Api\Response
	 */
	private function _head(ServerRequestInterface $serverRequest) {
		$response = $this->_get($serverRequest);
		return $response->withHeader('Content-Length', $response->getBody()->getSize())->withBody(stream_for(''));
	}
	
	/**
	 * 
	 * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
	 * @throws ComhonException
	 * @return \Comhon\Api\Response
	 */
	private function _post(ServerRequestInterface $serverRequest) {
		if (count($this->resource) != 1) {
			throw new ComhonException('unique id not verified or invalid options');
		}
		$interfacer = self::getInterfacerFromContentTypeHeader($serverRequest);
		$object = self::_importBody($serverRequest, $this->requestedModel, $interfacer);
		
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
		return ResponseBuilder::buildObjectResponse($object, $interfacer, 201);
	}
	
	/**
	 * 
	 * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
	 * @return \Comhon\Api\Response
	 */
	private function _put(ServerRequestInterface $serverRequest) {
		if (is_null($this->uniqueResourceId) || count($this->resource) != 2) {
			throw new ComhonException('unique id not verified or invalid options');
		}
		$idPropertiesNames = array_keys($this->requestedModel->getIdProperties());
		if (is_null($this->requestedModel->loadObject($this->uniqueResourceId, $idPropertiesNames, true))) {
			throw new NotFoundException($this->requestedModel, $this->uniqueResourceId);
		}
		$interfacer = self::getInterfacerFromContentTypeHeader($serverRequest);
		$object = self::_importBody($serverRequest, $this->requestedModel, $interfacer);
		
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
		return ResponseBuilder::buildObjectResponse($object, $interfacer, 200);
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
		
		return new Response(204);
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
			case 'application/xml':
			case 'application/x-yaml':
				return Interfacer::getInstance($contentType, true);
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
	 * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
	 * @param boolean $default if true and Content-Type not specified, return default interfacer otherwise return null
	 * @return \Comhon\Interfacer\Interfacer|null
	 */
	public static function getInterfacerFromContentTypeHeader(ServerRequestInterface $serverRequest, $default = true) {
		return $serverRequest->hasHeader('Content-Type')
			? self::getInterfacerFromContentType(current($serverRequest->getHeader('Content-Type')), true)
			: ($default ? new AssocArrayInterfacer() : null);
	}
	
	/**
	 * get interfacer according provided accept header
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
	 * @return \Comhon\Interfacer\Interfacer
	 */
	public static function getInterfacerFromAcceptHeader(ServerRequestInterface $serverRequest) {
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
	 * import request body and build comhon object according Content-Type header and given model
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
	 * @param \Comhon\Model\Model|\Comhon\Model\ModelArray $model
	 * @param boolean $default if true and Content-Type not specified, 
	 *                         try to parse body with default interfacer (json)
	 *                         otherwise return null
	 * @throws MalformedRequestException
	 * @return \Comhon\Object\AbstractComhonObject
	 */
	public static function importBody(ServerRequestInterface $serverRequest, ModelComhonObject $model, $default = true) {
		$interfacer = self::getInterfacerFromContentTypeHeader($serverRequest, $default);
		return $interfacer ? self::_importBody($serverRequest, $model, $interfacer) : null;
	}
	
	/**
	 * import request body and build comhon object according given model
	 * 
	 * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
	 * @param \Comhon\Model\Model|\Comhon\Model\ModelArray $model
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws MalformedRequestException
	 * @return \Comhon\Object\AbstractComhonObject
	 */
	private static function _importBody(ServerRequestInterface $serverRequest, ModelComhonObject $model, Interfacer $interfacer) {
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
	 * @param boolean $verifyValue
	 * @throws ResponseException
	 * @return mixed
	 */
	private function _getFormatedId(Model $model, $id, $verifyValue = true) {
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
		if ($verifyValue) {
			if ($model->hasUniqueIdProperty()) {
				$idModel = $model->getUniqueIdProperty()->getModel();
				if ($idModel instanceof StringCastableModelInterface) {
					$id = $idModel->castvalue($id, $model->getUniqueIdProperty()->getName());
				}
			} elseif (!$model->isCompleteId($id)) {
				throw new InvalidCompositeIdException($id);
			}
		}
		return $id;
	}
	
	
	
	/**
	 * 
	 * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
	 * @return \Comhon\Api\Response
	 */
	private function _options(ServerRequestInterface $serverRequest) {
		$options = $this->requestedModel->getOptions();
		if (is_null($options)) {
			return new Response(200, ['Allow' => implode(', ', $this->_getAllowedMethods())]);
		}
		return ResponseBuilder::buildObjectResponse(
			$options, 
			self::getInterfacerFromAcceptHeader($serverRequest), 
			200,
			['Allow' => implode(', ', $this->_getAllowedMethods())]
		);
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
	
}
