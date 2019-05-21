<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Mapping;

use LogicException;

final class EntityMapping {

	/** @var string[] */
	private $entities;

	/** @var string[] */
	private $mappings = [];

	public function __construct(array $entities = []) {
		$this->entities = $entities;
	}

	public function addAutoMapping(string $pattern) {
		$this->mappings[] = $pattern;

		return $this;
	}

	public function getEntityClass(string $shortName): string {
		if (isset($this->entities[$shortName])) {
			return $this->entities[$shortName];
		}

		foreach ($this->mappings as $mapping) {
			$class = str_replace('*', $shortName, $mapping);
			if (class_exists($class)) {
				return $class;
			}
		}

		throw new LogicException('Entity mapping not exists for ' . $shortName);
	}

}
