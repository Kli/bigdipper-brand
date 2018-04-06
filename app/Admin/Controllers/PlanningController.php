<?php

namespace App\Admin\Controllers;

use App\Admin\Models\Bbp;
use App\Admin\Models\Cbp;
use App\Admin\Models\BaseCbp;
use App\Admin\Models\CbpEvol;

use App\Admin\Models\BrandIndex;
use App\Admin\Models\CatIndex;

use App\Http\Controllers\Controller;
use Encore\Admin\Grid;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;

use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Tab;
use Encore\Admin\Widgets\Table;
use Encore\Admin\Widgets\Chart\Scatter;

use DB;

class PlanningController extends Controller
{


	public function cat($category = '')
	{
		return Admin::content(function (Content $content) use($category) {

			$content->header('生意规划');
			$content->description('品类规划');

				
			$content->row(function (Row $row) use($category) {
				
				// 门店品类规划 & 市场品类规划
				$selectHtml0 = '
					<select id="planningType" class="form-control" name="type">
						<option value="cat-planning" selected>门店品类规划</option>
						<option value="xtcat-planning">市场品类参考</option>
					</select>';
				$selectHtml0 .= '
					<script>
						$("#planningType").change(function(){
							window.location.href="/admin/store/" + $(this).val();
						})
					</script>
				';
				$row->column(4, function (Column $column) use ($selectHtml0) {
					$column->append($selectHtml0);
				});

				// 品类
				$cbpCategories = Cbp::select('category')
										->distinct()
										->orderBy('category', 'asc')
										->get();
				
				$selectHtml = '
					<select id="catname" class="form-control select2" name="catname"><option value="">选择一个品类</option>';
				if ($category != '') {
					foreach ($cbpCategories as $cbpCategory) {
						$selected = ($category==$cbpCategory->category)?' selected':'';
						$selectHtml .= '<option value="' . $cbpCategory->category . '" ' . $selected . '>' . $cbpCategory->category . '</option>';
					}
				} else {
					foreach ($cbpCategories as $cbpCategory) {
						$selectHtml .= '<option value="' . $cbpCategory->category . '">' . $cbpCategory->category . '</option>';
					}
				}
				$selectHtml .= '</select>';
				$selectHtml .= '
					<script>
						$("#catname").change(function(){
							console.log($(this).val());
							window.location.href="/admin/store/cat-planning/" + $(this).val();
						});
						$(document).ready(function() {
						    $(".select2").select2();
						});
					</script>
				';
				
				$row->column(8, function (Column $column) use ($selectHtml) {
					$column->append($selectHtml);
				});
			});

			if ($category != '') {
				$content->row(function (Row $row) use($category) {
					$row->column(12, function (Column $column) {
						$column->append('<br>');
					});
				});
				$content->row(function (Row $row) use($category) {
					$updateTime = getLastUpdateTime(Admin::user()->database, 'cbp');
					$updateTimeYear = date('Y', strtotime($updateTime));
					$updateTimeMonth = date('m', strtotime($updateTime));
					// 去年数据
					$data0 = Cbp::getPlanningData($category, $updateTimeYear-1, $updateTimeMonth);
					$data1 = Cbp::getPlanningData($category, $updateTimeYear-1, 12);
					
					// 今年数据
					$data2 = Cbp::getPlanningData($category, $updateTimeYear, $updateTimeMonth, $data0['originCbps']);
					$data3 = Cbp::getPlanningData($category, $updateTimeYear, 12);
					
					$row->column(6, function (Column $column) use ($data0, $data1, $updateTimeYear, $updateTimeMonth) {
						// 去年同月
						
						$header0 = ['类型','会员人数','平均购买<br />品牌数','平均品牌<br/>花费','销售'];
						$table0 = new Table($header0, $data0['cbps']);
						$box0 = new Box( ($updateTimeYear-1).'年'.($updateTimeMonth).'月累计（会员占比：<span class="text-aqua">'.$data0['pcnt'].'%</span>）', $table0, 'line-chart');

						$column->append($box0->style('info'));

						// 去年12月
						
						$header1 = ['类型','会员人数','平均购买<br />品牌数','平均品牌<br/>花费','销售'];
						$table1 = new Table($header1, $data1['cbps']);
						$box1 = new Box( ($updateTimeYear-1).'年12月累计（会员占比：<span class="text-aqua">'.$data1['pcnt'].'%</span>）', $table1, 'line-chart');

						$column->append($box1->style('info'));
						
					});

					// 今年数据
					$row->column(6, function (Column $column) use ($data1, $data2, $data3, $updateTimeYear, $updateTimeMonth) {
						// 今年同月
						
						$header2 = ['类型','会员人数','平均购买<br />品牌数','平均品牌<br/>花费','销售'];
						$table2 = new Table($header2, $data2['cbps']);
						$box2 = new Box( ($updateTimeYear).'年'.($updateTimeMonth).'月累计（会员占比：<span class="text-aqua">'.$data2['pcnt'].'%</span>）', $table2, 'line-chart');

						$column->append($box2->style('danger'));

						// 今年12月
						
						$storeRate = CbpEvol::getStoreRate();
						foreach ($data3['cbps'] as $key => &$data) {

							$cd_ytdtcnum = str_replace(',', '', $data1['cbps'][$key]['ytdtotalcustomernum']);
							$cd_avgcb = str_replace(',', '', $data1['cbps'][$key]['avgcustomerbrand']);
							$cd_avgbs = str_replace(',', '', $data1['cbps'][$key]['avgbrandsales']);
							$cd_sales = str_replace(',', '', $data1['cbps'][$key]['sales']);


							$crate = round((round($data['ytdtotalcustomernum'])-$cd_ytdtcnum)/$cd_ytdtcnum*100);
							$brate = round((round($data['avgcustomerbrand'],2)-$cd_avgcb)/$cd_avgcb*100);
							$bsrate = round((round($data['avgbrandsales'])-$cd_avgbs)/$cd_avgbs*100);
							$srate = round((round($data['sales'])-$cd_sales)/$cd_sales*100);

							$data['ytdtotalcustomernum'] = number_format(round($data['ytdtotalcustomernum']));
							$data['crate'] = $crate;
							$data['avgcustomerbrand'] = round($data['avgcustomerbrand'], 2);
							$data['brate'] = $brate;
							$data['avgbrandsales'] = number_format(round($data['avgbrandsales']));
							$data['bsrate'] = $bsrate;
							$data['sales'] = number_format(round($data['sales']*100));
							$data['srate'] = $srate;
						}
					
						$table3 = '
						<table id="warning-cat" class="table table-bordered table-hover">
			                <thead>
			                <tr>
			                  <th>类型</th>
			                  <th>会员人数</th>
			                  <th>平均购买<br />品牌数</th>
			                  <th>平均品牌<br />花费</th>
			                  <th>销售</th>
			                </tr>
			                </thead>
			                <tbody>
			                  <tr>
			                    <td>门店招新</td>
			                    <td><input type="text" id="newc" numOnly="true" value="' . $data3['cbps'][0]['ytdtotalcustomernum'] . '" size="3" /><span id="badge1" class="badge '.cellColor($crate, $storeRate).'">'.(($crate>0)?'+':'').$crate.'%</span></td>
			                    <td><input type="text" id="newb" decimalOnly="true" value="' . $data3['cbps'][0]['avgcustomerbrand'] . '" size="3" /><span id="badge2" class="badge '.cellColor($crate, $storeRate).'">'.(($crate>0)?'+':'').$crate.'%</span></td>
			                    <td><input type="text" id="newbs" numOnly="true" value="' . $data3['cbps'][0]['avgbrandsales'] . '" size="3" /><span id="badge3" class="badge '.cellColor($crate, $storeRate).'">'.(($crate>0)?'+':'').$crate.'%</span></td>
			                    <td><div id="newsales">' . $data3['cbps'][0]['sales'] . '</div><span id="badge4" class="badge '.cellColor($crate, $storeRate).'">'.(($crate>0)?'+':'').$crate.'%</span></tr>
			                  </tr>
			                  <tr>
			                    <td>品类招新</td>
			                    <td><input type="text" id="crossc" numOnly="true" value="' . $data3['cbps'][1]['ytdtotalcustomernum'] . '" size="3" /><span id="badge9" class="badge '.cellColor($brate, $storeRate).'">'.(($brate>0)?'+':'').$brate.'%</span></td>
			                    <td><input type="text" id="crossb" decimalOnly="true" value="' . $data3['cbps'][1]['avgcustomerbrand'] . '" size="3" /><span id="badge10" class="badge '.cellColor($brate, $storeRate).'">'.(($brate>0)?'+':'').$brate.'%</span></td>
			                    <td><input type="text" id="crossbs" numOnly="true" value="' . $data3['cbps'][1]['avgbrandsales'] . '" size="3" /><span id="badge11" class="badge '.cellColor($brate, $storeRate).'">'.(($brate>0)?'+':'').$brate.'%</span></td>
			                    <td><div id="crosssales">' . $data3['cbps'][1]['sales'] . '</div><span id="badge12" class="badge '.cellColor($brate, $storeRate).'">'.(($brate>0)?'+':'').$brate.'%</span></tr>
			                  </tr>
			                  <tr>
			                    <td>品类复购</td>
			                    <td><input type="text" id="repeatc" numOnly="true" value="' . $data3['cbps'][2]['ytdtotalcustomernum'] . '" size="3" /><span id="badge5" class="badge '.cellColor($bsrate, $storeRate).'">'.(($bsrate>0)?'+':'').$bsrate.'%</span></td>
			                    <td><input type="text" id="repeatb" decimalOnly="true" value="' . $data3['cbps'][2]['avgcustomerbrand'] . '" size="3" /><span id="badge6" class="badge '.cellColor($bsrate, $storeRate).'">'.(($bsrate>0)?'+':'').$bsrate.'%</span></td>
			                    <td><input type="text" id="repeatbs" numOnly="true" value="' . $data3['cbps'][2]['avgbrandsales'] . '" size="3" /><span id="badge7" class="badge '.cellColor($bsrate, $storeRate).'">'.(($bsrate>0)?'+':'').$bsrate.'%</span></td>
			                    <td><div id="repeatsales">' . $data3['cbps'][2]['sales'] . '</div><span id="badge8" class="badge '.cellColor($bsrate, $storeRate).'">'.(($bsrate>0)?'+':'').$bsrate.'%</span></tr>
			                  </tr>
			                </tbody>
			                <tfoot>
			                  <tr>
			                    <th>总计</th>
			                    <th><div id="totalc">' . $data3['cbps'][3]['ytdtotalcustomernum'] . '</div><span id="badge13" class="badge '.cellColor($srate, $storeRate).'">'.(($srate>0)?'+':'').$srate.'%</span></th>
			                    <th><div id="totalb">' . $data3['cbps'][3]['avgcustomerbrand'] . '</div><span id="badge14" class="badge '.cellColor($srate, $storeRate).'">'.(($srate>0)?'+':'').$srate.'%</span></th>
			                    <th><div id="totalbs">' . $data3['cbps'][3]['avgbrandsales'] . '</div><span id="badge15" class="badge '.cellColor($srate, $storeRate).'">'.(($srate>0)?'+':'').$srate.'%</span></th>
			                    <th><div id="totalsales">' . $data3['cbps'][3]['sales'] . '</div><span id="badge16" class="badge '.cellColor($srate, $storeRate).'">'.(($srate>0)?'+':'').$srate.'%</span></tr>
			                  </tr>
			                </tfoot>
			              </table>
						';

						$hiddenData = '';
						$id=1;
						foreach ($data1['originCbps'] as $originCbps) {
							foreach ($originCbps as $value) {
								$hiddenData .= '<span id="' . $id. '" style="display:none">' . $value . '</span>';
								$id++;
							}
						}
						$script =<<<HTML
<script type="text/javascript">

  function  commafy(num){  
     num  =  num+"";  
     var  re=/(-?\d+)(\d{3})/  
     while(re.test(num)){  
                 num=num.replace(re,"$1,$2")  
     }  
     return  num;  
  }  

  function updateb(){

    totalb=Number((parseInt($('#newc').val()*$('#newb').val())+parseInt($("#repeatc").val()*$("#repeatb").val())+parseInt($('#crossc').val()*$('#crossb').val()))/(parseInt($('#newc').val())+parseInt($('#repeatc').val())+parseInt($('#crossc').val()))).toFixed(2)
    $('#totalb').html(totalb); 

  }
  function updatebs(totalsales){

    totalc=parseInt($('#newc').val())+parseInt($('#repeatc').val())+parseInt($('#crossc').val());
    totalb=Number((parseInt($('#newc').val()*$('#newb').val())+parseInt($("#repeatc").val()*$("#repeatb").val())+parseInt($('#crossc').val()*$('#crossb').val()))/(parseInt($('#newc').val())+parseInt($('#repeatc').val())+parseInt($('#crossc').val()))).toFixed(2);

    $('#totalbs').html(Number(totalsales/(totalc*totalb)).toFixed(2));

  }
  function cellColor(value){
      if(value<-5){
        color="badge bg-red";
      }
      if(value>=-5 && value<=5){
        color="badge bg-yellow";
      }
      if(value>5){
        color="badge bg-green";
      }
      return color;
  }
    function updateNewC(){

        newsales=$('#newc').val()*$('#newb').val()*$('#newbs').val();
        repeatsales=$('#repeatc').val()*$('#repeatb').val()*$('#repeatbs').val();
        crosssales=$('#crossc').val()*$('#crossb').val()*$('#crossbs').val();
        
        $('#newsales').html(commafy(Number(newsales).toFixed(0))); 

        $('#totalc').html(commafy(parseInt($('#newc').val())+parseInt($('#repeatc').val())+parseInt($('#crossc').val())));

        totalsales=Number(newsales+repeatsales+crosssales).toFixed(0);
        
        updateb();
        updatebs(totalsales);
        $('#totalsales').html(commafy(totalsales));

        newCustomerRate=Number(($('#newc').val()/parseInt($("#1").text())-1)*100).toFixed(0);
        totalCustomer=parseInt($("#13").text());
        newSalesY1=parseInt($("#4").text());
        salesRate=Number((newsales/newSalesY1-1)*100).toFixed(0);

        if (newCustomerRate>0){positive="+";} else {positive="";}
        $('#badge1').removeClass();
        $('#badge1').addClass(cellColor(newCustomerRate));
        $('#badge1').html(positive+newCustomerRate+"%");

        if (salesRate>0){positive="+";} else {positive="";}
        $('#badge4').removeClass();
        $('#badge4').addClass(cellColor(salesRate));
        $('#badge4').html(positive+salesRate+"%");
    }

    function updateNewB(){
      newsales=$('#newc').val()*$('#newb').val()*$('#newbs').val();
      repeatsales=$('#repeatc').val()*$('#repeatb').val()*$('#repeatbs').val();
      crosssales=$('#crossc').val()*$('#crossb').val()*$('#crossbs').val();
      
      $('#newsales').html(commafy(Number(newsales).toFixed(0))); 
      
      totalsales=Number(newsales+repeatsales+crosssales).toFixed(0);
      
      updateb();
      updatebs(totalsales);
      $('#totalsales').html(commafy(totalsales));

      newbRate=Number((($('#newb').val()/$("#2").text())-1)*100).toFixed(0);
      totalb=parseInt($("#14").text());
      newSalesY1=parseInt($("#4").text());
      salesRate=Number((newsales/newSalesY1-1)*100).toFixed(0);

      if (newbRate>0){positive="+";} else {positive="";}
      $('#badge2').removeClass();
      $('#badge2').addClass(cellColor(newbRate));
      $('#badge2').html(positive+newbRate+"%");

      if (salesRate>0){positive="+";} else {positive="";}
      $('#badge4').removeClass();
      $('#badge4').addClass(cellColor(salesRate));
      $('#badge4').html(positive+salesRate+"%");
    }

    function updateNewBs(){
      newsales=$('#newc').val()*$('#newb').val()*$('#newbs').val();
      repeatsales=$('#repeatc').val()*$('#repeatb').val()*$('#repeatbs').val();
      crosssales=$('#crossc').val()*$('#crossb').val()*$('#crossbs').val();

      $('#newsales').html(commafy(Number(newsales).toFixed(0))); 

      totalsales=Number(newsales+repeatsales+crosssales).toFixed(0);
      
      updatebs(totalsales);
      $('#totalsales').html(commafy(totalsales)); 

      newbsRate=Number(($('#newbs').val()/$("#3").text()-1)*100).toFixed(0);
      totalbs=parseInt($("#15").text());
      newSalesY1=parseInt($("#4").text());
      salesRate=Number((newsales/newSalesY1-1)*100).toFixed(0);

      if (newbsRate>0){positive="+";} else {positive="";}
      $('#badge3').removeClass();
      $('#badge3').addClass(cellColor(newbsRate));
      $('#badge3').html(positive+newbsRate+"%");

      if (salesRate>0){positive="+";} else {positive="";}
      $('#badge4').removeClass();
      $('#badge4').addClass(cellColor(salesRate));
      $('#badge4').html(positive+salesRate+"%");
    }

    function updateRepeatC(){
      newsales=$('#newc').val()*$('#newb').val()*$('#newbs').val();
      repeatsales=$('#repeatc').val()*$('#repeatb').val()*$('#repeatbs').val();
      crosssales=$('#crossc').val()*$('#crossb').val()*$('#crossbs').val();

      $('#repeatsales').html(commafy(Number(repeatsales).toFixed(0))); 

      $('#totalc').html(commafy(parseInt($('#newc').val())+parseInt($('#repeatc').val())+parseInt($('#crossc').val()))); 

      totalsales=Number(newsales+repeatsales+crosssales).toFixed(0);
      
      updateb();
      updatebs(commafy(totalsales));
      $('#totalsales').html(commafy(totalsales)); 

      repeatCustomerRate=Number(($('#repeatc').val()/$("#9").text()-1)*100).toFixed(0);
      totalCustomer=parseInt($("#13").text());
      repeatSalesY1=parseInt($("#12").text());
      salesRate=Number((repeatsales/repeatSalesY1-1)*100).toFixed(0);

      if (repeatCustomerRate>0){positive="+";} else {positive="";}
      $('#badge5').removeClass();
      $('#badge5').addClass(cellColor(repeatCustomerRate));
      $('#badge5').html(positive+repeatCustomerRate+"%");

      if (salesRate>0){positive="+";} else {positive="";}
      $('#badge8').removeClass();
      $('#badge8').addClass(cellColor(salesRate));
      $('#badge8').html(positive+salesRate+"%");
    }

    function updateRepeatB(){
      newsales=$('#newc').val()*$('#newb').val()*$('#newbs').val();
      repeatsales=$('#repeatc').val()*$('#repeatb').val()*$('#repeatbs').val();
      crosssales=$('#crossc').val()*$('#crossb').val()*$('#crossbs').val(); 

      $('#repeatsales').html(commafy(Number(repeatsales).toFixed(0))); 
      
      totalsales=Number(newsales+repeatsales+crosssales).toFixed(0);
      
      updateb();
      updatebs(totalsales);
      $('#totalsales').html(commafy(totalsales));

      repeatbRate=Number((($('#repeatb').val()/$("#10").text())-1)*100).toFixed(0);
      totalb=parseInt($("#14").text());
      repeatSalesY1=parseInt($("#12").text());
      salesRate=Number((repeatsales/repeatSalesY1-1)*100).toFixed(0);

      if (repeatbRate>0){positive="+";} else {positive="";}
      $('#badge6').removeClass();
      $('#badge6').addClass(cellColor(repeatbRate));
      $('#badge6').html(positive+repeatbRate+"%");

      if (salesRate>0){positive="+";} else {positive="";}
      $('#badge8').removeClass();
      $('#badge8').addClass(cellColor(salesRate));
      $('#badge8').html(positive+salesRate+"%");
    }

    function updateRepeatBs(){
      newsales=$('#newc').val()*$('#newb').val()*$('#newbs').val();
      repeatsales=$('#repeatc').val()*$('#repeatb').val()*$('#repeatbs').val();
      crosssales=$('#crossc').val()*$('#crossb').val()*$('#crossbs').val();

      $('#repeatsales').html(commafy(Number(repeatsales).toFixed(0))); 

      totalsales=Number(newsales+repeatsales+crosssales).toFixed(0);

      updatebs(totalsales);
      $('#totalsales').html(commafy(totalsales));

      repeatbsRate=Number(($('#repeatbs').val()/$("#11").text()-1)*100).toFixed(0);
      totalbs=parseInt($("#15").text());
      repeatSalesY1=parseInt($("#12").text());
      salesRate=Number((repeatsales/repeatSalesY1-1)*100).toFixed(0);

      if (repeatbsRate>0){positive="+";} else {positive="";}
      $('#badge7').removeClass();
      $('#badge7').addClass(cellColor(repeatbsRate));
      $('#badge7').html(positive+repeatbsRate+"%");

      if (salesRate>0){positive="+";} else {positive="";}
      $('#badge8').removeClass();
      $('#badge8').addClass(cellColor(salesRate));
      $('#badge8').html(positive+salesRate+"%"); 
    }

    function updateCrossC(){
      newsales=$('#newc').val()*$('#newb').val()*$('#newbs').val();
      repeatsales=$('#repeatc').val()*$('#repeatb').val()*$('#repeatbs').val();
      crosssales=$('#crossc').val()*$('#crossb').val()*$('#crossbs').val();

      $('#crosssales').html(commafy(Number(crosssales).toFixed(0))); 

      $('#totalc').html(commafy(parseInt($('#newc').val())+parseInt($('#repeatc').val())+parseInt($('#crossc').val()))); 

      totalsales=Number(newsales+repeatsales+crosssales).toFixed(0);

      updateb();
      updatebs(totalsales);
      $('#totalsales').html(commafy(totalsales)); 

      crosscRate=Number(($('#crossc').val()/$("#5").text()-1)*100).toFixed(0);
      totalc=parseInt($("#13").text());
      crosssalesY1=parseInt($("#8").text());
      salesRate=Number((crosssales/crosssalesY1-1)*100).toFixed(0);

      if (crosscRate>0){positive="+";} else {positive="";}
      $('#badge9').removeClass();
      $('#badge9').addClass(cellColor(crosscRate));
      $('#badge9').html(positive+crosscRate+"%");

      if (salesRate>0){positive="+";} else {positive="";}
      $('#badge12').removeClass();
      $('#badge12').addClass(cellColor(salesRate));
      $('#badge12').html(positive+salesRate+"%"); 
    }

    function updateCrossB(){
      newsales=$('#newc').val()*$('#newb').val()*$('#newbs').val();
      repeatsales=$('#repeatc').val()*$('#repeatb').val()*$('#repeatbs').val();
      crosssales=$('#crossc').val()*$('#crossb').val()*$('#crossbs').val();

      $('#crosssales').html(commafy(Number(crosssales).toFixed(0))); 

      totalsales=Number(newsales+repeatsales+crosssales).toFixed(0);
      
      updateb();
      updatebs(totalsales);
      $('#totalsales').html(commafy(totalsales));

      crossbRate=Number(($('#crossb').val()/$("#6").text()-1)*100).toFixed(0);
      totalb=parseInt($("#14").text());
      crosssalesY1=parseInt($("#8").text());
      salesRate=Number((crosssales/crosssalesY1-1)*100).toFixed(0);

      if (crossbRate>0){positive="+";} else {positive="";}
      $('#badge10').removeClass();
      $('#badge10').addClass(cellColor(crossbRate));
      $('#badge10').html(positive+crossbRate+"%");

      if (salesRate>0){positive="+";} else {positive="";}
      $('#badge12').removeClass();
      $('#badge12').addClass(cellColor(salesRate));
      $('#badge12').html(positive+salesRate+"%"); 
    }

    function updateCrossBs(){
      newsales=$('#newc').val()*$('#newb').val()*$('#newbs').val();
      repeatsales=$('#repeatc').val()*$('#repeatb').val()*$('#repeatbs').val();
      crosssales=$('#crossc').val()*$('#crossb').val()*$('#crossbs').val();

      $('#crosssales').html(commafy(Number(crosssales).toFixed(0))); 

      totalsales=Number(newsales+repeatsales+crosssales).toFixed(0);

      updatebs(totalsales);
      $('#totalsales').html(commafy(totalsales));

      crossbsRate=Number(($('#crossbs').val()/$("#7").text()-1)*100).toFixed(0);
      totalbs=parseInt($("#15").text());
      crosssalesY1=parseInt($("#8").text());
      salesRate=Number((crosssales/crosssalesY1-1)*100).toFixed(0);

      if (crossbsRate>0){positive="+";} else {positive="";}
      $('#badge11').removeClass();
      $('#badge11').addClass(cellColor(crossbsRate));
      $('#badge11').html(positive+crossbsRate+"%");

      if (salesRate>0){positive="+";} else {positive="";}
      $('#badge12').removeClass();
      $('#badge12').addClass(cellColor(salesRate));
      $('#badge12').html(positive+salesRate+"%");
    }
  /*  
  * 把除了整数之外的去掉    
  */  
 function checkDecimal(value){   
     var newValue = value[0];   
     var returnValue = "";  
     for (var i =1; i <= value.length; i++) {  
         if(!newValue.match(/^[\-]?\d*?\.?\d*$/)){    
             break;  
         }  
         returnValue = newValue;  
         newValue += value[i];  
     }   
     return returnValue;  
 }

$(document).ready(function(){
 /*  
  * 文本框输入数字check  
  */  
    $("input").each(function(){  
       if ($(this).attr("numOnly") == "true") { //只可以输入数字（整数）  
           $(this).bind('keyup',function(){this.value = this.value.replace(/\D/g,'');});   
           $(this).bind("blur",function(){this.value=this.value.replace(/\D/g,'');});  
       }  
         
       if ($(this).attr("decimalOnly") == "true") { //只可以输入数字（整数或小数）  
           $(this).bind('keyup',function(){this.value = this.value.match(/^[+-]?\d*\.?\d*$/) ? this.value : checkDecimal(this.value);});    
           $(this).bind('blur',function(){this.value = this.value.match(/^[\-]?\d*?\.?\d*$/) ? this.value : checkDecimal(this.value);});    
       }
    }); 
    $('#newc').bind('input propertychange', function(){updateNewC();});
    $('#newb').bind('input propertychange', function(){updateNewB();});
    $('#newbs').bind('input propertychange', function(){updateNewBs();});
    $('#repeatc').bind('input propertychange', function(){updateRepeatC();});
    $('#repeatb').bind('input propertychange', function(){updateRepeatB();});
    $('#repeatbs').bind('input propertychange', function(){updateRepeatBs();});
    $('#crossc').bind('input propertychange', function(){updateCrossC();});
    $('#crossb').bind('input propertychange', function(){updateCrossB();});
    $('#crossbs').bind('input propertychange', function(){updateCrossBs();});

    updateNewC();
    updateNewB();
    updateNewBs();
    updateRepeatC();
    updateRepeatB();
    updateRepeatBs();
    updateCrossC();
    updateCrossB();
    updateCrossBs();

  });
  $("#showbrands").click(function(){
      $("#brands").show();
  });

</script>
HTML;

						$box3 = new Box( ($updateTimeYear).'年12月累计预测（会员占比预测：<span class="text-aqua">'.$data3['pcnt'].'%</span>）', $table3.$hiddenData.$script, 'line-chart');

						$column->append($box3->style('danger'));
					});
				});
			}
			
		});
	}

