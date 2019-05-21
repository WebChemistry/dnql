<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Metadata;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class ClassMetadata {

	/** @var \Doctrine\ORM\Mapping\ClassMetadata */
	private $classMetadata;

	/** @var EntityManagerInterface */
	private $em;

	public function __construct(EntityManagerInterface $em, \Doctrine\ORM\Mapping\ClassMetadata $classMetadata) {
		$this->classMetadata = $classMetadata;
		$this->em = $em;
	}

	public function getDiscriminatorColumn(): ?string {
		if (!$this->classMetadata->discriminatorColumn) {
			return null;
		}

		$mapping = $this->classMetadata->discriminatorColumn;
		return $mapping['name'];
	}

	public function getAllFieldMappings(): array {
		$subClasses = $this->classMetadata->subClasses;
		array_unshift($subClasses, $this->classMetadata->getName());
		$mappings = [];
		foreach ($subClasses as $class) {
			$metadata = $this->em->getClassMetadata($class);
			$mapping = $metadata->fieldMappings;
			foreach ($mapping as &$data) {
				$data['_entity'] = $metadata->getName();
			}
			$mappings = array_merge($mapping, $mappings);
		}

		return $mappings;
	}

	public function getAllManyToOne(): array {
		$mappings = [];
		foreach ($this->classMetadata->associationMappings as $index => $mapping) {
			if ($mapping['type'] === ClassMetadataInfo::MANY_TO_ONE) {
				$mappings[$index] = $mapping;
			}
		}

		foreach ($this->classMetadata->subClasses as $class) {
			$metadata = $this->em->getClassMetadata($class);
			foreach ($metadata->associationMappings as $index => $mapping) {
				if ($mapping['type'] === ClassMetadataInfo::MANY_TO_ONE) {
					$mappings[$index] = $mapping;
				}
			}
		}

		return $mappings;
	}

	public function getName(): string {
		return $this->classMetadata->getName();
	}

	public function getParent() {
		return $this->classMetadata;
	}

	public function validateColumnExists(string $field): void {
		if (!isset($this->classMetadata->fieldMappings[$field]) && !isset($this->classMetadata->associationMappings[$field])) {
			throw new DoctrineException("Field $field not exists in {$this->classMetadata->name}");
		}
	}

	public function validateAssociation(string $field): void {
		if (!isset($this->classMetadata->associationMappings[$field])) {
			throw new DoctrineException("Field $field is not an association");
		}
	}

	public function getAssociationMapping(string $field): array {
		return $this->classMetadata->associationMappings[$field];
	}

}
