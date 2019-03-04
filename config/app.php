<?php
return [
	// Application
	'mode'                  => 'production',
	// Debugging
	'debug'                 => false,
	// Logging
	'log'                   => [
		'handler'  => 'File',
		'log_path' => app()->storagePath('log'),
	],

	// Cookies
	'cookies'               => [
		'prefix'	 	=> 'lee',
		'encrypt'     => false,
		'expires'     => 0,
		'path'        => '/',
		'domain'      => null,
		'secure'      => false,
		'httponly'    => false,
		// Encryption
		'secret_key'  => 'CHANGE_ME',
		'cipher'      => MCRYPT_RIJNDAEL_256,
		'cipher_mode' => MCRYPT_MODE_CBC,
	],
	// Session
	'session'               => [
        'hanlder' => '',
		'name'    => 'lee_sessionid',
		'expires' => 3600,
		'cookie_path'    => '/',
		'cookie_domain'  => '',
		// 'cache_limiter', 'cookie_domain', 'cookie_httponly',
		// 'cookie_lifetime', 'cookie_path', 'cookie_secure',
		// 'entropy_file', 'entropy_length', 'gc_divisor',
		// 'gc_maxlifetime', 'gc_probability', 'hash_bits_per_character',
		// 'hash_function', 'name', 'referer_check',
		// 'serialize_handler', 'use_cookies',
		// 'use_only_cookies', 'use_trans_sid', 'upload_progress.enabled',
		// 'upload_progress.cleanup', 'upload_progress.prefix', 'upload_progress.name',
		// 'upload_progress.freq', 'upload_progress.min-freq', 'url_rewriter.tags'
	],
	// HTTP
	'http.version'          => '1.1',
	// Routing
	'routes'                => [
		'case_sensitive' => true,
	],
	'default_ajax_return'   => 'json', // 默认 ajax 数据返回格式
	'default_jsonp_handler' => 'lee_callback', // 默认jsonp格式返回的处理方法
	'var_jsonp_handler'		=> 'callback_method',
	'url'									=> env('APP_URL', 'http://localhost'),
];
