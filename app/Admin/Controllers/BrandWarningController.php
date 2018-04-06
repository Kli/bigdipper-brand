<?php

namespace App\Admin\Controllers;

use App\Admin\Models\Bbp;
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

class BrandWarningController extends StoreBaseController
{
	public function index($category='', $brand='')
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

		return Admin::content(function (Content $content) use($category, $brand) {

			$content->header('生意预警');
			$content->description('品类预警');

				
			$content->row(function (Row $row) use($category, $brand) {
				$bbpCategories = Bbp::select('category')->distinct()->orderBy('category', 'asc')->get();
				
				$selectHtml = '
					<div class="form-group">
			        <select id="catname" class="form-control select2" name="catname"><option value="">选择一个品类</option>';
				if ($category != '') {
					foreach ($bbpCategories as $bbpCategory) {
						$selected = ($category==$bbpCategory->category)?' selected':'';
						$selectHtml .= '<option value="' . $bbpCategory->category . '" ' . $selected . '>' . $bbpCategory->category . '</option>';
					}
				} else {
					foreach ($bbpCategories as $bbpCategory) {
						$selectHtml .= '<option value="' . $bbpCategory->category . '">' . $bbpCategory->category . '</option>';
					}
				}
	        	$selectHtml .= '</select></div>';
	        	$selectHtml .= '
	        		<script>
	        			$("#catname").change(function(){
	        				console.log($(this).val());
	        				window.location.href="/admin/store/brand-warning/" + $(this).val();
	        			})
	        			$(document).ready(function() {
						    $(".select2").select2();
						});
	        		</script>
	        	';
				
	        	$row->column(12, function (Column $column) use ($selectHtml) {
	        		$column->append($selectHtml);
	        	});

	        	if ($category != '') {
	        		if ($brand != '') {

	        			// 品牌列表
	        			$row->column(7, function (Column $column) use ($category) {
		        			$column->append($this->gridBbp($category));
		        		});

		        		// 品牌详情
		        		$row->column(5, function (Column $column) use ($category, $brand) {
		        			
		        			$updateTime = getLastUpdateTime(Admin::user()->database, 'bbs');
							$updateTimeYear = date('Y', strtotime($updateTime));
							
		        			$brandDatas = [];
		        			$brandObjs = Bbp::where('category', '=', $category)
		        							->where('brand', '=', $brand)
		        							->where('ytdmonth', '=', 12)
		        							->orderBy('year')
		        							->get();

		        			foreach ($brandObjs as $brandObj) {
		        				$brandDatas['customerno'][$brandObj->year][$brandObj->status]=round($brandObj->pcnt*100,2);
				                $brandDatas['avgbrandsales'][$brandObj->year][$brandObj->status]=round($brandObj->avgbrandsales);
				                $brandDatas['avgcustomerbrand'][$brandObj->year][$brandObj->status]=round($brandObj->avgcustomerbrand,2);
				                $brandDatas['ytdtotalcustomernum'][$brandObj->year][$brandObj->status]=round($brandObj->ytdtotalcustomernum);
				                $brandDatas['sales_part'][$brandObj->year][$brandObj->status]=round($brandObj->sales);
				                $brandDatas['brand'] = $brandObj->brand_name;
		        			}
			        		
			        		$buttons ='<div class="row">
										<div class="col-md-6 col-md-offset-3">
										<a class="btn btn-block btn-sm btn-primary" href="/admin/store/member-brand-warning/'.$category.'/'.$brand.'"> <i class="icon fa fa-users"></i> 品牌交易详情</a>
										</div>
									</div>';

							$title0 = '<h5 class="box-title"> 会员占比</h5>';
							$bar0 = new ChartBar(
				                        ['会员占比'],
				                        [
				                            [ 
				                            	'label' => $updateTimeYear-1,
				                            	'data' => [ $brandDatas['customerno'][$updateTimeYear-1]['new'] ],
				                            	'backgroundColor' => '#00c0ef',
				                            	'borderColor' => '0073b7',
				                            	'borderWidth' => 1

				                            ],
				                            [ 
				                            	'label' => $updateTimeYear,
				                            	'data' => [ $brandDatas['customerno'][$updateTimeYear]['new'] ],
				                            	'backgroundColor' => '#3c8dbc',
				                            	'borderColor' => '0073b7',
				                            	'borderWidth' => 1
				                            ],
				                        ]
				                    );
							$bar0->options(['barValueSpacing' => 60, 'brandDatasetSpacing' => 30]);

							$title1 = '<h5 class="box-title"> 会员人数</h5>';
							$bar1 = new ChartBar(
				                        ['门店招新', '品类招新', '品类复购'],
				                        [
				                            [ 
				                            	'label' => $updateTimeYear-1,
				                            	'data' => [ $brandDatas['ytdtotalcustomernum'][$updateTimeYear-1]['new'],$brandDatas['ytdtotalcustomernum'][$updateTimeYear-1]['cross'],$brandDatas['ytdtotalcustomernum'][$updateTimeYear-1]['repeat'] ],
				                            	'backgroundColor' => '#00c0ef',
				                            	'borderColor' => '0073b7',
				                            	'borderWidth' => 1
				                            ],
				                            [ 
				                            	'label' => $updateTimeYear,
				                            	'data' => [ $brandDatas['ytdtotalcustomernum'][$updateTimeYear]['new'],$brandDatas['ytdtotalcustomernum'][$updateTimeYear]['cross'],$brandDatas['ytdtotalcustomernum'][$updateTimeYear]['repeat'] ],
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
				                            	'data' => [$brandDatas['avgbrandsales'][$updateTimeYear-1]['new'],$brandDatas['avgbrandsales'][$updateTimeYear-1]['cross'],$brandDatas['avgbrandsales'][$updateTimeYear-1]['repeat'] ],
				                            	'backgroundColor' => '#00c0ef',
				                            	'borderColor' => '0073b7',
				                            	'borderWidth' => 1
				                            ],
				                            [
				                            	'label' => $updateTimeYear,
				                            	'data' => [ $brandDatas['avgbrandsales'][$updateTimeYear]['new'],$brandDatas['avgbrandsales'][$updateTimeYear]['cross'],$brandDatas['avgbrandsales'][$updateTimeYear]['repeat'] ],
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
				                            	'data' => [round($brandDatas['sales_part'][$updateTimeYear-1]['new']/$brandDatas['sales_part'][$updateTimeYear-1]['total']*100,1), round($brandDatas['sales_part'][$updateTimeYear]['new']/$brandDatas['sales_part'][$updateTimeYear]['total']*100,1)],
				                            	'backgroundColor' => '#dd4b39',
				                            	'borderColor' => '0073b7',
				                            	'borderWidth' => 1
				                            ],
				                            [
				                            	'label' => '品类招新',
				                            	'data' => [round($brandDatas['sales_part'][$updateTimeYear-1]['cross']/$brandDatas['sales_part'][$updateTimeYear-1]['total']*100,1), round($brandDatas['sales_part'][$updateTimeYear]['cross']/$brandDatas['sales_part'][$updateTimeYear]['total']*100,1)],
				                            	'backgroundColor' => '#f39c12',
				                            	'borderColor' => '0073b7',
				                            	'borderWidth' => 1
				                            ],
				                            [
				                            	'label' => '品类复购',
				                            	'data' => [round($brandDatas['sales_part'][$updateTimeYear-1]['repeat']/$brandDatas['sales_part'][$updateTimeYear-1]['total']*100,1), round($brandDatas['sales_part'][$updateTimeYear]['repeat']/$brandDatas['sales_part'][$updateTimeYear]['total']*100,1)],
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
							
							$bars = $buttons.$title0.$bar0.$titleHB.$horizonBar.$title1.$bar1.$title2.$bar2;

							$box = (new Box($brand, $bars, 'info', ''))->style('info');

							$column->append($box);
		        		});
	        		} else {
	        			$row->column(12, function (Column $column) use ($category) {
		        			$column->append($this->gridBbp($category));
		        		});
	        		}

	        	}
			});
		});
	}


	protected function gridBbp($category)
	{
		return Admin::grid(Bbp::class, function (Grid $grid) use($category) {
			
			$updateTime = getLastUpdateTime(Admin::user()->database, 'bbp');
			$updateTimeYear = date('Y', strtotime($updateTime));

			$grid->model()->where('category', '=', $category)->where('status', '=', 'total')->where('year', '=', $updateTimeYear)->where('ytdmonth', '=', 12);
			$grid->model()->orderBy('rnk', 'asc');

            $grid->rnk('排名')->sortable();
            $grid->brand_name('品牌')->sortable();
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
            		$pastYearData = Bbp::where('category', '=', $this->category)->where('year', '=', intval($this->year)-1)->where('status', '=', 'total')->where('ytdmonth', '=', 12)->first();
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
                	$actions->append('<a type="button" class="btn btn-block btn-primary disabled">品牌详情</a>');
                } else {
                	$actions->append('<a class="btn btn-block btn-primary" href="/admin/store/brand-warning/' . $actions->row->category .'/' . $actions->row->brand . '">品牌详情</a>');
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