	public function xtCat($category = '')
	{
		return Admin::content(function (Content $content) use($category) {

			$content->header('生意规划');
			$content->description('品类规划');

				
			$content->row(function (Row $row) use($category) {
				
				// 门店品类规划 & 市场品类规划
				$selectHtml0 = '
					<select id="planningType" class="form-control" name="type">
						<option value="cat-planning">门店品类规划</option>
						<option value="xtcat-planning" selected>市场品类参考</option>
					</select>';
				$selectHtml0 .= '
					<script>
						$("#planningType").change(function(){
							window.location.href="/admin/store/" + $(this).val();
						})
					</script>
				';
				$row->column(4, function (Column $column) use ($selectHtml0) {
					$column->append($selectHtml0);
				});

				// 品类
				$cbpCategories = BaseCbp::select('category')
										->where('store', '=', Admin::user()->database)
										->distinct()
										->orderBy('category', 'asc')
										->get();
				
				$selectHtml = '
					<select id="catname" class="form-control select2" name="catname"><option value="">选择一个品类</option>';
				if ($category != '') {
					foreach ($cbpCategories as $cbpCategory) {
						$selected = ($category==$cbpCategory->category)?' selected':'';
						$selectHtml .= '<option value="' . $cbpCategory->category . '" ' . $selected . '>' . $cbpCategory->category . '</option>';
					}
				} else {
					foreach ($cbpCategories as $cbpCategory) {
						$selectHtml .= '<option value="' . $cbpCategory->category . '">' . $cbpCategory->category . '</option>';
					}
				}
				$selectHtml .= '</select>';
				$selectHtml .= '
					<script>
						$("#catname").change(function(){
							console.log($(this).val());
							window.location.href="/admin/store/xtcat-planning/" + $(this).val();
						});
						$(document).ready(function() {
						    $(".select2").select2();
						});
					</script>
				';
				
				$row->column(8, function (Column $column) use ($selectHtml) {
					$column->append($selectHtml);
				});
			});

			if ($category != '') {
				$content->row(function (Row $row) use($category) {
					$row->column(12, function (Column $column) {
						$column->append('<br>');
					});
				});
				$content->row(function (Row $row) use($category) {
					
					$updateTime = getLastUpdateTime(env('DB_STORE_BASE_DATABASE', 'base'), 'cbp');
					$updateTimeYear = date('Y', strtotime($updateTime));
					$updateTimeMonth = date('m', strtotime($updateTime));

					$data0 = BaseCbp::where('store', '=', Admin::user()->database)
										->where('category', '=', $category)
										->where('ytdmonth', '=', $updateTimeMonth)
										->get();
					$data1 = BaseCbp::where('store', '=', Admin::user()->database)
										->where('category', '=', $category)
										->where('ytdmonth', '=', 12)
										->get();
					
					$row->column(12, function (Column $column) use ($data0, $updateTimeYear, $updateTimeMonth) {

							$table0 = '
										<table id="warning-cat" class="table table-bordered">
										<thead>
											<tr>
											<th colspan="2" style="text-align: center;">类型</th>
											<th colspan="3" style="text-align: center;">本店参考</th>
											<th>市场平均</th>
											</tr>
										</thead>
										<tbody>';

							if($data0->count() > 0) {
								foreach ( $data0 as $data  ){
									$customer_ytd[$data->status][$data->year] = round($data->ytdtotalcustomernum);
									$brand_ytd[$data->status][$data->year] = round($data->avgcustomerbrand,2);
									$brand_min_ytd[$data->status][$data->year] = round($data->avgcustomerbrand_min,2);
									$brand_max_ytd[$data->status][$data->year] = round($data->avgcustomerbrand_max,2);
									$brand_mean_ytd[$data->status][$data->year] = round($data->avgcustomerbrand_mean,2);
									$brandsales_ytd[$data->status][$data->year] = round($data->avgbrandsales);
									$brandsales_min_ytd[$data->status][$data->year] = round($data->avgbrandsales_min);
									$brandsales_max_ytd[$data->status][$data->year] = round($data->avgbrandsales_max);
									$brandsales_mean_ytd[$data->status][$data->year] = round($data->avgbrandsales_mean);
									$sales_ytd[$data->status][$data->year] = round($data->sales);
									$pcnt_ytd[$data->status][$data->year] = round($data->pcnt_mean*100);
								}
								$parts = array("new","cross","repeat","total");
								
								foreach ($parts as $part) {

									$progress=round(($brandsales_ytd[$part][$updateTimeYear]-$brandsales_min_ytd[$part][$updateTimeYear])/($brandsales_max_ytd[$part][$updateTimeYear]-$brandsales_min_ytd[$part][$updateTimeYear])*100);
									$rate = round(($brandsales_mean_ytd[$part][$updateTimeYear]/$brandsales_mean_ytd[$part][$updateTimeYear-1]-1)*100);
									$positive=($rate>0)?"+":"";

									$table0 .= '<tr>';
									$table0 .= '<td rowspan="2" style="width: 100px; vertical-align: middle; text-align: center;"><b>'.transStatusToText($part,"cat").'</b></td>';
									$table0 .= '<td style="width: 150px;">平均品牌花费</td>';
									$table0 .= '<td style="text-align:right;">'.$brandsales_min_ytd[$part][$updateTimeYear].'</td>';
									$table0 .= '<td style="width: 250px;">
											  <div class="progress progress-xs">
												<div class="progress-bar progress-bar-'.progressColor($progress).'" style="width: ".$progress."%" data-toggle="tooltip" title="'.$brandsales_ytd[$part][$updateTimeYear].'"></div>
											  </div>
											</td>';
									$table0 .= '<td>'.$brandsales_max_ytd[$part][$updateTimeYear].'</td>';
									$table0 .= '<td>'.$brandsales_mean_ytd[$part][$updateTimeYear].'</td>';
									$table0 .= '<!--td><span class="badge bg-'.cellStoreColor($rate).'">'.$positive.$rate.'%</span></td-->';
									$table0 .= '</tr>';

									$progress=round(($brand_ytd[$part][$updateTimeYear]-$brand_min_ytd[$part][$updateTimeYear])/($brand_max_ytd[$part][$updateTimeYear]-$brand_min_ytd[$part][$updateTimeYear])*100);
									$rate = round(($brand_mean_ytd[$part][$updateTimeYear]/$brand_mean_ytd[$part][$updateTimeYear-1]-1)*100);
									$positive=($rate>0)?"+":"";

									$table0 .= '<tr>';
									$table0 .= '<td style="width: 150px;">平均品牌数</td>';
									$table0 .= '<td style="text-align:right;">'.$brand_min_ytd[$part][$updateTimeYear].'</td>';
									$table0 .= '<td style="width: 250px;">
											  <div class="progress progress-xs">
												<div class="progress-bar progress-bar-'.progressColor($progress).'" style="width: '.$progress.'%" data-toggle="tooltip" title="'.$brand_ytd[$part][$updateTimeYear].'"></div>
											  </div>
											</td>';
									$table0 .= '<td>'.$brand_max_ytd[$part][$updateTimeYear].'</td>';
									$table0 .= '<td>'.$brand_mean_ytd[$part][$updateTimeYear].'</td>';
									$table0 .= '<!--td><span class="badge bg-'.cellStoreColor($rate).'">'.$positive.$rate.'%</span></td-->';
									$table0 .= '</tr>';
								}
							} else {
								$pcnt_ytd['total'][$updateTimeYear-1] = '-';
								$table0 .= '<tr>';
								$table0 .= '<td colspan="8" style="width: 100px; vertical-align: middle; text-align: center;"><b>没有数据</b></td>';
								$table0 .= '</tr>';
							}
						$table0 .= '</tbody></table>';
						$box0 = new Box( ($updateTimeYear).'年'.($updateTimeMonth).'月累计（会员占比：<span class="text-aqua">'.$pcnt_ytd['total'][$updateTimeYear-1].'%</span>）', $table0, 'line-chart');

						$column->append($box0->style('danger'));
						
					});

					$row->column(12, function (Column $column) use ($data1, $updateTimeYear, $updateTimeMonth) {

							$table1 = '
										<table id="warning-cat" class="table table-bordered">
										<thead>
											<tr>
											<th colspan="2" style="text-align: center;">类型</th>
											<th colspan="3" style="text-align: center;">本店参考</th>
											<th>市场平均</th>
											</tr>
										</thead>
										<tbody>';

							if($data1->count() > 0) {
								foreach ( $data1 as $data  ){
									$customer_ytd[$data->status][$data->year] = round($data->ytdtotalcustomernum);
									$brand_ytd[$data->status][$data->year] = round($data->avgcustomerbrand,2);
									$brand_min_ytd[$data->status][$data->year] = round($data->avgcustomerbrand_min,2);
									$brand_max_ytd[$data->status][$data->year] = round($data->avgcustomerbrand_max,2);
									$brand_mean_ytd[$data->status][$data->year] = round($data->avgcustomerbrand_mean,2);
									$brandsales_ytd[$data->status][$data->year] = round($data->avgbrandsales);
									$brandsales_min_ytd[$data->status][$data->year] = round($data->avgbrandsales_min);
									$brandsales_max_ytd[$data->status][$data->year] = round($data->avgbrandsales_max);
									$brandsales_mean_ytd[$data->status][$data->year] = round($data->avgbrandsales_mean);
									$sales_ytd[$data->status][$data->year] = round($data->sales);
									$pcnt_ytd[$data->status][$data->year] = round($data->pcnt_mean*100);
								}
								$parts = array("new","cross","repeat","total");
								
								foreach ($parts as $part) {

									$progress=round(($brandsales_ytd[$part][$updateTimeYear]-$brandsales_min_ytd[$part][$updateTimeYear])/($brandsales_max_ytd[$part][$updateTimeYear]-$brandsales_min_ytd[$part][$updateTimeYear])*100);
									$rate = round(($brandsales_mean_ytd[$part][$updateTimeYear]/$brandsales_mean_ytd[$part][$updateTimeYear-1]-1)*100);
									$positive=($rate>0)?"+":"";

									$table1 .= '<tr>';
									$table1 .= '<td rowspan="2" style="width: 100px; vertical-align: middle; text-align: center;"><b>'.transStatusToText($part,"cat").'</b></td>';
									$table1 .= '<td style="width: 150px;">平均品牌花费</td>';
									$table1 .= '<td style="text-align:right;">'.$brandsales_min_ytd[$part][$updateTimeYear].'</td>';
									$table1 .= '<td style="width: 250px;">
											  <div class="progress progress-xs">
												<div class="progress-bar progress-bar-'.progressColor($progress).'" style="width: ".$progress."%" data-toggle="tooltip" title="'.$brandsales_ytd[$part][$updateTimeYear].'"></div>
											  </div>
											</td>';
									$table1 .= '<td>'.$brandsales_max_ytd[$part][$updateTimeYear].'</td>';
									$table1 .= '<td>'.$brandsales_mean_ytd[$part][$updateTimeYear].'</td>';
									$table1 .= '<!--td><span class="badge bg-'.cellStoreColor($rate).'">'.$positive.$rate.'%</span></td-->';
									$table1 .= '</tr>';

									$progress=round(($brand_ytd[$part][$updateTimeYear]-$brand_min_ytd[$part][$updateTimeYear])/($brand_max_ytd[$part][$updateTimeYear]-$brand_min_ytd[$part][$updateTimeYear])*100);
									$rate = round(($brand_mean_ytd[$part][$updateTimeYear]/$brand_mean_ytd[$part][$updateTimeYear-1]-1)*100);
									$positive=($rate>0)?"+":"";

									$table1 .= '<tr>';
									$table1 .= '<td style="width: 150px;">平均品牌数</td>';
									$table1 .= '<td style="text-align:right;">'.$brand_min_ytd[$part][$updateTimeYear].'</td>';
									$table1 .= '<td style="width: 250px;">
											  <div class="progress progress-xs">
												<div class="progress-bar progress-bar-'.progressColor($progress).'" style="width: '.$progress.'%" data-toggle="tooltip" title="'.$brand_ytd[$part][$updateTimeYear].'"></div>
											  </div>
											</td>';
									$table1 .= '<td>'.$brand_max_ytd[$part][$updateTimeYear].'</td>';
									$table1 .= '<td>'.$brand_mean_ytd[$part][$updateTimeYear].'</td>';
									$table1 .= '<!--td><span class="badge bg-'.cellStoreColor($rate).'">'.$positive.$rate.'%</span></td-->';
									$table1 .= '</tr>';
								}
							} else {
								$pcnt_ytd['total'][$updateTimeYear-1] = '-';
								$table1 .= '<tr>';
								$table1 .= '<td colspan="8" style="width: 100px; vertical-align: middle; text-align: center;"><b>没有数据</b></td>';
								$table1 .= '</tr>';
							}
						$table1 .= '</tbody></table>';
						$box1 = new Box( ($updateTimeYear).'年'.($updateTimeMonth).'月累计（会员占比：<span class="text-aqua">'.$pcnt_ytd['total'][$updateTimeYear-1].'%</span>）', $table1, 'line-chart');

						$column->append($box1->style('danger'));
						
					});

				});
			}
			
		});
	}

