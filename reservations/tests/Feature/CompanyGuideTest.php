<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_owner_can_view_his_companies_guides(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create(['company_id' => $company->id]);

        $response = $this->actingAs($owner)->get(route('companies.guides.index', $company));

        $response->assertOk()
            ->assertSeeText($guide->name);
    }

    public function test_company_owner_cannot_view_other_companies_guides(): void
    {
        $company = Company::factory()->create();
        $company2 = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($owner)->get(route('companies.guides.index', $company2));

        $response->assertForbidden();
    }

    public function test_company_owner_can_create_guide_to_his_company(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($owner)->post(
            route('companies.guides.store', $company),
            [
                'name' => 'test guide',
                'email' => 'test@test.com',
                'password' => 'password',
            ]
        );

        $response->assertRedirect(route('companies.guides.index', $company));

        $this->assertDatabaseHas('users', [
            'name' => 'test guide',
            'email' => 'test@test.com',
        ]);
    }

    public function test_company_owner_cannot_create_guide_to_other_company(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $company2 = Company::factory()->create();

        $response = $this->actingAs($owner)->post(
            route('companies.guides.store', $company2),
            [
                'name' => 'test guide',
                'email' => 'test@test.com',
                'password' => 'password',
            ]
        );

        $response->assertForbidden();
    }

    public function test_company_owner_can_edit_guide_for_his_company(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create(['company_id' => $company->id]);

        $response = $this->actingAs($owner)->put(route('companies.guides.update', [$company, $guide]), [
            'name' => 'updated guide',
            'email' => 'test@update.com',
        ]);

        $response->assertRedirect(route('companies.guides.index', $company));

        $this->assertDatabaseHas('users', [
            'name' => 'updated guide',
            'email' => 'test@update.com',
            'company_id' => $company->id,
        ]);
    }

    public function test_company_owner_cannot_edit_guide_for_other_company(): void
    {
        $company = Company::factory()->create();
        $company2 = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide2 = User::factory()->guide()->create(['company_id' => $company2->id]);

        $response = $this->actingAs($owner)->put(
            route('companies.guides.update',
            [$company2, $guide2]), [
            'name' => 'updated guide',
            'email' => 'test@update.com',
        ]);

        $response->assertForbidden();
    }

    public function test_company_owner_can_delete_guide_for_his_company(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create([
            'email' => 'test@guide.com',
            'company_id' => $company->id
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@guide.com',
            'company_id' => $company->id,
        ]);

        $response = $this->actingAs($owner)->delete(
            route('companies.guides.destroy',
            [$company, $guide]
        ));
        $response->assertRedirect(route('companies.guides.index', $company));

        $this->assertSoftDeleted($guide->fresh());
    }
    public function test_company_owner_cannot_delete_guide_for_other_company(): void
    {
        $company = Company::factory()->create();
        $company2 = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide2 = User::factory()->guide()->create([
            'email' => 'test@guide.com',
            'company_id' => $company2->id
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@guide.com',
            'company_id' => $company2->id,
        ]);

        $response = $this->actingAs($owner)->delete(
            route('companies.guides.destroy',
            [$company2, $guide2]
        ));

        $response->assertForbidden();
    }
}
