<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Query\Parts;

class StringCollection implements ICollection {

	/** @var array */
	protected $parts = [];

	/** @var string */
	private $separator;

	public function __construct(string $separator = ', ') {
		$this->separator = $separator;
	}

	public function has(): bool {
		return (bool) $this->parts;
	}

	public function clean() {
		$this->parts = [];

		return $this;
	}

	public function add(string $part) {
		$this->parts[] = $part;

		return $this;
	}

	public function __toString(): string {
		return implode($this->separator, $this->parts);
	}

}
