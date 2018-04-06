<?php

namespace App\Admin\Models;

use App\Admin\Models\BaseModel;

use DB;

class BaseCbp extends BaseModel
{
	protected $table = 'cbp';
	protected $primaryKey = 'id';
	
	public static function getPlanningData($category, $year, $month)
	{
		$datas=[
				'cbpsOrigin' => [],
				'cbps' => [],
				'pcnt' => '',
			];

		$cbps = self::where('category', '=', $category)
						->where('ytdmonth', '=', $month)
						->where('year', '=', $year)
						->select('status', 'ytdtotalcustomernum', 'avgcustomerbrand', 'avgbrandsales', 'sales')
						->orderBy(DB::raw("FIELD(`status`, 'new', 'cross', 'repeat', 'total')"))
						->get()->toArray();
		
		foreach ($cbps as $key => $cbp) {
			$datas['cbpsOrigin'][$key] = $cbp;
			$datas['cbps'][$key]['status'] = transStatusToText($cbp['status'], 'cat');
			$datas['cbps'][$key]['ytdtotalcustomernum'] = number_format(round($cbp['ytdtotalcustomernum']));
			$datas['cbps'][$key]['avgcustomerbrand'] = round($cbp['avgcustomerbrand'], 2);
			$datas['cbps'][$key]['avgbrandsales'] = number_format(round($cbp['avgbrandsales']));
			$datas['cbps'][$key]['sales'] = number_format(round($cbp['sales']*100));
		}

		$datas['tfooter'] = array_pop($datas['cbps']);
		$datas['total'] = array_pop($datas['cbpsOrigin']);
		
		$pcnt = self::where('category', '=', $category)
						->where('ytdmonth', '=', $month)
						->where('year', '=', $year)
						->where('status', '=', 'total')
						->pluck('pcnt');
		
		$datas['pcnt'] = round($pcnt[0]*100);


		return $datas;
	}
}
