<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;


class DashboardController extends Controller
{
    public function index() : View {
        return view('auth.login');
    }

    public function dashboard() : View {
        return view('dashboards.dashboard');
    }


}
