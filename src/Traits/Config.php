<?php
/**
 * Config Trait
 * @package lee
 * @author  逍遥·李志亮
 * @since   1.0.0
 */
namespace Lee\Traits;

use Illuminate\Support\Arr;

trait Config {

    /**
     * Load a configuration file into the application.
     *
     * @param  string  $name
     * @return void
     */
    public function configure($name, $add_filename_to_key = true) {
        $key = $name . $add_filename_to_key;
        if (isset($this->loadedConfigurations[$key])) {
            return;
        }
        $this->loadedConfigurations[$key] = true;
        $default_config_path               = realpath(__DIR__ . '/../../config/' . $name . '.php');
        if (file_exists($default_config_path)) {
            $default_config = require $default_config_path;
            $add_filename_to_key ? $this->config([$name => $default_config]) : $this->config($default_config);
        }
        $self_config_path = $this->basePath('config') . '/' . $name . '.php';
        if (file_exists($self_config_path)) {
            $self_config = require $self_config_path;
            $add_filename_to_key ? $this->config([$name => $self_config]) : $this->config($self_config);
        }
        if (defined('APP_ENV')) {
            if (file_exists($custom_config_path = $this->basePath('config') . '/' . APP_ENV . '/' . $name . '.php')) {
                $custom_config = require $custom_config_path;
                $add_filename_to_key ? $this->config([$name => $custom_config]) : $this->config($custom_config);
            }
        }
    }

    /**
     * Configure Lee Settings
     *
     * This method defines application settings and acts as a setter and a getter.
     *
     * If only one argument is specified and that argument is a string, the value
     * of the setting identified by the first argument will be returned, or NULL if
     * that setting does not exist.
     *
     * If only one argument is specified and that argument is an associative array,
     * the array will be merged into the existing application settings.
     *
     * If two arguments are provided, the first argument is the name of the setting
     * to be created or updated, and the second argument is the setting value.
     *
     * @param  string|array $name  If a string, the name of the setting to set or retrieve. Else an associated array of setting names and values
     * @param  mixed        $value If name is a string, the value of the setting identified by $name
     * @return mixed        The value of a setting if only one argument is a string
     */
    public function config($name, $value = true) {
        $c = $this->container;
        if (is_array($name)) {
            if (true === $value) {
                $c['settings'] = array_replace_recursive($c['settings'], array_change_key_case($name));
            } else {
                $c['settings'] = array_merge($c['settings'], array_change_key_case($name));
            }
        } elseif (func_num_args() === 1) {
            return Arr::get($c['settings'], $name);
        } else {
            $c['settings'] = Arr::set($c['settings'], $name, $value);
        }
        return null;
    }


}