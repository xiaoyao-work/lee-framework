<?php
namespace Lee\Http;

/**
 * HTTP Utilities
 *
 * This class provides useful methods for handling HTTP requests.
 *
 * @package Hhailuo
 * @author  逍遥·李志亮
 * @since   1.0.0
 */
class Util {
    /**
     * Strip slashes from string or array
     *
     * This method strips slashes from its input. By default, this method will only
     * strip slashes from its input if magic quotes are enabled. Otherwise, you may
     * override the magic quotes setting with either TRUE or FALSE as the send argument
     * to force this method to strip or not strip slashes from its input.
     *
     * @param  array|string   $rawData
     * @param  bool           $overrideStripSlashes
     * @return array|string
     */
    public static function stripSlashesIfMagicQuotes($rawData, $overrideStripSlashes = null) {
        $strip = is_null($overrideStripSlashes) ? get_magic_quotes_gpc() : $overrideStripSlashes;
        if ($strip) {
            return self::stripSlashes($rawData);
        }

        return $rawData;
    }

    /**
     * Strip slashes from string or array
     * @param  array|string   $rawData
     * @return array|string
     */
    protected static function stripSlashes($rawData) {
        return is_array($rawData) ? array_map(['self', 'stripSlashes'], $rawData) : stripslashes($rawData);
    }

    /**
     * Encrypt data
     *
     * This method will encrypt data using a given key, vector, and cipher.
     * By default, this will encrypt data using the RIJNDAEL/AES 256 bit cipher. You
     * may override the default cipher and cipher mode by passing your own desired
     * cipher and cipher mode as the final key-value array argument.
     *
     * @param  string    $data     The unencrypted data
     * @param  string    $key      The encryption key
     * @param  string    $iv       The encryption initialization vector
     * @param  array     $settings Optional key-value array with custom algorithm and mode
     * @return string
     */
    public static function encrypt($data, $key, $iv, $settings = []) {
        if ($data === '' || !extension_loaded('mcrypt')) {
            return $data;
        }

        //Merge settings with defaults
        $defaults = [
            'algorithm' => MCRYPT_RIJNDAEL_256,
            'mode'      => MCRYPT_MODE_CBC,
        ];
        $settings = array_merge($defaults, $settings);

        //Get module
        $module = mcrypt_module_open($settings['algorithm'], '', $settings['mode'], '');

        //Validate IV
        $ivSize = mcrypt_enc_get_iv_size($module);
        if (strlen($iv) > $ivSize) {
            $iv = substr($iv, 0, $ivSize);
        }

        //Validate key
        $keySize = mcrypt_enc_get_key_size($module);
        if (strlen($key) > $keySize) {
            $key = substr($key, 0, $keySize);
        }

        //Encrypt value
        mcrypt_generic_init($module, $key, $iv);
        $res = @mcrypt_generic($module, $data);
        mcrypt_generic_deinit($module);

        return $res;
    }

    /**
     * Decrypt data
     *
     * This method will decrypt data using a given key, vector, and cipher.
     * By default, this will decrypt data using the RIJNDAEL/AES 256 bit cipher. You
     * may override the default cipher and cipher mode by passing your own desired
     * cipher and cipher mode as the final key-value array argument.
     *
     * @param  string    $data     The encrypted data
     * @param  string    $key      The encryption key
     * @param  string    $iv       The encryption initialization vector
     * @param  array     $settings Optional key-value array with custom algorithm and mode
     * @return string
     */
    public static function decrypt($data, $key, $iv, $settings = []) {
        if ($data === '' || !extension_loaded('mcrypt')) {
            return $data;
        }

        //Merge settings with defaults
        $defaults = [
            'algorithm' => MCRYPT_RIJNDAEL_256,
            'mode'      => MCRYPT_MODE_CBC,
        ];
        $settings = array_merge($defaults, $settings);

        //Get module
        $module = mcrypt_module_open($settings['algorithm'], '', $settings['mode'], '');

        //Validate IV
        $ivSize = mcrypt_enc_get_iv_size($module);
        if (strlen($iv) > $ivSize) {
            $iv = substr($iv, 0, $ivSize);
        }

        //Validate key
        $keySize = mcrypt_enc_get_key_size($module);
        if (strlen($key) > $keySize) {
            $key = substr($key, 0, $keySize);
        }

        //Decrypt value
        mcrypt_generic_init($module, $key, $iv);
        $decryptedData = @mdecrypt_generic($module, $data);
        $res           = rtrim($decryptedData, "\0");
        mcrypt_generic_deinit($module);

        return $res;
    }

