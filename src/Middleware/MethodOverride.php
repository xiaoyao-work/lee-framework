<?php
/**
 * Lee - a micro PHP framework
 *
 * @version     1.0.0
 * @package     xiaoyao-work/Lee
 *
 * @author      逍遥·李志亮 <xiaoyao.work@gmail.com>
 * @copyright   2017 逍遥·李志亮
 * @license     http://www.hhailuo.com/license
 *
 * @link        http://www.hhailuo.com/lee
 */
namespace Lee\Middleware;

/**
 * HTTP Method Override
 *
 * This is middleware for a Lee application that allows traditional
 * desktop browsers to submit pseudo PUT and DELETE requests by relying
 * on a pre-determined request parameter. Without this middleware,
 * desktop browsers are only able to submit GET and POST requests.
 *
 * This middleware is included automatically!
 *
 * @package    Lee
 */
class MethodOverride extends \Lee\Middleware {
	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * Constructor
	 * @param  array  $settings
	 */
	public function __construct($settings = []) {
		$this->settings = array_merge(['key' => '_METHOD'], $settings);
	}

	/**
	 * Call
	 *
	 * Implements Lee middleware interface. This method is invoked and passed
	 * an array of environment variables. This middleware inspects the environment
	 * variables for the HTTP method override parameter; if found, this middleware
	 * modifies the environment settings so downstream middleware and/or the Lee
	 * application will treat the request with the desired HTTP method.
	 *
	 * @return array[status, header, body]
	 */
	public function call() {
		$env = $this->app->environment();
		if (isset($env['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
			// Header commonly used by Backbone.js and others
			$env['lee.method_override.original_method'] = $env['REQUEST_METHOD'];
			$env['REQUEST_METHOD']                      = strtoupper($env['HTTP_X_HTTP_METHOD_OVERRIDE']);
		} elseif (isset($env['REQUEST_METHOD']) && $env['REQUEST_METHOD'] === 'POST') {
			// HTML Form Override
			$req    = new \Lee\Http\Request($env);
			$method = $req->post($this->settings['key']);
			if ($method) {
				$env['lee.method_override.original_method'] = $env['REQUEST_METHOD'];
				$env['REQUEST_METHOD']                      = strtoupper($method);
			}
		}
		$this->next->call();
	}
}
