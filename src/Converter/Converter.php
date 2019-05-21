<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Converter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Components\Expression;
use WebChemistry\DNQL\Converter\Results\ColumnResult;
use WebChemistry\DNQL\Mapping\FieldMapping;
use WebChemistry\DNQL\Metadata\ClassMetadata;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use WebChemistry\DNQL\Mapping\EntityMapping;
use WebChemistry\DNQL\Parser\Components\RuntimeFunction;
use WebChemistry\DNQL\Parser\Components\SubQuery;
use WebChemistry\DNQL\Parser\Components\Variable;
use WebChemistry\DNQL\Parser\KeywordParsers\JoinKeyword;
use WebChemistry\DNQL\Parser\Parser;

final class Converter {

	/** @var EntityManagerInterface */
	private $em;

	/** @var ConverterFactory */
	private $factory;

	/** @var SelectStatement */
	protected $stmt;

	/** @var EntityMapping */
	private $entityMapping;

	/** @var ClassMetadata[] */
	protected $mapping = [];

	/** @var string[] */
	protected $mappingReversed = [];

	/** @var FieldMapping */
	protected $fieldMapping;

	/** @var ResultSetMapping */
	protected $rsm;

	/** @var ClassMetadata[] */
	protected $metadataCache;

	/** @var string */
	protected $dnql;

	/** @var callable[] */
	protected $functions = [];

	public function __construct(EntityManagerInterface $em, EntityMapping $entityMapping, ConverterFactory $factory) {
		$this->em = $em;
		$this->entityMapping = $entityMapping;
		$this->factory = $factory;
		$this->functions = [
			'discriminator' => function (string $argument) {
				if (!isset($this->mapping[$argument])) {
					throw new ConverterException("Cannot resolve type of $argument");
				}

				return $argument . '.' . $this->mapping[$argument]->getParent()->discriminatorColumn['name'];
			},
			'instanceOf' => function (string $argument) {
				$metadata = $this->getClassMetadata($this->entityMapping->getEntityClass($argument))->getParent();
				$subClasses = $metadata->subClasses;
				array_unshift($subClasses, $metadata->getName());
				$types = [];
				foreach ($subClasses as $class) {
					$meta = $this->em->getClassMetadata($class);
					$types[] = $meta->discriminatorValue;
				}

				return 'IN(' . implode(',', $types) . ')';
			},
		];
	}

	public function convert(string $dnql): ConverterResult {
		$parser = new Parser($dnql);

		return $this->convertStatement($dnql, $parser->getStmt());
	}

	protected function convertStatement(string $dnql, SelectStatement $statement, array $mapping = []): ConverterResult {
		$this->stmt = $statement;
		$this->dnql = $dnql;
		$this->fieldMapping = new FieldMapping($this->em, $this->rsm = new ResultSetMapping());

		$this->mapping = array_merge($mapping, $this->getEntityMapping());

		$this->processSelect();

		$this->processJoins();
		$this->processWhere();
		$this->processOrder();

		return new ConverterResult($this->stmt->build(), $this->fieldMapping->getRsm());
	}

	protected function newInstance(): Converter {
		return new static($this->em, $this->entityMapping, $this->factory);
	}

	private function processOrder(): void {
		foreach ((array) $this->stmt->order as &$orderKeyword) {
			$orderKeyword->expr->expr = $this->convertStringToColumn($orderKeyword->expr->expr, $orderKeyword);
		}
	}

	private function processWhere(): void {
		foreach ((array) $this->stmt->where as &$condition) {
			if ($condition->isOperator) {
				continue;
			}

			$condition->expr = $this->convertStringToColumn($condition->expr, $condition);
			$condition->expr = $this->findAndConvertRuntimeFunctions($condition->expr, $condition);
		}
	}

	private function findAndConvertRuntimeFunctions(string $query, Component $component): string {
		return preg_replace_callback('#%(\w+)\((.+?)\)#', function (array $matches) use ($component) {
			[,$function, $arguments] = $matches;

			try {
				if (!isset($this->functions[$function])) {
					throw new ConverterException("Function %$function not exists", $this->dnql, $component);
				}
				return $this->functions[$function]($arguments);
			} catch (ConverterException $e) {
				throw new ConverterException($e->getMessage(), $this->dnql, $component);
			}
		}, $query);
	}

