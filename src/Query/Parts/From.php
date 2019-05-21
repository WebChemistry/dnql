<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Query\Parts;

class From {

	/** @var string */
	private $expression;

	/** @var string */
	private $alias;

	public function __construct(string $expression, string $alias) {
		$this->expression = $expression;
		$this->alias = $alias;
	}

	public function __toString(): string {
		return $this->expression . ' ' . $this->alias;
	}

}
