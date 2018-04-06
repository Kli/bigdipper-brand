<?php

namespace App\Admin\Controllers;

use App\Admin\Models\MemberBrand;
use App\Admin\Models\MemberCat;
use App\Admin\Models\MemberStore;

use App\Admin\Models\Bbp;
use App\Admin\Models\Cbp;

use App\Admin\Controllers\StoreBaseController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;

use Encore\Admin\Widgets\Alert;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Chart\Chartbar;
use Encore\Admin\Widgets\Chart\Line;

use DB, Request;

class MemberWarningController extends StoreBaseController
{
	public function index()
	{
		if (Admin::user()->isAdministrator() && Admin::user()->database=='') {
			return Admin::content(function (Content $content) {

				$content->header('生意预警');
				$content->description('交易预警');

				$content->row(function (Row $row) {

					$words = '请先配置您的数据库信息。';
					
					$row->column(12, function (Column $column) use ($words) {
						$column->append(new Callout($words));
					});
				});
			});
		}
	}

	/**
	 * 门店交易详情
	 * @return [type] [description]
	 */
	public function store()
	{
		if (Admin::user()->isAdministrator() && Admin::user()->database=='') {
			return Admin::content(function (Content $content) {

				$content->header('生意预警');
				$content->description('门店交易预警');

				$content->row(function (Row $row) {

					$words = '请先配置您的数据库信息。';
					
					$row->column(12, function (Column $column) use ($words) {
						$column->append(new Callout($words));
					});
				});
			});
		}

		return Admin::content(function (Content $content) {

			$content->header('生意预警');
			$content->description('交易预警');

			$storeDatas = [];
			$stores = MemberStore::get();

			foreach ($stores as $store) {
				$storeDatas['mempercent'][$store->purchase_year][$store->purchase_month]=round($store->mempercent*100,1);
				$storeDatas['mempercentytd'][$store->purchase_year][$store->purchase_month]=round($store->mempercentytd*100,1);
				$storeDatas['aus'][$store->purchase_year][$store->purchase_month]=$store->aus;
				$storeDatas['transnum'][$store->purchase_year][$store->purchase_month]=$store->transnum;
				$storeDatas['transnumytd'][$store->purchase_year][$store->purchase_month]=$store->transnumytd;
				$storeDatas['sales'][$store->purchase_year][$store->purchase_month]=$store->sales;
				$storeDatas['salesytd'][$store->purchase_year][$store->purchase_month]=$store->salesytd;
			}

			$updateTime = getLastUpdateTime(Admin::user()->database, 'member_store');
			$updateTimeYear = date('Y', strtotime($updateTime));

			$storeDatas['yr1'] = $yr1 = $updateTimeYear-1;
			$storeDatas['month'] = $month = max(array_keys($storeDatas['transnum'][$updateTimeYear]));

			$storeDatas['transRate'] = ($storeDatas['transnumytd'][$updateTimeYear][$month]-$storeDatas['transnumytd'][$yr1][$month])/$storeDatas['transnumytd'][$yr1][$month]*100; 
			$storeDatas['mempercentRate'] = round(($storeDatas['mempercentytd'][$updateTimeYear][$month]-$storeDatas['mempercentytd'][$yr1][$month])/$storeDatas['mempercentytd'][$yr1][$month]*100,2);
			$storeDatas['salesRate'] = ($storeDatas['salesytd'][$updateTimeYear][$month]-$storeDatas['salesytd'][$yr1][$month])/$storeDatas['salesytd'][$yr1][$month]*100;

			// 第一行
			$content->row(function (Row $row) use($storeDatas, $updateTimeYear) {

				$row->column(4, function (Column $column) use ($storeDatas) {

					if ($storeDatas['salesRate'] < 0) {

						$title = '警告!';
						
						if ($storeDatas['mempercentRate'] < 0) {
							$content = '生意预计下跌，同时会员占比同比下跌' . $storeDatas['mempercentRate'] . '％，会员占比大幅下降主要由：会员制不佳所致:<br />';
							if ($storeDatas['transRate']>10) {
								$content .= '客流大幅增加的同时，入会顾客比例未能跟上历史。';
							} else if ($storeDatas['transRate']<-10){
								$content .= '客流大幅减少的同时，入会顾客比例也在大幅减少。';
							} else {
								$content .= '客流基本不变的同时，入会顾客比例也在大幅减少。';
							}
							$style = 'danger';
							$icon = 'warning';
						} else {
							$content = '生意预计下跌，但是会员占比变化预计上涨' . $storeDatas['mempercentRate'] . '％，生意预测受到会员占比影响因素较大，但是拥有了更多的会员信息，对未来生意有巨大的帮助。';
							$style = 'warning';
							$icon = 'info';
						}

					} else if ($storeDatas['salesRate'] >= 0) {

						$title = '良好!';

						if ($storeDatas['mempercentRate'] < 0) {
							$content = '生意预计上涨，但是会员占比同比下跌' . $storeDatas['mempercentRate'] . '％，会员占比大幅下降主要由：会员制不佳所致:<br />';
							if ($storeDatas['transRate']>10) {
								$content .= '客流大幅增加的同时，入会顾客比例未能跟上历史。';
							} else if ($storeDatas['transRate']<-10){
								$content .= '客流大幅减少的同时，入会顾客比例也在大幅减少。';
							} else {
								$content .= '客流基本不变的同时，入会顾客比例也在大幅减少。';
							}
							$style = 'danger';
							$icon = 'warning';
						} else {
							$content = '生意预计上涨，会员占比变化预计上涨' . $storeDatas['mempercentRate'] . '％，但是拥有了更多的会员信息，对未来生意有巨大的帮助。';
							$style = 'warning';
							$icon = 'info';
						}

					}

					$alert = (new Alert($content, $title))->style($style)->icon($icon)->setAttributes('font-weight:bold;');
					$storeWarningButton = '<a type="button" class="btn btn-block btn-primary showdetail" href="/admin/store/store-warning"><i class="fa fa-mail-reply"></i> 返回门店预警</a>';

					$box = new Box('概况', $alert.$storeWarningButton, 'users', '');
					$column->append($box);
				});

				$row->column(4, function (Column $column) use ($storeDatas, $updateTimeYear) {
					$barPersentYtdChartData = new ChartBar(
															['会员占比YTD同比'],
															[
																[ 
																	'label' => $updateTimeYear-1,
																	'data' => [ $storeDatas['mempercentytd'][$updateTimeYear-1][$storeDatas['month']] ],
																	'backgroundColor' => '#00c0ef',
																	'borderColor' => '0073b7',
																	'borderWidth' => 1

																],
																[ 
																	'label' => $updateTimeYear,
																	'data' => [ $storeDatas['mempercentytd'][$updateTimeYear][$storeDatas['month']] ],
																	'backgroundColor' => '#3c8dbc',
																	'borderColor' => '0073b7',
																	'borderWidth' => 1
																],
															]
														);
					$box = (new Box('会员占比YTD同比', $barPersentYtdChartData, 'users', ''));
					$column->append($box);
				});

				$row->column(4, function (Column $column) use ($storeDatas, $updateTimeYear) {
					$barTransYtdChartData = new ChartBar(
															['交易数YTD同比'],
															[
																[ 
																	'label' => $updateTimeYear-1,
																	'data' => [ $storeDatas['transnumytd'][$updateTimeYear-1][$storeDatas['month']] ],
																	'backgroundColor' => '#00c0ef',
																	'borderColor' => '0073b7',
																	'borderWidth' => 1

																],
																[ 
																	'label' => $updateTimeYear,
																	'data' => [ $storeDatas['transnumytd'][$updateTimeYear][$storeDatas['month']] ],
																	'backgroundColor' => '#3c8dbc',
																	'borderColor' => '0073b7',
																	'borderWidth' => 1
																],
															]
														);
					$box = (new Box('交易数YTD同比', $barTransYtdChartData, 'line-chart', ''));
					$column->append($box);
				});
			});

			// 第二行
			$content->row(function (Row $row) use($storeDatas, $updateTimeYear) {
				$row->column(8, function (Column $column) use ($storeDatas, $updateTimeYear) {
					$barPercentChartData = new ChartBar(
															['一月','二月','三月','四月','五月','六月','七月','八月','九月','十月','十一月','十二月'],
															[
																[ 
																	'label' => $updateTimeYear-2,
																	'data' => array_values($storeDatas['mempercent'][$updateTimeYear-2]),
																	'backgroundColor' => '#00c0ef',
																	'borderColor' => '00c0ef',
																	'borderWidth' => 1

																],
																[ 
																	'label' => $updateTimeYear-1,
																	'data' => array_values($storeDatas['mempercent'][$updateTimeYear-1]),
																	'backgroundColor' => '#f39c12',
																	'borderColor' => 'f39c12',
																	'borderWidth' => 1

																],
																[ 
																	'label' => $updateTimeYear,
																	'data' => array_values($storeDatas['mempercent'][$updateTimeYear]),
																	'backgroundColor' => '#00a65a',
																	'borderColor' => '00a65a',
																	'borderWidth' => 1
																],
															]
														);
					$box = (new Box('月度会员占比', $barPercentChartData, 'users', ''));
					$column->append($box);
				});

				$row->column(4, function (Column $column) use ($storeDatas, $updateTimeYear) {

					$customer = [];
					$ninecellTtls = DB::connection('store_db')->table('ninecell_ttl')
								->where('purchase_month', '=', 12)
								->select('purchase_year', 'purchase_month', 'member_pcent')
								->orderBy('purchase_year', 'asc')
								->get();
					foreach ($ninecellTtls as $ninecellTtl) {
						$customer[$ninecellTtl->purchase_year][$ninecellTtl->purchase_month]=$ninecellTtl->member_pcent;
					}

					$barPercentChart2 = new ChartBar(
															['会员占比同比'],
															[
																[ 
																	'label' => $updateTimeYear-1,
																	'data' => [ round($customer[$updateTimeYear-1][12]*100) ],
																	'backgroundColor' => '#00c0ef',
																	'borderColor' => '0073b7',
																	'borderWidth' => 1

																],
																[ 
																	'label' => $updateTimeYear,
																	'data' => [ round($customer[$updateTimeYear][12]*100) ],
																	'backgroundColor' => '#3c8dbc',
																	'borderColor' => '0073b7',
																	'borderWidth' => 1
																],
															]
														);
					$box = (new Box('会员占比年底预测', $barPercentChart2, 'users', ''));
					$column->append($box);
				});
			});

			// 第三行
			$content->row(function (Row $row) use($storeDatas, $updateTimeYear) {
				$row->column(6, function (Column $column) use ($storeDatas, $updateTimeYear) {
					$lineChartOptions = [
						'datasetFill' => false
					];

					$labels = ['一月','二月','三月','四月','五月','六月','七月','八月','九月','十月','十一月','十二月'];

					$datasets = [
						[
							'label' => ($updateTimeYear-2) ,
							'fill' =>false,
							'borderColor' => "#00c0ef",
							'pointBackgroundColor' => "#00c0ef",
							'data' => array_values($storeDatas['aus'][$updateTimeYear-2]),
							'lineTension' => 0.1
						],
						[
							'label' => ($updateTimeYear-1) ,
							'fill' =>false,
							'borderColor' => "#f39c12",
							'pointBackgroundColor' => "#f39c12",
							'data' => array_values($storeDatas['aus'][$updateTimeYear-1]),
							'lineTension' => 0.1
						],
						[
							'label' => $updateTimeYear."预测",
							'fill' =>false,
							'borderColor' => "#00a65a",
							'pointBackgroundColor' => "#00a65a",
							'data' => array_values($storeDatas['aus'][$updateTimeYear]),
							'lineTension' => 0.1
						]
					];
					
					$line = new Line($labels, $datasets);

					$box = (new Box('平均客单价', $line, 'credit-card', ''))->style('info');

					$column->append($box);
				});

				$row->column(6, function (Column $column) use ($storeDatas, $updateTimeYear) {
					$lineChartOptions = [
						'datasetFill' => false
					];

					$labels = ['一月','二月','三月','四月','五月','六月','七月','八月','九月','十月','十一月','十二月'];

					$datasets = [
						[
							'label' => ($updateTimeYear-2) ,
							'fill' =>false,
							'borderColor' => "#00c0ef",
							'pointBackgroundColor' => "#00c0ef",
							'data' => array_values($storeDatas['transnum'][$updateTimeYear-2]),
							'lineTension' => 0.1
						],
						[
							'label' => ($updateTimeYear-1) ,
							'fill' =>false,
							'borderColor' => "#f39c12",
							'pointBackgroundColor' => "#f39c12",
							'data' => array_values($storeDatas['transnum'][$updateTimeYear-1]),
							'lineTension' => 0.1
						],
						[
							'label' => $updateTimeYear."预测",
							'fill' =>false,
							'borderColor' => "#00a65a",
							'pointBackgroundColor' => "#00a65a",
							'data' => array_values($storeDatas['transnum'][$updateTimeYear]),
							'lineTension' => 0.1
						]
					];
					
					$line = new Line($labels, $datasets);

					$box = (new Box('月度交易数', $line, 'list', ''))->style('info');

					$column->append($box);
				});

			});


		});
	}

