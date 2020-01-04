<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Exceptions;

use Exception;
use Throwable;

final class LexerException extends Exception {

	public function __construct(string $sql, string $message, string $str, int $pos, ?Throwable $previous = null) {
		parent::__construct(substr($message, 0, -1) . " $str in " . substr($sql, $pos), 0, $previous);
	}

}
