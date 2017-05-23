<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception;

abstract class ConstantException {
	
	// model exception
	const PROPERTY_EXCEPTION = 101;
	const CAST_EXCEPTION = 102;

	// value exception
	const NOT_SATISFIED_RESTRICTION_EXCEPTION = 201;
	
	// controller exception
	const CONTROLLER_PARAMETER_EXCEPTION = 301;
	
	// restriction exception
	const MALFORMED_INTERVAL_EXCEPTION = 401;
	const NOT_SUPPORTED_MODEL_INTERVAL_EXCEPTION = 402;
	const NOT_EXISTING_REGEX_EXCEPTION = 403;
	
	// manifest exception
	const RESERVED_WORD_EXCEPTION = 501;
}