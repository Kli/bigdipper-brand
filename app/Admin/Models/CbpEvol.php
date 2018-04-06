<?php

namespace App\Admin\Models;

use App\Admin\Models\StoreBaseModel;
use Encore\Admin\Facades\Admin;
use DB;

class CbpEvol extends StoreBaseModel
{
    protected $table = 'cbp_evol';
    protected $primaryKey = 'category';
    public $incrementing = false;
    
    public static function getStoreRate($year='', $month='')
    {
        $updateTime = getLastUpdateTime(Admin::user()->database, 'cbp_evol');
        $updateTimeYear = date('Y', strtotime($updateTime));
        $updateTimeMonth = date('m', strtotime($updateTime));

    	$year = ($year=='') ? $updateTimeYear : $year;
    	$month = ($month=='') ? $updateTimeMonth : $month;

    	$ninecellTtls = DB::connection('store_db')->table('ninecell_ttl')
    					->select('purchase_year', 'sales_ttl')
    					->where('purchase_month', '=', $month)
    					->orderBy('purchase_month')
    					->get();

    	foreach ($ninecellTtls as $ninecellTtl) {
    		$salesGlobal[$ninecellTtl->purchase_year] = $ninecellTtl->sales_ttl;
    	}

    	$rate = round(($salesGlobal[$year]-$salesGlobal[$year-1])/$salesGlobal[$year-1]*100,2);

    	return $rate;
    }
}
