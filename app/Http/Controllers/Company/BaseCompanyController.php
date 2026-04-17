<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

abstract class BaseCompanyController extends Controller
{
    protected function companyOrForbidden(): Company|JsonResponse
    {
        $company = auth('company-api')->user();
        if (!$company) {
            return $this->error('Unauthenticated', 401);
        }

        if (!in_array((string) $company->status, ['approved', 'active'], true)) {
            return $this->error('Company account is not approved', 403);
        }

        return $company;
    }
}
