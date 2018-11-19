<?php
namespace Lee\Facades;

use Illuminate\Support\Facades\Facade;

/**
 *
 * @see \Lee\Log\Log
 */
class Log extends Facade {
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() {
        return 'log';
    }
}
