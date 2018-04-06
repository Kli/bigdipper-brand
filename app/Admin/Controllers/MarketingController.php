<?php

namespace App\Admin\Controllers;

use App\Admin\Models\Cbp;
use App\Admin\Models\Mba;
use App\Admin\Models\MbaMonth;
use App\Admin\Models\MbaIndex;
use App\Admin\Models\Rfm;
use App\Admin\Models\BaseMba;
use App\Admin\Models\BaseMbaIndex;

use App\Admin\Controllers\StoreBaseController;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;

use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Tab;

use DB, Request;

class MarketingController extends StoreBaseController
{
	public function index()
	{
		if (Admin::user()->isAdministrator() && Admin::user()->database=='') {
			return Admin::content(function (Content $content) {

				$content->header('活动营销');
				$content->description('联合营销');

				$content->row(function (Row $row) {

					$words = '请先配置您的数据库信息。';
					
					$row->column(12, function (Column $column) use ($words) {
						$column->append(new Callout($words));
					});
				});
			});
		}

	}

	public function jointMarketing($category='', $brand='')
	{
		return Admin::content(function (Content $content) use($category, $brand) {

			$content->header('活动营销');
			$content->description('联合营销');

				
			$content->row(function (Row $row) use($category, $brand) {
				
				// 门店品类规划 & 市场品类规划
				$selectHtml0 = '
					<select id="marketingType" class="form-control" name="type">
						<option value="joint-marketing" selected>门店品类规划</option>
						<option value="xtjoint-marketing">市场品类参考</option>
					</select>';
				$selectHtml0 .= '
					<script>
						$("#marketingType").change(function(){
							window.location.href="/admin/store/" + $(this).val();
						})
					</script>
				';
				$row->column(3, function (Column $column) use ($selectHtml0) {
					$column->append($selectHtml0);
				});

				// 品类
				$cbpCategories = Cbp::select('category')
										->distinct()
										->orderBy('category', 'asc')
										->get();
				
				$selectHtml1 = '
					<select id="marketingCat" class="form-control select2" name="marketingCat"><option value="">选择一个品类</option>';
				
				$selectHtml1 .= '<option value="all"' . ($category=='all'?' selected':'') . '>全部品类</option>';

				if ($category != '') {
					foreach ($cbpCategories as $cbpCategory) {
						$selected = ($category==$cbpCategory->category)?' selected':'';
						$selectHtml1 .= '<option value="' . $cbpCategory->category . '" ' . $selected . '>' . $cbpCategory->category . '</option>';
					}
				} else {
					foreach ($cbpCategories as $cbpCategory) {
						$selectHtml1 .= '<option value="' . $cbpCategory->category . '">' . $cbpCategory->category . '</option>';
					}
				}
				$selectHtml1 .= '</select>';
				$selectHtml1 .= '
					<script>
						$("#marketingCat").change(function(){
							window.location.href="/admin/store/joint-marketing/" + $(this).val();
						})
						$(document).ready(function() {
						    $(".select2").select2();
						});
					</script>
				';
				
				$row->column(4, function (Column $column) use ($selectHtml1) {
					$column->append($selectHtml1);
				});

				if ($category != '') {
					// 品牌
					
					// $sql = "SELECT brand, cat, count(*) from mba_index ".$where." group by brand, cat order by convert(brand using gbk)";

					if ($category == 'all') {
						$mbaIndexes = MbaIndex::select('brand', 'cat')
												->groupBy('brand', 'cat')
												->get();
					} else {
						$mbaIndexes = MbaIndex::where('cat', '=', $category)
												->select('brand', 'cat')
												->groupBy('brand', 'cat')
												->get();
					}
					
					$selectHtml2 = '
						<select id="marketingBrand" class="form-control select2" name="marketingBrand"><option value="">选择一个品类</option>';
					if ($brand != '') {
						if($category == 'all'){
							foreach ($mbaIndexes as $mbaIndex) {
								$selected = ( urldecode($brand)==($mbaIndex->brand . ' - ' . $mbaIndex->cat) )?' selected':'';
								$selectHtml2 .= '<option value="' . urlencode($mbaIndex->brand . ' - ' . $mbaIndex->cat) . '" ' . $selected . '>' . $mbaIndex->brand . ' - ' . $mbaIndex->cat . '</option>';
							}
						} else {
							foreach ($mbaIndexes as $mbaIndex) {
								$selected = ( urldecode($brand)==$mbaIndex->brand )?' selected':'';
								$selectHtml2 .= '<option value="' . urlencode($mbaIndex->brand) . '" ' . $selected . '>' . $mbaIndex->brand . '</option>';
							}
						}
					} else {
						if ($category == 'all') {
							foreach ($mbaIndexes as $mbaIndex) {
								$selectHtml2 .= '<option value="' . urlencode($mbaIndex->brand . ' - ' . $mbaIndex->cat) . '">' . $mbaIndex->brand . ' - ' . $mbaIndex->cat . '</option>';
							}
						} else {
							foreach ($mbaIndexes as $mbaIndex) {
								$selectHtml2 .= '<option value="' . urlencode($mbaIndex->brand) . '">' . $mbaIndex->brand . '</option>';
							}
						}
					}
					$selectHtml2 .= '</select>';
					$selectHtml2 .= '
						<script>
							$("#marketingBrand").change(function(){
								window.location.href="/admin/store/joint-marketing/' . $category . '/" + $(this).val();
							})
						</script>
					';
					
					$row->column(5, function (Column $column) use ($selectHtml2) {
						$column->append($selectHtml2);
					});
				}
			});

			if ($category != '' && $brand != '') {
				$content->row(function (Row $row) {
					$row->column(12, function (Column $column) {
						$column->append('<br>');
					});
				});
				$content->row(function (Row $row) use($category, $brand) {
					
					$row->column(12, function (Column $column) use ($category, $brand) {
                        $tab = new Tab();

                        // 新晋关联排名
                        $tab->add('新晋关联排名', $this->gridLatest($category, $brand));

                        // 稳定关联排名
                        $tab->add('稳定关联排名', $this->gridStable($category, $brand),true);
                        
                        $tab->title('关联规则');
                        $tab->icon('users');

                        $column->append($tab);
                    });
				});
			}


			
		});
	}

