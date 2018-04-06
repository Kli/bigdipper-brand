<?php
use DB;

function getLastUpdateTime($database)
{
	$updateTime = DB::connection('store_db')->table('storeinfo')
		->where('store', '=', $database)
		->value('end');

	return $updateTime;
}

function indexCellColor($value,$status="")
{
	if($value < 90 or strpos($status, "已关")){
	  $color="badge bg-red";
	}
	if($value>=90 && $value<=110){
	  $color="badge bg-yellow";
	}
	if($value>110 or strpos($status, "新开未关")){
	  $color="badge bg-green";
	}
	return $color;
}

function cellColor($value, $storeRate, $fixed=0)
{
	if (empty($fixed)){
		if ($storeRate>5) {
			if($value < 0){
				$color="badge bg-red";
			}
			if($value>=0 && $value<=$storeRate){
				$color="badge bg-yellow";
			}
			if($value>$storeRate){
				$color="badge bg-green";
			}
		} elseif($storeRate<-5){
			if($value < $storeRate){
				$color="badge bg-red";
			}
			if($value>=$storeRate && $value<=0){
				$color="badge bg-yellow";
			}
			if($value>0){
				$color="badge bg-green";
			}
		} else {
			if($value < -5){
				$color="badge bg-red";
			}
			if($value>=-5 && $value<=5){
				$color="badge bg-yellow";
			}
			if($value>5){
				$color="badge bg-green";
			}
		}
	} else {
		if($value < -5){
			$color="badge bg-red";
		}
		if($value>=-5 && $value<=5){
			$color="badge bg-yellow";
		}
		if($value>5){
			$color="badge bg-green";
		}
	} 

	return $color;
}

function cellCustomerColor($value)
{
	if($value < 0){
		$color="badge bg-blue";
	} elseif($value > 0){
		$color="badge bg-aqua";
	} else {
		$color="badge";
	}
	return $color;
}

function createCatAction($row) 
{

	if($row->evol_ttl_sales != -1) {
		if($row->evol_ttl_sales < -0.05){
			if($row->evol_ttl_sales < -0.20) {
				$action="该品类销售呈<b class=\"text-red\">明显下降</b>趋势。";
			} else {
				$action="该品类销售同比<b class=\"text-red\">下降</b>。";
			}

			if($row->evol_new_sales<-0.05 || $row->evol_repeat_sales<-0.05 || $row->evol_cross_sales<-0.05){
				$action.="其中 ";
				if($row->evol_new_sales<-0.05){
					$action.="<b class=\"text-aqua\">门店招新 </b>";
				}
				if($row->evol_repeat_sales<-0.05){
					$action.="<b class=\"text-aqua\">品类复购 </b>";
				}
				if($row->evol_cross_sales<-0.05){
					$action.="<b class=\"text-aqua\">品类招新 </b>";
				}
				$action.="需要重点关注。";
			}
			if($row->evol_pcnt>0){
				$action.="预测生意的下降受到<b class=\"text-yellow\"> 会员占比 </b>变化较大影响。";
			}
		}
		if($row->evol_ttl_sales>=-0.05 && $row->evol_ttl_sales<=0.05){
			$action="该品类表现<b class=\"text-yellow\">稳定</b>。";
			if($row->evol_new_sales<0 || $row->evol_repeat_sales<0 || $row->evol_cross_sales<0){
				if($row->evol_new_sales<0){
					$action.="<b class=\"text-aqua\">门店招新 </b>";
				}
				if($row->evol_repeat_sales<0){
					$action.="<b class=\"text-aqua\">品类复购 </b>";
				}
				if($row->evol_cross_sales<0){
					$action.="<b class=\"text-aqua\">品类招新 </b>";
				}
				$action.="可作为提升的突破点。";
			}
		}
		if($row->evol_ttl_sales>0.05){
			$action="该品类表现<b class=\"text-green\">良好</b>，需要保持。";
			if($row->evol_new_sales<0 || $row->evol_repeat_sales<0 || $row->evol_cross_sales<0){
				if($row->evol_new_sales<0){
					$action.="<b class=\"text-aqua\">门店招新 </b>";
				}
				if($row->evol_repeat_sales<0){
					$action.="<b class=\"text-aqua\">品类复购 </b>";
				}
				if($row->evol_cross_sales<0){
					$action.="<b class=\"text-aqua\">品类招新 </b>";
				}
				$action.="存在提升空间。";
			}
			if($row->evol_pcnt<0){
				$action.="预测生意的上涨受到<b class=\"text-yellow\"> 会员占比 </b>变化较大影响。";
			}
		}
	} else {
		$action = "<span class=\"text-muted\">该品类已撤柜或本年新开。</span>";
	}

	return $action;
}

