<?php

require_once( 'lalr_parser/LalrGenerator.php' );

require_once( 'op5/spyc.php' );

class Calculator_generator extends generator_module {
	public function run() {
		$grammar = Spyc::YAMLLoad( $this->gen_dir.'grammar.yml' );
		
		$generator = new LalrGenerator( 'Calculator', $grammar );
		$generator->generate();
	}
}