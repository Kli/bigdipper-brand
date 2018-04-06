<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\StoreBaseController;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Tab;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Alert;
use Encore\Admin\Widgets\Callout;
use Encore\Admin\Widgets\InfoBox;
use Encore\Admin\Widgets\Collapse;
use Encore\Admin\Widgets\Chart\Line;
use Encore\Admin\Widgets\Chart\Doughnut;
use Encore\Admin\Widgets\Chart\Chartbar;

use DB;

class StoreWarningController extends StoreBaseController
{
	

	public function index()
	{
		if (Admin::user()->isAdministrator() && Admin::user()->database=='') {
			return Admin::content(function (Content $content) {

				$content->header('生意预警');
				$content->description('门店预警');

				$content->row(function (Row $row) {

					$words = '请先配置您的数据库信息。';
					
					$row->column(12, function (Column $column) use ($words) {
						$column->append(new Callout($words));
					});
				});
			});
		}

		return Admin::content(function (Content $content) {

			$ninecellTtlDatas = [];
			$ninecellTtls = DB::connection('store_db')->table('ninecell_ttl')->orderBy('purchase_month')->get();

			foreach ($ninecellTtls as $ninecellTtl) {
				$ninecellTtlDatas['sales'][$ninecellTtl->purchase_year][$ninecellTtl->purchase_month] = round($ninecellTtl->sales_ttl);
				$ninecellTtlDatas['customer'][$ninecellTtl->purchase_year][$ninecellTtl->purchase_month] = $ninecellTtl->member_pcent;
			}

			$updateTime = getLastUpdateTime(Admin::user()->database, 'ninecell_ttl');
			$updateTimeYear = date('Y', strtotime($updateTime));
			$updateTimeMonth = date('m', strtotime($updateTime));

			$ninecellTtlDatas['srate']=round(($ninecellTtlDatas['sales'][$updateTimeYear][12]-$ninecellTtlDatas['sales'][$updateTimeYear-1][12])/$ninecellTtlDatas['sales'][$updateTimeYear-1][12]*100,2);
			$ninecellTtlDatas['crate']=round(($ninecellTtlDatas['customer'][$updateTimeYear][12]-$ninecellTtlDatas['customer'][$updateTimeYear-1][12])/$ninecellTtlDatas['customer'][$updateTimeYear-1][12]*100,2);
			$ninecellTtlDatas['sfinal']=round($ninecellTtlDatas['sales'][$updateTimeYear][12]);
			$ninecellTtlDatas['cfinal']=round($ninecellTtlDatas['customer'][$updateTimeYear][12]*100);

			$ninecellData = [];
			$ninecells = DB::connection('store_db')->table('ninecell')->get();

			foreach ($ninecells as $ninecell) {
				$ninecellData['newCustomer'][$ninecell->year][$ninecell->month]=round($ninecell->new);
				$ninecellData['newSales'][$ninecell->year][$ninecell->month] = (!empty($ninecell->new))?round($ninecell->new_sales/$ninecell->new):'0';
				$ninecellData['newRepeat'][$ninecell->year][$ninecell->month]=round($ninecell->new_repeat_rate*100);
				$ninecellData['oldCustomer'][$ninecell->year][$ninecell->month]=round($ninecell->existing);
				$ninecellData['oldSales'][$ninecell->year][$ninecell->month] = (!empty($ninecell->existing))?round($ninecell->existing_sales/$ninecell->existing):'0';
				$ninecellData['oldRepeat'][$ninecell->year][$ninecell->month]=round($ninecell->existing_rate*100);
			}

			$cbp_evol = DB::connection('store_db')->table('cbp_evol')
						->select(
							DB::raw('sum(this_new_sales/this_pcnt)/sum(last_new_sales/last_pcnt) as `new`'),
							DB::raw('sum(this_cross_sales/this_pcnt)/sum(last_cross_sales/last_pcnt) as `cross`'),
							DB::raw('sum(this_repeat_sales/this_pcnt)/sum(last_repeat_sales/last_pcnt) as `repeat`'),
							DB::raw('sum(this_new_sales/this_pcnt)/sum(this_ttl_sales) as `newpcnt`'),
							DB::raw('sum(this_cross_sales/this_pcnt)/sum(this_ttl_sales) as `crosspcnt`'),
							DB::raw('sum(this_repeat_sales/this_pcnt)/sum(this_ttl_sales) as `repeatpcnt`')
						)
						->first();

			
			$content->header('生意预警');
			$content->description('门店预警');

			// 第一行
			$content->row(function (Row $row) use($ninecellTtlDatas) {
				$row->column(10, function (Column $column) use($ninecellTtlDatas) {
					if ($ninecellTtlDatas['srate'] < -10) {

						$title = '警告!';
						
						if ($ninecellTtlDatas['crate'] < 0) {
							$content = '今年生意预计将下跌<?php echo $srate?>％，会员占比<?php echo $cfinal?>%。<br />点击<a class="showdetail" href="#detail"><b>这里</b></a>查看详情';
							$style = 'danger';
							$icon = 'warning';
						} else {
							$content = '今年生意预计将下跌<?php echo $srate?>％，会员占比<?php echo $cfinal?>%，生意预测收到会员占比影响因素较大。<br />点击<a class="showdetail" href="#detail"><b>这里</b></a>查看详情';
							$style = 'warning';
							$icon = 'info';
						}

					} else if ($ninecellTtlDatas['srate'] > 10) {

						$title = '良好!';

						if ($ninecellTtlDatas['crate'] < 0) {
							$content = '今年生意预计能达到<?php echo round($sfinal/100000000,2)?>亿，实现<?php echo $srate?>%增长，会员占比<?php echo $cfinal?>%，同比变化<?php echo $crate?>％。<br />点击<a class="showdetail" href="#detail"><b>这里</b></a>查看详情';
							$style = 'success';
							$icon = 'check';
						} else {
							$content = '今年生意预计能达到<?php echo round($sfinal/100000000,2)?>亿，实现<?php echo $srate?>%增长，会员占比<?php echo $cfinal?>%，同比变化<?php echo $crate?>％，生意预测受到会员占比影响因素较大。<br />点击<a class="showdetail" href="#detail"><b>这里</b></a>查看详情';
							$style = 'warning';
							$icon = 'info';
						}

					} else {
						$title = '正常!';
						$content = '今年生意相对稳定，预计能达到' . round($ninecellTtlDatas['sfinal']/100000000,2) . '亿，同比变化' . $ninecellTtlDatas['srate'] . '%，会员占比' . $ninecellTtlDatas['cfinal'] . '%，同比变化' . $ninecellTtlDatas['crate'] . '％。<br />点击<a class="showdetail" href="#detail"><b>这里</b></a>查看详情';
						$style = 'warning';
						$icon = 'info';
					}


					$column->append( (new Alert($content, $title))->style($style)->icon($icon)->setAttributes('font-weight:bold;') );
				});
				$row->column(2, function (Column $column) {
					$buttons =<<<HTML
<div class="btn-group-vertical">
	<a class="btn btn-info" href="/admin/store/member-store-warning">
		<i class="fa fa-users"></i> 查看交易预警
	</a>
	<a class="btn btn-info" href="#">
		<i class="fa fa-line-chart"></i> 生意指标详情
	</a>
	<a class="btn btn-info" href="/admin/store/cat-warning">
		<i class="fa fa-shopping-basket"></i> 查看品类预警
	</a>
</div>
HTML;

					$column->append($buttons);
				});
			});

			// 第二行
			$content->row(function (Row $row) use($ninecellTtlDatas, $updateTimeYear, $updateTimeMonth) {

				$row->column(8, function (Column $column) use($ninecellTtlDatas, $updateTimeYear, $updateTimeMonth) {
					
					// Line
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
							'data' => array_values($ninecellTtlDatas['sales'][$updateTimeYear-2]),
							'lineTension' => 0.1
						],
						[
							'label' => ($updateTimeYear-1) ,
							'fill' =>false,
							'borderColor' => "#f39c12",
							'pointBackgroundColor' => "#f39c12",
							'data' => array_values($ninecellTtlDatas['sales'][$updateTimeYear-1]),
							'lineTension' => 0.1
						],
						[
							'label' => $updateTimeYear,
							'fill' =>false,
							'borderColor' => "#00a65a",
							'pointBackgroundColor' => "#00a65a",
							'data' => array_values(array_slice($ninecellTtlDatas['sales'][$updateTimeYear],0,($updateTimeMonth-1))),
							'lineTension' => 0.1
						],
						[
							'label' => $updateTimeYear."预测",
							'fill' =>false,
							'borderColor' => "#d2d6de",
							'pointBackgroundColor' => "#d2d6de",
							'data' => array_values($ninecellTtlDatas['sales'][$updateTimeYear]),
							'lineTension' => 0.1
						]
					];
					
					$line = new Line($labels, $datasets);

					$box = (new Box('销售', $line, 'info', '预测'))->style('info');

					$column->append($box);
				});

				$row->column(4, function (Column $column) use($ninecellTtlDatas, $updateTimeYear, $updateTimeMonth) {

					$bar = new ChartBar(
		                        ['会员占比同比'],
		                        [
		                            [ 
		                            	'label' => $updateTimeYear-1,
		                            	'data' => [ round($ninecellTtlDatas['customer'][$updateTimeYear-1][12]*100) ],
		                            	'backgroundColor' => '#3c8dbc',
		                            	'borderColor' => '3c8dbc',
		                            	'borderWidth' => 1

		                            ],
		                            [ 
		                            	'label' => $updateTimeYear,
		                            	'data' => [ round($ninecellTtlDatas['customer'][$updateTimeYear][12]*100) ],
		                            	'backgroundColor' => '#f56853',
		                            	'borderColor' => 'f56853',
		                            	'borderWidth' => 1
		                            ],
		                        ]
		                    );
					$bar->options(['barValueSpacing' => 60, 'brandDatasetSpacing' => 30]);

					$doughnut = new Doughnut(
	        								['会员销售占比','非会员销售占比'],
	        								[[
	        									'data' => [
	        										round($ninecellTtlDatas['customer'][$updateTimeYear][$updateTimeMonth]*100),
	        										round(100-$ninecellTtlDatas['customer'][$updateTimeYear][$updateTimeMonth]*100)
	        									],
	        									'backgroundColor' => [
	        										'#3c8dbc',
	        										'#f56954'
	        									]
	        								]]
	        							);
	        		$options = [
	        						'response' => true,
	        						'legend' => [
	        										'position' => 'bottom',
	        									],
	        						'cutoutPercentage' => 30,
	        						'animation' => ['animateRotate' => true]
	        					];

	        		$doughnut->options($options);

					$tab = new Tab();
					$tab->add('同比历史', $bar->render());
					$tab->add('占比', $doughnut->render(), true);
					
					$tab->title('会员');
					$tab->icon('users');

					$column->append($tab);

				});

			});

			// 第三行
			$content->row(function (Row $row) use($cbp_evol) {
			
				$row->column(12, function (Column $column) use($cbp_evol) {
					$instruction =<<<HTML
<div class="col-md-3">
<br>
	<ul class="chart-legend clearfix">
		<li><i class="fa fa-square fa-2x text-green"></i> 该指标呈现增长趋势</li>
		<li><i class="fa fa-square fa-2x text-yellow"></i> 该指标相对稳定</li>
		<li><i class="fa fa-square fa-2x text-red"></i> 该指标呈现下降趋势</li>
	</ul>
</div>
HTML;
					// 门店吸引力－门店新客
					$newRate = round(($cbp_evol->new-1)*100);
					if ($newRate > 0) {
						$newPositive = '+';
						$newBgColor = ($newRate <= 5) ? 'yellow' : 'green';
					} else {
						$newBgColor = ($newRate >= -5) ? 'yellow' : 'red';
					}

					$newPositive=($newRate>0)?"+":"";

					$infoBoxNew = new InfoBox( '<b>门店吸引力</b>－门店新客', 'user-plus', $newBgColor, '/admin/store/cat-warning', $newPositive . $newRate . '%', '查看详情');
					$infoBoxNew->class("small-box bg-$newBgColor");

					// 门店流转力－品类新客
					$crossRate = round(($cbp_evol->cross-1)*100);
					if ($crossRate > 0) {
						$crossPositive = '+';
						$crossBgColor = ($crossRate <= 5) ? 'yellow' : 'green';
					} else {
						$crossBgColor = ($crossRate >= -5) ? 'yellow' : 'red';
					}

					$crossPositive=($crossRate>0)?"+":"";

					$infoBoxCross = new InfoBox( '<b>门店流转力</b>－品类新客', 'cart-plus', '', '/admin/store/cat-warning', $crossPositive . $crossRate . '%', '查看详情');
					$infoBoxCross->class("small-box bg-$newBgColor");

					// 门店运营力－品类复购
					$repeatRate = round(($cbp_evol->repeat-1)*100);
					if ($repeatRate > 0) {
						$repeatPositive = '+';
						$repeatBgColor = ($repeatRate <= 5) ? 'yellow' : 'green';
					} else {
						$repeatBgColor = ($repeatRate >= -5) ? 'yellow' : 'red';
					}

					$repeatPositive=($repeatRate>0)?"+":"";

					$infoBoxRepeat = new InfoBox( '<b>门店运营力</b>－品类复购', 'usd', '', '/admin/store/cat-warning', $repeatPositive . $repeatRate . '%', '查看详情');
					$infoBoxRepeat->class("small-box bg-$newBgColor");



					$h = '<div class="col-md-9">' . 
							'<div class="col-lg-4 col-xs-6">' . $infoBoxNew . '</div>' .
							'<div class="col-lg-4 col-xs-6">' . $infoBoxCross . '</div>' .
							'<div class="col-lg-4 col-xs-6">' . $infoBoxRepeat . '</div>' .
						'<div>';

					$box = (new Box('核心生意指标：', $instruction.$h, 'dot-circle-o', ''))->style('');

					$column->append($box);
				});
			});
			


		});
	}

}