	public function brand($category='')
	{
		if (Admin::user()->isAdministrator() && Admin::user()->database=='') {
			return Admin::content(function (Content $content) {

				$content->header('生意规划');
				$content->description('品牌规划');

				$content->row(function (Row $row) {

					$words = '请先配置您的数据库信息。';
					
					$row->column(12, function (Column $column) use ($words) {
						$column->append(new Callout($words));
					});
				});
			});
		}

		return Admin::content(function (Content $content) use($category) {

			$content->header('生意规划');
			$content->description('品牌规划');

				
			$content->row(function (Row $row) use($category) {
				$bbpCategories = Bbp::select('category')->distinct()->orderBy('category', 'asc')->get();
				
				$selectHtml = '
					<div class="form-group">
					<select id="catname" class="form-control" name="catname"><option value="">选择一个品类</option>';
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
							window.location.href="/admin/store/brand-planning/" + $(this).val();
						})
					</script>
				';
				
				$row->column(12, function (Column $column) use ($selectHtml) {
					$column->append($selectHtml);
				});
			});
			
			if($category != '') {
					
				$baseBrands = DB::connection('mysql_store_base')
							->table('brandindex')
							->leftJoin('brandlist_all', function($join) {
								$join->on('brandindex.brandname_sc', '=', 'brandlist_all.brandname_sc')
										->on('brandindex.store', '=', 'brandlist_all.store');
							})
							->where('brandindex.store', '=', Admin::user()->database)
							->where('status', '=', '全年')
							->where('brandlist_all.catname', '=', $category)
							->where('brandindex.index_new_rate', '<>', '')
							->where('brandindex.index_cross_rate', '<>', '')
							->where('brandindex.index_repeat_rate', '<>', '')
							->get();

				if($baseBrands->count() > 0){
					foreach ($baseBrands as $baseBrand) {
						$x = round($baseBrand->index_new_rate*100-100);
						$y = round($baseBrand->index_new_rate_mean*100-100);
						// $baseBrandDatas['newRate'][htmlentities($baseBrand->brand_name,ENT_QUOTES)] = ['x' => $x, 'y' => $y];
						$baseBrandDatas['newRate'][] = ['x' => $x, 'y' => $y];
						$baseBrandDatas['newRateLabels'][] = $baseBrand->brand_name;
						$baseBrandDatas['newRateQuadrant'][getQuadrant($x,$y)][]=$baseBrand->brand_name;

						$x = round($baseBrand->index_cross_rate*100-100);
						$y = round($baseBrand->index_cross_rate_mean*100-100);
						// $baseBrandDatas['crossRate'][htmlentities($baseBrand->brand_name,ENT_QUOTES)] = ['x' => $x, 'y' => $y];
						$baseBrandDatas['crossRate'][] = ['x' => $x, 'y' => $y];
						$baseBrandDatas['crossRateLabels'][] = $baseBrand->brand_name;
						$baseBrandDatas['crossRateQuadrant'][getQuadrant($x,$y)][]=$baseBrand->brand_name;

						$x = round($baseBrand->index_repeat_rate*100-100);
						$y = round($baseBrand->index_repeat_rate_mean*100-100);
						// $baseBrandDatas['repeatRate'][htmlentities($baseBrand->brand_name,ENT_QUOTES)] = ['x' => $x, 'y' => $y];
						$baseBrandDatas['repeatRate'][] = ['x' => $x, 'y' => $y];
						$baseBrandDatas['repeatRateLabels'][] = $baseBrand->brand_name;
						$baseBrandDatas['repeatRateQuadrant'][getQuadrant($x,$y)][]=$baseBrand->brand_name;

						$x = round($baseBrand->index_new_f*100-100);
						$y = round($baseBrand->index_new_f_mean*100-100);
						// $baseBrandDatas['newf'][htmlentities($baseBrand->brand_name,ENT_QUOTES)] = ['x' => $x, 'y' => $y];
						$baseBrandDatas['newf'][] = ['x' => $x, 'y' => $y];
						$baseBrandDatas['newfLabels'][] = $baseBrand->brand_name;
						$baseBrandDatas['newfQuadrant'][getQuadrant($x,$y)][]=$baseBrand->brand_name;

						$x = round($baseBrand->index_cross_f*100-100);
						$y = round($baseBrand->index_cross_f_mean*100-100);
						// $baseBrandDatas['crossf'][htmlentities($baseBrand->brand_name,ENT_QUOTES)] = ['x' => $x, 'y' => $y];
						$baseBrandDatas['crossf'][] = ['x' => $x, 'y' => $y];
						$baseBrandDatas['crossfLabels'][] = $baseBrand->brand_name;
						$baseBrandDatas['crossfQuadrant'][getQuadrant($x,$y)][]=$baseBrand->brand_name;

						$x = round($baseBrand->index_repeat_f*100-100);
						$y = round($baseBrand->index_repeat_f_mean*100-100);
						// $baseBrandDatas['repeatf'][htmlentities($baseBrand->brand_name,ENT_QUOTES)] = ['x' => $x, 'y' => $y];
						$baseBrandDatas['repeatf'][] = ['x' => $x, 'y' => $y];
						$baseBrandDatas['repeatfLabels'][] = $baseBrand->brand_name;
						$baseBrandDatas['repeatfQuadrant'][getQuadrant($x,$y)][]=$baseBrand->brand_name;
					}

					$updateTime = getLastUpdateTime(env('DB_STORE_BASE_DATABASE', 'base'), 'brandindex');
					$updateTimeYear = date('Y', strtotime($updateTime));
					
					// 门店招新-品牌数量矩阵
					$scatter0 = new Scatter(
							'',
							[
								[
									'label' => $updateTimeYear-1,
									'labels' => $baseBrandDatas['newRateLabels'],
									'data' => $baseBrandDatas['newRate'],
									'backgroundColor' => '#00c0ef',
									'borderColor' => '#0073b7',
									'borderWidth' => 1,
									'radius' => 8,
									'hitRadius' => 4
								]
							]
						);
					$scatter0->options(['legend' => ['display' => false]]);

					$table0 ='
							<table class="table table-bordered table-hover">
								<tr>
									<td style="width: 80px;">优质品牌</td>
									<td>'.
									(empty($baseBrandDatas['newRateQuadrant'][1])?'':implode(",", $baseBrandDatas['newRateQuadrant'][1]))
									.'</td>
								</tr>
								<tr>
									<td style="width: 80px;">稳定品牌</td>
									<td>'.
									(empty($baseBrandDatas['newRateQuadrant'][4])?'':implode(",", $baseBrandDatas['newRateQuadrant'][4]))
									.'</td>
								</tr>
								<tr>
									<td style="width: 80px;">潜力品牌</td>
									<td>'.
									(empty($baseBrandDatas['newRateQuadrant'][2])?'':implode(",", $baseBrandDatas['newRateQuadrant'][2]))
									.'</td>
								</tr>
								<tr>
									<td style="width: 80px;">落后品牌</td>
									<td>'.
									(empty($baseBrandDatas['newRateQuadrant'][3])?'':implode(",", $baseBrandDatas['newRateQuadrant'][3]))
									.'</td>
								</tr>
							</table>';
					// 门店招新-品牌质量矩阵
					$scatter1 = new Scatter(
							'',
							[
								[
									'label' => $updateTimeYear-1,
									'labels' => $baseBrandDatas['newfLabels'],
									'data' => $baseBrandDatas['newf'],
									'backgroundColor' => '#f39c12',
									'borderColor' => '#f39c12',
									'borderWidth' => 1,
									'radius' => 8,
									'hitRadius' => 4
								]
							]
						);
					$scatter1->options(['legend' => ['display' => false]]);
					$table1 ='
							<table class="table table-bordered table-hover">
								<tr>
									<td style="width: 80px;">优质品牌</td>
									<td>'.
									(empty($baseBrandDatas['newfQuadrant'][1])?'':implode(",", $baseBrandDatas['newfQuadrant'][1]))
									.'</td>
								</tr>
								<tr>
									<td style="width: 80px;">稳定品牌</td>
									<td>'.
									(empty($baseBrandDatas['newfQuadrant'][4])?'':implode(",", $baseBrandDatas['newfQuadrant'][4]))
									.'</td>
								</tr>
								<tr>
									<td style="width: 80px;">潜力品牌</td>
									<td>'.
									(empty($baseBrandDatas['newfQuadrant'][2])?'':implode(",", $baseBrandDatas['newfQuadrant'][2]))
									.'</td>
								</tr>
								<tr>
									<td style="width: 80px;">落后品牌</td>
									<td>'.
									(empty($baseBrandDatas['newfQuadrant'][3])?'':implode(",", $baseBrandDatas['newfQuadrant'][3]))
									.'</td>
								</tr>
							</table>';

					// 品牌招新-品牌数量矩阵
					$scatter2 = new Scatter(
							'',
							[
								[
									'label' => $updateTimeYear-1,
									'labels' => $baseBrandDatas['crossRateLabels'],
									'data' => $baseBrandDatas['crossRate'],
									'backgroundColor' => '#00c0ef',
									'borderColor' => '#0073b7',
									'borderWidth' => 1,
									'radius' => 8,
									'hitRadius' => 4
								]
							]
						);
					$scatter2->options(['legend' => ['display' => false]]);

					$table2 ='
							<table class="table table-bordered table-hover">
								<tr>
									<td style="width: 80px;">优质品牌</td>
									<td>'.
									(empty($baseBrandDatas['crossRateQuadrant'][1])?'':implode(",", $baseBrandDatas['crossRateQuadrant'][1]))
									.'</td>
								</tr>
								<tr>
									<td style="width: 80px;">稳定品牌</td>
									<td>'.
									(empty($baseBrandDatas['crossRateQuadrant'][4])?'':implode(",", $baseBrandDatas['crossRateQuadrant'][4]))
									.'</td>
								</tr>
								<tr>
									<td style="width: 80px;">潜力品牌</td>
									<td>'.
									(empty($baseBrandDatas['crossRateQuadrant'][2])?'':implode(",", $baseBrandDatas['crossRateQuadrant'][2]))
									.'</td>
								</tr>
								<tr>
									<td style="width: 80px;">落后品牌</td>
									<td>'.
									(empty($baseBrandDatas['crossRateQuadrant'][3])?'':implode(",", $baseBrandDatas['crossRateQuadrant'][3]))
									.'</td>
								</tr>
							</table>';
					// 品牌招新-品牌质量矩阵
					$scatter3 = new Scatter(
							'',
							[
								[
									'label' => $updateTimeYear-1,
									'labels' => $baseBrandDatas['crossfLabels'],
									'data' => $baseBrandDatas['crossf'],
									'backgroundColor' => '#f39c12',
									'borderColor' => '#f39c12',
									'borderWidth' => 1,
									'radius' => 8,
									'hitRadius' => 4
								]
							]
						);
					$scatter3->options(['legend' => ['display' => false]]);
					$table3 ='
							<table class="table table-bordered table-hover">
								<tr>
									<td style="width: 80px;">优质品牌</td>
									<td>'.
									(empty($baseBrandDatas['crossfQuadrant'][1])?'':implode(",", $baseBrandDatas['crossfQuadrant'][1]))
									.'</td>
								</tr>
								<tr>
									<td style="width: 80px;">稳定品牌</td>
									<td>'.
									(empty($baseBrandDatas['crossfQuadrant'][4])?'':implode(",", $baseBrandDatas['crossfQuadrant'][4]))
									.'</td>
								</tr>
								<tr>
									<td style="width: 80px;">潜力品牌</td>
									<td>'.
									(empty($baseBrandDatas['crossfQuadrant'][2])?'':implode(",", $baseBrandDatas['crossfQuadrant'][2]))
									.'</td>
								</tr>
								<tr>
									<td style="width: 80px;">落后品牌</td>
									<td>'.
									(empty($baseBrandDatas['crossfQuadrant'][3])?'':implode(",", $baseBrandDatas['crossfQuadrant'][3]))
									.'</td>
								</tr>
							</table>';

					// 品牌复购-品牌数量矩阵
					$scatter4 = new Scatter(
							'',
							[
								[
									'label' => $updateTimeYear-1,
									'labels' => $baseBrandDatas['repeatRateLabels'],
									'data' => $baseBrandDatas['repeatRate'],
									'backgroundColor' => '#00c0ef',
									'borderColor' => '#0073b7',
									'borderWidth' => 1,
									'radius' => 8,
									'hitRadius' => 4
								]
							]
						);
					$scatter4->options(['legend' => ['display' => false]]);

					$table4 ='
							<table class="table table-bordered table-hover">
								<tr>
									<td style="width: 80px;">优质品牌</td>
									<td>'.
									(empty($baseBrandDatas['repeatRateQuadrant'][1])?'':implode(",", $baseBrandDatas['repeatRateQuadrant'][1]))
									.'</td>
								</tr>
								<tr>
									<td style="width: 80px;">稳定品牌</td>
									<td>'.
									(empty($baseBrandDatas['repeatRateQuadrant'][4])?'':implode(",", $baseBrandDatas['repeatRateQuadrant'][4]))
									.'</td>
								</tr>
								<tr>
									<td style="width: 80px;">潜力品牌</td>
									<td>'.
									(empty($baseBrandDatas['repeatRateQuadrant'][2])?'':implode(",", $baseBrandDatas['repeatRateQuadrant'][2]))
									.'</td>
								</tr>
								<tr>
									<td style="width: 80px;">落后品牌</td>
									<td>'.
									(empty($baseBrandDatas['repeatRateQuadrant'][3])?'':implode(",", $baseBrandDatas['repeatRateQuadrant'][3]))
									.'</td>
								</tr>
							</table>';
					// 品牌复购-品牌质量矩阵
					$scatter5 = new Scatter(
							'',
							[
								[
									'label' => $updateTimeYear-1,
									'labels' => $baseBrandDatas['repeatfLabels'],
									'data' => $baseBrandDatas['repeatf'],
									'backgroundColor' => '#f39c12',
									'borderColor' => '#f39c12',
									'borderWidth' => 1,
									'radius' => 8,
									'hitRadius' => 4
								]
							]
						);
					$scatter5->options(['legend' => ['display' => false]]);
					$table5 ='
							<table class="table table-bordered table-hover">
								<tr>
									<td style="width: 80px;">优质品牌</td>
									<td>'.
									(empty($baseBrandDatas['repeatfQuadrant'][1])?'':implode(",", $baseBrandDatas['repeatfQuadrant'][1]))
									.'</td>
								</tr>
								<tr>
									<td style="width: 80px;">稳定品牌</td>
									<td>'.
									(empty($baseBrandDatas['repeatfQuadrant'][4])?'':implode(",", $baseBrandDatas['repeatfQuadrant'][4]))
									.'</td>
								</tr>
								<tr>
									<td style="width: 80px;">潜力品牌</td>
									<td>'.
									(empty($baseBrandDatas['repeatfQuadrant'][2])?'':implode(",", $baseBrandDatas['repeatfQuadrant'][2]))
									.'</td>
								</tr>
								<tr>
									<td style="width: 80px;">落后品牌</td>
									<td>'.
									(empty($baseBrandDatas['repeatfQuadrant'][3])?'':implode(",", $baseBrandDatas['repeatfQuadrant'][3]))
									.'</td>
								</tr>
							</table>';


					$box0 = new Box(' <span class="text-aqua"> 门店招新</span> - 品牌数量矩阵', $scatter0.$table0, 'tags', 'x轴代表本店表现，y轴代表市场表现');
					$box1 = new Box(' <span class="text-aqua"> 门店招新</span> - 品牌质量矩阵', $scatter1.$table1, 'tags', 'x轴代表本店表现，y轴代表市场表现');
					$box2 = new Box(' <span class="text-aqua"> 门店招新</span> - 品牌数量矩阵', $scatter2.$table2, 'tags', 'x轴代表本店表现，y轴代表市场表现');
					$box3 = new Box(' <span class="text-aqua"> 门店招新</span> - 品牌质量矩阵', $scatter3.$table3, 'tags', 'x轴代表本店表现，y轴代表市场表现');
					$box4 = new Box(' <span class="text-aqua"> 门店招新</span> - 品牌数量矩阵', $scatter4.$table4, 'tags', 'x轴代表本店表现，y轴代表市场表现');
					$box5 = new Box(' <span class="text-aqua"> 门店招新</span> - 品牌质量矩阵', $scatter5.$table5, 'tags', 'x轴代表本店表现，y轴代表市场表现');
					$content->row(function(Row $row) use($box0, $box1, $box2, $box3, $box4, $box5) {
						$tab = new Tab();
						$tab->icon('info');
						$tabContentNew = '<div class="row"><div class="col-md-6">'.$box0.'</div>'.
										'<div class="col-md-6">'.$box1.'</div></div>';

						$tabContentCross = '<div class="row"><div class="col-md-6">'.$box2.'</div>'.
										'<div class="col-md-6">'.$box3.'</div></div>';

						$tabContentRepeat = '<div class="row"><div class="col-md-6">'.$box4.'</div>'.
										'<div class="col-md-6">'.$box5.'</div></div>';
						$tab->add('品牌复购', $tabContentRepeat);
						$tab->add('品牌招新', $tabContentCross);
						$tab->add('门店招新', $tabContentNew, true);
						$row->column(12,$tab);
					});
				} else {
					$content->row(function (Row $row) {
						$tab = new Tab();
						$tab->icon('info');
						
						$tab->add('品牌复购', '没有数据！');
						$tab->add('品牌招新', '没有数据！');
						$tab->add('门店招新', '没有数据！', true);
						$row->column(12,$tab);
					});
				}
					
			}
		});
	}


}
