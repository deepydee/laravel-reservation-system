<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Mail\RegistrationInvite;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
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

    // public function test_admin_can_create_user_for_a_company(): void
    // {
    //     $user = User::factory()->admin()->create();
    //     $company = Company::factory()->create();

    //     $response = $this->actingAs($user)->post(
    //         route('companies.users.store', $company),
    //         [
    //             'name' => 'test user',
    //             'email' => 'test@test.com',
    //             'password' => 'password',
    //         ]
    //     );

    //     $response->assertRedirect(route('companies.users.index', $company));

    //     $this->assertDatabaseHas('users', [
    //         'name' => 'test user',
    //         'email' => 'test@test.com',
    //     ]);
    // }

    public function test_admin_can_send_invite_to_user_for_a_company(): void
    {
        Mail::fake();

        $company = Company::factory()->create();
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);

        $response = $this->actingAs($admin)->post(route('companies.users.store', $company), [
            'email' => 'test@test.com',
        ]);

        Mail::assertSent(RegistrationInvite::class);
        $response->assertRedirect(route('companies.users.index', $company->id));

        $this->assertDatabaseHas('user_invitations', [
            'email' => 'test@test.com',
            'registered_at' => null,
            'company_id' => $company->id,
            'role_id' => Role::COMPANY_OWNER->value,
        ]);
    }

    public function test_invitation_can_be_sent_only_once_for_user(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);

        $this->actingAs($admin)->post(route('companies.users.store', $company), [
            'email' => 'test@test.com',
        ]);

        $response = $this->actingAs($admin)->post(route('companies.users.store', $company), [
            'email' => 'test@test.com',
        ]);

        $response->assertInvalid(['email' => 'Invitation with this email address already requested.']);
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

    public function test_admin_can_delete_user_for_a_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->create([
            'company_id' => $company->id
        ]);
        $user2 = User::factory()->create([
            'email' => 'delete@user.com',
            'company_id' => $company->id
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'delete@user.com',
            'company_id' => $company->id,
        ]);

        $response = $this->actingAs($user)->delete(
            route('companies.users.destroy', [$company, $user2])
        );

        $response->assertRedirect(route('companies.users.index', $company));

        $this->assertDatabaseMissing('users', [
            'name' => 'delete user',
            'company_id' => $company->id,
        ]);
    }

    public function test_company_owner_can_view_his_companies_users(): void
    {
        $this->seed(RoleSeeder::class);
        $company = Company::factory()->create();
        $user = User::factory()
            ->companyOwner()
            ->create(['company_id' => $company->id]);
        $secondUser = User::factory()
            ->companyOwner()
            ->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)
            ->get(route('companies.users.index', $company));

        $response->assertOk()
            ->assertSeeText($secondUser->name);
    }

    public function test_company_owner_cannot_view_other_companies_users(): void
    {
        $this->seed(RoleSeeder::class);
        $company = Company::factory()->create();
        $company2 = Company::factory()->create();
        $user = User::factory()
            ->companyOwner()
            ->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)
            ->get(route('companies.users.index', $company2));

        $response->assertForbidden();
    }

    // public function test_company_owner_can_create_user_to_his_company(): void
    // {
    //     $this->seed(RoleSeeder::class);
    //     $company = Company::factory()->create();
    //     $user = User::factory()
    //         ->companyOwner()
    //         ->create(['company_id' => $company->id]);

    //     $response = $this->actingAs($user)->post(
    //         route('companies.users.store', $company),
    //         [
    //             'name' => 'test user',
    //             'email' => 'test@test.com',
    //             'password' => 'password',
    //         ]
    //     );

    //     $response->assertRedirect(route('companies.users.index', $company));

    //     $this->assertDatabaseHas('users', [
    //         'name' => 'test user',
    //         'email' => 'test@test.com',
    //         'company_id' => $company->id,
    //     ]);
    // }

    public function test_company_owner_can_send_invite_to_user(): void
    {
        Mail::fake();

        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($owner)->post(route('companies.users.store', $company), [
            'email' => 'test@test.com',
        ]);

        Mail::assertSent(RegistrationInvite::class);

        $response->assertRedirect(route('companies.users.index', $company->id));

        $this->assertDatabaseHas('user_invitations', [
            'email' => 'test@test.com',
            'registered_at' => null,
            'company_id' => $company->id,
            'role_id' => Role::COMPANY_OWNER->value,
        ]);
    }

    public function test_company_owner_cannot_create_user_to_other_company(): void
    {
        $this->seed(RoleSeeder::class);
        $company = Company::factory()->create();
        $company2 = Company::factory()->create();
        $user = User::factory()
            ->companyOwner()
            ->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->post(
            route('companies.users.store', $company2),
            [
                'name' => 'test user',
                'email' => 'test@test.com',
                'password' => 'password',
            ]
        );

        $response->assertForbidden();
    }
    public function test_company_owner_can_edit_user_for_his_company(): void
    {
        $this->seed(RoleSeeder::class);
        $company = Company::factory()->create();
        $user = User::factory()
            ->companyOwner()
            ->create(['company_id' => $company->id]);
        $user2 = User::factory()
            ->companyOwner()
            ->create(['company_id' => $company->id]);


        $response = $this->actingAs($user)->put(
            route('companies.users.update', [$company, $user2]),
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

    public function test_company_owner_cannot_edit_user_for_other_company(): void
    {
        $company = Company::factory()->create();
        $company2 = Company::factory()->create();
        $user = User::factory()
            ->companyOwner()
            ->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->put(
            route(
                'companies.users.update',
                [$company2->id, $user->id]
            ),
            [
                'name' => 'updated user',
                'email' => 'test@update.com',
            ]
        );

        $response->assertForbidden();
    }

    public function test_company_owner_can_delete_user_for_his_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()
            ->companyOwner()
            ->create(['company_id' => $company->id]);
        $user2 = User::factory()
            ->companyOwner()
            ->create([
                'email' => 'test@user.com',
                'company_id' => $company->id
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@user.com',
            'company_id' => $company->id
        ]);

        $response = $this->actingAs($user)
            ->delete(route('companies.users.destroy', [$company, $user2]));
        $response->assertRedirect(route('companies.users.index', $company));

        $this->assertSoftDeleted($user2->fresh());
    }

    public function test_company_owner_cannot_delete_user_for_other_company()
    {
        $company = Company::factory()->create();
        $company2 = Company::factory()->create();

        $user = User::factory()
            ->companyOwner()
            ->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)
            ->delete(route('companies.users.destroy', [$company2, $user]));

        $response->assertForbidden();
    }
}
