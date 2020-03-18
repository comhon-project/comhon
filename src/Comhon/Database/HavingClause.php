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
use Comhon\Object\UniqueObject;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\ArgumentException;

class HavingClause extends Clause {
	
	/**
	 * build instance of HavingClause 
	 * 
	 * @param \Comhon\Object\UniqueObject $havingClause
	 * @param TableNode|string $firstTable table to link with literals with function HavingLiteral::COUNT
	 * @param TableNode|string $lastTable table to link with literals with other function than HavingLiteral::COUNT
	 * @param \Comhon\Model\Model $lastModel model linked to $lastTable
	 * @throws \Exception
	 * @return HavingClause
	 */
	public static function buildHaving(UniqueObject $havingClause, $firstTable, $lastTable, $lastModel) {
		if ($havingClause->getModel()->getName() != 'Comhon\Logic\Having\Clause') {
			$clauseModel = ModelManager::getInstance()->getInstanceModel('Comhon\Logic\Having\Clause');
			throw new ArgumentException($havingClause, $clauseModel->getObjectInstance(false)->getComhonClass(), 1);
		}
		$havingClause->validate();
		$clause = new HavingClause($havingClause->getValue('type'));
		
		/** @var \Comhon\Object\UniqueObject $element */
		foreach ($havingClause->getValue('elements') as $element) {
			if ($element->getModel()->getName() == 'Comhon\Logic\Having\Clause') { // clause
				$clause->addClause(self::buildHaving($element, $firstTable, $lastTable, $lastModel));
			} else { // literal
				// table is not used anymore for function "COUNT" because we now use COUNT(*) instead of COUNT(table.column)
				// but we keep condition just in case
				$table = $element->getModel()->getName() == 'Comhon\Logic\Having\Literal\Count' ? $firstTable : $lastTable;
				$clause->addLiteral(HavingLiteral::buildHaving($element, $table, $lastModel));
			}
		}
		return $clause;
	}
	
}