	public function xtJointMarketing($category='', $brand='')
	{
		return Admin::content(function (Content $content) use($category, $brand) {

			$content->header('活动营销');
			$content->description('品牌关联');

				
			$content->row(function (Row $row) use($category, $brand) {
				
				// 门店品类规划 & 市场品类规划
				$selectHtml0 = '
					<select id="marketingType" class="form-control" name="type">
						<option value="joint-marketing">门店品类规划</option>
						<option value="xtjoint-marketing" selected>市场品类参考</option>
					</select>';
				$selectHtml0 .= '
					<script>
						$("#marketingType").change(function(){
							window.location.href="/admin/store/" + $(this).val();
						})
					</script>
				';
				$row->column(3, function (Column $column) use ($selectHtml0) {
					$column->append($selectHtml0);
				});

				// 品类
				$mbaCategories = BaseMbaIndex::select('cat')
										->distinct()
										->orderBy('cat', 'asc')
										->get();
				
				$selectHtml1 = '
					<select id="marketingCat" class="form-control select2" name="marketingCat"><option value="">选择一个品类</option>';
				
				$selectHtml1 .= '<option value="all"' . ($category=='all'?' selected':'') . '>全部品类</option>';

				if ($category != '') {
					foreach ($mbaCategories as $mbaCategory) {
						$selected = ($category==$mbaCategory->cat)?' selected':'';
						$selectHtml1 .= '<option value="' . $mbaCategory->cat . '" ' . $selected . '>' . $mbaCategory->cat . '</option>';
					}
				} else {
					foreach ($mbaCategories as $mbaCategory) {
						$selectHtml1 .= '<option value="' . $mbaCategory->cat . '">' . $mbaCategory->cat . '</option>';
					}
				}
				$selectHtml1 .= '</select>';
				$selectHtml1 .= '
					<script>
						$("#marketingCat").change(function(){
							window.location.href="/admin/store/xtjoint-marketing/" + $(this).val();
						})
						$(document).ready(function() {
						    $(".select2").select2();
						});
					</script>
				';
				
				$row->column(4, function (Column $column) use ($selectHtml1) {
					$column->append($selectHtml1);
				});

				if ($category != '') {
					// 品牌
					
					// $sql = "SELECT brand, cat, count(*) from mba_index ".$where." group by brand, cat order by convert(brand using gbk)";

					if ($category == 'all') {
						$mbaIndexes = BaseMbaIndex::select('brand', 'cat')
												->groupBy('brand', 'cat')
												->get();
					} else {
						$mbaIndexes = BaseMbaIndex::where('cat', '=', $category)
												->select('brand', 'cat')
												->groupBy('brand', 'cat')
												->get();
					}
					
					$selectHtml2 = '
						<select id="marketingBrand" class="form-control select2" name="marketingBrand"><option value="">选择一个品类</option>';
					if ($brand != '') {
						if($category == 'all'){
							foreach ($mbaIndexes as $mbaIndex) {
								$selected = ( urldecode($brand)==($mbaIndex->brand . ' - ' . $mbaIndex->cat) )?' selected':'';
								$selectHtml2 .= '<option value="' . urlencode($mbaIndex->brand . ' - ' . $mbaIndex->cat) . '" ' . $selected . '>' . $mbaIndex->brand . ' - ' . $mbaIndex->cat . '</option>';
							}
						} else {
							foreach ($mbaIndexes as $mbaIndex) {
								$selected = ( urldecode($brand)==$mbaIndex->brand )?' selected':'';
								$selectHtml2 .= '<option value="' . urlencode($mbaIndex->brand) . '" ' . $selected . '>' . $mbaIndex->brand . '</option>';
							}
						}
					} else {
						if ($category == 'all') {
							foreach ($mbaIndexes as $mbaIndex) {
								$selectHtml2 .= '<option value="' . urlencode($mbaIndex->brand . ' - ' . $mbaIndex->cat) . '">' . $mbaIndex->brand . ' - ' . $mbaIndex->cat . '</option>';
							}
						} else {
							foreach ($mbaIndexes as $mbaIndex) {
								$selectHtml2 .= '<option value="' . urlencode($mbaIndex->brand) . '">' . $mbaIndex->brand . '</option>';
							}
						}
					}
					$selectHtml2 .= '</select>';
					$selectHtml2 .= '
						<script>
							$("#marketingBrand").change(function(){
								window.location.href="/admin/store/xtjoint-marketing/' . $category . '/" + $(this).val();
							})
						</script>
					';
					
					$row->column(5, function (Column $column) use ($selectHtml2) {
						$column->append($selectHtml2);
					});
				}
			});

			if ($category != '' && $brand != '') {
				$content->row(function (Row $row) {
					$row->column(12, function (Column $column) {
						$column->append('<br>');
					});
				});
				$content->row(function (Row $row) use($category, $brand) {
					
					$row->column(12, function (Column $column) use ($category, $brand) {
                        $tab = new Tab();

                        // 稳定关联排名
                        $tab->add('市场关联排名', $this->gridMarket($category, $brand),true);
                        
                        $tab->title('关联规则');
                        $tab->icon('users');

                        $column->append($tab);
                    });
				});
			}


			
		});
	}

