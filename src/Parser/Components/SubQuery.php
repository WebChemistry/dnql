<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Parser\Components;

use PhpMyAdmin\SqlParser\Statements\SelectStatement;

class SubQuery {

	/** @var SelectStatement */
	private $stmt;

	public function __construct(SelectStatement $stmt) {
		$this->stmt = $stmt;
	}

	/**
	 * @return SelectStatement
	 */
	public function getStmt(): SelectStatement {
		return $this->stmt;
	}

	public static function build(SubQuery $subQuery): string {
		return '(' . $subQuery->getStmt()->build() . ')';
	}
	
}
