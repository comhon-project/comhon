<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Serialization;

use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;

class ManifestSerializationException extends ComhonException {
	
	/**
	 * @param string $message
	 */
	public function __construct() {
		parent::__construct(
			'manifest file with \'Comhon\' prefix cannot be serialized or deleted',
			ConstantException::MANIFEST_SERIALIZATION_EXCEPTION
		);
	}
	
}