	private function convertRuntimeFunction(RuntimeFunction $runtimeFunction): string {
		$function = $runtimeFunction->functionName;
		$arguments = $runtimeFunction->expression->expr;

		try {
			if (!isset($this->functions[$function])) {
				throw new ConverterException("Function %$function not exists", $this->dnql);
			}
			return $this->functions[$function]($arguments);
		} catch (ConverterException $e) {
			throw new ConverterException($e->getMessage(), $this->dnql);
		}
	}

	private function convertStringToColumn(string $query, Component $component): string {
		return preg_replace_callback('#([\w]+)\.([\w]+)#', function (array $matches) use ($component) {
			[,$alias,$field] = $matches;
			if (!isset($this->mapping[$alias])) {
				throw new ConverterException("Alias $alias ($alias.$field) not exists", $this->dnql, $component);
			}
			$metadata = $this->mapping[$alias];
			$metadata->validateColumnExists($field);

			$column = $metadata->getParent()->fieldMappings[$field]['columnName'];

			return $alias . '.' . $column;
		}, $query);
	}

	private function processSelect(): void {
		$array = [];

		foreach ($this->stmt->expr as $index => $expression) {
			if ($expression instanceof Variable) {
				throw new ConverterException('Variables are not allowed in SELECT');
			}

			if ($expression instanceof RuntimeFunction) {
				$column = $expression->expression->expr;
				switch ($expression->functionName) {
					case 'string':
						$this->rsm->addScalarResult($column, $column, 'string');
						break;
					case 'int':
						$this->rsm->addScalarResult($column, $column, 'integer');
						break;
					case 'bool':
						$this->rsm->addScalarResult($column, $column, 'bool');
						break;
					case 'discriminator':
						$full = $this->functions['discriminator']($column);
						[$table, $column] = explode('.', $full);

						$expression = new Expression(null, $table, $column, $this->fieldMapping->getDiscriminator($table));
						break;
					default:
						throw new ConverterException("Function %{$expression->functionName} not exists", $this->dnql, $expression->expression);
				}

				$array[] = $expression;

				continue;
			}

			// select all
			if (!$expression->function && $expression->table === null && isset($this->mapping[$expression->column])) {
				foreach ($this->selectAll($expression->column) as $result) {
					$array[] = new Expression(null, $expression->column, $result->column, $result->alias);
				}

				continue;
			}

			// select alias(...)
			if ($expression->function && isset($this->mapping[$expression->function])) {
				$argument = Helper::removeBrackets($expression->expr);
				$arguments = Helper::normalizeArguments($argument);
				foreach ($arguments as $argument) {
					$result = $this->selectColumn($expression->column . '.' . $argument, $expression);
					$array[] = new Expression(null, $expression->column, $argument, $result->alias);
				}
				continue;

			} else if ($expression->table !== null) {
				$result = $this->selectColumn($expression->expr, $expression);
				$array[] = new Expression(null, $result->tableAlias, $result->column, $result->alias);

				continue;
			} else if ($expression instanceof SubQuery) {
				$this->newInstance()->convertStatement($this->dnql, $expression->getStmt(), $this->mapping);
			}

			$array[] = $expression;
		}

		$this->stmt->expr = $array;
	}

	/**
	 * @return ColumnResult[]
	 */
	private function selectAll(string $tableAlias): array {
		$mapping = $this->fieldMapping->getColumnMapping($tableAlias);
		$columns = [];
		foreach ($mapping as $column => $alias) {
			$columns[] = new ColumnResult($tableAlias, $column, $alias);
		}

		return $columns;
	}

