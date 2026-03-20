<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index() {
        return view('welcome',[
            'title' => 'Home',
            'data' => date("Y-m-d H:i:s")
        ]);
    }
}
