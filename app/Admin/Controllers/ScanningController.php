<?php

namespace App\Admin\Controllers;

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

class ScanningController extends Controller
{
	public function index()
	{
	}

	public function store()
	{
		return Admin::content(function (Content $content) {

			$content->header('市场扫描');
			$content->description('门店扫描');


			$similarStores = DB::connection('mysql_store_base')
								->table('similarstore')
								->where('store', '=', Admin::user()->database)
								->pluck('similar')->toArray();
			
			$storeIndex = DB::connection('mysql_store_base')
								->table('storeindex')
								->where('pingxiao', '>', 0)
								->select(DB::raw('
										MAX(pcnt_sales) as max_ps,
										MIN(pcnt_sales) as min_ps,
										MAX(pingxiao) as max_pingxiao,
										MIN(pingxiao) as min_pingxiao
									'))
								->first();

			$tableData['坪效']['max']=round($storeIndex->max_pingxiao/1000000);
			$tableData['坪效']['min']=round($storeIndex->min_pingxiao/1000000);
			$tableData['会员占比']['max']=round($storeIndex->max_ps*100);
			$tableData['会员占比']['min']=round($storeIndex->min_ps*100);
			$tableData['门店招新-数量指标']['max']=100;
			$tableData['门店招新-数量指标']['min']=0;
			$tableData['门店招新-质量指标']['max']=100;
			$tableData['门店招新-质量指标']['min']=0;
			$tableData['品类招新-数量指标']['max']=100;
			$tableData['品类招新-数量指标']['min']=0;
			$tableData['品类招新-质量指标']['max']=100;
			$tableData['品类招新-质量指标']['min']=0;
			$tableData['品类复购-数量指标']['max']=100;
			$tableData['品类复购-数量指标']['min']=0;
			$tableData['品类复购-质量指标']['max']=100;
			$tableData['品类复购-质量指标']['min']=0;

			$myStore = DB::connection('mysql_store_base')
								->table('storeindex')
								->where('store', '=', Admin::user()->database)
								->first();

			$radarMy['px']=round(($myStore->pingxiao-$storeIndex->min_pingxiao)/($storeIndex->max_pingxiao-$storeIndex->min_pingxiao)*100);
			$radarMy['pcnt']=round(($myStore->pcnt_sales-$storeIndex->min_ps)/($storeIndex->max_ps-$storeIndex->min_ps)*100);
			$radarMy['new']=round($myStore->index_new*100);
			$radarMy['cross']=round($myStore->index_cross*100);
			$radarMy['repeat']=round($myStore->index_repeat*100);

			$tableData['坪效']['store']=round($myStore->pingxiao/1000000);
			$tableData['会员占比']['store']=round($myStore->pcnt_sales*100);
			$tableData['门店招新-数量指标']['store']=round($myStore->index_new_rate*100);
			$tableData['门店招新-质量指标']['store']=round($myStore->index_new_f*100);
			$tableData['品类招新-数量指标']['store']=round($myStore->index_cross_rate*100);
			$tableData['品类招新-质量指标']['store']=round($myStore->index_cross_f*100);
			$tableData['品类复购-数量指标']['store']=round($myStore->index_repeat_rate*100);
			$tableData['品类复购-质量指标']['store']=round($myStore->index_repeat_f*100);

			$similarStoreIndex = DB::connection('mysql_store_base')
								->table('storeindex')
								->whereIn('store', $similarStores)
								->select(DB::raw('
										AVG(index_new) AS avg_new,
										AVG(index_new_f) AS avg_new_f,
										AVG(index_new_rate) AS avg_new_rate,
										AVG(index_cross) AS avg_cross,
										AVG(index_cross_f) AS avg_cross_f,
										AVG(index_cross_rate) AS avg_cross_rate,
										AVG(index_repeat) AS avg_repeat,
										AVG(index_repeat_f) AS avg_repeat_f,
										AVG(index_repeat_rate) AS avg_repeat_rate,
										AVG(pcnt_sales) AS avg_pcnt_sales,
										AVG(pingxiao) AS avg_pingxiao
									'))
								->first();

			$radarSimilar['px']=round(($similarStoreIndex->avg_pingxiao-$storeIndex->min_pingxiao)/($storeIndex->max_pingxiao-$storeIndex->min_pingxiao)*100);
			$radarSimilar['pcnt']=round(($similarStoreIndex->avg_pcnt_sales-$storeIndex->min_ps)/($storeIndex->max_ps-$storeIndex->min_ps)*100);
			$radarSimilar['new']=round($similarStoreIndex->avg_new*100);
			$radarSimilar['cross']=round($similarStoreIndex->avg_cross*100);
			$radarSimilar['repeat']=round($similarStoreIndex->avg_repeat*100);

			$tableData['坪效']['savg']=round($similarStoreIndex->avg_pingxiao/1000000);
			$tableData['会员占比']['savg']=round($similarStoreIndex->avg_pcnt_sales*100);
			$tableData['门店招新-数量指标']['savg']=round($similarStoreIndex->avg_new_rate*100);
			$tableData['门店招新-质量指标']['savg']=round($similarStoreIndex->avg_new_f*100);
			$tableData['品类招新-数量指标']['savg']=round($similarStoreIndex->avg_cross_rate*100);
			$tableData['品类招新-质量指标']['savg']=round($similarStoreIndex->avg_cross_f*100);
			$tableData['品类复购-数量指标']['savg']=round($similarStoreIndex->avg_repeat_rate*100);
			$tableData['品类复购-质量指标']['savg']=round($similarStoreIndex->avg_repeat_f*100);

			$new = (($radarMy['new']/$radarSimilar['new']-1)*100<-10)?1:0;
		    $repeat = (($radarMy['repeat']/$radarSimilar['repeat']-1)*100<-10)?1:0;
		    $cross = (($radarMy['cross']/$radarSimilar['cross']-1)*100<-10)?1:0;

		    if ($new+$repeat+$cross>0) {
		    	$content->row(function (Row $row) use($new, $repeat, $cross) {

					$row->column(12, function (Column $column) use($new, $repeat, $cross) {


						$title = '门店竞争力预警';
						$style = 'warning';
						$icon = 'info';

						$content = '<ul>';
						$content .= ($new > 0)?'<li>门店在顾客的吸引力上存在较大的提升空间</li>':'';
						$content .= ($repeat > 0)?'<li>门店的运营能力较市场相比较弱</li>':'';
						$content .= ($cross > 0)?'<li>门店的组货存在潜在问题，与市场相比较弱</li>':'';
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

			$content->header('市场扫描');
			$content->description('品类扫描');
			
			$content->body($this->gridCat());
		});
	}

	public function brand($catname_sc='')
	{
		if (Admin::user()->isAdministrator() && Admin::user()->database=='') {
			return Admin::content(function (Content $content) {

				$content->header('市场扫描');
				$content->description('品牌扫描');

				$content->row(function (Row $row) {

					$words = '请先配置您的数据库信息。';
					
					$row->column(12, function (Column $column) use ($words) {
						$column->append(new Callout($words));
					});
				});
			});
		}

		return Admin::content(function (Content $content) use($catname_sc) {

			$content->header('市场扫描');
			$content->description('品牌扫描');

				
			$content->row(function (Row $row) use($catname_sc) {
				
				$catnameScs = BrandIndex::select('catname_sc')
										->where('store', '=', Admin::user()->database)
										->distinct()
										->orderBy('catname_sc', 'asc')
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
							window.location.href="/admin/store/brand-scanning/" + $(this).val();
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
						$tab = new Tab();



						$tab->add('我的门店表现', $this->gridMyStore($catname_sc),true);
						$tab->add('市场平均表现', $this->gridAvg($catname_sc));

						
						$tab->title('品牌市场扫描');
						$tab->icon('users');

						$column->append($tab);
					});
					
				}
			});
		});
	}


	protected function gridCat()
	{
		return Admin::grid(CatIndex::class, function (Grid $grid) {
			$grid->model()->where('store', '=', Admin::user()->database);
			$grid->model()->orderBy('rnk', 'asc');

			$grid->rnk('销售排名')->sortable();
			$grid->catname_sc('品类')->sortable();
			
			$grid->column('品牌均销售指数/对比')->display(function() {
				$salesRate = round($this->sales_sqrt/$this->index_sales*100-100);
				$positive=($salesRate>0)?"+":"";

				return "<h4><b>".number_format(round($this->sales_sqrt))."</b> <span class='badge bg-".cellStoreColor($salesRate)."'>".$positive.round($salesRate)."%</span></h4>";

			});

			$grid->column('门店招新指数/对比')->display(function() {
				$positive=(round($this->index_new/$this->index_new_mean*100-100)>0)?"+":"";
				$index_new_evol=round($this->index_new/$this->index_new_mean*100-100);
				$progress = ($this->index_new_max!=$this->index_new_min)?
								round(($this->index_new-$this->index_new_min)/($this->index_new_max-$this->index_new_min)*100) : '';

				return  "<h4><b>".round($this->index_new*100)."</b> <span class='badge bg-".cellStoreColor(round($index_new_evol))."'>".$positive.round($index_new_evol)."%</span></h4><div class=\"progress progress-xs\">
								<div class=\"progress-bar progress-bar-".progressColor($progress)."\" style=\"width:".$progress."%\" data-toggle=\"tooltip\" title=\"".$progress."%\"></div>
							  </div>";
			});

			$grid->column('品类招新指数/对比')->display(function() {

				$positive=(round($this->index_cross/$this->index_cross_mean*100-100)>0)?"+":"";
				$index_cross_evol=round($this->index_cross/$this->index_cross_mean*100-100);
				$progress = ($this->index_cross_max!=$this->index_cross_min)?
								round(($this->index_cross-$this->index_cross_min)/($this->index_new_max-$this->index_cross_min)*100) : '';

				return  "<h4><b>".round($this->index_cross*100)."</b> <span class='badge bg-".cellStoreColor(round($index_cross_evol))."'>".$positive.round($index_cross_evol)."%</span></h4><div class=\"progress progress-xs\">
								<div class=\"progress-bar progress-bar-".progressColor($progress)."\" style=\"width:".$progress."%\" data-toggle=\"tooltip\" title=\"".$progress."%\"></div>
							  </div>";
			});

			$grid->column('品类复购指数/对比')->display(function() {

				$positive=(round($this->index_repeat/$this->index_repeat_mean*100-100)>0)?"+":"";
				$index_repeat_evol=round($this->index_repeat/$this->index_repeat_mean*100-100);
				$progress = ($this->index_cross_max!=$this->index_repeat_min)?
								round(($this->index_repeat-$this->index_repeat_min)/($this->index_new_max-$this->index_repeat_min)*100) : '';

				return  "<h4><b>".round($this->index_repeat*100)."</b> <span class='badge bg-".cellStoreColor(round($index_repeat_evol))."'>".$positive.round($index_repeat_evol)."%</span></h4><div class=\"progress progress-xs\">
								<div class=\"progress-bar progress-bar-".progressColor($progress)."\" style=\"width:".$progress."%\" data-toggle=\"tooltip\" title=\"".$progress."%\"></div>
							  </div>";
			});



			$grid->actions(function ($actions) {
				$actions->disableEdit();
				$actions->disableDelete();
				$actions->append('<a class="btn btn-block btn-primary" href="/admin/store/brand-scanning/' . $this->row->catname_sc . '">查看品牌</a>');
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
