<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class MyPageController extends Controller
{
    /**
     * 本人の出品・購入情報をモデルで読み込み、マイページへ渡します。
     */
    public function show(Request $request): View
    {
        return view('mypage.show', ['user' => $request->user()->getMyPageData()]);
    }
}
