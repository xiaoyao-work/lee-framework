<?php
/**
 * 异常处理
 * @package lee
 * @author  逍遥·李志亮
 * @since   1.0.0
 */
namespace Lee\Traits;

use ErrorException;
use Exception;
use Lee\Exception\FatalErrorException;
use Lee\Exception\FatalThrowableError;


trait RegistersExceptionHandlers {

	/**
	 * Set the error handling for the application.
	 *
	 * @return void
	 */
	public function registerErrorHandler() {
		// 设定Error 和 Fatal Error处理
		error_reporting(-1);
   		ini_set('display_errors', 0);
		set_error_handler([$this, 'handleError']);
		set_exception_handler([$this, 'handleException']);
		register_shutdown_function([$this, 'handleShutdown']);
	}

	/**
	 * Convert a PHP error to an ErrorException.
	 *
	 * @param  int  $level
	 * @param  string  $message
	 * @param  string  $file
	 * @param  int  $line
	 * @param  array  $context
	 * @return void
	 *
	 * @throws \ErrorException
	 */
	public function handleError($level, $message, $file = '', $line = 0, $context = []) {
		/*if (error_reporting() & $level) {
			throw new ErrorException($message, 0, $level, $file, $line);
		}*/
		switch ($level) {
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
			case E_USER_ERROR:
				throw new ErrorException($message, 0, $level, $file, $line);
			break;
			case E_DEPRECATED:
				if (rand(1,100) == 10) {
					$this->log()->error("[$level] $message " . $file . " 第 $line 行.", $this->storagePath('log') . '/' . date("Y-m-d") . ".log");
				}
				return ;
				break;
			default:
				if (defined('APP_DEBUG') && APP_DEBUG) {
					if ($level) {
						throw new ErrorException($message, 0, $level, $file, $line);
					}
				}
				$this->log()->error("[$level] $message " . $file . " 第 $line 行.", $this->storagePath('log') . '/' . date("Y-m-d") . ".log");
			break;
		}
	}

	/**
	 * Handle the PHP shutdown event.
	 *
	 * @return void
	 */
	public function handleShutdown() {
		if (!is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
			$this->handleException($this->fatalExceptionFromError($error, 0));
		}
	}

    /**
     * Handle an uncaught exception from the application.
     *
     * Note: Most exceptions can be handled via the try / catch block in
     * the HTTP and Console kernels. But, fatal error exceptions must
     * be handled differently since they are not normal exceptions.
     *
     * @param  \Throwable  $e
     * @return void
     */
	public function handleException($e) {
		if (!$e instanceof Exception) {
			$e = new FatalThrowableError($e);
		}
		$this->cleanBuffer();
		// IN CONSOLE
		if ($this->runningInConsole()) {
			echo "ErrorException \n" . $e->getMessage();
		} else {
			$this->contentType('text/html');
			if (defined('APP_DEBUG') && APP_DEBUG) {
				$content = $this->renderBody($e);
			} else {
				$content = $this->callErrorHandler($e);
			}
		}
		$this->response()->status(500);
		$this->response()->body($content);
		$this->response()->send();
		$log_string = "Code: " . $e->getCode();
		$log_string .= "\n Message: " . $e->getMessage();
		$log_string .= "\n File: " . $e->getFile();
		$log_string .= "\n Line: " . $e->getLine();
		$log_string .= $e->getTraceAsString();
		$this->log()->error($log_string, $this->storagePath('log') . '/' . date("Y-m-d") . ".log");
		$this->applyHook('lee.after');
	}

	/**
	 * Create a new fatal exception instance from an error array.
	 *
	 * @param  array  $error
	 * @param  int|null  $traceOffset
	 * @return Lee\Exception\FatalErrorException
	 */
	protected function fatalExceptionFromError(array $error, $traceOffset = null) {
		return new FatalErrorException(
			$error['message'], $error['type'], 0, $error['file'], $error['line'], $traceOffset
		);
	}

	/**
	 * Determine if the error type is fatal.
	 *
	 * @param  int  $type
	 * @return bool
	 */
	protected function isFatal($type) {
		return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
	}

	/**
	 * Not Found Handler
	 *
	 * This method defines or invokes the application-wide Not Found handler.
	 * There are two contexts in which this method may be invoked:
	 *
	 * 1. When declaring the handler:
	 *
	 * If the $callable parameter is not null and is callable, this
	 * method will register the callable to be invoked when no
	 * routes match the current HTTP request. It WILL NOT invoke the callable.
	 *
	 * 2. When invoking the handler:
	 *
	 * If the $callable parameter is null, Lee assumes you want
	 * to invoke an already-registered handler. If the handler has been
	 * registered and is callable, it is invoked and sends a 404 HTTP Response
	 * whose body is the output of the Not Found handler.
	 *
	 * @param mixed $callable Anything that returns true for is_callable()
	 */
	public function notFound($callable = null) {
		if (is_callable($callable)) {
			$this->notFound = $callable;
		} else {
			ob_start();
			if (is_callable($this->notFound)) {
				call_user_func($this->notFound);
			} else {
				call_user_func([$this, 'defaultNotFound']);
			}
			$this->halt(404, ob_get_clean());
		}
	}

