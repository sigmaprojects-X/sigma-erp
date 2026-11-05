<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CustomerTier;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\MinimalTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySwitchRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_switching_company_from_product_edit_page_redirects_safely_to_product_list(): void
    {
        $this->seed(MinimalTestSeeder::class);

        $companyA = Company::firstOrFail();
        $companyB = Company::create([
            'code' => 'COMP-B',
            'name' => 'PT Company B',
            'currency_code' => 'IDR',
            'is_active' => true,
        ]);

        $owner = User::where('role', 'owner')->firstOrFail();
        $this->actingAs($owner);

        // Akses ganti perusahaan dari URL edit produk
        $fakeEditUrl = url("/master/products/01a1a952-942e-72c2-95ef-e6753f5dca3d/edit");

        $response = $this->from($fakeEditUrl)->post(route('company.switch'), [
            'company_id' => $companyB->id,
        ]);

        // Harus dialihkan ke daftar produk, bukan kembali ke URL edit yang berujung 404
        $response->assertRedirect(route('master.products'));
        $response->assertSessionHas('success');
    }

    public function test_opening_edit_product_with_different_company_redirects_to_product_list(): void
    {
        $this->seed(MinimalTestSeeder::class);

        $companyA = Company::firstOrFail();
        $companyB = Company::create([
            'code' => 'COMP-B',
            'name' => 'PT Company B',
            'currency_code' => 'IDR',
            'is_active' => true,
        ]);

        $owner = User::where('role', 'owner')->firstOrFail();
        $this->actingAs($owner);

        // Set perusahaan aktif ke Company B
        session(['active_company_id' => $companyB->id]);

        // Coba buka ID yang tidak ada di Company B
        $response = $this->get('/master/products/01a1a952-942e-72c2-95ef-e6753f5dca3d/edit');

        // Harus dialihkan ke daftar produk dengan pesan ramah, bukan 404 Not Found
        $response->assertRedirect(route('master.products'));
        $response->assertSessionHas('error');
    }
}
