<?php
/**
 * fdvegan_tmdb.php
 *
 * Implementation of Tmdb class for module fdvegan.
 * Wrapper class for low-level 3rd party TMDb library.
 *
 * Example TMDb API URLs:
 *   https://api.themoviedb.org/3/configuration?api_key=[redacted]
 *   https://api.themoviedb.org/3/person/524?api_key=[redacted]
 *   https://api.themoviedb.org/3/person/524/credits?api_key=[redacted]
 *   https://api.themoviedb.org/3/person/524/images?api_key=[redacted]
 *   https://api.themoviedb.org/3/movie/123?api_key=[redacted]
 *   https://api.themoviedb.org/3/movie/123/casts?api_key=[redacted]
 *   https://api.themoviedb.org/3/movie/123/images?api_key=[redacted]
 *
 * PHP version 5.6
 *
 * @category   Tmdb
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2015-2016 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 0.8
 * @see        TMDb.php
 */


class fdvegan_Tmdb extends TMDb
{
    const BASE_URL = 'https://www.themoviedb.org/';

    /**
     * The API-key
     *
     * @var string
     */
    // Private key to the TMDb API.  This is created by setting up
    // an account on https://www.themoviedb.org/
    protected $_apikey = 'af7df5acc6dc1bcef9272d6c2c3a5e84';


    /**
     * Default constructor
     *
     * @param string $apikey			API-key recieved from TMDb
     * @param string $defaultLang		Default language (ISO 3166-1)
     * @param boolean $config			Load the TMDb-config
     * @return void
     */
    public function __construct($apikey = NULL, $default_lang = 'en', $config = FALSE, $scheme = TMDb::API_SCHEME)
    {
        $this->_apikey = empty($apikey) ? $this->_apikey : (string) $apikey;
        $this->_apischeme = ($scheme == TMDb::API_SCHEME) ? TMDb::API_SCHEME : TMDb::API_SCHEME_SSL;
        $this->setLang($default_lang);

        if($config === TRUE)
        {
            $this->getConfiguration();
        }
    }


    /**
     * Returns the TMDb API base_url from "configuration".
     */
    public static function getTmdbBaseUrl()
    {
// @TODO - make this a once /week cronjob instead of hard-coding?:
        return 'https://image.tmdb.org/t/p/';
    }


    public static function getTmdbPersonInfoUrl($tmdbid)
    {
        $ret_val = '';
        if (!empty($tmdbid)) {
            $ret_val = self::BASE_URL . "person/{$tmdbid}";
        }
        return $ret_val;
    }


    public static function getTmdbMovieInfoUrl($tmdbid)
    {
        $ret_val = '';
        if (!empty($tmdbid)) {
            $ret_val = self::BASE_URL . "movie/{$tmdbid}";
        }
        return $ret_val;
    }


    /**
     * Map TMDb gender values to the equivalent FDV value.
     *
     * @param string $value    Valid values are:  0,1,2.
     * @return string  FDV value.
     */
    public static function mapTmdbGenderToFDV($value)
    {
        // TMDb uses int 0=unknown,1=female,2=male
        $tmdb_gender_map = array(
            NULL => NULL,
            '0'  => NULL,
            '1'  => 'F',
            '2'  => 'M',
        );
        if (!array_key_exists($value, $tmdb_gender_map)) {
            fdvegan_Content::syslog('LOG_ERR', "mapTmdbGenderToFDV('{$value}') invalid gender");
            throw new FDVegan_InvalidArgumentException("mapTmdbGenderToFDV('{$value}') invalid gender.");
        }
        return $tmdb_gender_map[$value];
    }


    /**
     * Map standard types to the equivalent TMDb value.
     *
     * @param string $type    Valid values are:  'person', 'movie', or 'moviebackdrop'.
     * @return string  TMDb value.
     */
    public static function mapTmdbImageType($type)
    {
        $tmdb_image_type_map = array(
            'person'        => TMDb::IMAGE_PROFILE,
            'movie'         => TMDb::IMAGE_POSTER,
            'moviebackdrop' => TMDb::IMAGE_BACKDROP,
        );
        if (!array_key_exists($type, $tmdb_image_type_map)) {
            fdvegan_Content::syslog('LOG_ERR', "mapTmdbImageType('{$type}') invalid type");
            throw new FDVegan_InvalidArgumentException("mapTmdbImageType({$type}) invalid type.");
        }
        return $tmdb_image_type_map[$type];
    }


