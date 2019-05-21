<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Parser\KeywordParsers;

use PhpMyAdmin\SqlParser\Components\CaseExpression;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\ExpressionArray as ExpressionArrayOriginal;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use WebChemistry\DNQL\Parser\Components\RuntimeFunction;
use WebChemistry\DNQL\Parser\Components\Variable;

class ExpressionArray extends ExpressionArrayOriginal {

	public static function parse(Parser $parser, TokensList $list, array $options = []) {
		$ret = [];

		/**
		 * The state of the parser.
		 *
		 * Below are the states of the parser.
		 *
		 *      0 ----------------------[ array ]---------------------> 1
		 *
		 *      1 ------------------------[ , ]------------------------> 0
		 *      1 -----------------------[ else ]----------------------> (END)
		 *
		 * @var int
		 */
		$state = 0;

		for (; $list->idx < $list->count; ++$list->idx) {
			/**
			 * Token parsed at this moment.
			 *
			 * @var Token
			 */
			$token = $list->tokens[$list->idx];

			// End of statement.
			if ($token->type === Token::TYPE_DELIMITER) {
				break;
			}

			// Skipping whitespaces and comments.
			if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
				continue;
			}

			if (($token->type === Token::TYPE_KEYWORD)
				&& ($token->flags & Token::FLAG_KEYWORD_RESERVED)
				&& ((~$token->flags & Token::FLAG_KEYWORD_FUNCTION))
				&& ($token->value !== 'DUAL')
				&& ($token->value !== 'NULL')
				&& ($token->value !== 'CASE')
			) {
				// No keyword is expected.
				break;
			}

			if ($state === 0) {
				if ($token->type === Token::TYPE_KEYWORD
					&& $token->value === 'CASE'
				) {
					$expr = CaseExpression::parse($parser, $list, $options);
				} else if (Variable::is($token)) {
					$expr = Variable::parse($parser, $list, $options);
				} else if (RuntimeFunction::is($token)) {
					$expr = RuntimeFunction::parse($parser, $list, $options);
				} else {
					$expr = Expression::parse($parser, $list, $options);
				}

				if ($expr === null) {
					break;
				}
				$ret[] = $expr;
				$state = 1;
			} else if ($state === 1) {
				if ($token->value === ',') {
					$state = 0;
				} else {
					break;
				}
			}
		}

		if ($state === 0) {
			$parser->error(
				'An expression was expected.',
				$list->tokens[$list->idx]
			);
		}

		--$list->idx;

		return $ret;
	}

}
