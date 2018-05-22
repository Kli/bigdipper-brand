<?php
namespace App\Admin\Apis;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Admin\Models\CbpCustomized;

class CbpCustomizedController extends Controller
{
	public function post(Request $request) {
		$cbpCustomized = new CbpCustomized();
		$cbpCustomized->firstOrNew(['category'=>$request->input('category')]);
		
		$response = ['errcode'=>1, 'errmsg'=>'No data or total'];

		if ($request->input('data') && $request->input('total')) {
			$cbpCustomized->category = $request->input('category');
			$cbpCustomized->reference = str_rand();
			$cbpCustomized->data = $request->input('data');
			$cbpCustomized->total = $request->input('total');

			$cbpCustomized->save();

			$response = ['errcode' => 0, 'errmsg' => 'Data saved successfully!'];

		}
		
		return json_encode($response);
	}
}