<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Exceptions;

use PhpMyAdmin\SqlParser\Exceptions\ParserException as ParserExceptionOriginal;

class ParserException extends \Exception {

	/** @var ParserExceptionOriginal */
	private $previous;

	/** @var string */
	private $sql;

	public function __construct(ParserExceptionOriginal $previous, string $sql) {
		parent::__construct($previous->getMessage(), $previous->getCode());

		$this->previous = $previous;
		$this->sql = $sql;
	}

	public function getParent(): ParserExceptionOriginal {
		return $this->previous;
	}

	public function getSql(): string {
		return $this->sql;
	}

}
