<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Models\Activity;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Company $company): View
    {
        $this->authorize('viewAny', $company);

        $company->load('activities');

        return view('companies.activities.index', compact('company'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Company $company): View
    {
        $this->authorize('create', $company);

        $guides = User::where('company_id', $company->id)
            ->where('role_id', Role::GUIDE->value)
            ->pluck('name', 'id');

        return view('companies.activities.create', compact('guides', 'company'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreActivityRequest $request, Company $company): RedirectResponse
    {
        $this->authorize('create', $company);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('activities', 'public');
        }

        Activity::create([
            ...$request->validated(),
            'company_id' => $company->id,
            'photo' => $path ?? null,
        ]);

        return to_route('companies.activities.index', $company);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Company $company, Activity $activity): View
    {
        $this->authorize('update', $company);

        $guides = User::where('company_id', $company->id)
            ->where('role_id', Role::GUIDE->value)
            ->pluck('name', 'id');

        return view('companies.activities.edit', compact('guides', 'activity', 'company'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateActivityRequest $request,
        Company $company,
        Activity $activity
    ): RedirectResponse {
        $this->authorize('update', $company);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('activities', 'public');

            if ($activity->photo) {
                Storage::disk('public')->delete($activity->photo);
            }
        }

        $activity->update([
            ...$request->validated(),
            'photo' => $path ?? $activity->photo,
        ]);

        return to_route('companies.activities.index', $company);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company, Activity $activity)
    {
        $this->authorize('delete', $activity);
        $activity->delete();

        return to_route('companies.activities.index', $company);
    }
}
