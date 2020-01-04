<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Bridges\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use WebChemistry\DNQL\Query\IQueryBuilder;
use WebChemistry\Filter\DataSource\IDataSource;

class DNQLDataSource implements IDataSource {

	/** @var IQueryBuilder */
	private $queryBuilder;

	/** @var EntityManagerInterface */
	private $em;

	public function __construct(IQueryBuilder $queryBuilder, EntityManagerInterface $em) {
		$this->em = $em;
		$this->queryBuilder = $queryBuilder;
	}

	public function getItemCount(): int {
		$rsm = new ResultSetMapping();
		$rsm->addScalarResult('cnt', 'cnt', 'integer');
		$query = $this->queryBuilder->getQuery();
		$sql = 'SELECT COUNT(*) AS cnt FROM (' . $query->getSQL() . ') xc';

		$result = $this->em->createNativeQuery($sql, $rsm)->setParameters($query->getParameters())->getSingleScalarResult();

		return (int) $result;
	}

	public function getData(?int $limit, ?int $offset): iterable {
		return $this->queryBuilder->setMaxResults($limit)->setOffset($offset)->getQuery()->getResult();
	}

}
