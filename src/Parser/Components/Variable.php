<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Parser\Components;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

final class Variable {

	/** @var string */
	private $name;

	/** @var string */
	private $starts;

	public function __construct(string $name, string $starts) {
		$this->name = $name;
		$this->starts = $starts;
	}

	public static function is(Token $token): bool {
		$first = substr($token->token, 0, 1);

		return $first === ':' || $first === '?';
	}

	public static function parse(Parser $parser, TokensList $list, array $options = []): Variable {
		$token = $list->tokens[$list->idx];

		return new Variable($token->value, substr($token->token, 0, 1));
	}

	public static function build(Variable $variable, array $options = []): string {
		return $variable->starts . $variable->name;
	}

}
