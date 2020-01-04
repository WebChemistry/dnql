<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Query\Parts;

class HiddenCollection implements ICollection {

	/** @var array */
	protected $parts = [];


	public function has(): bool {
		return (bool) $this->parts;
	}

	public function add(string $part, string $name) {
		$this->parts[$name] = $part;

		return $this;
	}

	public function __toString(): string {
		$sql = '';
		foreach ($this->parts as $name => $expression) {
			$sql .= "HIDDEN($expression) $name, ";
		}

		return substr($sql, 0, -2);
	}


}
