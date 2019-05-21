<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Query;

use Doctrine\ORM\EntityManagerInterface;
use ProLib\QuestionMarkReplacer;
use WebChemistry\DNQL\Converter\ConverterFactory;
use WebChemistry\DNQL\Query\Parts\From;
use WebChemistry\DNQL\Query\Parts\ICollection;
use WebChemistry\DNQL\Query\Parts\JoinCollection;
use WebChemistry\DNQL\Query\Parts\StringCollection;
use WebChemistry\DNQL\Query\Parts\Where;
use WebChemistry\DNQL\Query\Parts\WhereCollection;

final class QueryBuilder {

	/** @var array */
	protected $parts = [
		'select' => [],
		'where' => [],
		'from' => null,
		'order' => [],
		'group' => [],
		'join' => [],
	];

	/** @var array */
	protected $parameters = [];

	/** @var int|null */
	protected $maxResults;

	/** @var int|null */
	protected $offset;

	/** @var ConverterFactory */
	private $converterFactory;

	/** @var EntityManagerInterface */
	private $em;

	public function __construct(ConverterFactory $converterFactory, EntityManagerInterface $em) {
		$this->parts = [
			'select' => new StringCollection(),
			'where' => new WhereCollection(),
			'from' => null,
			'order' => new StringCollection(),
			'group' => new StringCollection(),
			'join' => new JoinCollection(),
		];
		$this->converterFactory = $converterFactory;
		$this->em = $em;
	}

	public function create() {
		return new static($this->converterFactory, $this->em);
	}

	public function setMaxResults(?int $maxResults) {
		$this->maxResults = $maxResults;

		return $this;
	}

	public function setOffset(?int $offset) {
		$this->offset = $offset;

		return $this;
	}

	protected function toString(&$expression) {
		if ($expression instanceof self) {
			$expression = '(' . $expression . ')';
		} else if (!is_string($expression)) {
			$expression = (string) $expression;
		}

		return $expression;
	}

	public function setParameter($name, $value) {
		$this->parameters[$name] = $value;

		return $this;
	}

	public function setParameters(iterable $parameters) {
		$this->parameters = $parameters;

		return $this;
	}

	public function addParameters(array $parameters) {
		$this->parameters = array_merge($this->parameters, $parameters);

		return $this;
	}

	public function getParameters(): array {
		return $this->parameters;
	}

	protected function parseParams(string $expression, array $params): string {
		foreach ($params as &$param) {
			if ($param instanceof self) {
				$this->addParameters($param->getParameters());
				$param = '(' . (string) $param . ')';
			}
		}

		return QuestionMarkReplacer::replace($expression, $params);
	}

	public function select($select, array $params = []) {
		$this->parts['select']->clean();
		$this->addSelect($select, $params);

		return $this;
	}

	public function addSelect($select, array $params = []) {
		$this->parts['select']->add($this->parseParams($select, $params));

		return $this;
	}

	public function from(string $expression, string $alias, array $params = []) {
		$this->parts['from'] = new From($this->parseParams($expression, $params), $alias);

		return $this;
	}

	public function leftJoin(string $column, string $alias) {
		$this->parts['join']->add('LEFT', $column, $alias);

		return $this;
	}

	public function orderBy(string $column, string $type = 'ASC') {
		$this->parts['order']->clean();
		$this->addOrderBy($column, $type);

		return $this;
	}

	public function addOrderBy(string $column, string $type = 'ASC') {
		$this->parts['order']->add($column . ' ' . $type);

		return $this;
	}

	public function groupBy(string $expression) {
		$this->parts['group']->clean();
		$this->addGroupBy($expression);

		return $this;
	}

	public function addGroupBy(string $expression) {
		$this->parts['group']->add($expression);

		return $this;
	}

	public function where($expression) {
		$this->parts['where']->clean();
		$this->andWhere($expression);

		return $this;
	}

	public function andWhere($expression) {
		$this->parts['where']->add(new Where($expression));

		return $this;
	}

	public function orWhere($expression) {
		$this->parts['where']->add(new Where($expression, 'OR'));

		return $this;
	}

	protected function buildPart(string $type, ?string $stmt): string {
		$parts = $this->parts[$type];
		if (!$parts) {
			return '';
		}
		if ($parts instanceof ICollection && !$parts->has()) {
			return '';
		}
		return ($stmt !== null ? $stmt . ' ' : '') . $this->parts[$type] . ' ';
	}

	public function getQuery(): Query {
		return new Query($this->selfToString(), $this->em, $this->converterFactory, $this->parameters);
	}

	public function __toString(): string {
		return $this->selfToString();
	}

	protected function selfToString(): string {
		$sql = $this->buildPart('select', 'SELECT');
		$sql .= $this->buildPart('from', 'FROM');
		$sql .= $this->buildPart('join', null);
		$sql .= $this->buildPart('where', 'WHERE');
		$sql .= $this->buildPart('group', 'GROUP BY');
		$sql .= $this->buildPart('order', 'ORDER BY');

		if ($this->maxResults) {
			$sql .= 'LIMIT ' . $this->maxResults . ' ';
		}
		if ($this->offset) {
			$sql .= 'OFFSET ' . $this->offset . ' ';
		}

		return substr($sql, 0, -1);
	}

}
