<?php

namespace App\Admin\Controllers;

use App\Admin\Models\Rfm;

use App\Admin\Controllers\StoreBaseController;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;

use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Chart\Doughnut;

use DB, Request;

class ValueWarningController extends StoreBaseController
{
	public function index()
	{
		if (Admin::user()->isAdministrator() && Admin::user()->database=='') {
			return Admin::content(function (Content $content) {

				$content->header('生命周期');
				$content->description('价值预警');

				$content->row(function (Row $row) {

					$words = '请先配置您的数据库信息。';
					
					$row->column(12, function (Column $column) use ($words) {
						$column->append(new Callout($words));
					});
				});
			});
		}

		return Admin::content(function (Content $content) {

			$content->header('生命周期');
			$content->description('价值预警');
				
			$content->row(function (Row $row){

	        	$row->column(4, function (Column $column){
	        		$rfmCounts = Rfm::orderBy('grp', 'asc')->pluck('count');
	        		$doughnut = new Doughnut(
	        								['重点挽回','重点服务','重点培养','可能流失1','可能流失2','稳定1', '稳定2'],
	        								[[
	        									'data' => $rfmCounts,
	        									'backgroundColor' => [
	        										'#d2d6de',
	        										'#00a65a',
	        										'#3c8dbc',
	        										'#f39c12',
	        										'#f39c12',
	        										'#f56954',
	        										'#D81B60',
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

	        		$box = new Box('人群分布', $doughnut, 'users', '');
	        		$column->append($box);
	        	});


	        	$row->column(8, function (Column $column){
	        		$column->append($this->grid());
	        	});

			});
		});
	}


	protected function grid()
	{
		return Admin::grid(Rfm::class, function (Grid $grid) {
			$grid->model()->orderBy('grp', 'asc');

            $grid->grp('组号');
            $grid->column('priority', '沟通优先级')->display(function() {
            	return getPriorityStars($this->priority);
            });
            $grid->tag_r('人群描述');
            $grid->method('沟通方式');
            $grid->count('人数');
            $grid->tag_aus('活动内容');
            $grid->recommend_aus('建议活动档位/元');



            $grid->tools(function ($tools) {
                $tools->batch(function ($batch) {
                    $batch->disableDelete();
                });
            });

            $grid->disableActions();
            // $grid->disableRowSelector();
            $grid->disableCreation();
            $grid->disableFilter();
            // $grid->disableExport();
        });
	}
}