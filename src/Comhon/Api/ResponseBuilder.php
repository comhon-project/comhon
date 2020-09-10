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

use Comhon\Object\AbstractComhonObject;
use Comhon\Interfacer\Interfacer;
use Comhon\Exception\ArgumentException;

class ResponseBuilder {
	
	/**
	 * Build a response with given comhon object. 
	 * Given comhon object will be interfaced according given interfacer and resulting content will be put in response body.
	 * The header Content-Type is automaticaly added according interfacer.
	 * 
	 * @param \Comhon\Object\AbstractComhonObject $object
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param integer $statusCode
	 * @param string[] $headers
	 * @return \Comhon\Api\Response
	 */
	public static function buildObjectResponse(AbstractComhonObject $object, Interfacer $interfacer, $statusCode = 200, array $headers = []) {
		$body = $interfacer->toString($interfacer->export($object));
		$headers['Content-Type'] = $interfacer->getMediaType();
	
		return new Response($statusCode, $headers, $body);
	}
	
	/**
	 * Build a response with given content. Content will be put in response body.
	 * The header Content-Type is automaticaly added according interfacer.
	 * 
	 * - if $content is a string, header Content-Type 'text/plain' is added if it doesn't already exists
	 * - if $content is an array or a \stdClass, $content is json encoded and header Content-Type 'application/json' is added 
	 *
	 * @param integer $statusCode
	 * @param string[] $headers
	 * @param string|array|\stdClass $content
	 * @return \Comhon\Api\Response
	 */
	public static function buildSimpleResponse($statusCode = 200, array $headers = [], $content = '') {
		if (is_null($content)) {
			return new Response($statusCode, $headers);
		} elseif (is_string($content)) {
			if (!array_key_exists('Content-Type', $headers)) {
				$headers['Content-Type'] = 'text/plain';
			}
			return new Response($statusCode, $headers, $content);
		} elseif (is_array($content) || $content instanceof \stdClass) {
			$headers['Content-Type'] = 'application/json';
			return new Response($statusCode, $headers, json_encode($content));
		} else {
			throw new ArgumentException($content, ['array', '\stdClass'], 3);
		}
	}
	
}
