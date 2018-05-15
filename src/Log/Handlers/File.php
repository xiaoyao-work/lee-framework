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
namespace Lee\Log\Handlers;

class File {
	protected $config = [
		'log_time_format' => ' c ',
		'log_file_size'   => 2097152,
		'log_path'        => '',
	];

	// 实例化并传入参数
	public function __construct($config = []) {
		$this->config = array_merge($this->config, $config);
	}

	/**
	 * 日志写入接口
	 * @access public
	 * @param string $log 日志信息
	 * @param string $destination  写入目标
	 * @return void
	 */
	public function write($log, $level, $destination = '') {
		$now = date($this->config['log_time_format']);
		if (empty($destination)) {
			$destination = $this->config['log_path'] . '/' . date('y_m_d') . '/' . $level . '/' . (empty($destination) ? 'common' : $destination) . '.log';
		}
		// 自动创建日志目录
		$log_dir = dirname($destination);
		if (!is_dir($log_dir)) {
			mkdir($log_dir, 0755, true);
		}
		//检测日志文件大小，超过配置大小则备份日志文件重新生成
		if (is_file($destination) && floor($this->config['log_file_size']) <= filesize($destination)) {
			rename($destination, dirname($destination) . '/' . time() . '-' . basename($destination));
		}
		error_log("{$log}\r\n", 3, $destination);
	}
}
