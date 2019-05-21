<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Query\Parts;

class WhereCollection implements ICollection {

	/** @var Where[] */
	protected $parts = [];

	public function clean() {
		$this->parts = [];

		return $this;
	}

	public function add(Where $where) {
		$this->parts[] = $where;

		return $this;
	}

	public function has(): bool {
		return (bool) $this->parts;
	}

	public function __toString(): string {
		$or = [];
		$and = [];
		foreach ($this->parts as $part) {
			if ($part->getType() === 'OR') {
				$or[] = $part;
			} else {
				$and[] = $part;
			}
		}

		$and = implode(' AND ', $and);
		$or = implode(' OR ', $or);
		if ($and && $or) {
			return '(' . $and . ') OR (' . $or . ')';
		} else if ($and) {
			return $and;
		} else {
			return $or;
		}

		$first = true;
		$ret = '';
		foreach ($this->parts as $part) {
			if (!$first) {
				$ret .= ' ' . $part->getType() . ' ';
			}
			$first = false;

			$ret .= $part;
		}
		return $ret;
	}

}
