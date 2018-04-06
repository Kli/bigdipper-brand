<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected $connection = 'mysql_store_base';
    
}