    /**
     * Map standard sizes to the equivalent TMDb value.
     *
     * @param string $type    Valid values are:  'person', 'movie', or 'moviebackdrop'.
     * @param string $size    Valid values are:  "s,m,l,o" or: 'small', 'medium', 'large', or 'original'.
     * @return string  TMDb value.
     */
    public static function mapTmdbImageSize($type = 'person', $size = 'medium')
    {
        $tmdb_image_type = fdvegan_tmdb::mapTmdbImageType($type);
        $media_size = substr($size, 0, 1);

// @TODO - make this a once /week cronjob instead of hard-coding?:
//        $tmdb_api = new fdvegan_Tmdb();
//        $available_sizes = $tmdb_api->getAvailableImageSizes($tmdb_image_type);
//        fdvegan_Content::syslog('LOG_DEBUG', "mapTmdbImageSize('{$type}','{$media_size}') getAvailableImageSizes('{$tmdb_image_type}'): ". print_r($available_sizes,1));
        $tmdb_image_size_map = array();
        $tmdb_image_size_map['person'] = array(
            's' => 'w45',
            'm' => 'w185',
            'l' => 'h632',
            'o' => 'original'
        );
        $tmdb_image_size_map['movie'] = array(
            's' => 'w92',
            'm' => 'w185',
            'l' => 'w500',
            'o' => 'original'
        );
        $tmdb_image_size_map['moviebackdrop'] = array(
            's' => 'w300',
            'm' => 'w780',
            'l' => 'w1280',
            'o' => 'original'
        );
        if (!array_key_exists($media_size, $tmdb_image_size_map[$type])) {
            fdvegan_Content::syslog('LOG_ERR', "mapTmdbImageSize('{$type}','{$media_size}') invalid size");
            throw new FDVegan_InvalidArgumentException("mapTmdbImageSize({$type},{$media_size}) invalid size.");
        }
        return $tmdb_image_size_map[$type][$media_size];
    }


    /**
     * Map standard types to the equivalent TMDb value returned from the API.
     * For some reason, TMDb adds an 's' to the ImageType name returned as an ArrayKey.
     *
     * @param string $type    Valid values are:  'person', 'movie', or 'moviebackdrop'.
     * @return string    TMDb result-index value.
     */
    public static function mapTmdbImageResultType($type)
    {
        $tmdb_image_type_map = array(
            'person'        => TMDb::IMAGE_PROFILE . 's',
            'movie'         => TMDb::IMAGE_POSTER . 's',
            'moviebackdrop' => TMDb::IMAGE_BACKDROP . 's',
        );
        if (!array_key_exists($type, $tmdb_image_type_map)) {
            fdvegan_Content::syslog('LOG_ERR', "mapTmdbImageResultType('{$type}') invalid type");
            throw new FDVegan_InvalidArgumentException("mapTmdbImageResultType({$type}) invalid type.");
        }
        return $tmdb_image_type_map[$type];
    }


    /**
     * This function is DEPRECATED.
     *
     * Get the best image URL from TMDb for this "person or movie".
     *
     * @param string $type    Valid values are:  'person', or 'movie'.
     * @param int $tmdbid     The TMDbId.
     * @param string $size    Valid values are:  "s,m,l,o" or: 'small', 'medium', 'large', or 'original'.
     * @return string  URL or ''
     *                 e.g.: "https://image.tmdb.org/t/p/w185/{$this->_tmdb_file_path}"
     */
    public static function DEPRECATED_getTmdbImageUrl($type, $tmdbid, $size = 'medium', $orUseDefault = FALSE)
    {
        $media_size = substr($size, 0, 1);
        $type_ucase = ucfirst($type);
        $tmdb_image_type = fdvegan_tmdb::mapTmdbImageType($type);
        $tmdb_image_key = 'profiles';
        if ($type === 'movie') {
            $tmdb_image_key = 'posters';
        }
        $tmdb_file_path = '';
        $tmdb_image_url = '';
        $tmdb_size = fdvegan_tmdb::mapTmdbImageSize($type, $media_size);
        if (!empty($tmdbid)) {
                $tmdb_api = fdvegan_tmdb::getTMDbAPI();
                try {
                    $tmdb_function_name = "get{$type_ucase}Images";
                    $images = $tmdb_api->$tmdb_function_name($tmdbid);
                    fdvegan_Content::syslog('LOG_DEBUG', "{$type}->getTmdbImageUrl({$media_size}) {$tmdb_function_name}({$tmdbid}) returned data:" . print_r($images,1));
                    // There are often multiple images, so we'll use the one with the highest vote_count.
                    if (array_key_exists($tmdb_image_key, $images)) {
                        $max_vote_count = -1;
                        foreach ($images[$tmdb_image_key] as $image_rec) {
                            if ($max_vote_count < $image_rec['vote_count']) {
                                $max_vote_count = $image_rec['vote_count'];
                                $tmdb_file_path = $image_rec['file_path'];
                            }
                        }
                    }
                    if (!empty($tmdb_file_path)) {
                        //fdvegan_Content::syslog('LOG_DEBUG', "getTmdbImageUrl('{$type}','{$tmdbid}','{$media_size}') calling: getImageUrl('{$tmdb_file_path}','{$tmdb_image_type}','{$tmdb_size}').");
                        $tmdb_image_url = $tmdb_api->getImageUrl($tmdb_file_path, $tmdb_image_type, $tmdb_size);
                    }
                } catch (Exception $e) {
                    fdvegan_Content::syslog('LOG_ERR', "Caught exception:  ". $e->getMessage() ." while getTmdbImageUrl('{$type}','{$tmdbid}','{$media_size}').");
                    throw $e;
                }
        }
        fdvegan_Content::syslog('LOG_DEBUG', "{$type}->getTmdbImageUrl({$media_size}) found URL={$tmdb_image_url}.");
        if ($orUseDefault && empty($tmdb_image_url)) {
            return fdvegan_tmdb::mapTmdbImageSizeToDefaultUrl($type, $media_size);
        }

        return $tmdb_image_url;
    }


