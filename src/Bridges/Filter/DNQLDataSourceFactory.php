<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Bridges\Filter;

use Doctrine\ORM\EntityManagerInterface;
use WebChemistry\Filter\DataSource\IDataSource;
use WebChemistry\Filter\DataSource\IDataSourceFactory;

class DNQLDataSourceFactory implements IDataSourceFactory {

	/** @var EntityManagerInterface */
	private $em;

	public function __construct(EntityManagerInterface $em) {
		$this->em = $em;
	}

	public function create($source, array $options): IDataSource {
		return new DNQLDataSource($source, $this->em, $options);
	}

}
