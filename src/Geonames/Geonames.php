<?php
namespace Geonames;

use Exception;

/**
 * Main interface to the GeoNames API.
 * Modified library from David Jean Louis to be composer compatible and remove PEAR dependencies
 *
 * @link      http://www.geonames.org/export/web-services.html
 * @author    David Jean Louis <izi@php.net>, Nikko Bautista <nikko@nikkobautista.com>
 * @copyright 2008-2009 David Jean Louis
 * @license   http://opensource.org/licenses/mit-license.php MIT License
 * @version   Release: 1.0.1
 * @link      http://www.geonames.org/export/web-services.html
 * @link      http://www.geonames.org/export/ws-overview.html
 * @since     Class available since release 0.1.0
 *
 * @method array    children()                children(array $params)
 * @method array    cities()                  cities(array $params)
 * @method stdclass countryCode()             countryCode(array $params)
 * @method array    countryInfo()             countryInfo(array $params)
 * @method stdclass countrySubdivision()      countrySubdivision(array $params)
 * @method array    earthquakes()             earthquakes(array $params)
 * @method array    findNearby()              findNearby(array $params)
 * @method array    findNearbyPlaceName()     findNearbyPlaceName(array $params)
 * @method array    findNearbyPostalCodes()   findNearbyPostalCodes(array $params)
 * @method array    findNearbyStreets()       findNearbyStreets(array $params)
 * @method stdclass findNearByWeather()       findNearByWeather(array $params)
 * @method array    findNearbyWikipedia()     findNearbyWikipedia(array $params)
 * @method stdclass findNearestAddress()      findNearestAddress(array $params)
 * @method stdclass findNearestIntersection() findNearestIntersection(array $params)
 * @method stdclass get()                     get(array $params)
 * @method stdclass gtopo30()                 gtopo30(array $params)
 * @method array    hierarchy()               hierarchy(array $params)
 * @method stdclass neighbourhood()           neighbourhood(array $params)
 * @method array    neighbours()              neighbours(array $params)
 * @method array    postalCodeCountryInfo()   postalCodeCountryInfo(array $params)
 * @method array    postalCodeLookup()        postalCodeLookup(array $params)
 * @method array    postalCodeSearch()        postalCodeSearch(array $params)
 * @method array    search()                  search(array $params)
 * @method array    siblings()                siblings(array $params)
 * @method array    weather()                 weather(array $params)
 * @method stdclass weatherIcao()             weatherIcao(array $params)
 * @method stdclass srtm3()                   srtm3(array $params)
 * @method stdclass timezone()                timezone(array $params)
 * @method array    wikipediaBoundingBox()    wikipediaBoundingBox(array $params)
 * @method array    wikipediaSearch()         wikipediaSearch(array $params)
 */
class GeoNames
{
    /**
     * Exception code constant defined by this package.
     */
    const UNSUPPORTED_ENDPOINT = 1;

    /**
     * Exception codes constants from:
     *
     * @link http://www.geonames.org/export/webservice-exception.html
     */
    const AUTHORIZATION_EXCEPTION          = 10;
    const RECORD_DOES_NOT_EXIST            = 11;
    const OTHER_ERROR                      = 12;
    const DATABASE_TIMEOUT                 = 13;
    const INVALID_PARAMETER                = 14;
    const NO_RESULT_FOUND                  = 15;
    const DUPLICATE_EXCEPTION              = 16;
    const POSTAL_CODE_NOT_FOUND            = 17;
    const DAILY_LIMIT_OF_CREDITS_EXCEEDED  = 18;
    const HOURLY_LIMIT_OF_CREDITS_EXCEEDED = 19;
    const WEEKLY_LIMIT_OF_CREDITS_EXCEEDED = 20;

    /**
     * Url of the GeoNames web service.
     *
     * @var string $url
     */
    public $url = 'http://ws.geonames.net';

    /**
     * Array of failover servers.
     *
     * @var array
     */
    public $failoverServers = array();

    /**
     * Auth username, only relevant for the geonames commercial web services:
     *
     * @link http://www.geonames.org/commercial-webservices.html
     * @var string $username
     */
    protected $username;

    /**
     * Auth token, only relevant for the geonames commercial web services:
     *
     * @link http://www.geonames.org/commercial-webservices.html
     * @var string $token
     */
    protected $token;

