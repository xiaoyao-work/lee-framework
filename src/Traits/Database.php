<?php
/**
 * Database Trait
 * @package lee
 * @author  逍遥·李志亮
 * @since   1.0.0
 */
namespace Lee\Traits;

use Illuminate\Database\Capsule\Manager as DB;

trait Database {
    public function bootstrapDatabase() {
        $this->configure('database');
        $connections = $this->config('database.connections');
        $db = new DB;
        foreach ($connections as $key => $connection) {
            $db->addConnection($connection, $key);
        }
        $default_connection = $this->config('database.default');
        if (isset($connections[$default_connection])) {
            $db->addConnection($connections[$default_connection]);
        }
        $db->setAsGlobal();
        $db->bootEloquent();
        $this->container['db'] = $db;
    }
}