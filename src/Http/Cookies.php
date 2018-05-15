<?php
namespace Lee\Http;

use Lee\Helper\Set;

class Cookies extends Set {
	protected $configs = [
		'prefix'   => 'lee',
		'encrypt'  => false,
		'expires'  => 0,
		'path'     => '/',
		'domain'   => null,
		'secure'   => false,
		'httponly' => false,
	];

	/**
	 * Constructor
	 * @param array $options COOKIE 基础配置
	 */
	public function __construct(array $options = []) {
		$this->configs = array_merge($this->configs, $options);
		// Decode if encrypted
		if ($this->configs['encrypt']) {
			foreach ($_COOKIE as $key => &$value) {
				$value = Util::decodeSecureCookie(
					$value,
					$this->configs['secret_key'],
					$this->configs['cipher'],
					$this->configs['cipher_mode']
				);
				if ($value === false) {
					$this->remove($key);
				}
			}
		}
	}

	/**
	 * Set cookie
	 *
	 * The second argument may be a single scalar value, in which case
	 * it will be merged with the default settings and considered the `value`
	 * of the merged result.
	 *
	 * The second argument may also be an array containing any or all of
	 * the keys shown in the default settings above. This array will be
	 * merged with the defaults shown above.
	 *
	 * @param string $key   Cookie name
	 * @param mixed  $value Cookie settings
	 */
	public function set($key, $value) {
		if (is_array($value)) {
			$settings = array_replace($this->configs, $value);
		} else {
			$settings = array_replace($this->configs, ['value' => $value]);
		}
		$settings['expires'] = empty($settings['expires']) ? 0 : time() + $settings['expires'];
		$settings['value']   = $settings['encrypt'] ? Util::encodeSecureCookie(
			$settings['value'],
			$settings['expires'],
			$settings['secret_key'],
			$settings['cipher'],
			$settings['cipher_mode']
		) : $settings['value'];
		setcookie($this->normalizeKey($key), $settings['value'], $settings['expires'], $settings['path'], $settings['domain'], $settings['secure'], $settings['httponly']);
	}

	/**
	 * Remove cookie
	 *
	 * Unlike \Lee\Helper\Set, this will actually *set* a cookie with
	 * an expiration date in the past. This expiration date will force
	 * the client-side cache to remove its cookie with the given name
	 * and settings.
	 *
	 * @param string $key      Cookie name
	 * @param array  $settings Optional cookie settings
	 */
	public function remove($key, $settings = []) {
		$settings['value']   = '';
		$settings['expires'] = time() - 86400;
		$this->set($key, $settings);
		unset($_COOKIE[$key]);
	}

	/**
	 * Get data value with key
	 * @param  string $key     The data key
	 * @param  mixed  $default The value to return if data key does not exist
	 * @return mixed  The data value, or the default value
	 */
	public function get($key, $default = null) {
		if ($this->has($key)) {
			$isInvokable = is_object($_COOKIE[$this->normalizeKey($key)]) && method_exists($_COOKIE[$this->normalizeKey($key)], '__invoke');
			return $isInvokable ? $_COOKIE[$this->normalizeKey($key)]($this) : $_COOKIE[$this->normalizeKey($key)];
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
		return $_COOKIE;
	}

	/**
	 * Fetch set data keys
	 * @return array This set's key-value data array keys
	 */
	public function keys() {
		return array_keys($_COOKIE);
	}

	/**
	 * Does this set contain a key?
	 * @param  string    $key The data key
	 * @return boolean
	 */
	public function has($key) {
		return array_key_exists($this->normalizeKey($key), $_COOKIE);
	}

	/**
	 * Clear all values
	 */
	public function clear() {
		$prefix = func_get_arg(0);
		$prefix = empty($prefix) ? $this->configs['prefix'] : $prefix;
		if (!empty($prefix)) {
			// 如果前缀为空字符串将不作处理直接返回
			foreach ($_COOKIE as $key => $val) {
				if (0 === stripos($key, $prefix)) {
					$this->remove($key);
					unset($_COOKIE[$key]);
				}
			}
		}
	}

}