	/**
	 * 品类交易详情
	 * @param  [type] $category [description]
	 * @return [type]           [description]
	 */
	public function cat($category)
	{
		if (Admin::user()->isAdministrator() && Admin::user()->database=='') {
			return Admin::content(function (Content $content) {

				$content->header('生意预警');
				$content->description('门店交易预警');

				$content->row(function (Row $row) {

					$words = '请先配置您的数据库信息。';
					
					$row->column(12, function (Column $column) use ($words) {
						$column->append(new Callout($words));
					});
				});
			});
		}

		return Admin::content(function (Content $content) use($category) {

			$content->header('生意预警');
			$content->description('交易预警');

			$updateTime = getLastUpdateTime(Admin::user()->database, 'member_cat');
			$updateTimeYear = date('Y', strtotime($updateTime));
			$catDatas['category'] = $category;
			$cats = MemberCat::where('category', '=', $category)->get();

			foreach ($cats as $cat) {
				$catDatas['mempercent'][$cat->purchase_year][$cat->purchase_month]=round($cat->mempercent*100,1);
				$catDatas['mempercentytd'][$cat->purchase_year][$cat->purchase_month]=round($cat->mempercentytd*100,1);
				$catDatas['aus'][$cat->purchase_year][$cat->purchase_month]=$cat->aus;
				$catDatas['transnum'][$cat->purchase_year][$cat->purchase_month]=$cat->transnum;
				$catDatas['transnumytd'][$cat->purchase_year][$cat->purchase_month]=$cat->transnumytd;
				$catDatas['sales'][$cat->purchase_year][$cat->purchase_month]=$cat->sales;
				$catDatas['salesytd'][$cat->purchase_year][$cat->purchase_month]=$cat->salesytd;
			}

			$catDatas['yr1'] = $yr1 = $updateTimeYear-1;
			$catDatas['month'] = $month = max(array_keys($catDatas['transnum'][$updateTimeYear]));

			$catDatas['transRate'] = ($catDatas['transnumytd'][$updateTimeYear][$month]-$catDatas['transnumytd'][$yr1][$month])/$catDatas['transnumytd'][$yr1][$month]*100; 
			$catDatas['mempercentRate'] = round(($catDatas['mempercentytd'][$updateTimeYear][$month]-$catDatas['mempercentytd'][$yr1][$month])/$catDatas['mempercentytd'][$yr1][$month]*100,2);
			$catDatas['salesRate'] = ($catDatas['salesytd'][$updateTimeYear][$month]-$catDatas['salesytd'][$yr1][$month])/$catDatas['salesytd'][$yr1][$month]*100;

			// 第一行
			$content->row(function (Row $row) use($catDatas, $updateTimeYear) {

				$row->column(4, function (Column $column) use ($catDatas) {

					if ($catDatas['salesRate'] < 0) {

						$title = '警告!';
						
						if ($catDatas['mempercentRate'] < 0) {
							$content = '<i>' . $catDatas['category'] . '</i> 生意预计下跌，同时会员占比同比下跌' . $catDatas['mempercentRate'] . '％，会员占比大幅下降主要由：会员制不佳所致:<br />';
							if ($catDatas['transRate']>10) {
								$content .= '客流大幅增加的同时，入会顾客比例未能跟上历史。';
							} else if ($catDatas['transRate']<-10){
								$content .= '客流大幅减少的同时，入会顾客比例也在大幅减少。';
							} else {
								$content .= '客流基本不变的同时，入会顾客比例也在大幅减少。';
							}
							$style = 'danger';
							$icon = 'warning';
						} else {
							$content = '<i>' . $catDatas['category'] . '</i> 生意预计下跌，但是会员占比变化预计上涨' . $catDatas['mempercentRate'] . '％，生意预测受到会员占比影响因素较大，但是拥有了更多的会员信息，对未来生意有巨大的帮助。';
							$style = 'warning';
							$icon = 'info';
						}

					} else if ($catDatas['salesRate'] >= 0) {

						$title = '良好!';

						if ($catDatas['mempercentRate'] < 0) {
							$content = '<i>' . $catDatas['category'] . '</i> 生意预计上涨，但是会员占比同比下跌' . $catDatas['mempercentRate'] . '％，会员占比大幅下降主要由：会员制不佳所致:<br />';
							if ($catDatas['transRate']>10) {
								$content .= '客流大幅增加的同时，入会顾客比例未能跟上历史。';
							} else if ($catDatas['transRate']<-10){
								$content .= '客流大幅减少的同时，入会顾客比例也在大幅减少。';
							} else {
								$content .= '客流基本不变的同时，入会顾客比例也在大幅减少。';
							}
							$style = 'danger';
							$icon = 'warning';
						} else {
							$content = '<i>' . $catDatas['category'] . '</i> 生意预计上涨，会员占比变化预计上涨' . $catDatas['mempercentRate'] . '％，但是拥有了更多的会员信息，对未来生意有巨大的帮助。';
							$style = 'warning';
							$icon = 'info';
						}

					}

					$alert = (new Alert($content, $title))->style($style)->icon($icon)->setAttributes('font-weight:bold;');
					$catWarningButton = '<a type="button" class="btn btn-block btn-primary showdetail" href="/admin/store/warning-cat/' . $catDatas['category'] . '"><i class="fa fa-mail-reply"></i> 返回品类预警</a>';

					$box = new Box('<span class="text-aqua">' . $catDatas['category'] . '</span>概况', $alert.$catWarningButton, 'users', '');
					$column->append($box);
				});

				$row->column(4, function (Column $column) use ($catDatas, $updateTimeYear) {
					$barPersentYtdChartData = new ChartBar(
															['会员占比YTD同比'],
															[
																[ 
																	'label' => $updateTimeYear-1,
																	'data' => [ $catDatas['mempercentytd'][$updateTimeYear-1][$catDatas['month']] ],
																	'backgroundColor' => '#00c0ef',
																	'borderColor' => '0073b7',
																	'borderWidth' => 1

																],
																[ 
																	'label' => $updateTimeYear,
																	'data' => [ $catDatas['mempercentytd'][$updateTimeYear][$catDatas['month']] ],
																	'backgroundColor' => '#3c8dbc',
																	'borderColor' => '0073b7',
																	'borderWidth' => 1
																],
															]
														);
					$box = (new Box('<span class="text-aqua">' . $catDatas['category'] . '</span>会员占比YTD同比', $barPersentYtdChartData, 'users', ''));
					$column->append($box);
				});

				$row->column(4, function (Column $column) use ($catDatas, $updateTimeYear) {
					$barTransYtdChartData = new ChartBar(
															['交易数YTD同比'],
															[
																[ 
																	'label' => $updateTimeYear-1,
																	'data' => [ $catDatas['transnumytd'][$updateTimeYear-1][$catDatas['month']] ],
																	'backgroundColor' => '#00c0ef',
																	'borderColor' => '0073b7',
																	'borderWidth' => 1

																],
																[ 
																	'label' => $updateTimeYear,
																	'data' => [ $catDatas['transnumytd'][$updateTimeYear][$catDatas['month']] ],
																	'backgroundColor' => '#3c8dbc',
																	'borderColor' => '0073b7',
																	'borderWidth' => 1
																],
															]
														);
					$box = (new Box('<span class="text-aqua">' . $catDatas['category'] . '</span>交易数YTD同比', $barTransYtdChartData, 'line-chart', ''));
					$column->append($box);
				});
			});

			// 第二行
			$content->row(function (Row $row) use($catDatas, $updateTimeYear) {
				$row->column(8, function (Column $column) use ($catDatas, $updateTimeYear) {
					$barPercentChartData = new ChartBar(
															['一月','二月','三月','四月','五月','六月','七月','八月','九月','十月','十一月','十二月'],
															[
																[ 
																	'label' => $updateTimeYear-2,
																	'data' => array_values($catDatas['mempercent'][$updateTimeYear-2]),
																	'backgroundColor' => '#00c0ef',
																	'borderColor' => '00c0ef',
																	'borderWidth' => 1

																],
																[ 
																	'label' => $updateTimeYear-1,
																	'data' => array_values($catDatas['mempercent'][$updateTimeYear-1]),
																	'backgroundColor' => '#f39c12',
																	'borderColor' => 'f39c12',
																	'borderWidth' => 1

																],
																[ 
																	'label' => $updateTimeYear,
																	'data' => array_values($catDatas['mempercent'][$updateTimeYear]),
																	'backgroundColor' => '#00a65a',
																	'borderColor' => '00a65a',
																	'borderWidth' => 1
																],
															]
														);
					$box = (new Box('<span class="text-aqua">' . $catDatas['category'] . '</span>月度会员占比', $barPercentChartData, 'users', ''));
					$column->append($box);
				});

				$row->column(4, function (Column $column) use($catDatas, $updateTimeYear) {
					
					$customer = [];
					$cbps = Cbp::where('ytdmonth', '=', 12)
									->where('category', '=', $catDatas['category'])
									->select('year', 'ytdmonth', 'pcnt')
									->orderBy('ytdmonth', 'asc')
									->get();
					foreach ($cbps as $cbp) {
						$customer[$cbp->year][$cbp->ytdmonth]=$cbp->pcnt;
					}

					$barPercentChart2 = new ChartBar(
															['会员占比同比'],
															[
																[ 
																	'label' => $updateTimeYear-1,
																	'data' => [ round($customer[$updateTimeYear-1][12]*100) ],
																	'backgroundColor' => '#00c0ef',
																	'borderColor' => '0073b7',
																	'borderWidth' => 1

																],
																[ 
																	'label' => $updateTimeYear,
																	'data' => [ round($customer[$updateTimeYear][12]*100) ],
																	'backgroundColor' => '#3c8dbc',
																	'borderColor' => '0073b7',
																	'borderWidth' => 1
																],
															]
														);
					$box = (new Box('<span class="text-aqua">' . $catDatas['category'] . '</span>会员占比年底预测', $barPercentChart2, 'users', ''));
					$column->append($box);
				});
			});

			// 第三行
			$content->row(function (Row $row) use($catDatas, $updateTimeYear) {
				$row->column(6, function (Column $column) use ($catDatas, $updateTimeYear) {
					$lineChartOptions = [
						'datasetFill' => false
					];

					$labels = ['一月','二月','三月','四月','五月','六月','七月','八月','九月','十月','十一月','十二月'];

					$datasets = [
						[
							'label' => ($updateTimeYear-2) ,
							'fill' =>false,
							'borderColor' => "#00c0ef",
							'pointBackgroundColor' => "#00c0ef",
							'data' => array_values($catDatas['aus'][$updateTimeYear-2]),
							'lineTension' => 0.1
						],
						[
							'label' => ($updateTimeYear-1) ,
							'fill' =>false,
							'borderColor' => "#f39c12",
							'pointBackgroundColor' => "#f39c12",
							'data' => array_values($catDatas['aus'][$updateTimeYear-1]),
							'lineTension' => 0.1
						],
						[
							'label' => $updateTimeYear."预测",
							'fill' =>false,
							'borderColor' => "#00a65a",
							'pointBackgroundColor' => "#00a65a",
							'data' => array_values($catDatas['aus'][$updateTimeYear]),
							'lineTension' => 0.1
						]
					];
					
					$line = new Line($labels, $datasets);

					$box = (new Box('<span class="text-aqua">' . $catDatas['category'] . '</span>平均客单价', $line, 'credit-card', ''))->style('info');

					$column->append($box);
				});

				$row->column(6, function (Column $column) use ($catDatas, $updateTimeYear) {
					$lineChartOptions = [
						'datasetFill' => false
					];

					$labels = ['一月','二月','三月','四月','五月','六月','七月','八月','九月','十月','十一月','十二月'];

					$datasets = [
						[
							'label' => ($updateTimeYear-2) ,
							'fill' =>false,
							'borderColor' => "#00c0ef",
							'pointBackgroundColor' => "#00c0ef",
							'data' => array_values($catDatas['transnum'][$updateTimeYear-2]),
							'lineTension' => 0.1
						],
						[
							'label' => ($updateTimeYear-1) ,
							'fill' =>false,
							'borderColor' => "#f39c12",
							'pointBackgroundColor' => "#f39c12",
							'data' => array_values($catDatas['transnum'][$updateTimeYear-1]),
							'lineTension' => 0.1
						],
						[
							'label' => $updateTimeYear."预测",
							'fill' =>false,
							'borderColor' => "#00a65a",
							'pointBackgroundColor' => "#00a65a",
							'data' => array_values($catDatas['transnum'][$updateTimeYear]),
							'lineTension' => 0.1
						]
					];
					
					$line = new Line($labels, $datasets);

					$box = (new Box('<span class="text-aqua">' . $catDatas['category'] . '</span>月度交易数', $line, 'list', ''))->style('info');

					$column->append($box);
				});

			});


		});
	}

