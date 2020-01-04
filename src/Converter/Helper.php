<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Converter;

final class Helper {

	public static function removeBrackets(string $string): string {
		$string = substr($string, strpos($string, '(') + 1);
		$string = substr($string, 0, strrpos($string, ')'));

		return $string;
	}

	public static function normalizeArguments(string $string): array {
		return array_map(function (string $value) {
			return trim($value);
		}, explode(',', $string));
	}

}
