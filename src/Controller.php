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

use InvalidArgumentException;
use \Lee\Exception\Stop as StopException;

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
        $this->view->display($this->getTemplate($template), $data);
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

    protected function getTemplate($template) {
        $current_route = \Router::getCurrentRoute();
        if (empty($template)) {
            return trim($this->viewPathPrefix, '/') . '.' . strtolower($current_route->getController()) . '.' . $current_route->getAction();
        }
        try {
            $this->view->getFactory()->getFinder()->find($template);
        } catch (\InvalidArgumentException $e) {
            $template_arr         = preg_split("/[\/\.]/", $template);
            $default_template_arr = preg_split("/[\/\.]/", trim($this->viewPathPrefix, '/') . '/' . strtolower($current_route->getController()) . '/' . $current_route->getAction());
            $template             = implode('/', array_reverse(array_replace(array_reverse($default_template_arr), array_reverse($template_arr))));
        }
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
            throw new StopException("", 0);
        case 'XML':
            // 返回xml格式数据
            $this->app->response()->header(['Content-Type' => 'text/xml', 'charset' => 'utf-8'])->setBody(xml_encode($data))->send();
            throw new StopException("", 0);
        case 'JSONP':
            $handler = isset($_GET[config('var_jsonp_handler')]) ? $_GET[config('var_jsonp_handler')] : config('default_jsonp_handler');
            // 返回JSON数据格式到客户端 包含状态信息
            $this->app->response()->header(['Content-Type' => 'application/json', 'charset' => 'utf-8'])->setBody($handler . '(' . json_encode($data, $json_option) . ');')->send();
            throw new StopException("", 0);
        default:
            // 用于扩展其他返回格式数据
            do_action('lee.ajaxReturn', $this, $data);
            throw new StopException("", 0);
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
     * 析构方法
     * @access public
     */
    public function __destruct() {
        // 执行后续操作
    }
}