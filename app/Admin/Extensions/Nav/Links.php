<?php
namespace App\Admin\Extensions\Nav;

use Encore\Admin\Facades\Admin;
use DB;

class Links
{
    // DB::table()
    
	public function __toString()
	{
        $navHtml = '';
        
        if (Admin::user()->inRoles(['store_admin'])) {
            if ( empty(config('database.connections.store')) ) {
                $store_conn = [
                                'driver' => 'mysql',
                                'host' => env('DB_STORE_HOST', '127.0.0.1'),
                                'port' => env('DB_STORE_PORT', '3306'),
                                'database' => Admin::user()->database,
                                'username' => env('DB_STORE_USERNAME', 'forge'),
                                'password' => env('DB_STORE_PASSWORD', ''),
                                'unix_socket' => env('DB_STORE_SOCKET', ''),
                                'charset' => 'utf8mb4',
                                'collation' => 'utf8mb4_unicode_ci',
                                'prefix' => '',
                                'strict' => false,
                                'engine' => null,
                            ];
                
                config(['database.connections.store_db' => $store_conn]);
            }

            $notifications = DB::connection('store_db')->table('dashboard')
                                ->select('val')
                                ->whereIn('flag', ['G','R','Y'])
                                ->orderBy(DB::raw("concat(`tag`,`flag`)"))
                                ->groupBy('tag','flag')
                                ->get()->toArray();

            /*$navHtml = '
<li class="dropdown notifications-menu">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
        <i class="fa fa-shopping-cart"></i>
        <span class="label label-danger">' . $notifications[7]->val . '</span>
    </a>
    <ul class="dropdown-menu">
        <li class="header">门店预警</li>
        <li>
            <ul class="menu">
                <li>
                    <a href="/admin/store/store-warning">
                        <i class="fa fa fa-square text-red"></i> ' . $notifications[7]->val . ' 项重点关注
                    </a>
                </li>
                <li>
                    <a href="/admin/store/store-warning">
                        <i class="fa fa-square text-yellow"></i> ' . $notifications[8]->val . ' 项保持警惕
                    </a>
                </li>
                <li>
                    <a href="/admin/store/store-warning">
                        <i class="fa fa-square text-green"></i> ' . $notifications[6]->val . ' 项状态良好
                    </a>
                </li>
            </ul>
        </li>
        <li class="footer"><a href="/admin/store/store-warning">查看全部</a></li>
    </ul>
</li>
<li class="dropdown notifications-menu">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
        <i class="fa fa-shopping-basket"></i>
        <span class="label label-danger" >'. $notifications[4]->val . '</span>
    </a>
    <ul class="dropdown-menu">
        <li class="header">品类预警</li>
        <li>
            <ul class="menu">
                <li>
                    <a href="/admin/store/cat-warning">
                        <i class="fa fa-square text-red"></i> ' . $notifications[4]->val . ' 个品类存在潜在问题
                    </a>
                </li>
                <li>
                    <a href="/admin/store/cat-warning">
                        <i class="fa fa-square text-yellow"></i> ' . $notifications[5]->val . ' 个品类状态稳定
                    </a>
                </li>
                <li>
                    <a href="/admin/store/cat-warning">
                        <i class="fa fa-square text-green"></i> ' . $notifications[3]->val . ' 个品类表现良好
                    </a>
                </li>
            </ul>
        </li>
        <li class="footer"><a href="/admin/store/cat-warning">查看全部</a></li>
    </ul>
</li>
<li class="dropdown notifications-menu">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
        <i class="fa fa-tags"></i>
        <span class="label label-danger" >'. $notifications[1]->val . '</span>
    </a>
    <ul class="dropdown-menu">
        <li class="header">品牌预警</li>
        <li>
            <ul class="menu">
                <li>
                    <a href="/admin/store/brand-warning">
                        <i class="fa fa-square text-red"></i> ' . $notifications[1]->val . ' 个重点品牌预测销量下跌
                    </a>
                </li>
                <li>
                    <a href="/admin/store/brand-warning">
                        <i class="fa fa-square text-yellow"></i> ' . $notifications[2]->val . ' 个重点品牌预测销量稳定
                    </a>
                </li>
                <li>
                    <a href="/admin/store/brand-warning">
                        <i class="fa fa-square text-green"></i> ' . $notifications[0]->val . ' 个重点品牌预测销量上涨
                    </a>
                </li>
            </ul>
        </li>
        <li class="footer"><a href="/admin/store/brand-warning">查看全部</a></li>
    </ul>
</li>';*/
$navHtml = '';
        }

        return $navHtml;
	}

}