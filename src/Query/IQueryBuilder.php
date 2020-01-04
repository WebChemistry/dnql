<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Query;

interface IQueryBuilder {

	/**
	 * @return static
	 */
	public function create();

	/**
	 * @return static
	 */
	public function setMaxResults(?int $maxResults);

	public function setOffset(?int $offset);

	public function setParameter($name, $value);

	public function setParameters(iterable $parameters);

	public function addParameters(array $parameters);

	public function getParameters(): array;

	/**
	 * @return static
	 */
	public function select($select, array $params = []);

	/**
	 * @return static
	 */
	public function addSelect($select, array $params = []);

	/**
	 * @return static
	 */
	public function from(string $expression, string $alias, array $params = []);

	/**
	 * @return static
	 */
	public function leftJoin(string $column, string $alias);

	/**
	 * @return static
	 */
	public function orderBy(string $column, string $type = 'ASC');

	/**
	 * @return static
	 */
	public function addOrderBy(string $column, string $type = 'ASC');

	/**
	 * @return static
	 */
	public function groupBy(string $expression);

	/**
	 * @return static
	 */
	public function addGroupBy(string $expression);

	/**
	 * @return static
	 */
	public function where($expression);

	/**
	 * @return static
	 */
	public function andWhere($expression);

	/**
	 * @return static
	 */
	public function orWhere($expression);

	public function getQuery(): Query;

}
