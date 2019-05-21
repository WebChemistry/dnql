<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Query\Parts;

class JoinCollection implements ICollection {

	/** @var array */
	protected $parts = [];

	public function has(): bool {
		return (bool) $this->parts;
	}

	public function clean() {
		$this->parts = [];

		return $this;
	}

	public function add(string $type = 'LEFT', string $column, string $alias) {
		$this->parts[$alias] = [$type, $column];

		return $this;
	}

	public function __toString() {
		$dql = '';
		foreach ($this->parts as $alias => [$type, $column]) {
			$dql .= strtoupper($type) . ' JOIN ' . "$column $alias ";
		}

		return substr($dql, 0, -1);
	}

}
