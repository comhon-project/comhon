<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\Comhon\Utils;

use Psr\Http\Message\ServerRequestInterface;
use Comhon\Api\ApiModelNameHandlerInterface;

class ApiModelNameHandler implements ApiModelNameHandlerInterface
{
	/**
	 * 
	 * @var bool
	 */
	private $useApiModelName;
	
	/**
	 * 
	 * @var array
	 */
	private $modelNames = [];
	
	/**
	 * 
	 * @param bool $useApiModelName
	 * @param array $modelNames
	 */
	public function __construct(bool $useApiModelName, array $modelNames = null)
	{
		$this->useApiModelName = $useApiModelName;
		$this->modelNames = $modelNames;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Api\ApiModelNameHandlerInterface::useApiModelName()
	 */
	public function useApiModelName(): bool
	{
		return $this->useApiModelName;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Api\ApiModelNameHandlerInterface::resolveApiModelName()
	 */
	public function resolveApiModelName(string $apiModelName, ServerRequestInterface $request): ?string
	{
		$apiModelName = strtolower($apiModelName);
		if (!$this->useApiModelName) {
			throw new \Exception('resolveApiModelName should not be called when useApiModelName is false');
		}
		if (!is_null($this->modelNames)) {
			foreach ($this->modelNames as $model) {
				if (
					isset($model[ApiModelNameHandlerInterface::API_MODEL_NAME_KEY])
					&& $model[ApiModelNameHandlerInterface::API_MODEL_NAME_KEY] === $apiModelName
				) {
					return $model[ApiModelNameHandlerInterface::COMHON_MODEL_NAME_KEY];
				}
			}
		}
		return null;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Api\ApiModelNameHandlerInterface::getApiModels()
	 */
	public function getApiModels(ServerRequestInterface $request): ?array
	{
		return $this->modelNames;
	}
	
}
