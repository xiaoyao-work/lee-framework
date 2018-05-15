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

namespace Lee\Log;

/**
 * 日志处理类
 * @author 逍遥·李志亮 <xiaoyao.working@gmail.com>
 */
class Log {
	// 日志级别 从上到下，由低到高
	const FAULT  = 'fault'; // 严重错误: 导致系统崩溃无法使用
	const ALERT  = 'alert'; // 警戒性错误: 必须被立即修改的错误
	const ERR    = 'error'; // 一般错误: 一般性错误
	const WARN   = 'warn'; // 警告性错误: 需要发出警告的错误
	const NOTICE = 'notic'; // 通知: 程序可以运行但是还不够完美的错误
	const INFO   = 'info'; // 信息: 程序输出信息
	const DEBUG  = 'debug'; // 调试: 调试信息

	// 日志信息
	protected $log     = [];
	protected $storage = null;

	/**
	 * 日志初始化
	 * @param array $config 日志配置
	 */
	public function __construct($config = []) {
		$config  = array_replace(app()->config('log'), $config);
		$handler = $config['handler'];
		$class   = strpos($handler, '\\') ? $handler : '\\Lee\\Log\\Handlers\\' . ucwords(strtolower($handler));
		unset($config['handler']);
		$this->storage = new $class($config);
	}

	public function fault($message, $filename = '') {
		$this->write($message, self::FAULT, $filename);
	}

	public function alert($message, $filename = '') {
		$this->write($message, self::ALERT, $filename);
	}

	public function error($message, $filename = '') {
		$this->write($message, self::ERR, $filename);
	}

	public function warn($message, $filename = '') {
		$this->write($message, self::WARN, $filename);
	}

	public function notice($message, $filename = '') {
		$this->write($message, self::NOTICE, $filename);
	}

	public function info($message, $filename = '') {
		$this->write($message, self::INFO, $filename);
	}

	public function debug($message, $filename = '') {
		$this->write($message, self::DEBUG, $filename);
	}

	/**
	 * 记录日志 并且会过滤未经设置的级别
	 * @static
	 * @access public
	 * @param string $message 日志信息
	 * @param string $level  日志级别
	 * @param boolean $record  是否强制记录
	 * @return void
	 */
	public function record($message, $level = self::INFO) {
		$this->log[$level][] = "{$level}: {$message}\r\n";
	}

	/**
	 * 日志保存
	 * @static
	 * @access public
	 * @param integer $handler 日志记录方式
	 * @param string $destination  写入目标
	 * @return void
	 */
	public function save($destination = '') {
		if (empty($this->log)) {
			return;
		}
		foreach ($this->log as $level => $log) {
			$message = implode('', $log);
			$this->storage->write($message, $level, $destination);
		}
		// 保存后清空日志缓存
		$this->log = [];
	}

	/**
	 * 写入日志到文件
	 * @static
	 * @access protected
	 * @param string $message 日志信息
	 * @param string $level  日志级别
	 * @param string $destination  写入目标
	 * @return void
	 */
	protected function write($message, $level = self::INFO, $destination = '') {
		$message = "[" . date("Y-m-d H:i:s") . "] " . $_SERVER['REMOTE_ADDR'] . ' ' . $_SERVER['REQUEST_URI'] . "\r\n" . (is_string($message) ? $message : json_encode($message, JSON_UNESCAPED_UNICODE));
		$this->storage->write($message, $level, $destination);
	}
}
