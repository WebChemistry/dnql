<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Converter;

use PhpMyAdmin\SqlParser\Component;

class ConverterException extends \Exception {

	/** @var Component|null */
	public $component;

	/** @var string|null */
	public $dnql;

	public function __construct(string $message, ?string $dnql = null, ?Component $component = null) {
		parent::__construct($message, 0, null);

		$this->component = $component;
		$this->dnql = $dnql;
	}

}
