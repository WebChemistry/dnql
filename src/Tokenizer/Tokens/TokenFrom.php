<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Tokenizer\Tokens;

class TokenFrom	{

	/** @var array */
	private $input;

	public function __construct(array $input) {
		$this->input = $input;
	}

	public function getEntity(): string {
		return $this->input['table'];
	}

	public function getAlias(): string {
		return $this->input['alias']['name'];
	}

}
