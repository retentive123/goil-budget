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

    public function processGuide()
    {
        return view('docs.process-guide');
    }

    public function deploymentGuide()
    {
        return view('docs.deployment-guide');
    }
}
