<?php
namespace Lee\Route;

/**
 * Router
 *
 * This class organizes, iterates, and dispatches \Lee\Route\Route objects.
 * @package Hhailuo
 *
 * @author  逍遥·李志亮
 *
 * @since   1.0.0
 */
class Router {
	/**
	 * @var Route The current route (most recently dispatched)
	 */
	protected $currentRoute;

	/**
	 * @var array Lookup hash of all route objects
	 */
	protected $routes;

	/**
	 * @var array Lookup hash of named route objects, keyed by route name (lazy-loaded)
	 */
	protected $namedRoutes;

	/**
	 * @var array  (lazy-loaded)
	 */
	protected $actionRoutes;

	/**
	 * @var array Array of route objects that match the request URI (lazy-loaded)
	 */
	protected $matchedRoutes;

	/**
	 * @var array Array containing all route groups
	 */
	protected $routeGroups;

	/**
	 * @var bool 是否匹配所有
	 */
	protected $routeRcursion = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->routes      = [];
		$this->routeGroups = [];
	}

	/**
	 * Get Current Route object or the first matched one if matching has been performed
	 * @return \Lee\Route\Route|null
	 */
	public function getCurrentRoute() {
		if ($this->currentRoute !== null) {
			return $this->currentRoute;
		}

		if (is_array($this->matchedRoutes) && count($this->matchedRoutes) > 0) {
			return $this->matchedRoutes[0];
		}

		return null;
	}

	/**
	 * Return route objects that match the given HTTP method and URI
	 * @param  string                     $httpMethod  The HTTP method to match against
	 * @param  string                     $resourceUri The resource URI to match against
	 * @param  string                     $domain      请求域名
	 * @param  bool                       $reload      Should matching routes be re-parsed?
	 * @return array[\Lee\Route\Route]
	 */
	public function getMatchedRoutes($httpMethod, $resourceUri, $domain, $reload = false) {
		if ($reload || is_null($this->matchedRoutes)) {
			$this->matchedRoutes = [];
			foreach ($this->routes as $route) {
				if (!$route->supportsDomain($domain) || (!$route->supportsHttpMethod($httpMethod) && !$route->supportsHttpMethod("ANY"))) {
					continue;
				}
				if ($route->matches($resourceUri)) {
					$this->matchedRoutes[] = $route;
					if (!$this->routeRcursion) {
						break;
					}
				}
			}
		}
		return $this->matchedRoutes;
	}

	/**
	 * Add a route object to the router
	 * @param \Lee\Route\Route $route The Lee Route
	 */
	public function addRoute(\Lee\Route\Route $route) {
		list($groupPattern, $groupMiddleware) = $this->processGroups();

		$route->setPattern($groupPattern . $route->getPattern());
		// Domain Bind
		isset($this->routeDomain) && $route->setDomain($this->routeDomain);
		// Namespace Bind
		isset($this->namespace) && $route->setNamespace($this->namespace);
		$this->routes[] = $route;

		foreach ($groupMiddleware as $middleware) {
			$route->setMiddleware($middleware);
		}
	}

	/**
	 * A helper function for processing the group's pattern and middleware
	 * @return array Returns an array with the elements: pattern, middlewareArr
	 */
	protected function processGroups() {
		$pattern    = "";
		$middleware = [];
		foreach ($this->routeGroups as $group) {
			$pattern .= $group['pattern'];
			if (is_array($group['middleware'])) {
				$middleware = array_merge($middleware, $group['middleware']);
			}
		}
		return [$pattern, $middleware];
	}

	/**
	 * Add a route group to the array
	 * @param  array|string $group      The group pattern (ie. "/books/:id" , ['prefix' => "/books/:id", 'domain' => 'www.hhailuo.com'])
	 * @param  array|null   $middleware Optional parameter array of middleware
	 * @return int          The index of the new group
	 */
	public function pushGroup($group, $middleware = []) {
		if (is_array($group)) {
			$group             = array_merge(['prefix' => '', 'domain' => '', 'namespace' => '\\'], $group);
			$pattern           = $group['prefix'];
			$this->routeDomain = $group['domain'];
			$this->namespace   = $group['namespace'];
		} else {
			$pattern = $group;
		}
		return array_push($this->routeGroups, ['pattern' => $pattern, 'middleware' => $middleware]);
	}

	/**
	 * Removes the last route group from the array
	 * @return bool True if successful, else False
	 */
	public function popGroup() {
		$this->routeDomain = null;
        $this->namespace   = null;
		return (array_pop($this->routeGroups) !== null);
	}

	/**
	 * Get URL for named route
	 * @param  string            $name   The name of the route
	 * @param  array             $params Associative array of URL parameter names and replacement values
	 * @throws \RuntimeException If named route not found
	 * @return string            The URL for the given route populated with provided replacement values
	 */
	public function urlFor($name, $params = []) {
		if (!$this->hasNamedRoute($name)) {
			throw new \RuntimeException('Named route not found for name: ' . $name);
		}
		$search = [];
		foreach ($params as $key => $value) {
			$search[] = '#:' . preg_quote($key, '#') . '\+?(?!\w)#';
		}
		$route   = $this->getNamedRoute($name);
		$pattern = preg_replace($search, $params, $route->getPattern());

		//Remove remnants of unpopulated, trailing optional pattern segments, escaped special characters
		return preg_replace('#\(/?:.+\)|\(|\)|\\\\#', '', $pattern);
	}

	/**
	 * Add named route
	 * @param  string            $name  The route name
	 * @param  \Lee\Route\Route $route The route object
	 * @throws \RuntimeException If a named route already exists with the same name
	 */
	public function addNamedRoute($name, \Lee\Route\Route $route) {
		if ($this->hasNamedRoute($name)) {
			throw new \RuntimeException('Named route already exists with name: ' . $name);
		}
		$this->namedRoutes[(string) $name] = $route;
	}

	/**
	 * Has named route
	 * @param  string $name The route name
	 * @return bool
	 */
	public function hasNamedRoute($name) {
		$this->getNamedRoutes();

		return isset($this->namedRoutes[(string) $name]);
	}

	/**
	 * Get named route
	 * @param  string                   $name
	 * @return \Lee\Route\Route|null
	 */
	public function getNamedRoute($name) {
		$this->getNamedRoutes();
		if ($this->hasNamedRoute($name)) {
			return $this->namedRoutes[(string) $name];
		}

		return null;
	}

	/**
	 * Get named routes
	 * @return \ArrayIterator
	 */
	public function getNamedRoutes() {
		if (is_null($this->namedRoutes)) {
			$this->namedRoutes = [];
			foreach ($this->routes as $route) {
				if ($route->getName() !== null) {
					$this->addNamedRoute($route->getName(), $route);
				}
			}
		}

		return new \ArrayIterator($this->namedRoutes);
	}

    /**
     * Add GET|POST|PUT|PATCH|DELETE route
     *
     * Adds a new route to the router with associated callable. This
     * route will only be invoked when the HTTP request's method matches
     * this route's method.
     *
     * ARGUMENTS:
     *
     * First:       string  The URL pattern (REQUIRED)
     * In-Between:  mixed   Anything that returns TRUE for `is_callable` (OPTIONAL)
     * Last:        mixed   Anything that returns TRUE for `is_callable` (REQUIRED)
     *
     * The first argument is required and must always be the
     * route pattern (ie. '/books/:id').
     *
     * The last argument is required and must always be the callable object
     * to be invoked when the route matches an HTTP request.
     *
     * You may also provide an unlimited number of in-between arguments;
     * each interior argument must be callable and will be invoked in the
     * order specified before the route's callable is invoked.
     *
     * USAGE:
     *
     * Lee::get('/foo'[, middleware, middleware, ...], callable);
     *
     * @param  array          (See notes above)
     * @return \Lee\Route\Route
     */
    protected function mapRoute($args) {
        $pattern  = array_shift($args);
        $callable = array_pop($args);
        $route    = new \Lee\Route\Route($pattern, $callable, app()->config('routes.case_sensitive'));
        $this->addRoute($route);
        if (count($args) > 0) {
            $route->setMiddleware($args);
        }

        return $route;
    }

    /**
     * Add generic route without associated HTTP method
     * @see    mapRoute()
     *
     * @return \Lee\Route\Route
     */
    public function map() {
        $args = func_get_args();

        return $this->mapRoute($args);
    }

    /**
     * Add GET route
     * @see    mapRoute()
     *
     * @return \Lee\Route\Route
     */
    public function get() {
        $args = func_get_args();

        return $this->mapRoute($args)->via(\Lee\Http\Request::METHOD_GET, \Lee\Http\Request::METHOD_HEAD);
    }

    /**
     * Add POST route
     * @see    mapRoute()
     *
     * @return \Lee\Route\Route
     */
    public function post() {
        $args = func_get_args();

        return $this->mapRoute($args)->via(\Lee\Http\Request::METHOD_POST);
    }

    /**
     * Add PUT route
     * @see    mapRoute()
     *
     * @return \Lee\Route\Route
     */
    public function put() {
        $args = func_get_args();

        return $this->mapRoute($args)->via(\Lee\Http\Request::METHOD_PUT);
    }

    /**
     * Add PATCH route
     * @see    mapRoute()
     *
     * @return \Lee\Route\Route
     */
    public function patch() {
        $args = func_get_args();

        return $this->mapRoute($args)->via(\Lee\Http\Request::METHOD_PATCH);
    }

    /**
     * Add DELETE route
     * @see    mapRoute()
     *
     * @return \Lee\Route\Route
     */
    public function delete() {
        $args = func_get_args();

        return $this->mapRoute($args)->via(\Lee\Http\Request::METHOD_DELETE);
    }

    /**
     * Add OPTIONS route
     * @see    mapRoute()
     *
     * @return \Lee\Route\Route
     */
    public function options() {
        $args = func_get_args();

        return $this->mapRoute($args)->via(\Lee\Http\Request::METHOD_OPTIONS);
    }

    /**
     * Route Groups
     *
     * This method accepts a route pattern and a callback all Route
     * declarations in the callback will be prepended by the group(s)
     * that it is in
     *
     * Accepts the same parameters as a standard route so:
     * (pattern, middleware1, middleware2, ..., $callback)
     */
    public function group() {
        $args     = func_get_args();
        $pattern  = array_shift($args);
        $callable = array_pop($args);
        $this->pushGroup($pattern, $args);
        if (is_callable($callable)) {
            call_user_func($callable);
        }
        $this->popGroup();
    }

    /*
     * Add route for any HTTP method
     * @see    mapRoute()
     * @return \Lee\Route\Route
     */
    public function any() {
        $args = func_get_args();

        return $this->mapRoute($args)->via("ANY");
    }


}
