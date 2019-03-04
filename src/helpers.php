<?php
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Lee\Application;

if (!function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param  string  $make
     * @return \Lee\Application
     */
    function app($make = null) {
        if (is_null($make)) {
            return Application::getInstance();
        }
        return Application::getInstance($make);
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the path to the base of the install.
     *
     * @param  string  $path
     * @return string
     */
    function base_path($path = '') {
        return app()->basePath() . ($path ? '/' . $path : $path);
    }
}

if (!function_exists('config')) {
    /**
     * Get / set the specified configuration value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param  array|string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function config($key = null, $default = null) {
        if (is_null($key)) {
            return app()->settings;
        }
        $config = app()->config($key);
        return is_null($config) ? $default : $config;
    }
}

if (!function_exists('response')) {
    /**
     * Return a new response from the application.
     *
     * @param  string  $content
     * @param  int     $status
     * @param  array   $headers
     * @return \Lee\Http\Response
     */
    function response($content = '', $status = 200, array $headers = []) {
        return new \Lee\Http\Response($content, $status, $headers);
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the path to the storage folder.
     *
     * @param  string  $path
     * @return string
     */
    function storage_path($path = '') {
        return app()->storagePath($path);
    }
}

if (!function_exists('route')) {
    /**
     * Generate a url for the application.
     *
     * @param  string  $path
     * @param  mixed   $parameters
     * @param  bool    $secure
     * @return string
     */
    function route($name, $params = []) {
        app()->request()->urlFor($name, $params);
    }
}

if (!function_exists('is_cli')) {
    function is_cli() {
        return app()->runningInConsole();
    }
}

if (!function_exists('is_ajax')) {
    function is_ajax() {
        return app()->request()->isAjax();
    }
}

if (!function_exists('cookie')) {
    function cookie($key, $value = null, $options = []) {
        if (func_num_args() == 1) {
            return \Cookie::get($key);
        } else {
            if ($value === null) {
                return \Cookie::remove($key, $options);
            }
            $options['value'] = $value;
            return \Cookie::set($key, $options);
        }
    }
}

if (!function_exists('session')) {
    function session($key, $value = null) {
        if (func_num_args() == 1) {
            if (empty($key)) {
                return \Session::clear();
            } else {
                return \Session::get($key);
            }
        } else {
            if ($value === null) {
                return \Session::remove($key);
            }
            return \Session::set($key, $value);
        }
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate a CSRF token form field.
     *
     * @return \Illuminate\Support\HtmlString
     */
    function csrf_field() {
        return new HtmlString('<input type="hidden" name="_token" value="' . csrf_token() . '">');
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the CSRF token value.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    function csrf_token() {
        $session = app()->session();
        $env     = app()->environment();
        $token_key = md5($env['REQUEST_URI']);
        if ($session->get('_token.' . $token_key)) {
            $token = $session->get('_token.' . $token_key);
        } else {
            $token = md5(Str::random());
            $session->set('_token.' . $token_key, $token);
        }
        cookie('_token', $token_key . '_' . $token);
        header('_token' . ': ' . $token_key . '_' . $token);
        return $token_key . "_" . $token; // ['_token', $token_key, $token];
    }
}

if (!function_exists("plugins_path")) {
    function plugins_path() {
        return app()->appPath('Plugins');
    }
}

if (!function_exists("load_plugins")) {
    function load_plugins() {
        $plugins_path = plugins_path();
        if (!is_dir($plugins_path)) {
            return;
        }
        $handle = opendir($plugins_path);
        if ($handle) {
            while (false !== ($item = readdir($handle))) {
                if ('.' != $item && '..' != $item) {
                    $dir = $plugins_path . DIRECTORY_SEPARATOR . $item;
                    if (is_dir($dir) && file_exists($dir . DIRECTORY_SEPARATOR . 'function.php')) {
                        require_once $dir . DIRECTORY_SEPARATOR . 'function.php';
                    }
                }
            }
            closedir($handle);
        }
    }
}

if (!function_exists("get_registerdomain")) {
    function get_registerdomain($host = '') {
        $host = empty($host) ? $_SERVER['HTTP_HOST'] : $host;
        $extract = new \LayerShifter\TLDExtract\Extract();
        $result = $extract->parse($host);
        return $result->isValidDomain() ? $result->getRegistrableDomain() : $host;
    }
}
