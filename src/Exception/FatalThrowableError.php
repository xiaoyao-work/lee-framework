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
 * Fatal Throwable Error.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class FatalThrowableError extends FatalErrorException {
	public function __construct(\Throwable $e) {
		if ($e instanceof \ParseError) {
			$message  = 'Parse error: ' . $e->getMessage();
			$severity = E_PARSE;
		} elseif ($e instanceof \TypeError) {
			$message  = 'Type error: ' . $e->getMessage();
			$severity = E_RECOVERABLE_ERROR;
		} else {
			$message  = $e->getMessage();
			$severity = E_ERROR;
		}

		\ErrorException::__construct(
			$message,
			$e->getCode(),
			$severity,
			$e->getFile(),
			$e->getLine()
		);

		$this->setTrace($e->getTrace());
	}
}
