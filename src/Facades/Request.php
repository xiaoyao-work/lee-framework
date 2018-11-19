<?php
namespace Lee\Facades;

use Illuminate\Support\Facades\Facade;

/**
 *
 * @see \Lee\Http\Request
 */
class Request extends Facade {
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() {
        return 'request';
    }
}
