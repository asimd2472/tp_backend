<?php

namespace Tests\Feature;

use App\Models\Dealer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DealerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_a_dealer_with_an_image(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.dealers.store'), [
            'state' => 'Andhra Pradesh',
            'district' => 'Guntur',
            'dealer_name' => 'Sample Dealer',
            'dealer_address' => 'Main Road',
            'contact_person' => 'Alex',
            'mobile_number' => '9010100212',
            'email_id' => 'dealer@example.com',
            'image' => UploadedFile::fake()->image('dealer.png'),
        ])->assertSessionHasNoErrors();

        $dealer = Dealer::query()->firstOrFail();
        $this->assertSame('Sample Dealer', $dealer->dealer_name);
        Storage::disk('public')->assertExists($dealer->image);

        $this->put(route('admin.dealers.update', $dealer), [
            'state' => 'Andhra Pradesh',
            'district' => 'Guntur',
            'dealer_name' => 'Updated Dealer',
            'dealer_address' => 'Updated address',
            'contact_person' => 'Alex',
            'mobile_number' => '9010100212',
            'email_id' => 'dealer@example.com',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('dealers', [
            'id' => $dealer->id,
            'dealer_name' => 'Updated Dealer',
            'image' => $dealer->image,
        ]);

        $this->delete(route('admin.dealers.destroy', $dealer))
            ->assertRedirect(route('admin.dealers.index'));

        $this->assertDatabaseMissing('dealers', ['id' => $dealer->id]);
        Storage::disk('public')->assertMissing($dealer->image);
    }

    public function test_admin_can_import_dealers_from_csv(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $csv = implode("\n", [
            'State,District,Dealer Name,Dealer Address,Contact Person,Mobile Number,Email ID',
            'State A,District A,First Dealer,"1 Main Road",Contact One,9000000001,first@example.com',
            "State B,District B,Second Dealer,\"2 Main\nRoad\",Contact Two,9000000002,second@example.com",
        ]);

        $this->post(route('admin.dealers.import.store'), [
            'spreadsheet' => UploadedFile::fake()->createWithContent('dealers.csv', $csv),
        ])->assertRedirect(route('admin.dealers.index'))
            ->assertSessionHas('success', 'Imported 2 dealer(s) and updated 0 dealer(s).');

        $this->assertDatabaseCount('dealers', 2);
        $this->assertDatabaseHas('dealers', [
            'dealer_name' => 'First Dealer',
            'dealer_address' => '1 Main Road',
        ]);

        $this->assertDatabaseHas('dealers', [
            'dealer_name' => 'Second Dealer',
            'dealer_address' => "2 Main\nRoad",
        ]);
    }

    public function test_dealer_api_filters_each_csv_column_and_paginates(): void
    {
        Dealer::query()->create([
            'state' => 'Andhra Pradesh',
            'district' => 'Guntur',
            'dealer_name' => 'Airavath Interiors',
            'dealer_address' => 'Amaravathi Road',
            'contact_person' => 'Pavan',
            'mobile_number' => '9010100212',
            'email_id' => 'airavath@example.com',
        ]);
        Dealer::query()->create([
            'state' => 'Telangana',
            'district' => 'Hyderabad',
            'dealer_name' => 'Other Dealer',
            'dealer_address' => 'Other Road',
            'contact_person' => 'Other Contact',
            'mobile_number' => '9000000000',
            'email_id' => 'other@example.com',
        ]);

        $this->getJson(route('api.dealers.index', [
            'state' => 'Andhra',
            'district' => 'Guntur',
            'dealer_name' => 'Airavath',
            'dealer_address' => 'Amaravathi',
            'contact_person' => 'Pavan',
            'mobile_number' => '901010',
            'email_id' => 'airavath@',
            'per_page' => 1,
        ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('pagination.per_page', 1)
            ->assertJsonPath('data.0.dealer_name', 'Airavath Interiors');

        $this->getJson(route('api.dealers.index', ['per_page' => 1, 'page' => 2]))
            ->assertOk()
            ->assertJsonPath('pagination.current_page', 2)
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonCount(1, 'data');
    }
}
