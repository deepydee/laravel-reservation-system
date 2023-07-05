<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CompanyUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_company_users_page(): void
    {
        $user = User::factory()->admin()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($user)->get(route('companies.users.index', $company));

        $response->assertOk();
    }

    public function test_admin_can_create_user_for_a_company(): void
    {
        $user = User::factory()->admin()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($user)->post(
            route('companies.users.store', $company),
            [
                'name' => 'test user',
                'email' => 'test@test.com',
                'password' => 'password',
            ]
        );

        $response->assertRedirect(route('companies.users.index', $company));

        $this->assertDatabaseHas('users', [
            'name' => 'test user',
            'email' => 'test@test.com',
        ]);
    }

    public function test_admin_can_edite_user_for_a_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->put(
            route('companies.users.update', [$company, $user]),
            [
                'name' => 'updated user',
                'email' => 'test@update.com',
            ]
        );

        $response->assertRedirect(route('companies.users.index', $company));

        $this->assertDatabaseHas('users', [
            'name' => 'updated user',
            'email' => 'test@update.com',
        ]);
    }

    public function test_admin_can_delete_user_for_a_company()
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->create([
            'email' => 'delete@user.com',
            'company_id' => $company->id
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'delete@user.com',
            'company_id' => $company->id,
        ]);

        $response = $this->actingAs($user)->delete(
            route('companies.users.destroy', [$company, $user])
        );

        $response->assertRedirect(route('companies.users.index', $company));

        $this->assertDatabaseMissing('users', [
            'name' => 'updated user',
            'email' => 'test@update.com',
        ]);
    }
}
