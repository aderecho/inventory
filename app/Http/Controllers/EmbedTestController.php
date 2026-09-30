<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmbedTestController extends Controller
{
    public function index(){
        return inertia('EmbedTest/Index');
    }
}
