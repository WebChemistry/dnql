<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Query;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\ResultSetMapping;
use WebChemistry\DNQL\Converter\ConverterFactory;
use WebChemistry\DNQL\Converter\ConverterResult;

class Query {

	/** @var ConverterFactory */
	private $converterFactory;

	/** @var iterable */
	private $parameters;

	/** @var string */
	private $dnql;

	/** @var ConverterResult */
	private $result;

	/** @var EntityManagerInterface */
	private $em;

	public function __construct(string $dnql, EntityManagerInterface $em, ConverterFactory $converterFactory, iterable $parameters = []) {
		$this->em = $em;
		$this->converterFactory = $converterFactory;
		$this->parameters = $parameters;
		$this->dnql = $dnql;
	}

	protected function parse(): void {
		if (!$this->result) {
			$this->result = $this->converterFactory->create($this->dnql);
		}
	}

	public function getDNQL(): string {
		return $this->dnql;
	}

	public function getSQL(): string {
		$this->parse();

		return $this->result->sql;
	}

	public function getRSM(): ResultSetMapping {
		$this->parse();

		return $this->result->rsm;
	}

	public function getParameters(): ArrayCollection {
		if (is_array($this->parameters)) {
			$parameters = new ArrayCollection();

			foreach ($this->parameters as $key => $value) {
				$parameters->add(new Parameter($key, $value));
			}

			$this->parameters = $parameters;
		}

		return $this->parameters;
	}

	public function getResult() {
		return $this->createNativeQuery()->getResult();
	}

	public function getArrayResult() {
		return $this->createNativeQuery()->getArrayResult();
	}

	public function getSingleScalarResult() {
		return $this->createNativeQuery()->getSingleScalarResult();
	}

	public function createNativeQuery(): NativeQuery {
		return $this->em->createNativeQuery($this->getSQL(), $this->getRSM())
			->setParameters($this->getParameters());
	}

}