	/**
	 * Error Handler
	 *
	 * This method defines or invokes the application-wide Error handler.
	 * There are two contexts in which this method may be invoked:
	 *
	 * 1. When declaring the handler:
	 *
	 * If the $argument parameter is callable, this
	 * method will register the callable to be invoked when an uncaught
	 * Exception is detected, or when otherwise explicitly invoked.
	 * The handler WILL NOT be invoked in this context.
	 *
	 * 2. When invoking the handler:
	 *
	 * If the $argument parameter is not callable, Lee assumes you want
	 * to invoke an already-registered handler. If the handler has been
	 * registered and is callable, it is invoked and passed the caught Exception
	 * as its one and only argument. The error handler's output is captured
	 * into an output buffer and sent as the body of a 500 HTTP Response.
	 *
	 * @param mixed $argument Callable|\Exception
	 */
	public function error($argument = null) {
		if (is_callable($argument)) {
			//Register error handler
			$this->error = $argument;
		} else {
			//Invoke error handler
			$this->response->status(500);
			$this->response->body($this->callErrorHandler($argument));
			$this->stop();
		}
	}

	/**
	 * Call error handler
	 *
	 * This will invoke the custom or default error handler
	 * and RETURN its output.
	 *
	 * @param  \Exception|null $argument
	 * @return string
	 */
	protected function callErrorHandler($argument = null) {
		ob_start();
		if (is_callable($this->error)) {
			call_user_func_array($this->error, [$argument]);
		} else {
			call_user_func_array([$this, 'defaultError'], [$argument]);
		}

		return ob_get_clean();
	}

	/**
	 * Generate diagnostic template markup
	 *
	 * This method accepts a title and body content to generate an HTML document layout.
	 *
	 * @param  string   $title The title of the HTML template
	 * @param  string   $body  The body content of the HTML template
	 * @return string
	 */
	protected static function generateTemplateMarkup($title, $body) {
		return sprintf("<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body><h1>%s</h1>%s</body></html>", $title, $title, $body);
	}

	/**
	 * Default Not Found handler
	 */
	protected function defaultNotFound() {
		echo static::generateTemplateMarkup('404 Page Not Found', '<p>The page you are looking for could not be found. Check the address bar to ensure your URL is spelled correctly. If all else fails, you can visit our home page at the link below.</p><a href="' . $this->request->getRootUri() . '/">Visit the Home Page</a>');
	}

	/**
	 * Default Error handler
	 */
	protected function defaultError($e) {
		echo self::generateTemplateMarkup('Error', '<p>A website error has occurred. The website administrator has been notified of the issue. Sorry for the temporary inconvenience.</p>');
	}

	/**
	 * Render response body
	 * @param  \Exception $exception
	 * @return string
	 */
	protected static function renderBody($exception) {
		$title = 'Lee Application Error';
		if (is_array($exception)) {
			$code    = 500;
			$message = htmlspecialchars($exception['message']);
			$file    = $exception['file'];
			$line    = $exception['line'];
			$trace   = str_replace(['#', "\n"], ['<div>#', '</div>'], htmlspecialchars($exception['trace']));
			$type    = 'Fatal Error';
		} else {
			$code    = $exception->getCode();
			$message = htmlspecialchars($exception->getMessage());
			$file    = $exception->getFile();
			$line    = $exception->getLine();
			$trace   = str_replace(['#', "\n"], ['<div>#', '</div>'], htmlspecialchars($exception->getTraceAsString()));
			$type    = get_class($exception);
		}
		$html = sprintf('<h1>%s</h1>', $title);
		$html .= '<p>The application could not run because of the following error:</p>';
		$html .= '<h2>Details</h2>';
		$html .= sprintf('<div><strong>Type:</strong> %s</div>', $type);
		if ($code) {
			$html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);
		}
		if ($message) {
			$html .= sprintf('<div><strong>Message:</strong> %s</div>', $message);
		}
		if ($file) {
			$html .= sprintf('<div><strong>File:</strong> %s</div>', $file);
		}
		if ($line) {
			$html .= sprintf('<div><strong>Line:</strong> %s</div>', $line);
		}
		if ($trace) {
			$html .= '<h2>Trace</h2>';
			$html .= sprintf('<pre>%s</pre>', $trace);
		}

		return sprintf("<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body>%s</body></html>", $title, $html);
	}

}
