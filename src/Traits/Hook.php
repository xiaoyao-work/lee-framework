<?php
/**
 * Hook Trait
 * @package lee
 * @author  逍遥·李志亮
 * @since   1.0.0
 */
namespace Lee\Traits;

trait Hook {

	/****************************************************************************/
	/* Hooks ********************************************************************/
	/****************************************************************************/

	/**
	 * Assign hook
	 * @param string $name     The hook name
	 * @param mixed  $callable A callable object
	 * @param int    $priority The hook priority; 1 = high, 10 = low
	 */
	public function hook($name, $callable, $priority = 10) {
		if (!isset($this->hooks[$name])) {
			$this->hooks[$name] = [[]];
		}
		if (is_callable($callable)) {
			$this->hooks[$name][(int) $priority][] = $callable;
		}
	}

	/**
	 * Invoke hook
	 * @param string $name The hook name
	 * @param mixed  ...   (Optional) Argument(s) for hooked functions, can specify multiple arguments
	 */
	public function applyHook($name) {
		if (!isset($this->hooks[$name])) {
			$this->hooks[$name] = [[]];
		}
		if (!empty($this->hooks[$name])) {
			// Sort by priority, low to high, if there's more than one priority
			if (count($this->hooks[$name]) > 1) {
				ksort($this->hooks[$name]);
			}

			$args = func_get_args();
			array_shift($args);

			foreach ($this->hooks[$name] as $priority) {
				if (!empty($priority)) {
					foreach ($priority as $callable) {
						call_user_func_array($callable, $args);
					}
				}
			}
		}
	}

	/**
	 * Get hook listeners
	 *
	 * Return an array of registered hooks. If `$name` is a valid
	 * hook name, only the listeners attached to that hook are returned.
	 * Else, all listeners are returned as an associative array whose
	 * keys are hook names and whose values are arrays of listeners.
	 *
	 * @param  string       $name A hook name (Optional)
	 * @return array|null
	 */
	public function getHooks($name = null) {
		if (!is_null($name)) {
			return isset($this->hooks[(string) $name]) ? $this->hooks[(string) $name] : null;
		} else {
			return $this->hooks;
		}
	}

	/**
	 * Clear hook listeners
	 *
	 * Clear all listeners for all hooks. If `$name` is
	 * a valid hook name, only the listeners attached
	 * to that hook will be cleared.
	 *
	 * @param string $name A hook name (Optional)
	 */
	public function clearHooks($name = null) {
		if (!is_null($name) && isset($this->hooks[(string) $name])) {
			$this->hooks[(string) $name] = [[]];
		} else {
			foreach ($this->hooks as $key => $value) {
				$this->hooks[$key] = [[]];
			}
		}
	}
}