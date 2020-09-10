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

use Comhon\Api\Response;

trait RequestTestTrait {
	
	/**
	 *
	 * @param \Comhon\Api\Response $response
	 * @return array
	 */
	public function responseToArray(Response $response) {
		$array = [$response->getStatusCode(), [], $response->getFullBodyContents()];
		
		foreach ($response->getHeaders() as $header => $value) {
			$array[1][$header] = $response->getHeaderLine($header);
		}
		
		return $array;
	}
	
}