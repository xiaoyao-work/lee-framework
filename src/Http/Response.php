<?php
namespace Lee\Http;

/**
 * Hhailuo HTTP Response
 *
 * This class provides a human-friendly interface to the Hhailuo environment variables;
 * environment variables are passed by reference and will be modified directly.
 *
 * @package Hhailuo
 *
 * @author  逍遥·李志亮
 *
 * @since   1.0.0
 */
class Response {
	/**
	 * @var int HTTP status code
	 */
	protected $status;

	/**
	 * @var \Lee\Http\Headers
	 */
	public $headers;

	/**
	 * @var \Lee\Http\Cookies
	 */
	public $cookies;

	/**
	 * @var string HTTP response body
	 */
	protected $body;

	/**
	 * @var int Length of HTTP response body
	 */
	protected $length;

	/**
	 * Constructor
	 * @param string                   $body   The HTTP response body
	 * @param int                      $status The HTTP response status
	 * @param \Lee\Http\Headers|array $headers The HTTP response headers
	 */
	public function __construct($body = '', $status = 200, $headers = []) {
		$this->app = app();
		$this->setStatus($status);
		$this->headers = new \Lee\Http\Headers(['Content-Type' => 'text/html', 'X-Powered-By' => $this->app->version()]);
		$this->headers->replace($headers);
		$this->write($body);
	}

	public function getStatus() {
		return $this->status;
	}

	public function setStatus($status) {
		$this->status = (int) $status;
	}

	/**
	 * DEPRECATION WARNING! Use `getStatus` or `setStatus` instead.
	 *
	 * Get and set status
	 * @param  int|null $status
	 * @return int
	 */
	public function status($status = null) {
		if (!is_null($status)) {
			$this->status = (int) $status;
		}

		return $this->status;
	}

	/**
	 * DEPRECATION WARNING! Access `headers` property directly.
	 *
	 * Get and set header
	 * @param  string      $name  Header name
	 * @param  string|null $value Header value
	 * @return string|obj         Header value | self
	 */
	public function header($name, $value = null) {
		if (is_array($name)) {
		 	$this->headers->replace($name);
		 	return $this;
		}
		if (!is_null($value)) {
			$this->headers->set($name, $value);
		 	return $this;
		}
		return $this->headers->get($name);
	}

	/**
	 * DEPRECATION WARNING! Access `headers` property directly.
	 *
	 * Get headers
	 * @return \Lee\Http\Headers
	 */
	public function headers() {
		return $this->headers;
	}

	public function getBody() {
		return $this->body;
	}

	public function setBody($content) {
		$this->write($content, true);
		return $this;
	}

	/**
	 * DEPRECATION WARNING! use `getBody` or `setBody` instead.
	 *
	 * Get and set body
	 * @param  string|null $body Content of HTTP response body
	 * @return string
	 */
	public function body($body = null) {
		if (!is_null($body)) {
			$this->write($body, true);
		}
		return $this->body;
	}

	/**
	 * Append HTTP response body
	 * @param  string   $body       Content to append to the current HTTP response body
	 * @param  bool     $replace    Overwrite existing response body?
	 * @return string               The updated HTTP response body
	 */
	public function write($body, $replace = false) {
		if ($replace) {
			$this->body = $body;
		} else {
			$this->body .= (string) $body;
		}
		$this->length = strlen($this->body);
		return $this->body;
	}

	public function getLength() {
		return $this->length;
	}

	/**
	 * DEPRECATION WARNING! Use `getLength` or `write` or `body` instead.
	 *
	 * Get and set length
	 * @param  int|null $length
	 * @return int
	 */
	public function length($length = null) {
		if (!is_null($length)) {
			$this->length = (int) $length;
		}

		return $this->length;
	}

	/**
	 * Finalize
	 *
	 * This prepares this response and returns an array
	 * of [status, headers, body]. This array is passed to outer middleware
	 * if available or directly to the Lee run method.
	 *
	 * @return array[int status, array headers, string body]
	 */
	public function finalize() {
		// Prepare response
		if (in_array($this->status, [204, 304])) {
			$this->headers->remove('Content-Type');
			$this->headers->remove('Content-Length');
			$this->setBody('');
		}

		return [$this->status, $this->headers, $this->body];
	}

    public function send() {
        //Fetch status, header, and body
        list($status, $headers, $body) = $this->finalize();
		$this->setBody('');
        //Send headers
        if (headers_sent() === false) {
            //Send status
            if (strpos(PHP_SAPI, 'cgi') === 0) {
                header(sprintf('Status: %s', static::getMessageForCode($status)));
            } else {
                header(sprintf('HTTP/%s %s', $this->app->config('http.version'), static::getMessageForCode($status)));
            }

            //Send headers
            foreach ($headers as $name => $value) {
                $hValues = explode("\n", $value);
                foreach ($hValues as $hVal) {
                    header("$name: $hVal", false);
                }
            }
        }

        //Send body, but only if it isn't a HEAD request
        if (!$this->app->request()->isHead()) {
            echo $body;
        }
    }

