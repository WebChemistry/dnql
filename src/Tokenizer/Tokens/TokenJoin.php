<?php declare(strict_types = 1);

namespace WebChemistry\DNQL\Tokenizer\Tokens;

class TokenJoin {

	/** @var array */
	private $input;

	public function __construct(array $input) {
		$this->input = $input;
	}

	public function getEntity(): string {
		return $this->input['no_quotes']['parts'][0];
	}

	public function getColumn(): string {
		return $this->input['no_quotes']['parts'][1];
	}

	public function getType(): string {
		return $this->input['join_type'];
	}

	public function getAlias(): string {
		return $this->input['alias']['name'];
	}

}