    /**
     * Retrieve all images for a particular movie.
     * This function exists only to help map fdvegan image types to the TMDb API.
     *
     *
     * @param mixed $id		TMDb-id or IMDB-id
     * @param mixed $lang	Filter the result with a language (ISO 3166-1) other then default, use FALSE to retrieve results from all languages
     * @return TMDb result array
     */
    public function getMoviebackdropImages($id, $lang = NULL)
    {
        return $this->getMovieImages($id, $lang);
    }


    /**
     * TODO - put this in a cronjob?
     */
//	public function getChangedPersons($page = 1, $start_date = NULL, $end_date = NULL)


    /**
     * Setter for the TMDB-config.
     *
     * $param array $config
     * @return void
     */
    public function setConfig($config)
    {
        parent::setConfig($config);
        variable_set('fdvegan_tmdb_config', $config);
    }


    /**
     * Get configuration from TMDb.
     *
     * @return TMDb result array
     */
    public function getConfiguration()
    {
    if (empty($this->_config)) {
        $this->_config = variable_get('fdvegan_tmdb_config');
    }
    if ($this->isConfigStale() || empty($this->_config)) {
        $config = $this->_makeCall('configuration');
        if (!empty($config)) {
            $this->setConfig($config);
        }
    }
    return $this->_config;
    }


    /**
     * Get Image URL.
     *
     * @param string $filepath			Filepath to image
     * @param const $imagetype			Image type: TMDb::IMAGE_BACKDROP, TMDb::IMAGE_POSTER, TMDb::IMAGE_PROFILE
     * @param string $size				Valid size for the image
     * @return string
     */
    public function getImageUrl($filepath, $imagetype, $size)
    {
        $config = $this->getConfig();

        if(isset($config['images']))
        {
            $base_url = $config['images']['base_url'];
            $available_sizes = $this->getAvailableImageSizes($imagetype);

            if(in_array($size, $available_sizes))
            {
                return $base_url.$size.$filepath;
            }
            else
            {
// FDVEGAN DEBUG:  updated the following line:
                throw new FDVegan_TmdbException('The size "'.$size.'" is not supported by TMDb');
            }
        }
        else
        {
// FDVEGAN DEBUG:  updated the following line:
            throw new FDVegan_TmdbException("getImageUrl('{$filepath}','{$imagetype}','{$size}') no config available!", NULL, NULL, 'LOG_CRIT');
        }
    }


    public function isConfigStale()
    {
        $stale_flag = FALSE;
        // @TODO - if/when needed, consider implementing this properly someday.

        return $stale_flag;
    }



    //////////////////////////////



