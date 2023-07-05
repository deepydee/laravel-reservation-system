<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanyUserController extends Controller
{
    public function index(Company $company): View
    {
        $users = $company
            ->users()
            ->with('role')
            ->where('role_id', Role::COMPANY_OWNER->value)
            ->get();

        return view('companies.users.index', compact('company', 'users'));
    }

    public function create(Company $company): View
    {
        return view('companies.users.create', compact('company'));
    }

    public function store(StoreUserRequest $request, Company $company): RedirectResponse
    {
        $company->users()->create([
            ...$request->validated(),
            'role_id' => Role::COMPANY_OWNER->value,
        ]);

        return to_route('companies.users.index', $company);
    }

    public function edit(Company $company, User $user): View
    {
        return view('companies.users.edit', compact('company', 'user'));
    }

    public function update(
        UpdateUserRequest $request,
        Company $company,
        User $user
    ): RedirectResponse {
        $user->update($request->validated());

        return to_route('companies.users.index', $company);
    }

    public function destroy(Company $company, User $user): RedirectResponse
    {
        $user->delete();

        return to_route('companies.users.index', $company);
    }
}
