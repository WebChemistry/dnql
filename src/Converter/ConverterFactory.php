<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Converter;

use Doctrine\ORM\EntityManagerInterface;
use WebChemistry\DNQL\Mapping\EntityMapping;

class ConverterFactory {

	/** @var EntityManagerInterface */
	private $em;

	/** @var EntityMapping */
	private $entityMapping;

	public function __construct(EntityManagerInterface $em, EntityMapping $entityMapping) {
		$this->em = $em;
		$this->entityMapping = $entityMapping;
	}

	public function create(string $dnql): ConverterResult {
		$converter = new Converter($this->em, $this->entityMapping, $this);

		return $converter->convert($dnql);
	}

}
