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

namespace Lee\Exception;

/**
 * Fatal Error Exception.
 *
 * @author Konstanton Myakshin <koc-dp@yandex.ru>
 */
class FatalErrorException extends \ErrorException {
	public function __construct($message, $code, $severity, $filename, $lineno, $traceOffset = null, $traceArgs = true, array $trace = null) {
		parent::__construct($message, $code, $severity, $filename, $lineno);

		if (null !== $trace) {
			if (!$traceArgs) {
				foreach ($trace as &$frame) {
					unset($frame['args'], $frame['this'], $frame);
				}
			}

			$this->setTrace($trace);
		} elseif (null !== $traceOffset) {
			if (function_exists('xdebug_get_function_stack')) {
				$trace = xdebug_get_function_stack();
				if (0 < $traceOffset) {
					array_splice($trace, -$traceOffset);
				}

				foreach ($trace as &$frame) {
					if (!isset($frame['type'])) {
						// XDebug pre 2.1.1 doesn't currently set the call type key http://bugs.xdebug.org/view.php?id=695
						if (isset($frame['class'])) {
							$frame['type'] = '::';
						}
					} elseif ('dynamic' === $frame['type']) {
						$frame['type'] = '->';
					} elseif ('static' === $frame['type']) {
						$frame['type'] = '::';
					}

					// XDebug also has a different name for the parameters array
					if (!$traceArgs) {
						unset($frame['params'], $frame['args']);
					} elseif (isset($frame['params']) && !isset($frame['args'])) {
						$frame['args'] = $frame['params'];
						unset($frame['params']);
					}
				}

				unset($frame);
				$trace = array_reverse($trace);
			} elseif (function_exists('symfony_debug_backtrace')) {
				$trace = symfony_debug_backtrace();
				if (0 < $traceOffset) {
					array_splice($trace, 0, $traceOffset);
				}
			} else {
				$trace = [];
			}

			$this->setTrace($trace);
		}
	}

	protected function setTrace($trace) {
		$traceReflector = new \ReflectionProperty('Exception', 'trace');
		$traceReflector->setAccessible(true);
		$traceReflector->setValue($this, $trace);
	}
}
