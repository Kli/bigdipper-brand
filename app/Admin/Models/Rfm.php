<?php

namespace App\Admin\Models;

use App\Admin\Models\StoreBaseModel;

use DB;

class Rfm extends StoreBaseModel
{
    protected $table = 'rfm';
    protected $primaryKey = 'grp';
    public $incrementing = false;
    
  
}
