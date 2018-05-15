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

/**
 * 中间件
 * @author 逍遥·李志亮 <xiaoyao.working@gmail.com>
 */
abstract class Middleware {
	/**
	 * @var Lee\Application Reference to the primary application instance
	 */
	protected $app;

	/**
	 * @var mixed Reference to the next downstream middleware
	 */
	protected $next;

	/**
	 * Set application
	 *
	 * This method injects the primary Slim application instance into
	 * this middleware.
	 *
	 * @param  Lee\Application $application
	 */
	final public function setApplication($application) {
		$this->app = $application;
	}

	/**
	 * Get application
	 *
	 * This method retrieves the application previously injected
	 * into this middleware.
	 *
	 * @return Lee\Application
	 */
	final public function getApplication() {
		return $this->app;
	}

	/**
	 * Set next middleware
	 *
	 * This method injects the next downstream middleware into
	 * this middleware so that it may optionally be called
	 * when appropriate.
	 *
	 * @param \Lee|\Lee\Middleware
	 */
	final public function setNextMiddleware($nextMiddleware) {
		$this->next = $nextMiddleware;
	}

	/**
	 * Get next middleware
	 *
	 * This method retrieves the next downstream middleware
	 * previously injected into this middleware.
	 *
	 * @return Lee\Application|\Lee\Middleware
	 */
	final public function getNextMiddleware() {
		return $this->next;
	}

	/**
	 * Call
	 *
	 * Perform actions specific to this middleware and optionally
	 * call the next downstream middleware.
	 */
	abstract public function call();
}
