<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Converter;

use Doctrine\ORM\Query\ResultSetMapping;

final class ConverterResult {

	/** @var string */
	public $sql;

	/** @var ResultSetMapping */
	public $rsm;

	public function __construct(string $sql, ResultSetMapping $rsm) {
		$this->sql = $sql;
		$this->rsm = $rsm;
	}

}
