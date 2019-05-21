<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\DI;

use Nette;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Tracy\Debugger;
use WebChemistry\DNQL\Converter\ConverterFactory;
use WebChemistry\DNQL\Mapping\EntityMapping;
use WebChemistry\DNQL\Query\IQueryFactory;
use WebChemistry\DNQL\Query\QueryFactory;
use WebChemistry\DNQL\Tracy\BlueScreenPanel;

class DNQLExtension extends CompilerExtension {

	public function getConfigSchema(): Schema {
		return Expect::structure([
			'mapping' => Expect::arrayOf('string'),
			'entities' => Expect::arrayOf('string'),
		]);
	}

	public function loadConfiguration() {
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		$mapping = $builder->addDefinition($this->prefix('entityMapping'))
			->setType(EntityMapping::class, [$config->entities]);

		foreach ($config->mapping as $item) {
			$mapping->addSetup('addAutoMapping', [$item]);
		}

		$builder->addDefinition($this->prefix('converter'))
			->setType(ConverterFactory::class, [
				'entityMapping' => $mapping,
			]);

		$builder->addDefinition($this->prefix('queryFactory'))
			->setType(IQueryFactory::class)
			->setFactory(QueryFactory::class);
	}

	public function afterCompile(Nette\PhpGenerator\ClassType $class) {
		$init = $class->getMethod('initialize');

		if (class_exists(Debugger::class)) {
			$init->addBody(BlueScreenPanel::class . '::register();');
		}
	}

}
