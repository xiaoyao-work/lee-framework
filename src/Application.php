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
namespace Lee;

use \Lee\Environment;
use \Lee\Http\Cookies;
use \Lee\Http\Request;
use \Lee\Http\Response;
use \Lee\Log\Log;
use \Lee\Route\Router;
use \Lee\Session\Session;
use \Lee\View;

/**
 * Lee
 * @package  Lee
 *
 * @author   逍遥·李志亮 <xiaoyao.work@gmail.com>
 *
 * @since    1.0.0
 */
class Application {
	use \Lee\Traits\RegistersExceptionHandlers;
	use \Lee\Traits\Hook;
	use \Lee\Traits\Header;
	use \Lee\Traits\Flash;
	use \Lee\Traits\Config;
	use \Lee\Traits\Database;

	/**
	 * @const string
	 */
	const VERSION = 'Lee (1.0.0) (Lee Framework)';

	/**
	 * @var \Lee\Helper\Set
	 */
	public $container;

	/**
	 * @var array[\Lee]
	 */
	protected static $apps = [];

	/**
	 * All of the loaded configuration files.
	 *
	 * @var array
	 */
	protected $loadedConfigurations = [];

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $middleware;

	/**
	 * @var mixed Callable to be invoked if application error
	 */
	protected $error;

	/**
	 * @var mixed Callable to be invoked if no matching routes are found
	 */
	protected $notFound;

	/**
	 * The base path of the application installation.
	 *
	 * @var string
	 */
	protected $basePath;

	/**
	 * @var array
	 */
	protected $hooks = [
		'lee.before'          => [[]],
		'lee.before.router'   => [[]],
		'lee.before.dispatch' => [[]],
		'lee.after.dispatch'  => [[]],
		'lee.after.router'    => [[]],
		'lee.after'           => [[]],
	];

	/************************************************************************/
	/* Instantiation and Configuration **************************************/
	/************************************************************************/

	/**
	 * Constructor
	 * @param array $userSettings Associative array of application settings
	 */
	public function __construct($base_path) {
		$this->basePath = $base_path;
		// Make default if first instance
		if (is_null(static::getInstance())) {
			$this->setName('default');
		}
		// Setup IoC container
		$this->container             = new \Lee\Helper\Set();
		$this->container['settings'] = [];

		// load system config
		$this->configure('app');
		$this->configure('app', false);

		$this->registerAliases();
		$this->bootstrapContainer();
		$this->bootstrapDatabase();

		// Define default middleware stack
		$this->middleware = [$this];
		$this->middleware(new \Lee\Middleware\Flash());
		$this->middleware(new \Lee\Middleware\MethodOverride());

		$this->registerErrorHandler();
	}

	public function bootstrapContainer() {
		// Default log
		$this->container->singleton('log', function ($c) {
			return new Log();
		});
		// Default environment
		$this->container->singleton('environment', function ($c) {
			return Environment::getInstance();
		});
		// Default request
		$this->container->singleton('request', function ($c) {
			return new Request($c['environment']);
		});
		// Default response
		$this->container->singleton('response', function ($c) {
			return new Response();
		});
		// Default router
		$this->container->singleton('router', function ($c) {
			return new Router();
		});
		// Default cookie
		$this->container->singleton('cookie', function ($c) {
			return new Cookies($config = $c['settings']['cookies']);
		});
		// Session
		$this->container->singleton('session', function ($c) {
			$config     = $c['settings']['session'];
			$config['cookie_encrypt'] = $c['settings']['cookies']['encrypt'];
			$config['session_id'] = cookie($config['name']);
			return new Session($config);
		});
		// View
		$this->container->singleton('view', function ($c) {
			return new View();
		});
	}

	/**
	 * alias map
	 */
	public function registerAliases() {
		$aliases = $this->config('aliases');
		if (is_array($aliases)) {
			foreach ($aliases as $key => $value) {
				class_alias($value, $key);
			}
		}
	}

	/**
	 * Get application instance by name
	 * @param  string            $name The name of the Lee application
	 * @return \Lee\Lee|null
	 */
	public static function getInstance($name = 'default') {
		return isset(static::$apps[$name]) ? static::$apps[$name] : null;
	}

	/**
	 * Set Lee application name
	 * @param string $name The name of this Lee application
	 */
	public function setName($name) {
		$this->name          = $name;
		static::$apps[$name] = $this;
	}

	/**
	 * Get Lee application name
	 * @return string|null
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get the path to the application "app" directory.
	 *
	 * @return string
	 */
	public function appPath($path = null) {
		return $this->basePath('app') . ($path ? '/' . ltrim($path, '/') : $path);
	}

