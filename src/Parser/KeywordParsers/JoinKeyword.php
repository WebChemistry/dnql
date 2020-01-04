<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Parser\KeywordParsers;

use PhpMyAdmin\SqlParser\Components\Condition;
use PhpMyAdmin\SqlParser\Components\JoinKeyword as JoinKeywordOriginal;

class JoinKeyword extends JoinKeywordOriginal {

	public function setParsed(string $table, string $on) {
		$this->expr->table = $table;
		$this->expr->database = null;
		$this->expr->expr = $table;
		if ($this->on) {
			return;
		}

		$condition = new Condition();
		$condition->expr = $on;
		$this->on = [
			$condition,
		];
	}

	public static function build($component, array $options = []) {
		return parent::build($component, $options);
	}

}
