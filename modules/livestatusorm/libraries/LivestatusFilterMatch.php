<?php

class LivestatusFilterMatch extends LivestatusFilterBase {
	private $field;
	private $op;
	private $value;

	function get_field() {
		return $this->field;
	}
	function get_op() {
		return $this->op;
	}
	function get_value() {
		return $this->value;
	}
	
	function __construct( $field, $value, $op = "=" ) {
		$this->field = str_replace('.','_',$field); //TODO: Do this in some fancy way...
		$this->op = $op;
		$this->value = $value;
	}
	
	function prefix( $prefix ) {
		return new LivestatusFilterMatch( $prefix.$this->field, $this->value, $this->op );
	}

	function generateFilter() {
		/* TODO: escape */
		return "Filter: ".$this->field." ".$this->op. " ".$this->value."\n";
	}
	function generateStats() {
		/* TODO: escape */
		return "Stats: ".$this->field." ".$this->op. " ".$this->value."\n";
	}

	function visit( LivestatusFilterVisitor $visitor ) {
		return $visitor->visit_match($this);
	}
}