	private function selectColumn(string $column, ?Component $component = null): ColumnResult {
		[$tableAlias, $field] = explode('.', $column);
		$alias = $this->fieldMapping->getColumnAliasByColumn($tableAlias, $field);
		$metadata = $this->mapping[$tableAlias];
		$column = $metadata->getParent()->getColumnName($field);

		if (!$alias) {
			$entity = $this->mapping[$tableAlias]->getName();

			throw new ConverterException("Field $tableAlias.$field not exists in $entity", $this->dnql, $component);
		}

		return new ColumnResult($tableAlias, $column, $alias);
	}

	private function processJoins(): void {
		// refactoring
		/** @var JoinKeyword $joinKeyword */
		foreach ((array) $this->stmt->join as &$joinKeyword) {
			$alias = $joinKeyword->expr->database;
			$field = $joinKeyword->expr->table;
			$metadata = $this->mapping[$alias];

			if ($metadata->getParent()->isAssociationInverseSide($field)) {
				$mappedBy = $metadata->getAssociationMapping($field)['mappedBy'];
				$joinTable = $this->getAliasByClassName($metadata->getName());
				$metadata = $this->getClassMetadata($metadata->getParent()->getAssociationTargetClass($field));

				$column = $metadata->getParent()->getSingleAssociationJoinColumnName($mappedBy);
				$joinColumn = $metadata->getParent()->getSingleAssociationReferencedJoinColumnName($mappedBy);
				$columnTable = $this->getAliasByClassName($metadata->getName());
				$targetTable = $metadata->getParent()->getTableName();
			} else {
				$column = $metadata->getParent()->getSingleAssociationJoinColumnName($field);
				$joinColumn = $metadata->getParent()->getSingleAssociationReferencedJoinColumnName($field);
				$columnTable = $this->getAliasByClassName($metadata->getName());
				$joinTable = $this->getAliasByClassName($metadata->getParent()->getAssociationTargetClass($field));

				$targetTable = $this->getClassMetadata($metadata->getAssociationMapping($field)['targetEntity'])
					->getParent()
					->getTableName();
			}

			$joinKeyword->setParsed($targetTable, $joinTable . '.' . $joinColumn . ' = ' . $columnTable . '.' . $column);
		}
	}

	/**
	 * @return ClassMetadata[]
	 */
	protected function getEntityMapping(): array {
		/** @var ClassMetadata[] $mapping */
		$mapping = [];
		foreach ($this->stmt->from as $expr) {
			if ($expr->alias === null) {
				throw new ConverterException('Alias must be set');
			}
			$class = $this->entityMapping->getEntityClass($expr->table);
			$mapping[$expr->alias] = $metadata = $this->getClassMetadata($class);

			// process table
			$expr->table = $expr->expr = $metadata->getParent()->getTableName();

			$this->fieldMapping->addEntityResult($metadata->getName(), $expr->alias);
		}

		foreach ((array) $this->stmt->join as $join) {
			$expr = $join->expr;
			if (!isset($mapping[$expr->database])) {
				throw new ConverterException("Alias $expr->database not exists.");
			}
			$metadata = $mapping[$expr->database];
			$field = $expr->table;

			$metadata->validateColumnExists($field);
			$metadata->validateAssociation($field);

			$data = $metadata->getAssociationMapping($field);
			if (!$expr->alias) {
				throw new ConverterException('Alias must be set for ' . $expr->expr, $this->dnql, $expr);
			}
			$mapping[$expr->alias] = $metadata2 = $this->getClassMetadata($data['targetEntity']);

			$this->fieldMapping->addJoinedEntityResult($metadata2->getName(), $expr->alias, $expr->database, $field);
		}

		$this->mappingReversed = [];
		foreach ($mapping as $alias => $metadata) {
			$this->mappingReversed[$metadata->getParent()->getName()] = $alias;
		}

		return $mapping;
	}

	protected function getAliasByClassName(string $className): string {
		return $this->mappingReversed[$className];
	}

	protected function getClassMetadata(string $className): ClassMetadata {
		if (!isset($this->metadataCache[$className])) {
			$this->metadataCache[$className] = new ClassMetadata($this->em, $this->em->getClassMetadata($className));
		}

		return $this->metadataCache[$className];
	}

	protected function getMetadataByAlias(string $alias): ClassMetadata {
		return $this->mapping[$alias];
	}

}
