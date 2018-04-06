<?php

namespace App\Admin\Controllers;

use App\Admin\Models\BaseMerchandisingStore;
use App\Admin\Models\BaseMerchandisingCat;
use App\Admin\Models\BrandIndex;
use App\Admin\Models\CatIndex;

use App\Http\Controllers\Controller;
use Encore\Admin\Grid;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;

use Encore\Admin\Widgets\Tab;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Chart\Radar;
use Encore\Admin\Widgets\Alert;
use Encore\Admin\Widgets\Table;

use DB;

class MerchandisingController extends Controller
{
	public function index()
	{
	}

	public function store()
	{
		return Admin::content(function (Content $content) {

			$content->header('组货调改');
			$content->description('组货预警');


			$similarStores = DB::connection('mysql_store_base')
								->table('similarstore')
								->where('store', '=', Admin::user()->database)
								->pluck('similar')->toArray();
			


			$merchandisingStore = BaseMerchandisingStore::where('store', '=', Admin::user()->database)->first();

			$radarMy['pingxiao']=round(($merchandisingStore->pingxiao-$merchandisingStore->pingxiao_min)/($merchandisingStore->pingxiao_max-$merchandisingStore->pingxiao_min)*100);
			$radarMy['avg_cat']=round(($merchandisingStore->avg_cat-$merchandisingStore->avg_cat_min)/($merchandisingStore->avg_cat_max-$merchandisingStore->avg_cat_min)*100);
			$radarMy['avg_brand']=round(($merchandisingStore->avg_brand-$merchandisingStore->avg_brand_min)/($merchandisingStore->avg_brand_max-$merchandisingStore->avg_brand_min)*100);
			$radarMy['avg_brand_f']=round(($merchandisingStore->avg_brand_f-$merchandisingStore->avg_brand_f_min)/($merchandisingStore->avg_brand_f_max-$merchandisingStore->avg_brand_f_min)*100);
			$radarMy['avg_brand_sales']=round(($merchandisingStore->avg_brand_sales-$merchandisingStore->avg_brand_sales_min)/($merchandisingStore->avg_brand_sales_max-$merchandisingStore->avg_brand_sales_min)*100);

			$tableData['坪效']['store']=round($merchandisingStore->pingxiao/1000000);
			$tableData['人均跨品类数']['store']=round($merchandisingStore->avg_cat,2);
			$tableData['人均跨品牌数']['store']=round($merchandisingStore->avg_brand,2);
			$tableData['平均品牌频次']['store']=round($merchandisingStore->avg_brand_f,2);
			$tableData['平均品牌消费']['store']=round($merchandisingStore->avg_brand_sales,2);
			$tableData['坪效']['min']=round($merchandisingStore->pingxiao_min/1000000);
			$tableData['人均跨品类数']['min']=round($merchandisingStore->avg_cat_min,2);
			$tableData['人均跨品牌数']['min']=round($merchandisingStore->avg_brand_min,2);
			$tableData['平均品牌频次']['min']=round($merchandisingStore->avg_brand_f_min,2);
			$tableData['平均品牌消费']['min']=round($merchandisingStore->avg_brand_sales_min,2);
			$tableData['坪效']['max']=round($merchandisingStore->pingxiao_max/1000000);
			$tableData['人均跨品类数']['max']=round($merchandisingStore->avg_cat_max,2);
			$tableData['人均跨品牌数']['max']=round($merchandisingStore->avg_brand_max,2);
			$tableData['平均品牌频次']['max']=round($merchandisingStore->avg_brand_f_max,2);
			$tableData['平均品牌消费']['max']=round($merchandisingStore->avg_brand_sales_max,2);


			$similarMerchandisingStore = BaseMerchandisingStore::whereIn('store', $similarStores)
								->select(DB::raw('
										AVG(pingxiao) AS pingxiao,
										AVG(avg_cat) AS avg_cat,
										AVG(avg_brand) AS avg_brand,
										AVG(avg_brand_f) AS avg_brand_f,
										AVG(avg_brand_sales) AS avg_brand_sales,
										pingxiao_max, 
										pingxiao_min, 
										avg_cat_max, 
										avg_cat_min, 
										avg_brand_max, 
										avg_brand_min, 
										avg_brand_f_max, 
										avg_brand_f_min, 
										avg_brand_sales_max, 
										avg_brand_sales_min
									'))
								->first();

			$radarSimilar['pingxiao']=round(($similarMerchandisingStore->pingxiao-$similarMerchandisingStore->pingxiao_min)/($similarMerchandisingStore->pingxiao_max-$similarMerchandisingStore->pingxiao_min)*100);
			$radarSimilar['avg_cat']=round(($similarMerchandisingStore->avg_cat-$similarMerchandisingStore->avg_cat_min)/($similarMerchandisingStore->avg_cat_max-$similarMerchandisingStore->avg_cat_min)*100);
			$radarSimilar['avg_brand']=round(($similarMerchandisingStore->avg_brand-$similarMerchandisingStore->avg_brand_min)/($similarMerchandisingStore->avg_brand_max-$similarMerchandisingStore->avg_brand_min)*100);
			$radarSimilar['avg_brand_f']=round(($similarMerchandisingStore->avg_brand_f-$similarMerchandisingStore->avg_brand_f_min)/($similarMerchandisingStore->avg_brand_f_max-$similarMerchandisingStore->avg_brand_f_min)*100);
			$radarSimilar['avg_brand_sales']=round(($similarMerchandisingStore->avg_brand_sales-$similarMerchandisingStore->avg_brand_sales_min)/($similarMerchandisingStore->avg_brand_sales_max-$similarMerchandisingStore->avg_brand_sales_min)*100);

			$tableData['坪效']['savg']=round($similarMerchandisingStore->pingxiao/1000000);
			$tableData['人均跨品类数']['savg']=round($similarMerchandisingStore->avg_cat,2);
			$tableData['人均跨品牌数']['savg']=round($similarMerchandisingStore->avg_brand,2);
			$tableData['平均品牌频次']['savg']=round($similarMerchandisingStore->avg_brand_f,2);
			$tableData['平均品牌消费']['savg']=round($similarMerchandisingStore->avg_brand_sales,2);

			$combination = ($radarMy['avg_brand']<$radarSimilar['avg_brand'] || $radarMy['avg_cat']<$radarSimilar['avg_cat'])?1:0;
			$operation = ($radarMy['avg_brand_f']<$radarSimilar['avg_brand_f'] || $radarMy['avg_brand_sales']<$radarSimilar['avg_brand_sales'])?1:0;

			if ($combination+$operation>0) {
				$content->row(function (Row $row) use($combination, $operation) {

					$row->column(12, function (Column $column) use($combination, $operation) {


						$title = '门店竞争力预警';
						$style = 'warning';
						$icon = 'info';

						$content = '<ul>';
						$content .= ($combination > 0)?'<li>本店品类／品牌之间组合存在提升空间，现有品类／品牌客群不一致</li>':'';
						$content .= ($operation > 0)?'<li>本店品牌存在提升空间，品牌自身的顾客维护不足</li>':'';
						$content .= '</ul>';

						$column->append( (new Alert($content, $title))->style($style)->icon($icon)->setAttributes('font-weight:bold;') );
					});
				});
			}

			$content->row(function (Row $row) use($tableData, $radarMy, $radarSimilar) {

				$row->column(5, function (Column $column) use($radarMy, $radarSimilar) {

					$labels = ['坪效','会员占比','门店招新','品类招新','品类复购'];
					$datasets = [
						[
							'label' => '我的门店',
							'data' => array_values($radarMy),
							'backgroundColor' => 'rgba(243,156,18,0.2)',
							'borderColor' => '#f39c12',
							'pointBackgroundColor' => '#f39c12',
							
						],
						[
							'label' => '类似门店',
							'data' => array_values($radarSimilar),
							'backgroundColor' => 'rgba(0,192,239,0.2)',
							'borderColor' => '#00c0ef',
							'pointBackgroundColor' => '#00c0ef',
						]
					];
					$options = [
						'legend' => [
										'position' => 'bottom',
									],
					];

					$radar = new Radar($labels, $datasets);
					$radar->options($options);

					$box = (new Box('门店概况', $radar, 'info', ''))->style('info');

					$column->append($box);
					
				});

				$row->column(7, function (Column $column) use($tableData) {

					$data = [];
					$i = 0;
					foreach ($tableData as $key => $value) {
						$progress=round(($value['store']-$value['min'])/($value['max']-$value['min'])*100);
						$rate = round(($value['store']/$value['savg']-1)*100);
						$positive=($rate>0)?"+":"";

						$data[$i][] = $key;
						$data[$i][] = transToRate($value['min'],false);
						$data[$i][] = "<div class=\"progress progress-xs\">
										<div class=\"progress-bar progress-bar-".progressColor($progress)."\" style=\"width: ".$progress."%\" data-toggle=\"tooltip\" title=\"".$progress."%\"></div>
									  </div>";
						$data[$i][] = transToRate($value['max'],false);
						$data[$i][] = "<span class=\"badge bg-".cellStoreColor($rate)."\">".$positive.$rate."%</span>";

						$i++;
					}

					$header = ['类型','Min','本店分数','Max','类似门店对比'];
					$table = new Table($header, $data);

					$box = (new Box( '门店详情', $table, ''))->style('danger');

					$column->append($box);
				});


			});
		});
	}


	public function cat()
	{
		return Admin::content(function (Content $content) {

			$content->header('组货调改');
			$content->description('品类动能');
			
			$content->body($this->gridCat());
		});
	}

	public function brand($catname_sc='')
	{
		if (Admin::user()->isAdministrator() && Admin::user()->database=='') {
			return Admin::content(function (Content $content) {

				$content->header('组货调改');
				$content->description('品牌土壤');

				$content->row(function (Row $row) {

					$words = '请先配置您的数据库信息。';
					
					$row->column(12, function (Column $column) use ($words) {
						$column->append(new Callout($words));
					});
				});
			});
		}

		return Admin::content(function (Content $content) use($catname_sc) {

			$content->header('组货调改');
			$content->description('品牌土壤');

				
			$content->row(function (Row $row) use($catname_sc) {
				$catnameScs = BaseMerchandisingCat::select('catname as catname_sc')
										->where('store', '=', Admin::user()->database)
										->distinct()
										->orderBy('catname', 'asc')
										->get();
				
				$selectHtml = '
					<div class="form-group">
					<select id="catname" class="form-control select2" name="catname"><option value="">选择一个品类</option>';
				if ($catname_sc != '') {
					foreach ($catnameScs as $catnameSc) {
						$selected = ($catname_sc==$catnameSc->catname_sc)?' selected':'';
						$selectHtml .= '<option value="' . $catnameSc->catname_sc . '" ' . $selected . '>' . $catnameSc->catname_sc . '</option>';
					}
				} else {
					foreach ($catnameScs as $catnameSc) {
						$selectHtml .= '<option value="' . $catnameSc->catname_sc . '">' . $catnameSc->catname_sc . '</option>';
					}
				}
				$selectHtml .= '</select></div>';
				$selectHtml .= '
					<script>
						$("#catname").change(function(){
							console.log($(this).val());
							window.location.href="/admin/store/brand-merchandising/" + $(this).val();
						});
						$(document).ready(function() {
							$(".select2").select2();
						});
					</script>
				';
				
				$row->column(12, function (Column $column) use ($selectHtml) {
					$column->append($selectHtml);
				});

				if ($catname_sc != '') {
					
					$row->column(12, function (Column $column) use ($catname_sc) {
						$merchandisingCat = BaseMerchandisingCat::where('store', '=', Admin::user()->database)->where('catname', $catname_sc)->first();

						$table = '
						<table class="table table-bordered dataTable">
							<tr>
								<th>客单价</th>
								<th>年龄</th>
								<th>人均品牌数</th>
								<th>人均品牌客单</th>
								<th>人均品牌频次</th>
							</tr>
							<tr>
							<td><h2 class="text-aqua">'.number_format($merchandisingCat->aus).'</h2></td>
							<td><h2 class="text-aqua">'.round($merchandisingCat->avg_age,1).'</h2></td>';

						$positive=(round($merchandisingCat->avg_brand/$merchandisingCat->avg_brand_mean*100-100)>0)?'+':'';
										$avg_brand_evol=round($merchandisingCat->avg_brand/$merchandisingCat->avg_brand_mean*100-100);
						if($merchandisingCat->avg_brand_max!=$merchandisingCat->avg_brand_min){
							$progress=round(($merchandisingCat->avg_brand-$merchandisingCat->avg_brand_min)/($merchandisingCat->avg_brand_max-$merchandisingCat->avg_brand_min)*100);
						} else {
							$progress=100;
						}
						$table .= '<td><h4><b>'.round($merchandisingCat->avg_brand,2).'</b> <span class="badge bg-'.cellStoreColor(round($avg_brand_evol)).'">'.$positive.round($avg_brand_evol).'%</span></h4>

						<div class="progress progress-xs">
											<div class="progress-bar progress-bar-'.progressColor($progress).'" style="width:'.$progress.'%"></div>
										</div>
									</td>';

						$positive=(round($merchandisingCat->avg_brand_aus/$merchandisingCat->avg_brand_aus_mean*100-100)>0)?'+':'';
						$avg_brand_aus_evol=round($merchandisingCat->avg_brand_aus/$merchandisingCat->avg_brand_aus_mean*100-100);
						if($merchandisingCat->avg_brand_aus_max!=$merchandisingCat->avg_brand_aus_min){
							$progress=round(($merchandisingCat->avg_brand_aus-$merchandisingCat->avg_brand_aus_min)/($merchandisingCat->avg_brand_aus_max-$merchandisingCat->avg_brand_aus_min)*100);
						} else {
							$progress=100;
						}
						$table .= '<td><h4><b>'.number_format(round($merchandisingCat->avg_brand_aus)).'</b> <span class="badge bg-'.cellStoreColor($avg_brand_aus_evol).'">'.$positive.round($avg_brand_aus_evol).'%</span></h4>
						<div class="progress progress-xs">
											<div class="progress-bar progress-bar-'.progressColor($progress).'" style="width:'.$progress.'%"></div>
										</div>
									</td>';

						$positive=(round($merchandisingCat->avg_brand_f/$merchandisingCat->avg_brand_f_mean*100-100)>0)?'+':'';
						$avg_brand_f_evol=round($merchandisingCat->avg_brand_f/$merchandisingCat->avg_brand_f_mean*100-100);
						if($merchandisingCat->avg_brand_f_max!=$merchandisingCat->avg_brand_f_min){
							$progress=round(($merchandisingCat->avg_brand_f-$merchandisingCat->avg_brand_f_min)/($merchandisingCat->avg_brand_f_max-$merchandisingCat->avg_brand_f_min)*100);
						} else {
							$progress=100;
						}
						$table .= '<td><h4><b>'.round($merchandisingCat->avg_brand_f,2).'</b> <span class="badge bg-'.cellStoreColor($avg_brand_f_evol).'">'.$positive.round($avg_brand_f_evol).'%</span></h4>
						<div class="progress progress-xs">
											<div class="progress-bar progress-bar-'.progressColor($progress).'" style="width:'.$progress.'%"></div>
										</div>
									</td>
							</tr>
						</table>';

						$box = (new Box('<span class="text-aqua">'.$catname_sc . '</span> - 目前表现', $table, 'info', ''))->style('info');
						$column->append($box);
					});

					$row->column(12, function (Column $column) use ($catname_sc) {

					});
					
				}
			});
		});
	}


	protected function gridCat()
	{
		return Admin::grid(BaseMerchandisingCat::class, function (Grid $grid) {
			$grid->model()->where('store', '=', Admin::user()->database);
			$grid->model()->orderBy('rnk', 'asc');

			$grid->rnk('排名')->sortable();
			$grid->catname('品类')->sortable();
			$grid->aus('客单价')->display(function() {
				return number_format($this->aus);
			})->sortable();
			$grid->avg_age('平均年龄')->display(function() {
				return round($this->avg_age);
			})->sortable();
			
			$grid->brand_cnt('品类品牌数')->display(function() {
				$positive=(round($this->brand_cnt/$this->brand_cnt_mean*100-100)>0)?"+":"";
				$brand_cnt_evol=round($this->brand_cnt/$this->brand_cnt_mean*100-100);
				$progress = ($this->brand_cnt_max!=$this->brand_cnt_min)?
								round(($this->brand_cnt-$this->brand_cnt_min)/($this->brand_cnt_max-$this->brand_cnt_min)*100) : 100;

				return  "<h4><b>".round($this->brand_cnt*100)."</b> <span class='badge bg-".cellStoreColor(round($brand_cnt_evol))."'>".$positive.round($brand_cnt_evol)."%</span></h4><div class=\"progress progress-xs\">
								<div class=\"progress-bar progress-bar-".progressColor($progress)."\" style=\"width:".$progress."%\" data-toggle=\"tooltip\" title=\"".$progress."%\"></div>
							  </div>";
			})->sortable();

			$grid->brand_sales('品类品牌均销售')->display(function() {

				$positive=(round($this->brand_sales/$this->brand_sales_mean*100-100)>0)?"+":"";
				$brand_sales_evol=round($this->brand_sales/$this->brand_sales_mean*100-100);
				$progress = ($this->brand_sales_max!=$this->brand_sales_min)?
								round(($this->brand_sales-$this->brand_sales_min)/($this->brand_sales_max-$this->brand_sales_min)*100) : 100;

				return  "<h4><b>".round($this->brand_sales*100)."</b> <span class='badge bg-".cellStoreColor(round($brand_sales_evol))."'>".$positive.round($brand_sales_evol)."%</span></h4><div class=\"progress progress-xs\">
								<div class=\"progress-bar progress-bar-".progressColor($progress)."\" style=\"width:".$progress."%\" data-toggle=\"tooltip\" title=\"".$progress."%\"></div>
							  </div>";
			})->sortable();

			$grid->correlation('品类关联度')->display(function() {

				$positive=(round($this->correlation/$this->correlation_mean*100-100)>0)?"+":"";
				$correlation_evol=round($this->correlation/$this->correlation_mean*100-100);
				$progress = ($this->correlation_max!=$this->correlation_min)?
								round(($this->correlation-$this->correlation_min)/($this->correlation_max-$this->correlation_min)*100) : 100;

				return  "<h4><b>".round($this->correlation*100)."</b> <span class='badge bg-".cellStoreColor(round($correlation_evol))."'>".$positive.round($correlation_evol)."%</span></h4><div class=\"progress progress-xs\">
								<div class=\"progress-bar progress-bar-".progressColor($progress)."\" style=\"width:".$progress."%\" data-toggle=\"tooltip\" title=\"".$progress."%\"></div>
							  </div>";
			})->sortable();



			$grid->actions(function ($actions) {
				$actions->disableEdit();
				$actions->disableDelete();
				$actions->append('<a class="btn btn-block btn-primary" href="/admin/store/brand-merchandising/' . $this->row->catname . '">组货调整</a>');
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

	// 我的门店表现
	protected function gridMyStore($catname_sc)
	{
		return Admin::grid(BrandIndex::class, function (Grid $grid) use($catname_sc) {
			$grid->model()->where('store', '=', Admin::user()->database)
							->where('catname_sc', '=', $catname_sc)
							->where('index_new', '<>', '')
							->where('index_cross', '<>', '')
							->where('index_repeat', '<>', '');
			$grid->model()->orderBy('rnk', 'asc');

			$grid->rnk('销售排名')->sortable();
			$grid->brandname_sc('品牌')->display(function() {
				return strstr($this->brandname_sc,'~',true);
			})->sortable();
			$grid->catname_sc('品类')->sortable();
			$grid->column('age_avg', '平均年龄')->display(function() {
				return round($this->age_avg);
			})->sortable();

			$grid->aus('平均客单')->display(function() {
				return round($this->aus);
			})->sortable();

			$grid->column('门店招新指数/对比')->display(function() {
				if ($this->index_new_mean) {
					$positive=(round($this->index_new/$this->index_new_mean*100-100)>0)?"+":"";
					$index_new_evol=round($this->index_new/$this->index_new_mean*100-100);
				} else {
					$positive='+';
					$index_new_evol=round($this->index_new*100);
				}
				
				$progress = ($this->index_new_max!=$this->index_new_min)?
								round(($this->index_new-$this->index_new_min)/($this->index_new_max-$this->index_new_min)*100) : '';

				return  "<h4><b>".round($this->index_new*100)."</b> <span class='badge bg-".cellStoreColor(round($index_new_evol))."'>".$positive.round($index_new_evol)."%</span></h4><div class=\"progress progress-xs\">
								<div class=\"progress-bar progress-bar-".progressColor($progress)."\" style=\"width:".$progress."%\" data-toggle=\"tooltip\" title=\"".$progress."%\"></div>
							  </div>";
			});

			$grid->column('品牌招新指数/对比')->display(function() {
				if ($this->index_cross_mean) {
					$positive=(round($this->index_cross/$this->index_cross_mean*100-100)>0)?"+":"";
					$index_cross_evol=round($this->index_cross/$this->index_cross_mean*100-100);
				} else {
					$positive='+';
					$index_cross_evol=round($this->index_new*100);
				}

				$progress = ($this->index_cross_max!=$this->index_cross_min)?
								round(($this->index_cross-$this->index_cross_min)/($this->index_new_max-$this->index_cross_min)*100) : '';

				return  "<h4><b>".round($this->index_cross*100)."</b> <span class='badge bg-".cellStoreColor(round($index_cross_evol))."'>".$positive.round($index_cross_evol)."%</span></h4><div class=\"progress progress-xs\">
								<div class=\"progress-bar progress-bar-".progressColor($progress)."\" style=\"width:".$progress."%\" data-toggle=\"tooltip\" title=\"".$progress."%\"></div>
							  </div>";
			});

			$grid->column('品牌复购指数/对比')->display(function() {
				if ($this->index_repeat_mean) {
					$positive=(round($this->index_repeat/$this->index_repeat_mean*100-100)>0)?"+":"";
					$index_repeat_evol=round($this->index_repeat/$this->index_repeat_mean*100-100);
				} else {
					$positive='+';
					$index_repeat_evol=round($this->index_new*100);
				}
					
				$progress = ($this->index_cross_max!=$this->index_repeat_min)?
								round(($this->index_repeat-$this->index_repeat_min)/($this->index_new_max-$this->index_repeat_min)*100) : '';

				return  "<h4><b>".round($this->index_repeat*100)."</b> <span class='badge bg-".cellStoreColor(round($index_repeat_evol))."'>".$positive.round($index_repeat_evol)."%</span></h4><div class=\"progress progress-xs\">
								<div class=\"progress-bar progress-bar-".progressColor($progress)."\" style=\"width:".$progress."%\" data-toggle=\"tooltip\" title=\"".$progress."%\"></div>
							  </div>";
			});

			$grid->tools(function ($tools) {
				$tools->batch(function ($batch) {
					$batch->disableDelete();
				});
			});

			$grid->disableActions();
			$grid->disableRowSelector();
			$grid->disableCreation();
			$grid->disableFilter();
			$grid->disableExport();
		});
	}

	// 市场平均表现
	protected function gridAvg($catname_sc)
	{
		return Admin::grid(BrandIndex::class, function (Grid $grid) use($catname_sc) {
			$grid->model()->where('store', '=', Admin::user()->database)
							->where('catname_sc', '=', $catname_sc)
							->where('index_new_mean', '<>', '')
							->where('index_cross_mean', '<>', '')
							->where('index_repeat_mean', '<>', '');
			$grid->model()->orderBy('rnk', 'asc');
			$grid->model()->groupBy('brandname_sc');

			$grid->brandname_sc('品牌')->display(function() {
				return strstr($this->brandname_sc,'~',true);
			})->sortable();
			$grid->catname_sc('品类')->sortable();
			$grid->column('age_avg', '平均年龄')->display(function() {
				return round($this->age_avg);
			})->sortable();

			$grid->aus('平均客单')->display(function() {
				return round($this->aus);
			})->sortable();

			$grid->column('门店招新指数/对比')->display(function() {
				return  "<h4><span class='badge bg-".indexCellColor(round($this->index_new_mean*100))."'>".round($this->index_new_mean*100)."</span></h4>";
			});

			$grid->column('品牌招新指数/对比')->display(function() {
				return  "<h4><span class='badge bg-".indexCellColor(round($this->index_repeat_mean*100))."'>".round($this->index_repeat_mean*100)."</span></h4>";
			});

			$grid->column('品牌复购指数/对比')->display(function() {
				return  "<h4><span class='badge bg-".indexCellColor(round($this->index_cross_mean*100))."'>".round($this->index_cross_mean*100)."</span></h4>";
			});

			$grid->tools(function ($tools) {
				$tools->batch(function ($batch) {
					$batch->disableDelete();
				});
			});

			$grid->disableActions();
			$grid->disableRowSelector();
			$grid->disableCreation();
			$grid->disableFilter();
			$grid->disableExport();
		});
	}


}
