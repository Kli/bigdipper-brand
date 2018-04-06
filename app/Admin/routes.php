<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');

    $router->get('/dashboard', 'DashboardController@index')->name('admin.dashboard');
    $router->get('/test', 'DashboardController@test');
    
    // 门店预警/生意预警
    $router->get('/store/store-warning', 'StoreWarningController@index')->name('admin.store-warning');
    $router->get('/store/cat-warning', 'CatWarningController@index')->name('admin.cat-warning');

    // 品类预警
    $router->get('/store/warning-cat/{category}', 'CatWarningController@warningCat')->name('admin.warning-cat');

    // 品牌预警
    $router->get('/store/brand-warning/{category?}/{brand?}', 'BrandWarningController@index')->name('admin.brand-warning');

    // 交易预警
    // 品牌交易预警
    $router->get('/store/member-brand-warning/{category}/{brand}', 'MemberWarningController@brand')->name('admin.member-brand-warning');
    // 品类交易预警
    $router->get('/store/member-cat-warning/{category}', 'MemberWarningController@cat')->name('admin.member-cat-warning');
    // 门店交易预警
    $router->get('/store/member-store-warning', 'MemberWarningController@store')->name('admin.member-store-warning');


    // 市场扫描
    // 品牌扫描
    $router->get('/store/brand-scanning/{catname_sc?}', 'ScanningController@brand')->name('admin.brand-scanning');
    // 品类扫描
    $router->get('/store/cat-scanning', 'ScanningController@cat')->name('admin.cat-scanning');
    // 门店扫描
    $router->get('/store/store-scanning', 'ScanningController@store')->name('admin.store-scanning');


    // 生意规划
    // 品类规划-门店品类规划
    $router->get('/store/cat-planning/{category?}', 'PlanningController@cat')->name('admin.cat-planning');
    // 品类规划-市场品类规划
    $router->get('/store/xtcat-planning/{category?}', 'PlanningController@xtCat')->name('admin.xingtucat-planning');
    // 品牌规划-门店品牌规划
    $router->get('/store/brand-planning/{category?}', 'PlanningController@brand')->name('admin.brand-planning');
    // 品牌规划-市场品牌规划
    $router->get('/store/xtbrand-planning', 'PlanningController@xtBrand')->name('admin.xingtubrand-planning');

    // 组货调改
    // 组货预警
    $router->get('/store/store-merchandising', 'MerchandisingController@store')->name('admin.store-merchandising');
    // 品类动能
    $router->get('/store/cat-merchandising', 'MerchandisingController@cat')->name('admin.cat-merchandising');
    // 品牌土壤
    $router->get('/store/brand-merchandising/{catname?}', 'MerchandisingController@brand')->name('admin.brand-merchandising');

    // 活动营销
    $router->get('/store/joint-marketing/{category?}/{brand?}', 'MarketingController@jointMarketing')->name('joint-marketing');
    $router->get('/store/xtjoint-marketing/{category?}/{brand?}', 'MarketingController@xtJointMarketing')->name('joint-marketing');

    // 生命周期
    // 价值预警
    $router->get('/store/value-warning', 'ValueWarningController@index')->name('admin.value-warning');


});
