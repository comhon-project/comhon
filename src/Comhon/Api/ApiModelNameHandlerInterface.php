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

use Psr\Http\Message\ServerRequestInterface;

interface ApiModelNameHandlerInterface {
	
	/** @var string */
	const COMHON_MODEL_NAME_KEY = 'comhon_model_name';
	
	/** @var string */
	const API_MODEL_NAME_KEY = 'api_model_name';
	
	/** @var string */
	const EXTENDS_KEY = 'extends';
	
	/**
	 * determine if path URI must contain api model name or comhon model name.
	 * if true, path URI may look like "/api/model/1" or "/api/my-other-model"...
	 * otherwise, path URI must contain model name with namespace 
	 * and may look like "/api/MyNamspace%5cMyModel/1" (charactre "\" is encoded to "%5c")
	 * 
	 * @return bool
	 */
	public function useApiModelName(): bool;
	
	/**
	 * get comhon model name according api model name given in request path URI.
	 * 
	 * called only if self::useApiModelName return true.
	 * 
	 * It must return the comhon model name if comhon model is requestable,
	 * or null if comhon model is not found or not requestable.
	 *
	 * @param string $apiModelName
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 * @return string|null
	 */
	public function resolveApiModelName(string $apiModelName, ServerRequestInterface $request): ?string;
	
	/**
	 * get model names that are requestable by client.
	 * 
	 * the returned array MAY be different according some options given in request (account, collection range...)
	 * each element of returned array MUST be an array that contain :
	 * - "comhon_model_name": string (required)
	 * - "api_model_name": string (optional)
	 * - "extends": string array (optional)
	 * 
	 * example of return : [
	 *   [
	 *     "api_model_name" => "person"
	 *     "comhon_model_name" => "Test\Person"
	 *   ],
	 *   [
	 *     "api_model_name" => "woman"
	 *     "comhon_model_name" => "Test\Person\Woman"
	 *     "extends" => ["Test\Person"]
	 *   ]
	 * ]
	 * this function is called when request path is "/basepath/models"
	 * (with "basepath" defined in request handler).
	 * function may return null if you don't want to handle request path "/basepath/models".
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 * @return string[]|null
	 */
	public function getApiModels(ServerRequestInterface $request): ?array;
	
}
