<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Parser\Components;

use PhpMyAdmin\SqlParser\Statements\SelectStatement;

class SubQuery {

	/** @var SelectStatement */
	public $stmt;

	/** @var string|null */
	public $alias;

	/** @var bool */
	public $hidden;

	public function __construct(SelectStatement $stmt, ?string $alias, bool $hidden) {
		$this->stmt = $stmt;
		$this->alias = $alias;
		$this->hidden = $hidden;
	}

	public static function build(SubQuery $subQuery): string {
		return '(' . $subQuery->stmt->build() . ')';
	}
	
}
