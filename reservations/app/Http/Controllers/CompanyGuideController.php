<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreGuideRequest;
use App\Http\Requests\UpdateGuideRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CompanyGuideController extends Controller
{
    public function index(Company $company): View
    {
        $this->authorize('viewAny', $company);

        $guides = $company
            ->users()
            ->with('role')
            ->where('role_id', Role::GUIDE->value)
            ->get();

        return view('companies.guides.index', compact('company', 'guides'));
    }

    public function create(Company $company): View
    {
        $this->authorize('create', $company);

        return view('companies.guides.create', compact('company'));
    }

    public function store(StoreGuideRequest $request, Company $company): RedirectResponse
    {
        $this->authorize('create', $company);

        $company->users()->create([
            ...$request->validated(),
            'role_id' => Role::GUIDE->value,
        ]);

        return to_route('companies.guides.index', $company);
    }

    public function edit(Company $company, User $guide): View
    {
        $this->authorize('update', $company);

        return view('companies.guides.edit', compact('company', 'guide'));
    }

    public function update(
        UpdateGuideRequest $request,
        Company $company,
        User $guide
    ): RedirectResponse {
        $this->authorize('update', $company);
        $guide->update($request->validated());

        return to_route('companies.guides.index', $company);
    }

    public function destroy(Company $company, User $guide): RedirectResponse
    {
        $this->authorize('delete', $company);
        $guide->delete();

        return to_route('companies.guides.index', $company);
    }
}
