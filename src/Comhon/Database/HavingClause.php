<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Database;

use Comhon\Logic\Clause;
use Comhon\Exception\Literal\MalformedLiteralException;

class HavingClause extends Clause {
	
	/**
	 * build instance of HavingClause 
	 * 
	 * @param \stdClass $stdObject
	 * @param TableNode|string $firstTable table to link with literals with function HavingLiteral::COUNT
	 * @param TableNode|string $lastTable table to link with literals with other function than HavingLiteral::COUNT
	 * @param \Comhon\Model\Model $lastModel model linked to $lastTable
	 * @param boolean $allowPrivateProperties
	 * @throws \Exception
	 * @return HavingClause
	 */
	public static function stdObjectToHavingClause($stdObject, $firstTable, $lastTable, $lastModel, $allowPrivateProperties) {
		if (!is_object($stdObject) || !isset($stdObject->type) || (isset($stdObject->elements) && !is_array($stdObject->elements))) {
			throw new MalformedLiteralException($stdObject);
		}
		$clause = new HavingClause($stdObject->type);
		if (isset($stdObject->elements)) {
			foreach ($stdObject->elements as $stdObjectElement) {
				if (isset($stdObjectElement->type)) { // clause
					$clause->addClause(self::stdObjectToHavingClause($stdObjectElement, $firstTable, $lastTable, $lastModel, $allowPrivateProperties));
				} else { // literal
					// table is not used anymore for function "COUNT" because we now use COUNT(*) instead of COUNT(table.column)
					// but we keep condition just in case
					$table = isset($stdObjectElement->function) && ($stdObjectElement->function == HavingLiteral::COUNT) ? $firstTable : $lastTable;
					$clause->addLiteral(HavingLiteral::stdObjectToHavingLiteral($stdObjectElement, $table, $lastModel, $allowPrivateProperties));
				}
			}
		}
		return $clause;
	}
	
}