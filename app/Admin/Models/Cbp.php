<?php

namespace App\Admin\Models;

use App\Admin\Models\StoreBaseModel;

use App\Admin\Models\CbpEvol;
use DB;

class Cbp extends StoreBaseModel
{
	protected $table = 'cbp';
	protected $primaryKey = 'id';
	
	public static function getPlanningData($category, $year, $month, $compareDatas=[])
	{
		$datas=[
				'cbpsOrigin' => [],
				'cbps' => [],
				'originCbps' => [],
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
			$datas['originCbps'][$key]['ytdtotalcustomernum'] = round($cbp['ytdtotalcustomernum']);
			$datas['cbps'][$key]['avgcustomerbrand'] = round($cbp['avgcustomerbrand'], 2);
			$datas['originCbps'][$key]['avgcustomerbrand'] = round($cbp['avgcustomerbrand'], 2);
			$datas['cbps'][$key]['avgbrandsales'] = number_format(round($cbp['avgbrandsales']));
			$datas['originCbps'][$key]['avgbrandsales'] = round($cbp['avgbrandsales']);
			$datas['cbps'][$key]['sales'] = number_format(round($cbp['sales']*100));
			$datas['originCbps'][$key]['sales'] = round($cbp['sales']*100);
		}
		if (!empty($compareDatas)) {
			$storeRate = CbpEvol::getStoreRate();
			
			foreach ($datas['cbps'] as $key => &$data) {

				$crate = round((round($data['ytdtotalcustomernum'])-$compareDatas[$key]['ytdtotalcustomernum'])/$compareDatas[$key]['ytdtotalcustomernum']*100);
				$brate = round((round($data['avgcustomerbrand'],2)-$compareDatas[$key]['avgcustomerbrand'])/$compareDatas[$key]['avgcustomerbrand']*100);
				$bsrate = round((round($data['avgbrandsales'])-$compareDatas[$key]['avgbrandsales'])/$compareDatas[$key]['avgbrandsales']*100);
				$srate = round((round($data['sales'])-$compareDatas[$key]['sales'])/$compareDatas[$key]['sales']*100);

				$data['ytdtotalcustomernum'] = number_format(round($data['ytdtotalcustomernum'])).' <span class="'.cellColor($crate, $storeRate).'">'.(($crate>0)?'+':'').$crate.'%</span></td>';
				$data['avgcustomerbrand'] = round($data['avgcustomerbrand'], 2).' <span class="'.cellColor($brate, $storeRate).'">'.(($brate>0)?'+':'').$brate.'%</span></td>';
				$data['avgbrandsales'] = number_format(round($data['avgbrandsales'])).' <span class="'.cellColor($bsrate, $storeRate).'">'.(($bsrate>0)?'+':'').$bsrate.'%</span></td>';
				$data['sales'] = number_format(round($data['sales']*100)).' <span class="'.cellColor($srate, $storeRate).'">'.(($srate>0)?'+':'').$srate.'%</span></td>';
			}
		}

		$pcnt = self::where('category', '=', $category)
						->where('ytdmonth', '=', $month)
						->where('year', '=', $year)
						->where('status', '=', 'total')
						->pluck('pcnt');
		
		$datas['pcnt'] = round($pcnt[0]*100);


		return $datas;
	}
}
