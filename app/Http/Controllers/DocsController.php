<?php

namespace App\Http\Controllers;

class DocsController extends Controller
{
    public function index()
    {
        return view('docs.index');
    }

    public function sageIntegration()
    {
        return view('docs.sage-integration');
    }
}