	/**
	 * Get the base path for the application.
	 *
	 * @param  string|null  $path
	 * @return string
	 */
	public function basePath($path = null) {
		if (isset($this->basePath)) {
			return $this->basePath . ($path ? '/' . ltrim($path, '/') : $path);
		}

		if ($this->runningInConsole()) {
			$this->basePath = getcwd();
		} else {
			$this->basePath = realpath(getcwd() . '/../');
		}

		return $this->basePath($path);
	}

	/**
	 * Get the storage path for the application.
	 *
	 * @param  string|null  $path
	 * @return string
	 */
	public function storagePath($path = null) {
		$path = $this->basePath('storage') . ($path ? '/' . ltrim($path, '/') : $path);
		if (!is_dir($path)) {
			@mkdir($path, 755, true);
		}
		return $path;
	}

	/**
	 * Determine if the application is running in the console.
	 *
	 * @return bool
	 */
	public function runningInConsole() {
		return php_sapi_name() == 'cli';
	}

	/************************************************************************/
	/* Application Modes ****************************************************/
	/************************************************************************/

	/**
	 * Get application mode
	 *
	 * This method determines the application mode. It first inspects the $_ENV
	 * superglobal for key `SLIM_MODE`. If that is not found, it queries
	 * the `getenv` function. Else, it uses the application `mode` setting.
	 *
	 * @return string
	 */
	public function getMode() {
		return $this->mode;
	}

	/**
	 * Configure Lee for a given mode
	 *
	 * This method will immediately invoke the callable if
	 * the specified mode matches the current application mode.
	 * Otherwise, the callable is ignored. This should be called
	 * only _after_ you initialize your Lee app.
	 *
	 * @param  string $mode
	 * @param  mixed  $callable
	 * @return void
	 */
	public function configureMode($mode, $callable) {
		if ($mode === $this->getMode() && is_callable($callable)) {
			call_user_func($callable);
		}
	}

	/************************************************************************/
	/* Application Accessors ************************************************/
	/************************************************************************/

	/**
	 * Get application log
	 * @return \Lee\Log\Log
	 */
	public function log() {
		return $this->log;
	}

	/**
	 * Get a reference to the Environment object
	 * @return \Lee\Environment
	 */
	public function environment() {
		return $this->environment;
	}

	/**
	 * Get the Request object
	 * @return \Lee\Http\Request
	 */
	public function request() {
		return $this->request;
	}

	/**
	 * Get the Response object
	 * @return \Lee\Http\Response
	 */
	public function response() {
		return $this->response;
	}

	/**
	 * Get the Router object
	 * @return \Lee\Route\Router
	 */
	public function router() {
		return $this->router;
	}

	/**
	 * Get the Session object
	 * @return \Lee\Session\Session
	 */
	public function session() {
		return $this->session;
	}

	/**
	 * Get the cookie object
	 * @return \Lee\Http\Cookies
	 */
	public function cookie() {
		return $this->cookie;
	}

	/**
	 * Get the View object
	 * @return \Lee\View
	 */
	public function view() {
		return $this->view;
	}

	/**
	 * Get the version number of the application.
	 *
	 * @return string
	 */
	public function version() {
		return self::VERSION;
	}

	/************************************************************************/
	/* Helper Methods *******************************************************/
	/************************************************************************/

	/**
	 * Get the absolute path to this Lee application's root directory
	 *
	 * This method returns the absolute path to the Lee application's
	 * directory. If the Lee application is installed in a public-accessible
	 * sub-directory, the sub-directory path will be included. This method
	 * will always return an absolute path WITH a trailing slash.
	 *
	 * @return string
	 */
	public function root() {
		return rtrim($_SERVER['DOCUMENT_ROOT'], '/') . rtrim($this->request->getRootUri(), '/') . '/';
	}

	/**
	 * Stop
	 *
	 * The thrown exception will be caught in application's `call()` method
	 * and the response will be sent as is to the HTTP client.
	 *
	 * @throws \Lee\Exception\Stop
	 */
	public function stop() {
		throw new \Lee\Exception\Stop();
	}

	/**
	 * Halt
	 *
	 * Stop the application and immediately send the response with a
	 * specific status and body to the HTTP client. This may send any
	 * type of response: info, success, redirect, client error, or server error.
	 * If you need to render a template AND customize the response status,
	 * use the application's `render()` method instead.
	 *
	 * @param int    $status  The HTTP response status
	 * @param string $message The HTTP response body
	 */
	public function halt($status, $message = '') {
		$this->cleanBuffer();
		$this->response->status($status);
		$this->response->body($message);
		$this->stop();
	}

