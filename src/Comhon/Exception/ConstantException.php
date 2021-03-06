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
	const UNDEFINED_PROPERTY_EXCEPTION      = 103;
	const CAST_EXCEPTION                    = 104;
	const PROPERTY_VISIBILITY_EXCEPTION     = 105;
	const NO_ID_PROPERTY_EXCEPTION          = 106;
	const REQUESTABLE_PROPERTY_EXCEPTION    = 107;
	const CACHE_EXCEPTION                   = 108;
	
	// object/value exception
	const NOT_SATISFIED_RESTRICTION_EXCEPTION = 200;
	const ENUMERATION_EXCEPTION               = 201;
	const UNEXPECTED_VALUE_TYPE_EXCEPTION     = 202;
	const ABSTRACT_OBJECT_EXCEPTION           = 203;
	const MISSING_REQUIRED_VALUE_EXCEPTION    = 204;
	const MISSING_ID_FOREIGN_VALUE_EXCEPTION  = 205;
	const INVALID_COMPOSITE_ID_EXCEPTION      = 206;
	const CONFLICT_VALUES_EXCEPTION           = 207;
	const DEPENDS_VALUES_EXCEPTION            = 208;
	
	// interfacing exception
	const DUPLICATED_ID_EXCEPTION          = 300;
	const NOT_REFERENCED_VALUE_EXCEPTION   = 301;
	const CONTEXT_ID_EXCEPTION             = 302;
	const OBJECT_LOOP_EXCEPTION            = 303;
	const ABSTRACT_OBJECT_EXPORT_EXCEPTION = 304;
	
	// restriction exception
	const MALFORMED_INTERVAL_EXCEPTION           = 400;
	const NOT_SUPPORTED_MODEL_INTERVAL_EXCEPTION = 401;
	const NOT_EXISTING_REGEX_EXCEPTION           = 402;
	
	// manifest exception
	const MANIFEST_EXCEPTION      = 500;
	const RESERVED_WORD_EXCEPTION = 501;
	
	// database exception
	const NOT_SUPPORTED_DBMS_EXCEPTION            = 600;
	const QUERY_EXECUTION_FAILURE_EXCEPTION       = 601;
	const QUERY_BINDING_VALUE_FAILURE_EXCEPTION   = 602;
	const UNEXPECTED_COUNT_VALUES_QUERY_EXCEPTION = 603;
	const INCOMPLETE_SQL_DB_INFOS_EXCEPTION       = 604;
	const DUPLICATED_TABLE_NAME_EXCEPTION         = 605;
	
	// request exception
	const MALFORMED_REQUEST_EXCEPTION                  = 700;
	const LITERAL_NOT_FOUND_EXCEPTION                  = 701;
	const INCOMPATIBLE_LITERAL_SERIALIZATION_EXCEPTION = 702;
	const LITERAL_PROPERTY_AGGREGATION_EXCEPTION       = 703;
	const MALFORMED_LITERAL_EXCEPTION                  = 704;
	const UNRESOLVABLE_LITERAL_EXCEPTION               = 705;
	const NOT_LINKABLE_LITERAL_EXCEPTION               = 706;
	const NOT_ALLOWED_REQUEST_EXCEPTION                = 707;
	const MULTIPLE_PROPERTY_LITERAL_EXCEPTION          = 708;
	const NOT_ALLOWED_LITERAL_EXCEPTION                = 709;
	
	// serialization exception
	const SERIALIZATION_EXCEPTION          = 800;
	const NOT_NULL_CONSTRAINT_EXCEPTION    = 801;
	const FOREIGN_CONSTRAINT_EXCEPTION     = 802;
	const UNIQUE_CONSTRAINT_EXCEPTION      = 803;
	const MANIFEST_SERIALIZATION_EXCEPTION = 804;
	const MISSING_NOT_NULL_EXCEPTION       = 805;
	
	// config
	const CONFIG_NOT_FOUND_EXCEPTION = 900;
	const CONFIG_MALFORMED_EXCEPTION = 901;
	
}