function cellStoreColor($value)
{
	if($value <-10){
		$color="red";
	}
	if($value>=-10 && $value<=10){
		$color="yellow";
	}
	if($value>10){
		$color="green";
	}
	return $color;
}

function progressColor($value)
{
	if($value <35){
		$color="red";
	}
	if($value>=35 && $value<=65){
		$color="yellow";
	}
	if($value>65){
		$color="green";
	}
	return $color;
}

function transStatusToText($status,$type)
{
	switch ($status) {
		case 'new':
			$text = "门店招新";
			break;
		case 'repeat':
			if($type=="brand"){
				$text = "品牌复购";
			} elseif($type=="cat") {
				$text = "品类复购";
			}
			break;
		case 'cross':
			if($type=="brand"){
				$text = "品牌招新";
			} elseif($type=="cat") {
				$text = "品类招新";
			}
			break;
		case 'total':
			$text = "总计";
			break;
		default:
			$text = "";
			break;
	}

	return $text;
}

function getQuadrant($x,$y)
{
	if ($x>=0 && $y>=0)
	{
		$quadrant = 1;
	}
	if ($x<0 && $y>=0)
	{
		$quadrant = 2;
	}
	if ($x<0 && $y<0)
	{
		$quadrant = 3;
	}
	if ($x>=0 && $y<0)
	{
		$quadrant = 4;
	}
	return $quadrant;
}

function getPriorityStars($priority)
{
	// $priority = intval($priority);

	$stars = [
				0 => '',
				1 => '<i class="fa fa-fw fa-star text-yellow"></i>',
				2 => '<i class="fa fa-fw fa-star text-yellow"></i>
						<i class="fa fa-fw fa-star text-yellow"></i>',
				3 => '<i class="fa fa-fw fa-star text-yellow"></i>
						<i class="fa fa-fw fa-star text-yellow"></i>
						<i class="fa fa-fw fa-star text-yellow"></i>',
				4 => '<i class="fa fa-fw fa-star text-yellow"></i>
						<i class="fa fa-fw fa-star text-yellow"></i>
						<i class="fa fa-fw fa-star text-yellow"></i>
						<i class="fa fa-fw fa-star text-yellow"></i>',
				5 => '<i class="fa fa-fw fa-star text-yellow"></i>
						<i class="fa fa-fw fa-star text-yellow"></i>
						<i class="fa fa-fw fa-star text-yellow"></i>
						<i class="fa fa-fw fa-star text-yellow"></i>
						<i class="fa fa-fw fa-star text-yellow"></i>',
				6 => '<i class="fa fa-fw fa-star text-yellow"></i>
						<i class="fa fa-fw fa-star text-yellow"></i>
						<i class="fa fa-fw fa-star text-yellow"></i>
						<i class="fa fa-fw fa-star text-yellow"></i>
						<i class="fa fa-fw fa-star text-yellow"></i>
						<i class="fa fa-fw fa-star text-yellow"></i>',
			];

	return $stars[$priority];
}

function transToRate($value,$bool)
{
	if($bool){
		$output=round($value*100,0)."%";
	} else {
		$output=round($value,1);
	}
	return $output;
}