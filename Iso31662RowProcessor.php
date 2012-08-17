<?php
/**
 * A row processor for a csv dump of ISO-3166-2 data from
 * commondatahub.com
 * @author <arichards@wikimedia.org>
 */

class Iso31662RowProcessor {
	protected $row;
	protected $header_map;
	protected $subdivision;

	public function __construct( $row, $header_map ) {
		$this->row = $row;
		$this->header_map = $header_map;
	}

	public function parseRow() {
		$iso_sub = new IsoSubdivision;
		foreach ( $this->header_map as $key_name => $row_key ) {
			$func_name = $this->getSetFuncName( $key_name );
			$iso_sub->{$func_name}( $this->row[ $row_key ] );
		}
		$this->subdivision = $iso_sub;
	}

	private function getSetFuncName( $key ) {
		$key_arr = explode( "_", $key );
		foreach ( $key_arr as $k => $v ) {
			$key_arr[$k] = ucfirst( $v );
		}
		$func_name = "set" . implode( "", $key_arr );
		return $func_name;
	}

	public function getSubdivision() {
		return $this->subdivision;
	}
}
