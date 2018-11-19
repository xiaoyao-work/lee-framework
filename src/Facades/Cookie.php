<?php
namespace Lee\Facades;

use Illuminate\Support\Facades\Facade;

/**
 *
 * @see \Lee\Http\Cookies
 */
class Cookie extends Facade {
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() {
        return 'cookie';
    }
}
