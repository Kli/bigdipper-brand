<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Facades\Admin;

class StoreBaseModel extends Model
{
    protected $connection = 'store_db';
    
    public function __construct($database = '')
    {
        if ( empty(config('database.connections.store')) ) {
            $store_conn = [
                            'driver' => 'mysql',
                            'host' => env('DB_STORE_HOST', '127.0.0.1'),
                            'port' => env('DB_STORE_PORT', '3306'),
                            'database' => $database==''?Admin::user()->database:$database,
                            'username' => env('DB_STORE_USERNAME', 'forge'),
                            'password' => env('DB_STORE_PASSWORD', ''),
                            'unix_socket' => env('DB_STORE_SOCKET', ''),
                            'charset' => 'utf8mb4',
                            'collation' => 'utf8mb4_unicode_ci',
                            'prefix' => '',
                            'strict' => false,
                            'engine' => null,
                        ];
            
            config(['database.connections.store_db' => $store_conn]);
        }
        
    }
}
