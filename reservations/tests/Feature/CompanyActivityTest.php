<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_owner_can_view_activities_page(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($owner)->get(route('companies.activities.index', $company));

        $response->assertOk();
    }

    public function test_company_owner_can_see_only_his_companies_activities(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $activity = Activity::factory()->create(['company_id' => $company->id]);
        $activity2 = Activity::factory()->create();

        $response = $this->actingAs($owner)->get(route('companies.activities.index', $company));

        $response->assertSeeText($activity->name)
            ->assertDontSeeText($activity2->name);
    }

    public function test_company_owner_can_create_activity(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create();

        $response = $this->actingAs($owner)->post(route('companies.activities.store', $company), [
            'name' => 'activity',
            'description' => 'description',
            'start_time' => '2023-09-01 10:00',
            'price' => 9999,
            'guide_id' => $guide->id,
        ]);

        $response->assertRedirect(route('companies.activities.index', $company));

        $this->assertDatabaseHas('activities', [
            'company_id' => $company->id,
            'guide_id' => $guide->id,
            'name' => 'activity',
            'description' => 'description',
            'start_time' => '2023-09-01 10:00',
            'price' => 999900,
        ]);
    }

    public function test_can_upload_image(): void
    {
        Storage::fake('public');

        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create();

        $file = UploadedFile::fake()->image('avatar.jpg');

        $this->actingAs($owner)->post(route('companies.activities.store', $company), [
            'name' => 'activity',
            'description' => 'description',
            'start_time' => '2023-09-01 10:00',
            'price' => 9999,
            'guide_id' => $guide->id,
            'image' => $file,
        ]);

        Storage::disk('public')->assertExists('/activities/' . $file->hashName());
    }
    public function test_cannot_upload_non_image_file(): void
    {
        Storage::fake('public');

        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create();

        $file = UploadedFile::fake()->create('document.pdf', 2000, 'application/pdf');

        $response = $this->actingAs($owner)->post(route('companies.activities.store', $company), [
            'name' => 'activity',
            'description' => 'description',
            'start_time' => '2023-09-01 10:00',
            'price' => 9999,
            'guide_id' => $guide->id,
            'image' => $file,
        ]);

        $response->assertSessionHasErrors(['image']);
        Storage::disk('public')->assertMissing('/activities/' . $file->hashName());
    }

    public function test_guides_are_shown_only_for_specific_company_in_create_form(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create(['company_id' => $company->id]);

        $company2 = Company::factory()->create();
        $guide2 = User::factory()->guide()->create(['company_id' => $company2->id]);

        $response = $this->actingAs($owner)->get(route('companies.activities.create', $company));

        $response->assertViewHas('guides', function (Collection $guides) use ($guide) {
            return $guide->name === $guides[$guide->id];
        });

        $response->assertViewHas('guides', function (Collection $guides) use ($guide2) {
            return !array_key_exists($guide2->id, $guides->toArray());
        });
    }

    public function test_guides_are_shown_only_for_specific_company_in_edit_form(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create(['company_id' => $company->id]);
        $activity = Activity::factory()->create(['company_id' => $company->id]);

        $company2 = Company::factory()->create();
        $guide2 = User::factory()->guide()->create(['company_id' => $company2->id]);

        $response = $this->actingAs($owner)->get(route('companies.activities.edit', [$company, $activity]));

        $response->assertViewHas('guides', function (Collection $guides) use ($guide) {
            return $guide->name === $guides[$guide->id];
        });

        $response->assertViewHas('guides', function (Collection $guides) use ($guide2) {
            return !array_key_exists($guide2->id, $guides->toArray());
        });
    }

    public function test_company_owner_can_edit_activity(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create(['company_id' => $company->id]);
        $activity = Activity::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($owner)->put(route('companies.activities.update', [$company, $activity]), [
            'name' => 'activity',
            'description' => 'description',
            'start_time' => '2023-09-01 10:00',
            'price' => 9999,
            'guide_id' => $guide->id,
        ]);

        $response->assertRedirect(route('companies.activities.index', $company));

        $this->assertDatabaseHas('activities', [
            'company_id' => $company->id,
            'guide_id' => $guide->id,
            'name' => 'activity',
            'description' => 'description',
            'start_time' => '2023-09-01 10:00',
            'price' => 999900,
        ]);
    }

    public function test_company_owner_cannot_edit_activity_for_other_company(): void
    {
        $company = Company::factory()->create();
        $company2 = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create();
        $activity2 = Activity::factory()->create(['company_id' => $company2->id]);

        $response = $this->actingAs($owner)->put(route('companies.activities.update', [$company2, $activity2]), [
            'name' => 'activity',
            'description' => 'description',
            'start_time' => '2023-09-01 10:00',
            'price' => 9999,
            'guide_id' => $guide->id,
        ]);

        $response->assertForbidden();
    }

    public function test_company_owner_can_delete_activity(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $activity = Activity::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($owner)->delete(route('companies.activities.destroy', [$company, $activity]));

        $response->assertRedirect(route('companies.activities.index', $company));

        $this->assertModelMissing($activity);
    }

    public function test_company_owner_cannot_delete_activity_for_other_company(): void
    {
        $company = Company::factory()->create();
        $company2 = Company::factory()->create();
        $owner = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $activity2 = Activity::factory()->create(['company_id' => $company2->id]);

        $response = $this->actingAs($owner)->delete(route('companies.activities.destroy', [$company2, $activity2]));

        $this->assertModelExists($activity2);
        $response->assertForbidden();
    }

    public function test_admin_can_view_companies_activities(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('companies.activities.index', $company));

        $response->assertOk();
    }

    public function test_admin_can_create_activity_for_company(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->create();
        $guide = User::factory()->guide()->create();

        $response = $this->actingAs($admin)->post(route('companies.activities.store', $company), [
            'name' => 'activity',
            'description' => 'description',
            'start_time' => '2023-09-01 10:00',
            'price' => 9999,
            'guide_id' => $guide->id,
        ]);

        $response->assertRedirect(route('companies.activities.index', $company->id));

        $this->assertDatabaseHas('activities', [
            'company_id' => $company->id,
            'guide_id' => $guide->id,
            'name' => 'activity',
            'description' => 'description',
            'start_time' => '2023-09-01 10:00',
            'price' => 999900,
        ]);
    }

    public function test_admin_can_edit_activity_for_company(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->create();
        $guide = User::factory()->guide()->create();
        $activity = Activity::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($admin)->put(route('companies.activities.update', [$company, $activity]), [
            'name' => 'activity',
            'description' => 'description',
            'start_time' => '2023-09-01 10:00',
            'price' => 9999,
            'guide_id' => $guide->id,
        ]);

        $response->assertRedirect(route('companies.activities.index', $company));

        $this->assertDatabaseHas('activities', [
            'company_id' => $company->id,
            'guide_id' => $guide->id,
            'name' => 'activity',
            'description' => 'description',
            'start_time' => '2023-09-01 10:00',
            'price' => 999900,
        ]);
    }
}
