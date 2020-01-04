<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Tracy;

use Throwable;
use Tracy\Debugger;
use WebChemistry\DNQL\Converter\ConverterException;
use WebChemistry\DNQL\Exceptions\ParserException;

class BlueScreenPanel {

	/**
	 * @internal
	 */
	public static function catch(?Throwable $e): ?array {
		if ($e instanceof ParserException) {
			$token = $e->getParent()->token;
			$sql = $e->getSql();
			$sql = substr_replace($sql, self::highlight($token->token), $token->position, strlen($token->token));
			return [
				'tab' => 'DNQL',
				'panel' => '<pre>' . $sql . '</pre>',
			];
		} else if ($e instanceof ConverterException && $e->dnql) {
			$sql = $e->dnql;
			$sql = str_replace($e->component->expr, self::highlight($e->component->expr), $sql);
			return [
				'tab' => 'DNQL',
				'panel' => '<pre>' . $sql . '</pre>'
			];
		}

		return null;
	}

	private static function highlight(string $message): string {
		return sprintf('<span style="background:#CD1818;padding: 2px;color: #fff;">%s</span>', $message);
	}

	public static function register(): void {
		Debugger::getBlueScreen()->addPanel([self::class, 'catch']);
	}

}