	/**
	 * 品牌交易详情
	 * @param  [type] $category [description]
	 * @return [type]           [description]
	 */
	public function brand($category, $brand)
	{
		if (Admin::user()->isAdministrator() && Admin::user()->database=='') {
			return Admin::content(function (Content $content) {

				$content->header('生意预警');
				$content->description('门店交易预警');

				$content->row(function (Row $row) {

					$words = '请先配置您的数据库信息。';
					
					$row->column(12, function (Column $column) use ($words) {
						$column->append(new Callout($words));
					});
				});
			});
		}

		return Admin::content(function (Content $content) use($category, $brand) {

			$content->header('生意预警');
			$content->description('交易预警');

			$updateTime = getLastUpdateTime(Admin::user()->database, 'member_brand');
			$updateTimeYear = date('Y', strtotime($updateTime));
			$brandDatas['category'] = $category;
			$brandDatas['brand'] = $brand;
			$brandObjs = MemberBrand::where('brandname', '=', $brand)->get();

			foreach ($brandObjs as $brandObj) {
				$brandDatas['mempercent'][$brandObj->purchase_year][$brandObj->purchase_month]=round($brandObj->mempercent*100,1);
				$brandDatas['mempercentytd'][$brandObj->purchase_year][$brandObj->purchase_month]=round($brandObj->mempercentytd*100,1);
				$brandDatas['aus'][$brandObj->purchase_year][$brandObj->purchase_month]=$brandObj->aus;
				$brandDatas['transnum'][$brandObj->purchase_year][$brandObj->purchase_month]=$brandObj->transnum;
				$brandDatas['transnumytd'][$brandObj->purchase_year][$brandObj->purchase_month]=$brandObj->transnumytd;
				$brandDatas['sales'][$brandObj->purchase_year][$brandObj->purchase_month]=$brandObj->sales;
				$brandDatas['salesytd'][$brandObj->purchase_year][$brandObj->purchase_month]=$brandObj->salesytd;
			}

			$brandDatas['yr1'] = $yr1 = $updateTimeYear-1;
			$brandDatas['month'] = $month = max(array_keys($brandDatas['transnum'][$updateTimeYear]));

			$brandDatas['transRate'] = ($brandDatas['transnumytd'][$updateTimeYear][$month]-$brandDatas['transnumytd'][$yr1][$month])/$brandDatas['transnumytd'][$yr1][$month]*100; 
			$brandDatas['mempercentRate'] = round(($brandDatas['mempercentytd'][$updateTimeYear][$month]-$brandDatas['mempercentytd'][$yr1][$month])/$brandDatas['mempercentytd'][$yr1][$month]*100,2);
			$brandDatas['salesRate'] = ($brandDatas['salesytd'][$updateTimeYear][$month]-$brandDatas['salesytd'][$yr1][$month])/$brandDatas['salesytd'][$yr1][$month]*100;

			// 第一行
			$content->row(function (Row $row) use($brandDatas, $updateTimeYear) {

				$row->column(4, function (Column $column) use ($brandDatas) {

					if ($brandDatas['salesRate'] < 0) {

						$title = '警告!';
						
						if ($brandDatas['mempercentRate'] < 0) {
							$content = '<i>' . $brandDatas['category'] . ' - ' . $brandDatas['brand'] . '</i> 生意预计下跌，同时会员占比同比下跌' . $brandDatas['mempercentRate'] . '％，会员占比大幅下降主要由：会员制不佳所致:<br />';
							if ($brandDatas['transRate']>10) {
								$content .= '客流大幅增加的同时，入会顾客比例未能跟上历史。';
							} else if ($brandDatas['transRate']<-10){
								$content .= '客流大幅减少的同时，入会顾客比例也在大幅减少。';
							} else {
								$content .= '客流基本不变的同时，入会顾客比例也在大幅减少。';
							}
							$style = 'danger';
							$icon = 'warning';
						} else {
							$content = '<i>' . $brandDatas['category'] . ' - ' . $brandDatas['brand'] . '</i> 生意预计下跌，但是会员占比变化预计上涨' . $brandDatas['mempercentRate'] . '％，生意预测受到会员占比影响因素较大，但是拥有了更多的会员信息，对未来生意有巨大的帮助。';
							$style = 'warning';
							$icon = 'info';
						}

					} else if ($brandDatas['salesRate'] >= 0) {

						$title = '良好!';

						if ($brandDatas['mempercentRate'] < 0) {
							$content = '<i>' . $brandDatas['category'] . ' - ' . $brandDatas['brand'] . '</i> 生意预计上涨，但是会员占比同比下跌' . $brandDatas['mempercentRate'] . '％，会员占比大幅下降主要由：会员制不佳所致:<br />';
							if ($brandDatas['transRate']>10) {
								$content .= '客流大幅增加的同时，入会顾客比例未能跟上历史。';
							} else if ($brandDatas['transRate']<-10){
								$content .= '客流大幅减少的同时，入会顾客比例也在大幅减少。';
							} else {
								$content .= '客流基本不变的同时，入会顾客比例也在大幅减少。';
							}
							$style = 'danger';
							$icon = 'warning';
						} else {
							$content = '<i>' . $brandDatas['category'] . ' - ' . $brandDatas['brand'] . '</i> 生意预计上涨，会员占比变化预计上涨' . $brandDatas['mempercentRate'] . '％，但是拥有了更多的会员信息，对未来生意有巨大的帮助。';
							$style = 'warning';
							$icon = 'info';
						}

					}

					$alert = (new Alert($content, $title))->style($style)->icon($icon)->setAttributes('font-weight:bold;');
					$brandWarningButton = '<a type="button" class="btn btn-block btn-primary showdetail" href="/admin/store/warning-cat/' . $brandDatas['category'] . ' - ' . $brandDatas['brand'] . '"><i class="fa fa-mail-reply"></i> 返回品类预警</a>';

					$box = new Box('<span class="text-aqua">' . $brandDatas['category'] . ' - ' . $brandDatas['brand'] . '</span>概况', $alert.$brandWarningButton, 'users', '');
					$column->append($box);
				});

				$row->column(4, function (Column $column) use ($brandDatas, $updateTimeYear) {
					$barPersentYtdChartData = new ChartBar(
															['会员占比YTD同比'],
															[
																[ 
																	'label' => $updateTimeYear-1,
																	'data' => [ $brandDatas['mempercentytd'][$updateTimeYear-1][$brandDatas['month']] ],
																	'backgroundColor' => '#00c0ef',
																	'borderColor' => '0073b7',
																	'borderWidth' => 1

																],
																[ 
																	'label' => $updateTimeYear,
																	'data' => [ $brandDatas['mempercentytd'][$updateTimeYear][$brandDatas['month']] ],
																	'backgroundColor' => '#3c8dbc',
																	'borderColor' => '0073b7',
																	'borderWidth' => 1
																],
															]
														);
					$box = (new Box('<span class="text-aqua">' . $brandDatas['category'] . ' - ' . $brandDatas['brand'] . '</span>会员占比YTD同比', $barPersentYtdChartData, 'users', ''));
					$column->append($box);
				});

				$row->column(4, function (Column $column) use ($brandDatas, $updateTimeYear) {
					$barTransYtdChartData = new ChartBar(
															['交易数YTD同比'],
															[
																[ 
																	'label' => $updateTimeYear-1,
																	'data' => [ $brandDatas['transnumytd'][$updateTimeYear-1][$brandDatas['month']] ],
																	'backgroundColor' => '#00c0ef',
																	'borderColor' => '0073b7',
																	'borderWidth' => 1

																],
																[ 
																	'label' => $updateTimeYear,
																	'data' => [ $brandDatas['transnumytd'][$updateTimeYear][$brandDatas['month']] ],
																	'backgroundColor' => '#3c8dbc',
																	'borderColor' => '0073b7',
																	'borderWidth' => 1
																],
															]
														);
					$box = (new Box('<span class="text-aqua">' . $brandDatas['category'] . ' - ' . $brandDatas['brand'] . '</span>交易数YTD同比', $barTransYtdChartData, 'line-chart', ''));
					$column->append($box);
				});
			});

			// 第二行
			$content->row(function (Row $row) use($brandDatas, $updateTimeYear) {
				$row->column(8, function (Column $column) use ($brandDatas, $updateTimeYear) {
					$emptyArr = [1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0,8=>0,9=>0,10=>0,11=>0,12=>0];
					$data2 = $brandDatas['mempercent'][$updateTimeYear-2] + $emptyArr;
					ksort($data2);
					$data2 = array_values($data2);
					$data1 = $brandDatas['mempercent'][$updateTimeYear-1] + $emptyArr;
					ksort($data1);
					$data1 = array_values($data1);
					$data0 = $brandDatas['mempercent'][$updateTimeYear] + $emptyArr;
					ksort($data0);
					$data0 = array_values($data0);
					
					$barPercentChartData = new ChartBar(
															['一月','二月','三月','四月','五月','六月','七月','八月','九月','十月','十一月','十二月'],
															[
																[ 
																	'label' => $updateTimeYear-2,
																	'data' => $data2,
																	'backgroundColor' => '#00c0ef',
																	'borderColor' => '00c0ef',
																	'borderWidth' => 1

																],
																[ 
																	'label' => $updateTimeYear-1,
																	'data' => $data1,
																	'backgroundColor' => '#f39c12',
																	'borderColor' => 'f39c12',
																	'borderWidth' => 1

																],
																[ 
																	'label' => $updateTimeYear,
																	'data' => $data0,
																	'backgroundColor' => '#00a65a',
																	'borderColor' => '00a65a',
																	'borderWidth' => 1
																],
															]
														);
					$box = (new Box('<span class="text-aqua">' . $brandDatas['category'] . ' - ' . $brandDatas['brand'] . '</span>月度会员占比', $barPercentChartData, 'users', ''));
					$column->append($box);
				});

				$row->column(4, function (Column $column) use($brandDatas, $updateTimeYear) {


					$customer = [];
					$cbps = Bbp::where('ytdmonth', '=', 12)
									->where('brand', '=', $brandDatas['brand'])
									->select('year', 'ytdmonth', 'pcnt')
									->orderBy('ytdmonth', 'asc')
									->get();
					foreach ($cbps as $cbp) {
						$customer[$cbp->year][$cbp->ytdmonth]=$cbp->pcnt;
					}

					$barPercentChart2 = new ChartBar(
															['会员占比同比'],
															[
																[ 
																	'label' => $updateTimeYear-1,
																	'data' => [ round($customer[$updateTimeYear-1][12]*100) ],
																	'backgroundColor' => '#00c0ef',
																	'borderColor' => '0073b7',
																	'borderWidth' => 1

																],
																[ 
																	'label' => $updateTimeYear,
																	'data' => [ round($customer[$updateTimeYear][12]*100) ],
																	'backgroundColor' => '#3c8dbc',
																	'borderColor' => '0073b7',
																	'borderWidth' => 1
																],
															]
														);
					$box = (new Box('<span class="text-aqua">' . $brandDatas['category'] . ' - ' . $brandDatas['brand'] . '</span>会员占比年底预测', $barPercentChart2, 'users', ''));
					$column->append($box);
				});
			});

			// 第三行
			$content->row(function (Row $row) use($brandDatas, $updateTimeYear) {
				$row->column(6, function (Column $column) use ($brandDatas, $updateTimeYear) {
					$lineChartOptions = [
						'datasetFill' => false
					];

					$labels = ['一月','二月','三月','四月','五月','六月','七月','八月','九月','十月','十一月','十二月'];

					$datasets = [
						[
							'label' => ($updateTimeYear-2) ,
							'fill' =>false,
							'borderColor' => "#00c0ef",
							'pointBackgroundColor' => "#00c0ef",
							'data' => array_values($brandDatas['aus'][$updateTimeYear-2]),
							'lineTension' => 0.1
						],
						[
							'label' => ($updateTimeYear-1) ,
							'fill' =>false,
							'borderColor' => "#f39c12",
							'pointBackgroundColor' => "#f39c12",
							'data' => array_values($brandDatas['aus'][$updateTimeYear-1]),
							'lineTension' => 0.1
						],
						[
							'label' => $updateTimeYear."预测",
							'fill' =>false,
							'borderColor' => "#00a65a",
							'pointBackgroundColor' => "#00a65a",
							'data' => array_values($brandDatas['aus'][$updateTimeYear]),
							'lineTension' => 0.1
						]
					];
					
					$line = new Line($labels, $datasets);

					$box = (new Box('<span class="text-aqua">' . $brandDatas['category'] . ' - ' . $brandDatas['brand'] . '</span>平均客单价', $line, 'credit-card', ''))->style('info');

					$column->append($box);
				});

				$row->column(6, function (Column $column) use ($brandDatas, $updateTimeYear) {
					$lineChartOptions = [
						'datasetFill' => false
					];

					$labels = ['一月','二月','三月','四月','五月','六月','七月','八月','九月','十月','十一月','十二月'];

					$datasets = [
						[
							'label' => ($updateTimeYear-2) ,
							'fill' =>false,
							'borderColor' => "#00c0ef",
							'pointBackgroundColor' => "#00c0ef",
							'data' => array_values($brandDatas['transnum'][$updateTimeYear-2]),
							'lineTension' => 0.1
						],
						[
							'label' => ($updateTimeYear-1) ,
							'fill' =>false,
							'borderColor' => "#f39c12",
							'pointBackgroundColor' => "#f39c12",
							'data' => array_values($brandDatas['transnum'][$updateTimeYear-1]),
							'lineTension' => 0.1
						],
						[
							'label' => $updateTimeYear."预测",
							'fill' =>false,
							'borderColor' => "#00a65a",
							'pointBackgroundColor' => "#00a65a",
							'data' => array_values($brandDatas['transnum'][$updateTimeYear]),
							'lineTension' => 0.1
						]
					];
					
					$line = new Line($labels, $datasets);

					$box = (new Box('<span class="text-aqua">' . $brandDatas['category'] . ' - ' . $brandDatas['brand'] . '</span>月度交易数', $line, 'list', ''))->style('info');

					$column->append($box);
				});

			});


		});
	}


}