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
 * Lee
 * @package  Lee
 *
 * @author   逍遥·李志亮 <xiaoyao.work@gmail.com>
 *
 * @since    1.0.0
 */
abstract class Controller {
	/**
	 * @var Lee\Application Reference to the primary application instance
	 */
	protected $app;

	/**
	 * 视图实例对象
	 * @var view
	 * @access protected
	 */
	protected $view = null;

	/**
	 * 路由
	 * @var Lee\Route\Router
	 */
	protected $router;

	/**
	 * 当前路由
	 * @var Lee\Route\Route
	 */
	protected $route;

	protected $viewPathPrefix = '';

	public function __construct() {
		$this->app    = app();
		$this->view   = $this->app->view();
		$this->router = $this->app->router();
		$this->route  = $this->router->getCurrentRoute();
	}

	/**
	 * 模板显示 调用内置的模板引擎显示方法，
	 * @access protected
	 * @param string $templateFile 指定要调用的模板文件
	 * 默认为空 由系统自动定位模板文件
	 * @param string $charset 输出编码
	 * @param string $contentType 输出类型
	 * @param string $content 输出内容
	 * @param string $prefix 模板缓存前缀
	 * @return void
	 */
	protected function display($template = '', $data = null) {
		$template = empty($template) ? $this->getDefaultTemplate() : $template;
		$this->view->display($template, $data);
	}

	/**
	 *  获取输出页面内容
	 * 调用内置的模板引擎fetch方法，
	 * @access protected
	 * @param string $templateFile 指定要调用的模板文件
	 * 默认为空 由系统自动定位模板文件
	 * @param string $content 模板输出内容
	 * @param string $prefix 模板缓存前缀*
	 * @return string
	 */
	protected function fetch($template = '', $data = null) {
		$template = empty($template) ? $this->getDefaultTemplate() : $template;
		return $this->view->fetch($template, $data);
	}

	protected function getDefaultTemplate() {
		// $namespace     = $current_route->getNamespace();
		$current_route = $this->app->router()->getCurrentRoute();
		$template      = empty($this->viewPathPrefix) ? strtolower($current_route->getController()) . '.' . $current_route->getAction() : trim($this->viewPathPrefix, '/') . '.' . strtolower($current_route->getController()) . '.' . $current_route->getAction();
		return $template;
	}

	/**
	 * 模板变量赋值
	 * @access protected
	 * @param mixed $name 要显示的模板变量
	 * @param mixed $value 变量的值
	 * @return Action
	 */
	protected function assign($name, $value = '') {
		if (is_array($name)) {
			$this->view->replace($name);
		} else {
			$this->view->set($name, $value);
		}
		return $this;
	}

	public function __set($name, $value) {
		$this->assign($name, $value);
	}

	/**
	 * 取得模板显示变量的值
	 * @access protected
	 * @param string $name 模板显示变量
	 * @return mixed
	 */
	public function get($name = '') {
		return $this->view->get($name);
	}

	public function __get($name) {
		return $this->get($name);
	}

	/**
	 * 检测模板变量的值
	 * @access public
	 * @param string $name 名称
	 * @return boolean
	 */
	public function __isset($name) {
		return $this->get($name);
	}

	/**
	 * 魔术方法 有不存在的操作的时候执行
	 * @access public
	 * @param string $method 方法名
	 * @param array $args 参数
	 * @return mixed
	 */
	public function __call($method, $args) {
		return call_user_func_array([$this->app, $method], $args);
	}

	/**
	 * Ajax方式返回数据到客户端
	 * @access protected
	 * @param mixed $data 要返回的数据
	 * @param String $type AJAX返回数据格式
	 * @param int $json_option 传递给json_encode的option参数
	 * @return void
	 */
	protected function ajaxReturn($data, $type = '', $json_option = JSON_UNESCAPED_UNICODE) {
		if (empty($type)) {
			$type = config('default_ajax_return');
		}
		switch (strtoupper($type)) {
		case 'JSON':
			// 返回JSON数据格式到客户端 包含状态信息
			$this->app->response()->header(['Content-Type' => 'application/json', 'charset' => 'utf-8'])->setBody(json_encode($data, $json_option))->send();
			return;
		case 'XML':
			// 返回xml格式数据
			$this->app->response()->header(['Content-Type' => 'text/xml', 'charset' => 'utf-8'])->setBody(xml_encode($data))->send();
			return;
		case 'JSONP':
			$handler = isset($_GET[config('var_jsonp_handler')]) ? $_GET[config('var_jsonp_handler')] : config('default_jsonp_handler');
			// 返回JSON数据格式到客户端 包含状态信息
			$this->app->response()->header(['Content-Type' => 'application/json', 'charset' => 'utf-8'])->setBody($handler . '(' . json_encode($data, $json_option) . ');')->send();
			return;
		default:
			// 用于扩展其他返回格式数据
			$this->app->applyHook('lee.ajaxReturn', $this, $data);
		}
	}

	/**
	 * URL重定向
	 * @access protected
	 * @param string $url 跳转的URL
	 * @param integer $status 延时跳转的时间 单位为秒
	 * @return void
	 */
	protected function redirect($url, $status = 302) {
		$this->app->redirect($url, $status);
	}

	/**
	 * 操作错误跳转的快捷方法
	 * @access protected
	 * @param string $message 错误信息
	 * @param string $jumpUrl 页面跳转地址
	 * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
	 * @return void
	 */
	protected function error($message = '', $jumpUrl = '', $ajax = false) {
		$this->dispatchJump($message, 0, $jumpUrl, $ajax);
	}

	/**
	 * 操作成功跳转的快捷方法
	 * @access protected
	 * @param string $message 提示信息
	 * @param string $jumpUrl 页面跳转地址
	 * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
	 * @return void
	 */
	protected function success($message = '', $jumpUrl = '', $ajax = false) {
		$this->dispatchJump($message, 1, $jumpUrl, $ajax);
	}

	/**
	 * 默认跳转操作 支持错误导向和正确跳转
	 * 调用模板显示 默认为public目录下面的success页面
	 * 提示页面为可配置 支持模板标签
	 * @param string $message 提示信息
	 * @param Boolean $status 状态
	 * @param string $jumpUrl 页面跳转地址
	 * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
	 * @access private
	 * @return void
	 */
	private function dispatchJump($message, $status = 1, $jumpUrl = '', $ajax = false) {

	}

	/**
	 * 析构方法
	 * @access public
	 */
	public function __destruct() {
		// 执行后续操作
	}
}