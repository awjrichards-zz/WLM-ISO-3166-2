<?php

/**
 * A class to represent an ISO-3166-2 subdivision
 * @author <arichards@wikimedia.org>
 */
class ISOSubdivision {
	/**
	 * Two-letter country code
	 * @param string
	 */
	private $country_iso_code;

	/**
	 * Name of the subdivision level
	 * @param string
	 */
	private $level_name;

	/**
	 * The ISO-3166-2 code for this subdivision
	 * @param string
	 */
	private $iso_code;

	/**
	 * The ISO-3166-2 name of this subdivision
	 * @param string
	 */
	private $name;

	/**
	 * Alternate names for this subdivision
	 * @param array
	 */
	private $alternate_names = array();

	protected function ensureStrLen( $str, $len, $except = true ) {
		if ( !strlen( $str ) == $len ) {
			if ( $except ) {
				var_dump( $this );
				throw new Exception( sprintf( 'The provided string, %s, is expected to be %d characters.', $str, $len ) );
			}
			return false;
		}
		return true;
	}

	public function setCountryIsoCode( $country_iso_code ) {
		$country_iso_code = trim( $country_iso_code );
		if ( $this->ensureStrLen( $country_iso_code, 2 ) ) {
			$this->country_iso_code = $country_iso_code;
		}
	}

	public function getCountryIsoCode() {
		return $this->country_iso_code;
	}

	public function setLevelName( $level_name ) {
		$this->level_name = strtolower( trim( $level_name ) );
	}

	public function getLevelName() {
		return $this->level_name;
	}

	public function setIsoCode( $iso_code ) {
		$iso_code = trim( $iso_code );
		if ( $this->ensureStrLen( $iso_code, 2 ) ) {
			$this->iso_code = $iso_code;
		}
	}

	public function getIsoCode() {
		return $this->iso_code;
	}

	public function setName( $name ) {
		$this->name = trim( $name );
	}

	public function getName() {
		return $this->name;
	}

	public function setAlternateNames( $alt_names ) {
		if ( is_array( $alt_names ) ) {
			$this->alternate_names = $alt_names;
			return;
		}

		$alt_names_arr = explode( ",", $alt_names );
		$this->alternate_names = array_map( 'trim', $alt_names_arr );
	}

	public function getAlternateNames() {
		return $this->alternate_names;
	}

	public function formatRecord() {
		return SubdivisionL10n::formatL10nRecord(
			$this->getCountryIsoCode(),
			$this->getIsoCode(),
			$this->getName(),
			$this->getLevelName()
		);
	}

	public function __toString() {
		return $this->formatRecord();
	}
}
