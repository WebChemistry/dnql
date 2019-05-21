<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Query\Parts;

class Where {

	const AND = 'AND';
	const OR = 'OR';

	/** @var string */
	private $type;

	/** @var string */
	private $expression;

	public function __construct(string $expression, string $type = self::AND) {
		$this->type = $type;
		$this->expression = $expression;
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	public function __toString() {
		return $this->expression;
	}

}
