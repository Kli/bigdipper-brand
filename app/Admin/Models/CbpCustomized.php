<?php

namespace App\Admin\Models;

use App\Admin\Models\StoreBaseModel;
use Encore\Admin\Facades\Admin;
use DB;

class CbpCustomized extends StoreBaseModel
{
    protected $table = 'cbp_customized';
    protected $primaryKey = 'category';
    public $incrementing = false;
    public $timestamps = false;
}
