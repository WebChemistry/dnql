<?php

namespace WebChemistry\DNQL\Query;

interface IQueryFactory {

	public function createQuery(string $dqnl, iterable $parameters): Query;

	public function createQueryBuilder(string $from, string $alias): QueryBuilder;

}