	/**
	 * Pass
	 *
	 * The thrown exception is caught in the application's `call()` method causing
	 * the router's current iteration to stop and continue to the subsequent route if available.
	 * If no subsequent matching routes are found, a 404 response will be sent to the client.
	 *
	 * @throws \Lee\Exception\Pass
	 */
	public function pass() {
		$this->cleanBuffer();
		throw new \Lee\Exception\Pass();
	}

	/**
	 * Clean current output buffer
	 */
	protected function cleanBuffer() {
		if (ob_get_level() !== 0) {
			ob_clean();
		}
	}

	/**
	 * Set the HTTP response Content-Type
	 * @param string $type The Content-Type for the Response (ie. text/html)
	 */
	public function contentType($type) {
		$this->response->headers->set('Content-Type', $type);
	}

	/**
	 * Set the HTTP response status code
	 * @param int $code The HTTP response status code
	 */
	public function status($code) {
		$this->response->setStatus($code);
	}

	/**
	 * Get the URL for a named route
	 * @param  string            $name   The route name
	 * @param  array             $params Associative array of URL parameters and replacement values
	 * @throws \RuntimeException If named route does not exist
	 * @return string
	 */
	public function urlFor($name, $params = []) {
		return $this->request->getRootUri() . $this->router->urlFor($name, $params);
	}

	/**
	 * Redirect
	 *
	 * This method immediately redirects to a new URL. By default,
	 * this issues a 302 Found response; this is considered the default
	 * generic redirect response. You may also specify another valid
	 * 3xx status code if you want. This method will automatically set the
	 * HTTP Location header for you using the URL parameter.
	 *
	 * @param string $url    The destination URL
	 * @param int    $status The HTTP redirect status code (optional)
	 */
	public function redirect($url, $status = 302) {
		$this->response->redirect($url, $status);
		$this->halt($status);
	}

	/**
	 * RedirectTo
	 *
	 * Redirects to a specific named route
	 *
	 * @param string $route  The route name
	 * @param array  $params Associative array of URL parameters and replacement values
	 */
	public function redirectTo($route, $params = [], $status = 302) {
		$this->redirect($this->urlFor($route, $params), $status);
	}

	/****************************************************************************/
	/* Middleware ***************************************************************/
	/****************************************************************************/

	/**
	 * add middleware
	 *
	 * This method prepends new middleware to the application middleware stack.
	 * The argument must be an instance that subclasses Lee_Middleware.
	 *
	 * @param \Lee\Middleware
	 */
	public function middleware(\Lee\Middleware $newMiddleware) {
		if (in_array($newMiddleware, $this->middleware)) {
			$middleware_class = get_class($newMiddleware);
			throw new \RuntimeException("Circular Middleware setup detected. Tried to queue the same Middleware instance ({$middleware_class}) twice.");
		}
		$newMiddleware->setApplication($this);
		$newMiddleware->setNextMiddleware($this->middleware[0]);
		array_unshift($this->middleware, $newMiddleware);
	}

	/****************************************************************************/
	/* Runner *******************************************************************/
	/****************************************************************************/

	/**
	 * Run
	 *
	 * This method invokes the middleware stack, including the core Lee application;
	 * the result is an array of HTTP status, header, and body. These three items
	 * are returned to the HTTP client.
	 */
	public function run() {
		$this->applyHook('lee.before');
		// start session
		if (!$this->runningInConsole()) {
			$this->session()->start();
		}

		// Invoke middleware and application stack
		$this->middleware[0]->call();
		$this->response()->send();
		$this->applyHook('lee.after');
	}

	/**
	 * Call
	 *
	 * This method finds and iterates all route objects that match the current request URI.
	 */
	public function call() {
		try {
			if (isset($this->environment['lee.flash'])) {
				$this->view()->set('flash', $this->environment['lee.flash']);
			}
			$this->applyHook('lee.before.router', $this->router);
			$dispatched    = false;
			$matchedRoutes = $this->router->getMatchedRoutes($this->request->getMethod(), $this->request->getResourceUri(), $this->request->getHost());
			foreach ($matchedRoutes as $route) {
				try {
					$this->applyHook('lee.before.dispatch');
					$dispatched = $route->dispatch();
					$this->applyHook('lee.after.dispatch');
					if ($dispatched) {
						break;
					}
				} catch (\Lee\Exception\Pass $e) {
					continue;
				}
			}
			$this->applyHook('lee.after.router', $this);
			if (!$dispatched) {
				$this->notFound();
			}
		} catch (\Lee\Exception\Stop $e) {
			$this->response()->write(ob_get_clean());
		}
	}

	public function __get($name) {
		return $this->container->get($name);
	}

	public function __set($name, $value) {
		$this->container->set($name, $value);
	}

	public function __isset($name) {
		return $this->container->has($name);
	}

	public function __unset($name) {
		$this->container->remove($name);
	}

}