	protected function gridStable($category, $brand)
	{
		return Admin::grid(Mba::class, function (Grid $grid) use ($category, $brand) {

			if ($category == 'all') {
				$params = explode(" - ", urldecode($brand));

				$brand_left = trim($params[0]);
				$catname_left = trim($params[1]);
			} else {
				$brand_left = urldecode($brand);
				$catname_left = $category;
			}

			$grid->model()->where('brand_left', '=', $brand_left)->where('catname_left', '=', $catname_left);
			
            $grid->type('类型')->sortable();
            $grid->brand_right('品牌')->sortable();
            $grid->catname_right('品类')->sortable();
            $grid->rightfloor('楼层')->sortable();
            $grid->count('活跃会员人数')->sortable();

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

	protected function gridLatest($category, $brand)
	{
		return Admin::grid(MbaMonth::class, function (Grid $grid) use ($category, $brand) {

			if ($category == 'all') {
				$params = explode(" - ", urldecode($brand));

				$brand_left = trim($params[0]);
				$catname_left = trim($params[1]);
			} else {
				$brand_left = urldecode($brand);
				$catname_left = $category;
			}

			$grid->model()->where('brand_left', '=', $brand_left)->where('catname_left', '=', $catname_left);
			
            $grid->type('类型')->sortable();
            $grid->brand_right('品牌')->sortable();
            $grid->catname_right('品类')->sortable();
            $grid->rightfloor('楼层')->sortable();
            $grid->count('活跃会员人数')->sortable();

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

	protected function gridMarket($category, $brand)
	{
		return Admin::grid(BaseMba::class, function (Grid $grid) use ($category, $brand) {

			if ($category == 'all') {
				$params = explode(" - ", urldecode($brand));

				$brand_left = trim($params[0]);
				$catname_left = trim($params[1]);
			} else {
				$brand_left = urldecode($brand);
				$catname_left = $category;
			}

			$grid->model()->where('brand_left', '=', $brand_left)->where('catname_left', '=', $catname_left);
			
            $grid->type('类型')->sortable();
            $grid->brand_right('品牌')->sortable();
            $grid->catname_right('品类')->sortable();

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