    /**
     * Encode secure cookie value
     *
     * This method will create the secure value of an HTTP cookie. The
     * cookie value is encrypted and hashed so that its value is
     * secure and checked for integrity when read in subsequent requests.
     *
     * @param  string    $value     The insecure HTTP cookie value
     * @param  int       $expires   The UNIX timestamp at which this cookie will expire
     * @param  string    $secret    The secret key used to hash the cookie value
     * @param  int       $algorithm The algorithm to use for encryption
     * @param  int       $mode      The algorithm mode to use for encryption
     * @return string
     */
    public static function encodeSecureCookie($value, $expires, $secret, $algorithm, $mode) {
        $key          = hash_hmac('sha1', (string) $expires, $secret);
        $iv           = self::getIv($expires, $secret);
        $secureString = base64_encode(
            self::encrypt(
                $value,
                $key,
                $iv,
                [
                    'algorithm' => $algorithm,
                    'mode'      => $mode,
                ]
            )
        );
        $verificationString = hash_hmac('sha1', $expires . $value, $key);

        return implode('|', [$expires, $secureString, $verificationString]);
    }

    /**
     * Decode secure cookie value
     *
     * This method will decode the secure value of an HTTP cookie. The
     * cookie value is encrypted and hashed so that its value is
     * secure and checked for integrity when read in subsequent requests.
     *
     * @param  string         $value     The secure HTTP cookie value
     * @param  string         $secret    The secret key used to hash the cookie value
     * @param  int            $algorithm The algorithm to use for encryption
     * @param  int            $mode      The algorithm mode to use for encryption
     * @return bool|string
     */
    public static function decodeSecureCookie($val, $secret, $algorithm, $mode) {
        if ($val) {
            $value = explode('|', $val);
            // 非加密Cookie
            if (count($value) !== 3) {
                return $val;
            }
            if (((int) $value[0] === 0 || (int) $value[0] > time())) {
                $key  = hash_hmac('sha1', $value[0], $secret);
                $iv   = self::getIv($value[0], $secret);
                $data = self::decrypt(
                    base64_decode($value[1]),
                    $key,
                    $iv,
                    [
                        'algorithm' => $algorithm,
                        'mode'      => $mode,
                    ]
                );
                $verificationString = hash_hmac('sha1', $value[0] . $data, $key);
                if ($verificationString === $value[2]) {
                    return $data;
                }
            }
        }

        return false;
    }

    /**
     * Generate a random IV
     *
     * This method will generate a non-predictable IV for use with
     * the cookie encryption
     *
     * @param  int    $expires The UNIX timestamp at which this cookie will expire
     * @param  string $secret  The secret key used to hash the cookie value
     * @return string Hash
     */
    private static function getIv($expires, $secret) {
        $data1 = hash_hmac('sha1', 'a' . $expires . 'b', $secret);
        $data2 = hash_hmac('sha1', 'z' . $expires . 'y', $secret);

        return pack("h*", $data1 . $data2);
    }

    /**
     * Parse cookie header
     *
     * This method will parse the HTTP request's `Cookie` header
     * and extract cookies into an associative array.
     *
     * @param  string
     * @return array
     */
    public static function parseCookieHeader($header) {
        $cookies      = [];
        $header       = rtrim($header, "\r\n");
        $headerPieces = preg_split('@\s*[;,]\s*@', $header);
        foreach ($headerPieces as $c) {
            $cParts = explode('=', $c, 2);
            if (count($cParts) === 2) {
                $key   = urldecode($cParts[0]);
                $value = urldecode($cParts[1]);
                if (!isset($cookies[$key])) {
                    $cookies[$key] = $value;
                }
            }
        }

        return $cookies;
    }


}
