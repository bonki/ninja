<?php
/**
 * Stores the result of a form validation.
 */
class Form_Result_Model {

	/**
	 * The internal container for the form results
	 */
	private $results = array();

	/**
	 * Sets the value of a key in the form results
	 *
	 * @param $key string
	 * @param $value mixed
	 */
	public function set_value ($key, $value) {
		$this->results[$key] = $value;
	}

	/**
	 * Gets the value given a key in the form results
	 *
	 * @param $key string
	 * @return mixed
	 */
	public function get_value ($key) {
		return $this->results[$key];
	}

	/**
	 * Return whether the given key has been set in the results
	 *
	 * @param $key string
	 * @return bool
	 */
	public function has_value ($key) {
		return isset($this->results[$key]);
	}

	/**
	 * Returns the current state of the form results as an associative array
	 *
	 * @return array
	 */
	public function to_array () {
		return $this->results;
	}

}
