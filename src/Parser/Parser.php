<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Parser;

use PhpMyAdmin\SqlParser\Exceptions\ParserException;
use PhpMyAdmin\SqlParser\Lexer as SQLLexer;
use PhpMyAdmin\SqlParser\Parser as SQLParser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use WebChemistry\DNQL\DNQLException;
use WebChemistry\DNQL\Parser\Components\RuntimeFunction;
use WebChemistry\DNQL\Parser\Components\SubQuery;
use WebChemistry\DNQL\Parser\KeywordParsers\ExpressionArray;
use WebChemistry\DNQL\Parser\KeywordParsers\JoinKeyword;

final class Parser {

	/** @var bool */
	private static $installed = false;

	/** @var SelectStatement */
	protected $stmt;

	public function __construct(string $sql) {
		if (!self::$installed) {
			self::$installed = true;

			SQLParser::$KEYWORD_PARSERS['SELECT']['class'] = ExpressionArray::class;
			SQLParser::$KEYWORD_PARSERS['JOIN']['class'] = JoinKeyword::class;
			SQLParser::$KEYWORD_PARSERS['LEFT JOIN']['class'] = JoinKeyword::class;
			SQLParser::$KEYWORD_PARSERS['RIGHT JOIN']['class'] = JoinKeyword::class;

			SQLLexer::$PARSER_METHODS[] = Lexer::class . '::parse';
		}
		try {
			$this->stmt = self::parse($sql);
		} catch (ParserException $e) {
			throw new \WebChemistry\DNQL\Exceptions\ParserException($e, $sql);
		}

		$this->process();
	}

	/**
	 * @return SelectStatement
	 */
	public function getStmt(): SelectStatement {
		return $this->stmt;
	}

	protected function parse(string $sql): SelectStatement {
		$parser = new SQLParser($sql, true);
		$parser->parse();
		$stmt = $parser->statements[0];
		if (!$stmt instanceof SelectStatement) {
			throw new DNQLException('DNQL supports only SELECT');
		}

		return $stmt;
	}

	private function process(): void {
		foreach ($this->stmt->expr as $i => $expression) {
			if ($expression instanceof RuntimeFunction) {
				continue;
			}

			if ($expression->subquery !== null) {
				if ($expression->subquery !== 'SELECT') {
					throw new DNQLException('DNQL supports only SELECT sub query');
				}
				$hidden = $expression->function === 'HIDDEN';
				$expr = $expression->expr;
				if ($hidden) {
					$expr = trim($expr);
					$expr = substr($expr, 7, -1);
				}

				$this->stmt->expr[$i] = new SubQuery(self::parse($expr), $expression->alias, $expression->function === 'HIDDEN');
			}
		}
	}

}
