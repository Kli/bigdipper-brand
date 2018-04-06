<?php

namespace App\Admin\Controllers;

use App\Admin\Models\Cbp;
use App\Admin\Models\CbpEvol;

use App\Admin\Controllers\StoreBaseController;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;

use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Chart\Chartbar;

use DB, Request;

class CatWarningController extends StoreBaseController
{
	

	public function index()
	{
		if (Admin::user()->isAdministrator() && Admin::user()->database=='') {
			return Admin::content(function (Content $content) {

				$content->header('生意预警');
				$content->description('品类预警');

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
			$content->description('品牌预警');

			$content->body($this->gridCbpEvol());
		});
	}


	public function warningCat(Request $request, $category)
	{
		if (Admin::user()->isAdministrator() && Admin::user()->database=='') {
			return Admin::content(function (Content $content) {

				$content->header('生意预警');
				$content->description('品类预警');

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
			$content->description('品类预警');

			$barDatas = [];
			$cbps = DB::connection('store_db')->table('cbp')
						->where('category', '=', $category)
						->where('ytdmonth', '=', 12)
						->where('status', '=', 'total')
						->get();

			foreach ($cbps as $cbp) {
				$barDatas['customerno'][$cbp->year][$cbp->status]=round($cbp->pcnt*100,2);
				$barDatas['avgbrandsales'][$cbp->year][$cbp->status]=round($cbp->avgbrandsales);
				$barDatas['avgcustomerbrand'][$cbp->year][$cbp->status]=round($cbp->avgcustomerbrand,2);
				$barDatas['ytdtotalcustomernum'][$cbp->year][$cbp->status]=round($cbp->ytdtotalcustomernum);
				$barDatas['sales_part'][$cbp->year][$cbp->status]=round($cbp->sales);
			}

			$content->row(function (Row $row) use($category, $barDatas) {
				$row->column(7, function (Column $column) {
					$column->append($this->gridCbp());
				});
				if (empty($barDatas)) {
					$row->column(5, function (Column $column) use($category, $barDatas) {
						$box = (new Box($category, '没有数据', 'info', ''))->style('info');

						$column->append($box);
					});
				} else {
					$row->column(5, function (Column $column) use($category, $barDatas) {
						
						$updateTime = getLastUpdateTime(Admin::user()->database);
						$updateTimeYear = date('Y', strtotime($updateTime));

						$buttons ='<div class="row">
									<div class="col-md-5 col-md-offset-1">
									<a class="btn btn-block btn-sm btn-primary" href="/admin/store/brand-warning/' . $category . '"> <i class="icon fa fa-tags"></i> 品类品牌详情</a>
									</div>
									<div class="col-md-5">
									<a class="btn btn-block btn-sm btn-primary" href="/admin/store/member-cat-warning/' . $category . '"> <i class="icon fa fa-users"></i> 品类交易详情</a>
									</div>
								</div>';

						$title0 = '<h5 class="box-title"> 会员占比</h5>';
						$bar0 = new ChartBar(
			                        ['会员占比'],
			                        [
			                            [ 
			                            	'label' => $updateTimeYear-1,
			                            	'data' => [ $barDatas['customerno'][$updateTimeYear-1]['new'] ],
			                            	'backgroundColor' => '#00c0ef',
			                            	'borderColor' => '0073b7',
			                            	'borderWidth' => 1

			                            ],
			                            [ 
			                            	'label' => $updateTimeYear,
			                            	'data' => [ $barDatas['customerno'][$updateTimeYear]['new'] ],
			                            	'backgroundColor' => '#3c8dbc',
			                            	'borderColor' => '0073b7',
			                            	'borderWidth' => 1
			                            ],
			                        ]
			                    );
						$bar0->options(['barValueSpacing' => 60, 'barDatasetSpacing' => 30]);

						$title1 = '<h5 class="box-title"> 会员人数</h5>';
						$bar1 = new ChartBar(
			                        ['门店招新', '品类招新', '品类复购'],
			                        [
			                            [ 
			                            	'label' => $updateTimeYear-1,
			                            	'data' => [ $barDatas['ytdtotalcustomernum'][$updateTimeYear-1]['new'],$barDatas['ytdtotalcustomernum'][$updateTimeYear-1]['cross'],$barDatas['ytdtotalcustomernum'][$updateTimeYear-1]['repeat'] ],
			                            	'backgroundColor' => '#00c0ef',
			                            	'borderColor' => '0073b7',
			                            	'borderWidth' => 1
			                            ],
			                            [ 
			                            	'label' => $updateTimeYear,
			                            	'data' => [ $barDatas['ytdtotalcustomernum'][$updateTimeYear]['new'],$barDatas['ytdtotalcustomernum'][$updateTimeYear]['cross'],$barDatas['ytdtotalcustomernum'][$updateTimeYear]['repeat'] ],
			                            	'backgroundColor' => '#3c8dbc',
			                            	'borderColor' => '0073b7',
			                            	'borderWidth' => 1
			                            ],
			                        ]
			                    );

						$title2 = '<h5 class="box-title"> 平均品牌花费</h5>';
						$bar2 = new ChartBar(
			                        ['门店招新', '品类招新', '品类复购'],
			                        [
			                            [
			                            	'label' => $updateTimeYear-1,
			                            	'data' => [$barDatas['avgbrandsales'][$updateTimeYear-1]['new'],$barDatas['avgbrandsales'][$updateTimeYear-1]['cross'],$barDatas['avgbrandsales'][$updateTimeYear-1]['repeat'] ],
			                            	'backgroundColor' => '#00c0ef',
			                            	'borderColor' => '0073b7',
			                            	'borderWidth' => 1
			                            ],
			                            [
			                            	'label' => $updateTimeYear,
			                            	'data' => [ $barDatas['avgbrandsales'][$updateTimeYear]['new'],$barDatas['avgbrandsales'][$updateTimeYear]['cross'],$barDatas['avgbrandsales'][$updateTimeYear]['repeat'] ],
			                            	'backgroundColor' => '#3c8dbc',
			                            	'borderColor' => '0073b7',
			                            	'borderWidth' => 1
			                            ],
			                        ]
			                    );

						$title3 = '<h5 class="box-title"> 平均跨品牌数</h5>';
						$bar3 = new ChartBar(
			                        ['门店招新', '品类招新', '品类复购'],
			                        [
			                            [
			                            	'label' => $updateTimeYear-1,
			                            	'data' => [ $barDatas['avgcustomerbrand'][$updateTimeYear-1]['new'],$barDatas['avgcustomerbrand'][$updateTimeYear-1]['cross'],$barDatas['avgcustomerbrand'][$updateTimeYear-1]['repeat'] ],
			                            	'backgroundColor' => '#00c0ef',
			                            	'borderColor' => '0073b7',
			                            	'borderWidth' => 1
			                            ],
			                            [
			                            	'label' => $updateTimeYear,
			                            	'data' => [ $barDatas['avgcustomerbrand'][$updateTimeYear]['new'],$barDatas['avgcustomerbrand'][$updateTimeYear]['cross'],$barDatas['avgcustomerbrand'][$updateTimeYear]['repeat'] ],
			                            	'backgroundColor' => '#3c8dbc',
			                            	'borderColor' => '0073b7',
			                            	'borderWidth' => 1
			                            ],
			                        ]
			                    );

						$titleHB = '<h5 class="box-title"> 会员销售组成</h5>';
						$horizonBar = new ChartBar(
									[$updateTimeYear-1, $updateTimeYear],
			                        [
			                            [
			                            	'label' => '门店招新',
			                            	'data' => [round($barDatas['sales_part'][$updateTimeYear-1]['new']/$barDatas['sales_part'][$updateTimeYear-1]['total']*100,1), round($barDatas['sales_part'][$updateTimeYear]['new']/$barDatas['sales_part'][$updateTimeYear]['total']*100,1)],
			                            	'backgroundColor' => '#dd4b39',
			                            	'borderColor' => '0073b7',
			                            	'borderWidth' => 1
			                            ],
			                            [
			                            	'label' => '品类招新',
			                            	'data' => [round($barDatas['sales_part'][$updateTimeYear-1]['cross']/$barDatas['sales_part'][$updateTimeYear-1]['total']*100,1), round($barDatas['sales_part'][$updateTimeYear]['cross']/$barDatas['sales_part'][$updateTimeYear]['total']*100,1)],
			                            	'backgroundColor' => '#f39c12',
			                            	'borderColor' => '0073b7',
			                            	'borderWidth' => 1
			                            ],
			                            [
			                            	'label' => '品类复购',
			                            	'data' => [round($barDatas['sales_part'][$updateTimeYear-1]['repeat']/$barDatas['sales_part'][$updateTimeYear-1]['total']*100,1), round($barDatas['sales_part'][$updateTimeYear]['repeat']/$barDatas['sales_part'][$updateTimeYear]['total']*100,1)],
			                            	'backgroundColor' => '#00c0ef',
			                            	'borderColor' => '0073b7',
			                            	'borderWidth' => 1
			                            ],
			                        ]
								);
						$horizonBar->type('horizontalBar');
						$horizonBar->options([
												'elements' => [
												    'rectangle' => [
												        'borderWidth' => 2,
												    ]
												],
												'responsive' => true,
												'scales' => [
												    'xAxes' => [[
												        'stacked' => true,
												    ]],
												    'yAxes' => [[
												        'stacked' => true
												    ]]
												]
											]);
						
						$bars = $buttons.$title0.$bar0.$titleHB.$horizonBar.$title1.$bar1.$title2.$bar2.$title3.$bar3;

						$box = (new Box($category, $bars, 'info', ''))->style('info');

						$column->append($box);
					});
				}
			});
		});
	}


	protected function gridCbpEvol()
	{
		return Admin::grid(CbpEvol::class, function (Grid $grid) {
			
			$storeRate = CbpEvol::getStoreRate();

            $grid->rnk('排名')->sortable();
            $grid->category('品类(万)')->sortable();
            $grid->column('this_ttl_sales', '销售预警')->display(function() use($storeRate) {
            	return number_format(round($this->this_ttl_sales/10000)) . " <span class='label " . cellColor($this->evol_ttl_sales*100, $storeRate)."'>".round($this->evol_ttl_sales*100)."%</span>";
            })->sortable();
            $grid->column('this_new_sales', '门店招新')->display(function() use($storeRate) {
            	return number_format(round($this->this_new_sales/10000)) . " <span class='label " . cellColor($this->evol_new_sales*100, $storeRate)."'>".round($this->evol_new_sales*100)."%</span>";
            })->sortable();
            $grid->column('this_cross_sales', '品类招新')->display(function() use($storeRate) {
            	return number_format(round($this->this_cross_sales/10000)) . " <span class='label " . cellColor($this->evol_cross_sales*100, $storeRate)."'>".round($this->evol_cross_sales*100)."%</span>";
            })->sortable();
            $grid->column('this_repeat_sales', '品类复购')->display(function() use($storeRate) {
            	return number_format(round($this->this_repeat_sales/10000)) . " <span class='label " . cellColor($this->evol_repeat_sales*100, $storeRate)."'>".round($this->evol_repeat_sales*100)."%</span>";
            })->sortable();
            $grid->column('this_pcnt', '会员占比')->display(function() use($storeRate) {
            	return number_format(round($this->this_pcnt*100)) . "% <span class='label " . cellCustomerColor($this->evol_pcnt*100, $storeRate)."'>".round($this->evol_pcnt*100)."%</span>";
            })->sortable();
            $grid->column('措施')->display(function() {
            	return createCatAction($this);
            })->style('max-width:250px');

            $grid->actions(function ($actions)  {
                $id = $actions->getKey();
                $actions->disableEdit();
                $actions->disableDelete();
                $actions->append('<a class="btn btn-block btn-primary" href="/admin/store/warning-cat/' . $id . '">品类详情</a>');
            });

            $grid->tools(function ($tools) {
                $tools->batch(function ($batch) {
                    $batch->disableDelete();
                });
            });

            $grid->disableRowSelector();
            $grid->disableCreation();
            $grid->disableFilter();
            $grid->disableExport();
        });
	}

	protected function gridCbp()
	{
		return Admin::grid(Cbp::class, function (Grid $grid) {
			$updateTime = 品类(万)(Admin::user()->database);
			
			$grid->model()->where('status', '=', 'total')
						  ->where('year', '=', date('Y', strtotime($updateTime)))
						  ->where('ytdmonth', '=', 12);

			$grid->model()->orderBy('rnk', 'asc');

            $grid->rnk('排名')->sortable();
            $grid->category('品类')->sortable();
            
            $grid->column('sales', '今年销售预测')->display(function() {
            	if ($this->lifestatus == '新开' || $this->lifestatus == '已关') {
            		return ' - ';
            	} else {
            		return number_format(round($this->sales));
            	}
            })->sortable();

            $grid->column('lifestatus', '销售预警')->display(function() {
            	if ($this->lifestatus == '新开') {
            		return '<span class="badge bg-green">' . $this->lifestatus . '</span>';
            	} else if ($this->lifestatus == '已关'){
            		return '<span class="badge bg-red">' . $this->lifestatus . '</span>';
            	} else {
            		$pastYearData = Cbp::where('category', '=', $this->category)->where('year', '=', intval($this->year)-1)->where('status', '=', 'total')->where('ytdmonth', '=', 12)->first();
            		// dd($pastYearData);
            		$rate = round($this->sales/$pastYearData->sales*100)-100;
                    $positive=($rate>0)?"+":"";

            		return '<span class="' . cellColor($rate, CbpEvol::getStoreRate()) . '">' . $positive . $rate . '%</span>';
            	}
            });

            $grid->actions(function ($actions) {
                $actions->disableEdit();
                $actions->disableDelete();
                if ($actions->row->lifestatus == '新开' || $actions->row->lifestatus == '已关') {
                	$actions->append('<a type="button" class="btn btn-block btn-primary disabled">品类详情</a>');
                } else {
                	$actions->append('<a class="btn btn-block btn-primary" href="/admin/store/warning-cat/' . $actions->row->category . '">品类详情</a>');
                }

            });

            $grid->tools(function ($tools) {
                $tools->batch(function ($batch) {
                    $batch->disableDelete();
                });
            });

            $grid->disableRowSelector();
            $grid->disableCreation();
            $grid->disableFilter();
            $grid->disableExport();
        });
	}

	
}
