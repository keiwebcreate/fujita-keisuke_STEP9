<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class MyPageController extends Controller
{
    public function show(Request $request): View
    {
        return view('mypage.show', ['user' => $request->user()->getMyPageData()]);
    }
}