    /**
     * Array of supported endpoints (listed alphabetically) and their
     * corresponding root property (if any). You can retrieve the list of
     * endpoints (only the keys of this array) with the
     * Services_GeoNames::getSupportedEndpoints() method.
     *
     * Note that we only support json endpoints, so the following endpoints are
     * not supported:
     * - extendedFindNearby (JSON not available for now)
     * - rssToGeo (RSS/KML only)
     *
     * @link http://www.geonames.org/export/ws-overview.html
     * @var array $endpoints
     */
    protected $endpoints = array(
        'children'                => 'geonames',
        'cities'                  => 'geonames',
        'countryCode'             => false,
        'countryInfo'             => 'geonames',
        'countrySubdivision'      => false,
        'earthquakes'             => 'earthquakes',
        'findNearby'              => 'geonames',
        'findNearbyPlaceName'     => 'geonames',
        'findNearbyPostalCodes'   => 'postalCodes',
        'findNearbyStreets'       => 'streetSegment',
        'findNearByWeather'       => 'weatherObservation',
        'findNearbyWikipedia'     => 'geonames',
        'findNearestAddress'      => 'address',
        'findNearestIntersection' => 'intersection',
        'get'                     => false,
        'gtopo30'                 => false,
        'hierarchy'               => 'geonames',
        'neighbourhood'           => 'neighbourhood',
        'neighbours'              => 'geonames',
        'postalCodeCountryInfo'   => 'geonames',
        'postalCodeLookup'        => 'postalcodes', // not a typo
        'postalCodeSearch'        => 'postalCodes',
        'search'                  => 'geonames',
        'siblings'                => 'geonames',
        'weather'                 => 'weatherObservations',
        'weatherIcao'             => 'weatherObservation',
        'srtm3'                   => false,
        'timezone'                => false,
        'wikipediaBoundingBox'    => 'geonames',
        'wikipediaSearch'         => 'geonames',
    );

    /**
     * Constructor, if you're using a commercial account (optional), you must
     * pass your "username" and "token".
     *
     * @param string $username Username for commercial webservice (optional)
     * @param string $token    Token for commercial webservice (optional)
     * @return void
     */
    public function __construct($username = null, $token = null)
    {
        if ($username !== null) {
            $this->username = $username;
        }
        if ($token !== null) {
            $this->token = $token;
        }
    }

    /**
     * Method interceptor that retrieves the corresponding endpoint and return
     * a json decoded object or throw a Exception.
     *
     * @param string $endpoint The endpoint to call
     * @param array  $params   Array of parameters to pass to the endpoint
     *
     * @return mixed stdclass|array The JSON decoded response or an array
     * @throws Exception When an invalid method is called or
     *                                     when the websercices returns an error
     */
    public function __call($endpoint, $params = array())
    {
        // check that endpoint is supported
        if (!in_array($endpoint, $this->getSupportedEndpoints())) {
            throw new Exception("Unknown service endpoint \"{$endpoint}\"", self::UNSUPPORTED_ENDPOINT);
        }

        // handle params
        if (isset($params[0])) {
            $params = is_array($params[0])
                ? $params[0]
                : array('geonameId' => $params[0]);
        } else {
            $params = array();
        }

        if (isset($params['type'])) {
            // we only do json
            unset($params['type']);
        }

        // manage authentication to commercial webservice
        if ($this->username !== null) {
            $params['username'] = $this->username;
        }

        if ($this->token !== null) {
            $params['token'] = $this->token;
        }

        // build the url and retrieve the result
        $qString = $this->formatQueryString($params);
        $urlPath = $this->url . '/' . $endpoint . 'JSON?' . $qString;
        $ret = json_decode(file_get_contents($urlPath));

        // check if we have a error response
        if (isset($ret->status->message) && isset($ret->status->value)) {
            throw new Exception($ret->status->message, (int)$ret->status->value);
        }

        // remove useless root property, to make the result more user friendly
        if ($this->endpoints[$endpoint] !== false && is_object($ret)) {
            $prop = $this->endpoints[$endpoint];
            $ret = $ret->$prop;
        }

        return $ret;
    }

    /**
     * Builds a valid query string (url and utf8 encoded) to pass to the
     * endpoint and returns it.
     *
     * @param array $params Associative array of query parameters (name=>val)
     *
     * @return string The formatted query string
     */
    protected function formatQueryString($params = array())
    {
        $qString = array();
        foreach ($params as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $val) {
                    $val = $this->isUtf8($val) ? $val : utf8_encode($val);
                    $qString[] = $name . '=' . urlencode($val);
                }
            } else {
                $value = $this->isUtf8($value) ? $value : utf8_encode($value);
                $qString[] = $name . '=' . urlencode($value);
            }
        }
        return implode('&', $qString);
    }

    /**
     * Returns an array of supported services endpoints.
     *
     * @return array The endpoints array
     * @see Services_GeoNames::$endpoints
     */
    public function getSupportedEndpoints()
    {
        return array_keys($this->endpoints);
    }

    // }}}
    // isUtf8() {{{

    /**
     * Check if the given string is a UTF-8 string or an iso-8859-1 one.
     *
     * @param string $str The string to check
     *
     * @return boolean Wether the string is unicode or not
     */
    protected function isUtf8($str)
    {
        return (bool)preg_match(
            '%^(?:
                  [\x09\x0A\x0D\x20-\x7E]            # ASCII
                | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
                |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
                | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
                |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
                |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
                | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
                |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
            )*$%xs',
            $str
        );
    }
}
