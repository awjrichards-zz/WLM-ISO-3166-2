<?php

/**
 * Handles localisation of ISO subdivision names using interwiki lang links
 *
 * Only supports json output format at the moment.
 * * @author <arichards@wikimedia.org>
 */
class SubdivisionL10n {
	protected $query_url = 'http://en.wikipedia.org/w/api.php';
	protected $lllimit = 500;
	protected $format = 'json';
	protected $titles = '';
	protected $lllang = 'en';
	protected $apiResult = array();
	public $debug = false;

	public function getQueryUrl() {
		return $this->query_url;
	}

	/**
	 * @TODO enforce a sane url
	 */
	public function setQueryUrl( $url ) {
		$this->query_url = $url;
	}

	public function getLimit() {
		return $this->lllimit;
	}

	public function setLimit( $limit ) {
		if ( !is_numeric( $limit ) ) {
			$limit = 0;
		}
		$this->lllimit = intval( $limit );
	}

	public function getForamt() {
		return $this->format;
	}

	public function setFormat( $format ) {
		$this->format = $format;
	}

	public function getTitles() {
		return $this->titles;
	}

	public function setTitles( $titles ) {
		if ( is_array( $titles ) ) {
			$titles = implode( "|", $titles );
		}
		$this->titles = $titles;
	}

	public function getLang() {
		return $this->lllang;
	}

	public function setLang( $lang ) {
		$this->lllang = $lang;
	}

	public function setApiResult( $apiResult ) {
		$this->apiResult = $apiResult;
	}

	public function getApiResult( $query=false ) {
		if ( $query === true ) {
			$this->queryApi();
		}
		return $this->apiResult;
	}

	/**
	 * Prepares API query parameters
	 *
	 * @return array
	 */
	protected function getQueryParams() {
		return array(
			'action' => 'query',
			'prop' => 'langlinks',
			'redirects' => '',
			'lllimit' => $this->getLimit(),
			'format' => $this->getForamt(),
			'titles' => $this->getTitles(),
			'lllang' => $this->getLang()
		);
	}

	/**
	 * Query the API and store the result in an object property
	 */
	public function queryApi() {
		if ( $this->debug ) {
			$this->debug( 'Query URL', $this->getQueryUrl() );
			$this->debug( 'Query Params', print_r( $this->getQueryParams(), true ) );
		}
		$apiResult = static::getApiResults( $this->getQueryUrl(), $this->getQueryParams() );
		$this->apiResult = json_decode( $apiResult, true );
		if ( $this->debug ) $this->debug( 'Api result', print_r( $this->apiResult, true ) );
	}

	/**
	 * Fetches any redirect information from an api query result
	 *
	 * @return array|bool
	 */
	public function getRedirects() {
		$apiResult = $this->getApiResult();

		if ( !is_array( $apiResult['query'] ) || !in_array( 'redirects', array_keys( $apiResult['query'] ) ) ) {
			return false;
		} else {
			return $apiResult['query']['redirects'];
		}
	}

	/**
	 * Use curl to query the API
	 * @TODO this is pretty basic - it would be nice to support
	 *    authentication, etc.
	 */
	public static function getApiResults( $url, $params, $i=0 ) {
		$i++; // keep track of attempts
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_USERAGENT, 'ISO-3166-2 l10n 0.1' );
		curl_setopt( $ch, CURLOPT_ENCODING, "UTF-8" );
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		// quiet 'HTTP/1.0 417 Expectation failed' error by disabling the header
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( "Expect:" ) );
		// curl_setopt( $ch, CURLOPT_VERBOSE, 1 );
		$ret = curl_exec( $ch );
		if ( curl_errno( $ch ) ) {
			if ( $i <= 3  ) {
				sleep( 15 );
				getApiResults( $url, $params, $i );
			} else {
				throw new Exception( sprintf( 'Curl error: %s', curl_error( $ch ) ) ) ;
			}
		}
		curl_close( $ch );
		return $ret;
	}

	/**
	 * Formats a localised subdivision record
	 *
	 * @param string $country_iso_code
	 * @param string $iso_code
	 * @param string $name
	 * @param string $level_name
	 * @return string
	 */
	public static function formatL10nRecord( $country_iso_code, $iso_code, $name, $level_name ) {
		$str = '$subdivisions[\'%s\'][\'%s\'] = array(' . PHP_EOL;
		$str .= '\'name\' => \'%s\',' . PHP_EOL;
		$str .= '\'level\' => \'%s\',' . PHP_EOL;
		$str .= ');' . PHP_EOL;
		return sprintf( $str, $country_iso_code, $iso_code, $name, $level_name );
	}

	/**
	 * Does a little cleanup on subdivision names
	 *
	 * Some names (either from the CSV or coming back from the language links)
	 * have erroneous information and/or characters in them. This gets rid
	 * of the cruft.
	 *
	 * @param string $name
	 * @return string
	 */
	public static function fixL10nName( $name ) {
		$pattern = array(
			"/\([^\)]+\)/", // remove any text in parens - eg 'California (state)'
			"/,.*[^D\.C\.]$/", // remove a comma and anything after it, except 'D.C.' (ugly Washington, D.C. hack)
			"/\*+/", // remove any * symbols
			"/\[.*\]/", // remove anything contained in square brackets
		);

		$name_l10n = preg_replace( $pattern, '', $name );

		// trim and addslashes
		return trim( addslashes( $name_l10n ) );
	}

	/**
	 * Debug output helper method
	 *
	 * @param string $preface
	 * @param string $log_item
	 */
	private function debug( $preface, $log_item ) {
		echo $preface . ": " . $log_item . PHP_EOL;
	}
}
