<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ServiceController extends Controller
{
 public function index(){
       return response()
       ->view('service.wsdl', [], 200)
       ->header('Content-Type', 'application/soap+xml');
 }   //
}
