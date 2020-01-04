<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Converter\Results;

class ColumnResult {

	/** @var string */
	public $alias;

	/** @var string */
	public $query;

	/** @var string */
	public $tableAlias;

	/** @var string */
	public $column;

	public function __construct(string $tableAlias, string $column, string $columnAlias) {
		$this->alias = $columnAlias;
		$this->query = $tableAlias . '.' . $column;
		$this->tableAlias = $tableAlias;
		$this->column = $column;
	}

}
