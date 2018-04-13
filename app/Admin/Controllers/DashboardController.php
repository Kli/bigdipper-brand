<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\StoreBaseController;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Alert;
use Encore\Admin\Widgets\Callout;
use Encore\Admin\Widgets\InfoBox;

use DB;

class DashboardController extends StoreBaseController
{
    

    public function index()
    {
        if (Admin::user()->isAdministrator() && Admin::user()->database=='') {
            return Admin::content(function (Content $content) {

                $content->header('仪表盘');
                $content->description('控制面板');

                $content->row(function (Row $row) {

                    $words = '请先配置您的数据库信息。';
                    
                    $row->column(12, function (Column $column) use ($words) {
                        $column->append(new Callout($words));
                    });
                });
            });
        }

        return Admin::content(function (Content $content) {

            /*
                0:  bbp-G,
                1:  bbp-R,
                2:  bbp-Y,
                3:  cbp-G,
                4:  cbp-R,
                5:  cbp-Y,
                6:  ninecell-G,
                7:  ninecell-R,
                8:  ninecell-Y,
             */
            $dashboardObjs = DB::connection('store_db')
                                ->table('dashboard')
                                ->select('val','tag','flag')
                                ->whereIn('flag', ['G','R','Y'])
                                ->get();

            foreach ($dashboardObjs as $dashboardObj) {
                $datas[$dashboardObj->tag][$dashboardObj->flag] = $dashboardObj->val;
            }

            $content->header('仪表盘');
            $content->description('控制面板');

            $content->row(function (Row $row) use($datas) {

                $row->column(4, function (Column $column) use($datas) {
                    $box_ninecell ='
<div class="box box-widget widget-user-1">
    <div class="small-box bg-teal" style="margin-bottom:0; padding-bottom:5px;">
        <div class="inner">
            <h3>门店预警</h3>
            <p><br /></p>
        </div>
        <div class="icon">
            <i class="fa fa-shopping-bag"></i>
        </div>
    </div>
    <div class="box-footer no-padding">
        <ul class="nav nav-stacked">
            <li><a href="#">重点关注 <span class="pull-right badge bg-red">'. @$datas['ninecell']['R'] .'</span></a></li>
            <li><a href="#">保持警惕 <span class="pull-right badge bg-yellow">'. @$datas['ninecell']['Y'] .'</span></a></li>
            <li><a href="#">状态良好 <span class="pull-right badge bg-green">'. @$datas['ninecell']['G'] .'</span></a></li>
        </ul>
    </div>
</div>';
                    $column->append($box_ninecell);
                });

                $row->column(4, function (Column $column) use($datas) {
                    $box_cbp ='
<div class="box box-widget widget-user-1">
    <div class="small-box bg-orange" style="margin-bottom:0; padding-bottom:5px;">
        <div class="inner">
            <h3>商品预警</h3>
            <p><br /></p>
        </div>
        <div class="icon">
            <i class="fa fa-tags"></i>
        </div>
    </div>
    <div class="box-footer no-padding">
        <ul class="nav nav-stacked">
            <li><a href="#">潜在问题品类 <span class="pull-right badge bg-red">'. @$datas['cbp']['R'] .'</span></a></li>
            <li><a href="#">状态稳定品类 <span class="pull-right badge bg-yellow">'. @$datas['cbp']['Y'] .'</span></a></li>
            <li><a href="#">预测良好品类 <span class="pull-right badge bg-green">'. @$datas['cbp']['G'] .'</span></a></li>
        </ul>
    </div>
</div>';
                    $column->append($box_cbp);
                });

                $row->column(4, function (Column $column) use($datas) {
                    $box_bbp ='
<div class="box box-widget widget-user-1">
    <div class="small-box bg-purple" style="margin-bottom:0; padding-bottom:5px;">
        <div class="inner">
            <h3>人群预警</h3>
            <p><br /></p>
        </div>
        <div class="icon">
            <i class="fa fa-users"></i>
        </div>
    </div>
    <div class="box-footer no-padding">
        <ul class="nav nav-stacked">
            <li><a href="#">销量下降品牌 <span class="pull-right badge bg-red">'. @$datas['bbp']['R'] .'</span></a></li>
            <li><a href="#">销量稳定品牌 <span class="pull-right badge bg-yellow">'. @$datas['bbp']['Y'] .'</span></a></li>
            <li><a href="#">销量上涨品牌 <span class="pull-right badge bg-green">'. @$datas['bbp']['G'] .'</span></a></li>
        </ul>
    </div>
</div>';
                    $column->append($box_bbp);
                });
            });
        });
    }
}
