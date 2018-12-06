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

use Lee\Blade\FileViewFinder;
use Lee\Blade\Factory;
use Lee\Blade\Compilers\BladeCompiler;
use Lee\Blade\Engines\CompilerEngine;
use Lee\Blade\Filesystem;
use Lee\Blade\Engines\EngineResolver;

/**
 * Lee
 * @package  Lee
 *
 * @author   逍遥·李志亮 <xiaoyao.work@gmail.com>
 *
 * @since    1.0.0
 */
class View {
	/**
	 * Data available to the view templates
	 * @var \Lee\Helper\Set
	 */
	protected $data;

    /**
     * @var Lee\Application Reference to the primary application instance
     */
    protected $app;

	/**
	 * Constructor
	 */
	public function __construct($config = []) {
        $this->app = app();
		$this->data = new \Lee\Helper\Set();
        $config = array_merge([
            'cache_path' => $this->app->storagePath('view'),
            'view_path' => $this->app->appPath('Views')
        ], $config);

        // get an instance of factory
        $this->factory = $this->buildFactory($config);
	}

    /*public static function registerDirective($directive, Closure $fun) {

    }*/

    protected function buildFactory($config) {
        $file = new Filesystem;
        $compiler = new BladeCompiler($file, $config['cache_path']);

        // you can add a custom directive if you want
        /*$compiler->directive('datetime', function($timestamp) {
            return preg_replace('/(\(\d+\))/', '<?php echo date("Y-m-d H:i:s", $1); ?>', $timestamp);
        });*/

        $resolver = new EngineResolver;
        $resolver->register('blade', function () use ($compiler) {
            return new CompilerEngine($compiler);
        });
        return new Factory($resolver, new FileViewFinder($file, [$config['view_path']]));
    }

	/**
	 * Display template
	 *
	 * This method echoes the rendered template to the current output buffer
	 *
	 * @param  string   $template   Pathname of template file relative to templates directory
	 * @param  array    $data       Any additonal data to be passed to the template.
	 */
	public function display($template, $data = null) {
		echo $this->fetch($template, $data);
	}

	/**
	 * Return the contents of a rendered template file
	 *
	 * @param    string $template   The template pathname, relative to the template base directory
	 * @param    array  $data       Any additonal data to be passed to the template.
	 * @return string               The rendered template
	 */
	public function fetch($template, $data = null) {
		return $this->render($template, $data);
	}

	/**
	 * Render a template file
	 *
	 * NOTE: This method should be overridden by custom view subclasses
	 *
	 * @param  string $template     The template pathname, relative to the template base directory
	 * @param  array  $data         Any additonal data to be passed to the template.
	 * @return string               The rendered template
	 * @throws \RuntimeException    If resolved template pathname is not a valid file
	 */
	protected function render($template, $data = null) {
		return $this->factory->make($template, array_merge($this->data->all(), (array)$data))->render();
	}

    /**
     * Get the view factory instance.
     *
     * @return \Lee\Blade\Factory
     */
    public function getFactory() {
        return $this->factory;
    }

    /**
     * Does view data have value with key?
     * @param  string  $key
     * @return boolean
     */
    public function has($key) {
        return $this->data->has($key);
    }

    /**
     * Return view data value with key
     * @param  string $key
     * @return mixed
     */
    public function get($key) {
        return $this->data->get($key);
    }

    /**
     * Set view data value with key
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value) {
        $this->data->set($key, $value);
    }

    /**
     * Set view data value as Closure with key
     * @param string $key
     * @param mixed $value
     */
    public function keep($key, \Closure $value) {
        $this->data->keep($key, $value);
    }

    /**
     * Return view data
     * @return array
     */
    public function all() {
        return $this->data->all();
    }

    /**
     * Replace view data
     * @param  array  $data
     */
    public function replace(array $data) {
        $this->data->replace($data);
    }

    /**
     * Clear view data
     */
    public function clear() {
        $this->data->clear();
    }

}