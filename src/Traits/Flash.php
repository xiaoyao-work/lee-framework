<?php
/**
 * Hook Trait
 * @package lee
 * @author  逍遥·李志亮
 * @since   1.0.0
 */
namespace Lee\Traits;

trait Flash {
    /************************************************************************/
    /* Flash Messages *******************************************************/
    /************************************************************************/

    /**
     * Set flash message for subsequent request
     * @param string $key
     * @param mixed  $value
     */
    public function flash($key, $value) {
        if (isset($this->environment['lee.flash'])) {
            $this->environment['lee.flash']->set($key, $value);
        }
    }

    /**
     * Set flash message for current request
     * @param string $key
     * @param mixed  $value
     */
    public function flashNow($key, $value) {
        if (isset($this->environment['lee.flash'])) {
            $this->environment['lee.flash']->now($key, $value);
        }
    }

    /**
     * Keep flash messages from previous request for subsequent request
     */
    public function flashKeep() {
        if (isset($this->environment['lee.flash'])) {
            $this->environment['lee.flash']->keep();
        }
    }

    /**
     * Get all flash messages
     */
    public function flashData() {
        if (isset($this->environment['lee.flash'])) {
            return $this->environment['lee.flash']->getMessages();
        }
    }

}