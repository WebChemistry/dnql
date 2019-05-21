<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Mapping;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Utility\PersisterHelper;

final class FieldMapping {

	/** @var ResultSetMapping */
	private $rsm;

	/** @var EntityManagerInterface */
	private $em;

	/** @var mixed[] */
	private $mapping = [];

	/** @var mixed[] */
	private $columnMapping = [];

	/** @var string[] */
	private $discriminators = [];

	public function __construct(EntityManagerInterface $em, ResultSetMapping $rsm) {
		$this->rsm = $rsm;
		$this->em = $em;
	}

	public function getRsm(): ResultSetMapping {
		return $this->rsm;
	}

	protected function getClassMetadata(string $class): ClassMetadata {
		return $this->em->getClassMetadata($class);
	}

	public function getColumnAliasByColumn(string $alias, string $column): ?string {
		return $this->mapping[$alias][$column] ?? null;
	}

	public function getColumn(string $entityAlias, string $field): ?string {
		return $this->mapping[$entityAlias][$field] ?? null;
	}

	public function getMappingByAlias(string $alias): ?array {
		return $this->mapping[$alias] ?? null;
	}

	public function getColumnMapping(string $alias): ?array {
		return $this->columnMapping[$alias] ?? null;
	}

	public function getDiscriminator(string $alias): ?string {
		return $this->discriminators[$alias] ?? null;
	}

	public function addEntityResult(string $class, string $alias): self {
		$this->rsm->addEntityResult($class, $alias);
		$metadata = $this->getClassMetadata($class);
		$this->addAllClassFields($alias, $metadata);

		return $this;
	}

	public function addJoinedEntityResult(string $class, string $alias, string $parentAlias, string $relation): self {
		$this->rsm->addJoinedEntityResult($class, $alias, $parentAlias, $relation);
		$metadata = $this->getClassMetadata($class);
		$this->addAllClassFields($alias, $metadata);

		return $this;
	}

	protected function addAllClassFields(string $alias, ClassMetadata $metadata): void {
		$classes = $metadata->subClasses;
		$classes = array_reverse($classes);
		$classes[] = $metadata->getName();

		foreach ($classes as $class) {
			$meta = $this->getClassMetadata($class);
			foreach ($meta->fieldMappings as $mapping) {
				$columnAlias = $this->generateColumnAlias($mapping['columnName'], $alias);
				$this->mapping[$alias][$mapping['fieldName']] = $columnAlias;
				$this->columnMapping[$alias][$mapping['columnName']] = $columnAlias;

				$this->rsm->addFieldResult($alias, $columnAlias, $mapping['fieldName'], $class);
			}

			foreach ($meta->associationMappings as $mapping) {
				if ($mapping['isOwningSide'] && $mapping['type'] & ClassMetadataInfo::TO_ONE) {
					$target = $this->getClassMetadata($mapping['targetEntity']);
					$isIdentifier = isset($mapping['id']) && $mapping['id'] === true;

					foreach ($mapping['joinColumns'] as $joinColumn) {
						$columnName  = $joinColumn['name'];
						$columnAlias = $this->generateColumnAlias($columnName, $alias);
						$columnType = PersisterHelper::getTypeOfColumn($joinColumn['referencedColumnName'], $target, $this->em);

						$this->mapping[$alias][$mapping['fieldName']] = $columnAlias;
						$this->columnMapping[$alias][$columnName] = $columnAlias;
						$this->rsm->addMetaResult($alias, $columnAlias, $columnName, $isIdentifier, $columnType);
					}
				}
			}
		}

		if ($mapping = $metadata->discriminatorColumn) {
			$this->discriminators[$alias] = $columnAlias = $this->generateColumnAlias($mapping['name'], $alias);

			$this->columnMapping[$alias][$mapping['name']] = $columnAlias;
			$this->rsm->setDiscriminatorColumn($alias, $columnAlias);
			$this->rsm->addMetaResult($alias, $columnAlias, $mapping['fieldName'], false, $mapping['type']);
		}
	}

	protected function generateColumnAlias(string $column, string $entityAlias): string {
		$platform = $this->em->getConnection()->getDatabasePlatform();

		return $platform->getSQLResultCasing($entityAlias . '_' . $column);
	}

}
