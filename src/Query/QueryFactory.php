<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Query;

use Doctrine\ORM\EntityManagerInterface;
use WebChemistry\DNQL\Converter\ConverterFactory;

final class QueryFactory implements IQueryFactory {

	/** @var ConverterFactory */
	private $converterFactory;

	/** @var EntityManagerInterface */
	private $em;

	public function __construct(ConverterFactory $converterFactory, EntityManagerInterface $em) {
		$this->converterFactory = $converterFactory;
		$this->em = $em;
	}

	public function createQuery(string $dqnl, iterable $parameters): Query {
		return new Query($dqnl, $this->em, $this->converterFactory, $parameters);
	}

	public function createQueryBuilder(string $from, string $alias): QueryBuilder {
		$qb = new QueryBuilder($this->converterFactory, $this->em);
		$qb->from($from, $alias);

		return $qb;
	}

}