	/**
	 * Makes the call to the API.
	 *
	 * Note - this method was originally "private" in TMDb.php, but it must be non-private in order to
	 *        override it here.
	 *
	 * @param string $function			API specific function name for in the URL
	 * @param array $params				Unencoded parameters for in the URL
	 * @param string $session_id		Session_id for authentication to the API for specific API methods
	 * @param const $method				TMDb::GET or TMDb:POST (default TMDb::GET)
	 * @return TMDb result array
	 */
	protected function _makeCall($function, $params = NULL, $session_id = NULL, $method = TMDb::GET)
	{
 // FDVEGAN DEBUG:  added the following line:
 fdvegan_Content::syslog('LOG_NOTICE', "TMDb API called: _makeCall('{$function}','{$method}').");
		$params = ( ! is_array($params)) ? array() : $params;
		$auth_array = array('api_key' => $this->_apikey);

		if($session_id !== NULL)
		{
			$auth_array['session_id'] = $session_id;
		}

// FDVEGAN DEBUG:  added the following line:  // To avoid outputting any sensitive api_key or session_id data.
$base_url = $this->_apischeme.TMDb::API_URL.'/'.TMDb::API_VERSION.'/'.$function;

		$url = $this->_apischeme.TMDb::API_URL.'/'.TMDb::API_VERSION.'/'.$function.'?'.http_build_query($auth_array, '', '&');

		if($method === TMDb::GET)
		{
			if(isset($params['language']) AND $params['language'] === FALSE)
			{
				unset($params['language']);
			}

			$url .= ( ! empty($params)) ? '&'.http_build_query($params, '', '&') : '';
// FDVEGAN DEBUG:  added the following line:  // To avoid outputting any sensitive api_key or session_id data.
			$base_url .= ( ! empty($params)) ? '?'.http_build_query($params, '', '&') : '';
		}

		$results = '{}';

		if (extension_loaded('curl'))
		{
			$headers = array(
				'Accept: application/json',
			);

			$ch = curl_init();

			if($method == TMDB::POST)
			{
				$json_string = json_encode($params);
				curl_setopt($ch,CURLOPT_POST, 1);
				curl_setopt($ch,CURLOPT_POSTFIELDS, $json_string);
				$headers[] = 'Content-Type: application/json';
				$headers[] = 'Content-Length: '.strlen($json_string);
			}
			elseif($method == TMDb::HEAD)
			{
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
				curl_setopt($ch, CURLOPT_NOBODY, 1);
			}

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// FDVEGAN DEBUG:  added the following line:
fdvegan_Content::syslog('LOG_DEBUG', "_makeCall('{$function}') calling TMDb API URL={$base_url}");
			$response = curl_exec($ch);

			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($response, 0, $header_size);
			$body = substr($response, $header_size);

			$error_number = curl_errno($ch);
			$error_message = curl_error($ch);

			if($error_number > 0)
			{
				throw new FDVegan_TmdbException('Method failed: '.$function.' - '.$error_message);
			}

			curl_close($ch);
		}
		else
		{
			throw new FDVegan_TmdbException('CURL-extension not loaded');
		}

		$results = json_decode($body, TRUE);

		if(strpos($function, 'authentication/token/new') !== FALSE)
		{
			$parsed_headers = $this->_http_parse_headers($header);
			$results['Authentication-Callback'] = $parsed_headers['Authentication-Callback'];
		}

		if($results !== NULL)
		{
            // FDVEGAN DEBUG:  added the following line:
            $this->_validateTmdbResponse($results, $base_url);

			return $results;
		}
		elseif($method == TMDb::HEAD)
		{
			return $this->_http_parse_headers($header);
		}
		else
		{
			throw new FDVegan_TmdbException('Server error on "'.$url.'": '.$response);
		}
	}


    /**
     * Validate the response from the TMDb API.
     * Throws an exception if the API-usage limit is reached, so everything will abruptly stop rather than continue making calls.
     * @return bool  TRUE if success, FALSE if failure
     */
    private function _validateTmdbResponse($results, $url = NULL) {
        fdvegan_Content::syslog('LOG_DEBUG', "_validateTmdbResponse() for url=\"{$url}\" returned: " . print_r($results,1));
        $ret_val = FALSE;
        if (!empty($results)) {
            if (array_key_exists('status_code', $results)) {
                $statusCode = $results['status_code'];
                $statusMessage = $results['status_message'];
                if ($statusCode == 25) {
                    throw new FDVegan_TmdbException("_validateTmdbResponse({$statusCode}) TMDb API for url=\"{$url}\" says: \"{$statusMessage}\".", $statusCode, NULL, 'LOG_WARNING');
                }
                fdvegan_Content::syslog('LOG_NOTICE', "_validateTmdbResponse({$statusCode}) TMDb API for url=\"{$url}\" says: \"{$statusMessage}\".");
            } else {
                $ret_val = TRUE;
            }
        }

        /* This is as good a place as any to add a delay per TMDb API call to ensure
         * that no more than 40 API requests are made each 10 seconds.
         * Otherwise, we'll receive the error: "over the allowed limit of 40"
         */
        usleep(274000);  // must sleep for at least 1/4 of a second

        return $ret_val;
    }


}

