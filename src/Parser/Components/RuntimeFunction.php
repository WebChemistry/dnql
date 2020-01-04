<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Parser\Components;

use LogicException;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use WebChemistry\DNQL\Converter\Helper;

class RuntimeFunction {

	/** @var Expression */
	public $expression;

	/** @var string */
	public $functionName;

	public function __construct(Expression $expression) {
		$this->functionName = $expression->function;
		$expression->expr = Helper::removeBrackets($expression->expr);
		$this->expression = $expression;
		if ($this->expression->function === null) {
			throw new LogicException('RuntimeFunction must be an function.');
		}
	}

	public static function is(Token $token): bool {
		return $token->value === '%';
	}

	public static function parse(Parser $parser, TokensList $list, array $options = []): RuntimeFunction {
		$token = $list[$list->idx + 1];
		$token->value = $token->token;
		$token->keyword = null;
		$token->type = 0;
		$token->flags = 0;

		return new RuntimeFunction(Expression::parse($parser, $list, $options));
	}

	public static function build(RuntimeFunction $function, array $options = []): string {
		return Expression::build($function->expression);
	}

}
