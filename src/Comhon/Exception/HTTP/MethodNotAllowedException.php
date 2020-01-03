<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\HTTP;

class MethodNotAllowedException extends ResponseException {
	
	/**
	 * 
	 * @param string|array|\stdClass $content
	 * @param string[] $headers
	 */
	public function __construct($content = null, $headers = []) {
		parent::__construct(405, $content, $headers);
	}
}
