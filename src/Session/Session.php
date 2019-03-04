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

namespace Lee\Session;

use Lee\Helper\Set;

/**
 * Session 处理类
 * @author 逍遥·李志亮 <xiaoyao.working@gmail.com>
 */
class Session extends Set {

    protected $options = [
        'name'          => 'lee_sessionid',
        'expires'       => 0,
        'cookie_domain' => '',
        'cookie_path'   => '/',
        'use_cookies'   => 1,
    ];

    /**
     * @var bool
     */
    protected $started = false;

    /**
     * Constructor
     * @param array $this->options session 基础配置
     */
    public function __construct(array $options = []) {
        $this->options                    = array_merge($this->options, $options);
        $this->options['cookie_lifetime'] = $this->options['gc_maxlifetime'] = $this->options['expires'];
        session_register_shutdown();
        $this->setOptions($this->options);
        $session_handler = isset($this->options['hanlder']) ? $this->options['hanlder'] : '';
        if ($session_handler) {
            // 读取session驱动
            $class  = strpos($session_handler, '\\') ? $session_handler : 'Lee\\Session\\Handlers\\' . ucwords(strtolower($session_handler));
            $hander = new $class();
            session_set_save_handler(
                [ & $hander, "open"],
                [ & $hander, "close"],
                [ & $hander, "read"],
                [ & $hander, "write"],
                [ & $hander, "destroy"],
                [ & $hander, "gc"]);
        }
        $this->start();
    }

    public function start() {
        if ($this->started) {
            return true;
        }

        if (\PHP_SESSION_ACTIVE === session_status()) {
            throw new \RuntimeException('Failed to start the session: already started by PHP.');
        }

        if (ini_get('session.use_cookies') && headers_sent($file, $line)) {
            throw new \RuntimeException(sprintf('Failed to start the session because headers have already been sent by "%s" at line %d.', $file, $line));
        }
        // ok to try and start the session
        if (!session_start()) {
            throw new \RuntimeException('Failed to start the session');
        }
        $this->started = true;
        // setcookie(session_name(), session_id(), ini_get('session.cookie_lifetime'), ini_get('session.cookie_path'), ini_get('session.cookie_domain'), ini_get('session.cookie_secure'), ini_get('session.cookie_httponly'));
    }

    public function stop() {
        session_write_close();
    }

    public function destroy() {
        // 销毁session
        $_SESSION = [];
        session_unset();
        session_destroy();
    }

    /**
     * Sets session.* ini variables.
     *
     * For convenience we omit 'session.' from the beginning of the keys.
     * Explicitly ignores other ini keys.
     *
     * @param array $options Session ini directives array(key => value)
     *
     * @see http://php.net/session.configuration
     */
    public function setOptions(array $options) {
        $validOptions = array_flip([
            'cache_limiter', 'cookie_domain', 'cookie_httponly',
            'cookie_lifetime', 'cookie_path', 'cookie_secure',
            'entropy_file', 'entropy_length', 'gc_divisor',
            'gc_maxlifetime', 'gc_probability', 'hash_bits_per_character',
            'hash_function', 'name', 'referer_check',
            'serialize_handler', 'use_cookies',
            'use_only_cookies', 'use_trans_sid', 'upload_progress.enabled',
            'upload_progress.cleanup', 'upload_progress.prefix', 'upload_progress.name',
            'upload_progress.freq', 'upload_progress.min-freq', 'url_rewriter.tags',
        ]);

        foreach ($options as $key => $value) {
            if (isset($validOptions[$key])) {
                ini_set('session.' . $key, $value);
            }
        }
    }

    /**
     * Set data key to value
     * @param string $key   The data key
     * @param mixed  $value The data value
     */
    public function set($key, $value) {
        $_SESSION[$this->normalizeKey($key)] = $value;
    }

    /**
     * Get data value with key
     * @param  string $key     The data key
     * @param  mixed  $default The value to return if data key does not exist
     * @return mixed  The data value, or the default value
     */
    public function get($key, $default = null) {
        if ($this->has($key)) {
            $isInvokable = is_object($_SESSION[$this->normalizeKey($key)]) && method_exists($_SESSION[$this->normalizeKey($key)], '__invoke');

            return $isInvokable ? $_SESSION[$this->normalizeKey($key)]($this) : $_SESSION[$this->normalizeKey($key)];
        }

        return $default;
    }

    /**
     * Add data to set
     * @param array $items Key-value array of data to append to this set
     */
    public function replace($items) {
        foreach ($items as $key => $value) {
            $this->set($key, $value); // Ensure keys are normalized
        }
    }

    /**
     * Fetch set data
     * @return array This set's key-value data array
     */
    public function all() {
        return $_SESSION;
    }

    /**
     * Fetch set data keys
     * @return array This set's key-value data array keys
     */
    public function keys() {
        return array_keys($_SESSION);
    }

    /**
     * Does this set contain a key?
     * @param  string    $key The data key
     * @return boolean
     */
    public function has($key) {
        return array_key_exists($this->normalizeKey($key), $_SESSION);
    }

    /**
     * Remove value with key from this set
     * @param string $key The data key
     */
    public function remove($key) {
        unset($_SESSION[$this->normalizeKey($key)]);
    }

    /**
     * Clear all values
     */
    public function clear() {
        $this->destroy();
    }
}