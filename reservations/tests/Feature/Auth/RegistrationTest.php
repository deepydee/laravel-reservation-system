<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\UserInvitation;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_id' => Role::CUSTOMER->value,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_user_can_register_with_token_for_company_owner_role(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $this->actingAs($owner)->post(route('companies.users.store', $company), [
            'email' => 'test@test.com',
        ]);

        $invitation = UserInvitation::where('email', 'test@test.com')->first();

        Auth::logout();

        $response = $this->withSession(['invitation_token' => $invitation->token])->post('/register', [
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@test.com',
            'company_id' => $company->id,
            'role_id' => Role::COMPANY_OWNER->value,
        ]);

        $this->assertAuthenticated();

        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_user_can_register_with_token_for_guide_role(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $this->actingAs($owner)->post(route('companies.guides.store', $company), [
            'email' => 'test@test.com',
        ]);

        $invitation = UserInvitation::where('email', 'test@test.com')->first();

        Auth::logout();

        $response = $this->withSession(['invitation_token' => $invitation->token])->post('/register', [
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@test.com',
            'company_id' => $company->id,
            'role_id' => Role::GUIDE->value,
        ]);

        $this->assertAuthenticated();

        $response->assertRedirect(RouteServiceProvider::HOME);
    }
}
