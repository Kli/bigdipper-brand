<?php

namespace App\Admin\Models;

use App\Admin\Models\StoreBaseModel;

use DB;

class MbaIndex extends StoreBaseModel
{
    protected $table = 'mba_index';
    protected $primaryKey = 'brand';
    public $incrementing = false;
  
}
