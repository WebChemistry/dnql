<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Query\Parts;

class Select {

	/** @var string */
	private $select;

	public function __construct(string $select) {
		$this->select = $select;
	}

	public function __toString(): string {
		return $this->select;
	}

}
