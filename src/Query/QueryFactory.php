<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Query;

use Doctrine\ORM\EntityManagerInterface;
use WebChemistry\DNQL\Converter\ConverterFactory;

final class QueryFactory implements IQueryFactory {

	/** @var ConverterFactory */
	private $converterFactory;

	/** @var EntityManagerInterface */
	private $em;

	/** @var string */
	private $queryBuilder;

	public function __construct(string $queryBuilder, ConverterFactory $converterFactory, EntityManagerInterface $em) {
		$this->converterFactory = $converterFactory;
		$this->em = $em;
		$this->queryBuilder = $queryBuilder;
	}

	public function createQuery(string $dqnl, iterable $parameters): Query {
		return new Query($dqnl, $this->em, $this->converterFactory, $parameters);
	}

	public function createQueryBuilder(string $from, string $alias): IQueryBuilder {
		$class = $this->queryBuilder;
		$qb = new $class($this->converterFactory, $this->em);
		$qb->from($from, $alias);
		$qb->select($alias);

		return $qb;
	}

}
