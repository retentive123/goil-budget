<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class DocumentationController extends Controller
{
    public function index()
    {
        return response()->json([
            'api'     => 'GOIL Budget Management API',
            'version' => '1.0',
            'base_url'=> url('/api'),
            'auth'    => [
                'type'        => 'Bearer Token (Laravel Sanctum)',
                'login'       => 'POST /api/auth/login',
                'logout'      => 'POST /api/auth/logout',
                'me'          => 'GET /api/auth/me',
            ],
            'endpoints' => [
                'periods'   => 'GET /api/periods',
                'budgets'   => [
                    'list'        => 'GET /api/budgets',
                    'show'        => 'GET /api/budgets/{id}',
                    'update'      => 'PATCH /api/budgets/{id}/line-items',
                    'submit'      => 'POST /api/budgets/{id}/submit',
                ],
                'departments' => [
                    'list'  => 'GET /api/departments',
                    'show'  => 'GET /api/departments/{id}',
                ],
                'actuals' => [
                    'list'  => 'GET /api/actuals',
                    'store' => 'POST /api/actuals',
                ],
                'reports' => [
                    'summary'     => 'GET /api/reports/summary',
                    'departments' => 'GET /api/reports/departments',
                    'variance'    => 'GET /api/reports/variance',
                ],
            ],
            'filters' => [
                'budgets'  => ['period_id','department_id','status','per_page'],
                'actuals'  => ['period_id','department_id','month','year','per_page'],
                'reports'  => ['period_id','department_id'],
            ],
        ]);
    }
}