	/**
	 * Redirect
	 *
	 * This method prepares this response to return an HTTP Redirect response
	 * to the HTTP client.
	 *
	 * @param string $url    The redirect destination
	 * @param int    $status The redirect HTTP status code
	 */
	public function redirect($url, $status = 302) {
		$this->setStatus($status);
		$this->headers->set('Location', $url);
	}

	/**
	 * Helpers: Empty?
	 * @return bool
	 */
	public function isEmpty() {
		return in_array($this->status, [201, 204, 304]);
	}

	/**
	 * Helpers: Informational?
	 * @return bool
	 */
	public function isInformational() {
		return $this->status >= 100 && $this->status < 200;
	}

	/**
	 * Helpers: OK?
	 * @return bool
	 */
	public function isOk() {
		return $this->status === 200;
	}

	/**
	 * Helpers: Successful?
	 * @return bool
	 */
	public function isSuccessful() {
		return $this->status >= 200 && $this->status < 300;
	}

	/**
	 * Helpers: Redirect?
	 * @return bool
	 */
	public function isRedirect() {
		return in_array($this->status, [301, 302, 303, 307]);
	}

	/**
	 * Helpers: Redirection?
	 * @return bool
	 */
	public function isRedirection() {
		return $this->status >= 300 && $this->status < 400;
	}

	/**
	 * Helpers: Forbidden?
	 * @return bool
	 */
	public function isForbidden() {
		return $this->status === 403;
	}

	/**
	 * Helpers: Not Found?
	 * @return bool
	 */
	public function isNotFound() {
		return $this->status === 404;
	}

	/**
	 * Helpers: Client error?
	 * @return bool
	 */
	public function isClientError() {
		return $this->status >= 400 && $this->status < 500;
	}

	/**
	 * Helpers: Server Error?
	 * @return bool
	 */
	public function isServerError() {
		return $this->status >= 500 && $this->status < 600;
	}

	/**
	 * Get message for HTTP status code
	 * @param  int         $status
	 * @return string|null
	 */
	public static function getMessageForCode($status) {
		if (isset(self::$messages[$status])) {
			return self::$messages[$status];
		} else {
			return null;
		}
	}

	/**
	 * @var array HTTP response codes and messages
	 */
	protected static $messages = [
		//Informational 1xx
		100 => '100 Continue',
		101 => '101 Switching Protocols',
		//Successful 2xx
		200 => '200 OK',
		201 => '201 Created',
		202 => '202 Accepted',
		203 => '203 Non-Authoritative Information',
		204 => '204 No Content',
		205 => '205 Reset Content',
		206 => '206 Partial Content',
		226 => '226 IM Used',
		//Redirection 3xx
		300 => '300 Multiple Choices',
		301 => '301 Moved Permanently',
		302 => '302 Found',
		303 => '303 See Other',
		304 => '304 Not Modified',
		305 => '305 Use Proxy',
		306 => '306 (Unused)',
		307 => '307 Temporary Redirect',
		//Client Error 4xx
		400 => '400 Bad Request',
		401 => '401 Unauthorized',
		402 => '402 Payment Required',
		403 => '403 Forbidden',
		404 => '404 Not Found',
		405 => '405 Method Not Allowed',
		406 => '406 Not Acceptable',
		407 => '407 Proxy Authentication Required',
		408 => '408 Request Timeout',
		409 => '409 Conflict',
		410 => '410 Gone',
		411 => '411 Length Required',
		412 => '412 Precondition Failed',
		413 => '413 Request Entity Too Large',
		414 => '414 Request-URI Too Long',
		415 => '415 Unsupported Media Type',
		416 => '416 Requested Range Not Satisfiable',
		417 => '417 Expectation Failed',
		418 => '418 I\'m a teapot',
		422 => '422 Unprocessable Entity',
		423 => '423 Locked',
		426 => '426 Upgrade Required',
		428 => '428 Precondition Required',
		429 => '429 Too Many Requests',
		431 => '431 Request Header Fields Too Large',
		//Server Error 5xx
		500 => '500 Internal Server Error',
		501 => '501 Not Implemented',
		502 => '502 Bad Gateway',
		503 => '503 Service Unavailable',
		504 => '504 Gateway Timeout',
		505 => '505 HTTP Version Not Supported',
		506 => '506 Variant Also Negotiates',
		510 => '510 Not Extended',
		511 => '511 Network Authentication Required',
	];
}
