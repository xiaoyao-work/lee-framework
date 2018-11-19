<?php
namespace Lee\Facades;

use Illuminate\Support\Facades\Facade;

/**
 *
 * @see \Lee\View
 */
class View extends Facade {
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() {
        return 'view';
    }
}
