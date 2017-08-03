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
	const ALREADY_USED_MODEL_NAME_EXCEPTION = 100;
	const NOT_DEFINED_MODEL_EXCEPTION       = 101;
	const UNEXPECTED_MODEL_EXCEPTION        = 102;
	const UNIQUE_MODEL_NAME_EXCEPTION       = 103;
	const MANIFEST_EXCEPTION                = 104;
	const UNDEFINED_PROPERTY_EXCEPTION      = 105;
	const CONFLICT_PROPERTIES_EXCEPTION     = 106;
	const CAST_EXCEPTION                    = 107;
	const PROPERTY_VISIBILITY_EXCEPTION     = 108;
	
	// value exception
	const NOT_SATISFIED_RESTRICTION_EXCEPTION = 201;
	const ENUMERATION_EXCEPTION               = 202;
	const UNEXPECTED_VALUE_TYPE_EXCEPTION     = 203;
	
	// visitor exception
	const VISITOR_PARAMETER_EXCEPTION = 301;
	
	// restriction exception
	const MALFORMED_INTERVAL_EXCEPTION           = 401;
	const NOT_SUPPORTED_MODEL_INTERVAL_EXCEPTION = 402;
	const NOT_EXISTING_REGEX_EXCEPTION           = 403;
	
	// manifest exception
	const RESERVED_WORD_EXCEPTION = 501;
	
	// database exception
	const NOT_SUPPORTED_DBMS_EXCEPTION            = 601;
	const QUERY_EXECUTION_FAILURE_EXCEPTION       = 602;
	const QUERY_BINDING_VALUE_FAILURE_EXCEPTION   = 603;
	const UNEXPECTED_COUNT_VALUES_QUERY_EXCEPTION = 604;
	const INCOMPLETE_SQL_DB_INFOS_EXCEPTION       = 605;
	
	// request exception
	const MALFORMED_REQUEST_EXCEPTION                  = 700;
	const LITERAL_NOT_FOUND_EXCEPTION                  = 701;
	const INCOMPATIBLE_LITERAL_SERIALIZATION_EXCEPTION = 702;
	const LITERAL_PROPERTY_AGGREGATION_EXCEPTION       = 703;
	const MALFORMED_LITERAL_EXCEPTION                  = 704;
	const UNRESOLVABLE_LITERAL_EXCEPTION               = 705;
	const NOT_LINKABLE_LITERAL_EXCEPTION               = 706;
	const NOT_ALLOWED_REQUEST_EXCEPTION                = 707;
	
}