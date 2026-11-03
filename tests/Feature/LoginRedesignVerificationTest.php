<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginRedesignVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::firstOrCreate(
            ['code' => 'SIGMA-HO'],
            [
                'name' => 'SIGMA PROJECTION',
                'currency_code' => 'IDR',
                'is_active' => true,
            ]
        );

        Store::firstOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'STR-001',
            ],
            [
                'name' => 'SIGMA CABANG UTAMA',
                'is_active' => true,
            ]
        );
    }

    public function test_login_page_renders_redesigned_ui_with_sigma_projection_branding(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);

        // 1. Footer & Company Branding
        $response->assertSee('SIGMA PROJECTION');
        $response->assertDontSee('PT SIGMA RETAIL INDONESIA');
        $response->assertSee('Didukung Keamanan Enkripsi Tingkat Tinggi');

        // 2. Brand Identity & Product Title
        $response->assertSee('SIGMA PROJECTS');
        $response->assertSee('SIGMA');
        $response->assertSee('ERP &amp; POS', false);
        $response->assertSee('One Integrated System. Smarter Business.');
        $response->assertSee('Sistem terintegrasi untuk manajemen toko, kasir, persediaan dan logistik.');

        // 3. Four Feature Cards
        $response->assertSee('Manajemen');
        $response->assertSee('Toko');
        $response->assertSee('Kasir');
        $response->assertSee('Modern');
        $response->assertSee('Inventori');
        $response->assertSee('Real-Time');
        $response->assertSee('Logistik');
        $response->assertSee('Terintegrasi');

        // 4. Security Trust Badge
        $response->assertSee('Aman');
        $response->assertSee('Andal');
        $response->assertSee('Profesional');
        $response->assertSee('Solusi bisnis terbaik untuk masa depan Anda');

        // 5. Login Card Elements
        $response->assertSee('Selamat Datang Kembali');
        $response->assertSee('Masuk untuk melanjutkan aktivitas bisnis Anda.');
        $response->assertSee('Alamat Email');
        $response->assertSee('Password');
        $response->assertSee('Ingat sesi saya di perangkat ini');
        $response->assertSee('Lupa Password?');
        $response->assertSee('Masuk ke Sistem');
        $response->assertSee('SIGMA ERP &amp; POS v2.0', false);

        // 6. Footer Status
        $response->assertSee('Server Online');
        $response->assertSee('Terhubung');
        $response->assertSee('Support');

        // 7. No supermarket shelf graphics
        $response->assertDontSee('Aisle Perspective Lines');
        $response->assertDontSee('Shelves Left');
    }

    public function test_invalid_login_shows_error_message(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'wrong@sigma.test',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_valid_owner_login_redirects_to_sales(): void
    {
        $user = User::where('email', 'owner@sigma.test')->first();
        if (! $user) {
            $user = User::create([
                'name' => 'Owner Test',
                'email' => 'owner@sigma.test',
                'password' => Hash::make('password123'),
                'role' => 'owner',
                'is_active' => true,
            ]);
        }

        $response = $this->post('/login', [
            'email' => 'owner@sigma.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('sales.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_valid_cashier_login_redirects_to_pos(): void
    {
        $user = User::where('email', 'kasir1@sigma.test')->first();
        if (! $user) {
            $user = User::create([
                'name' => 'Kasir Test',
                'email' => 'kasir1@sigma.test',
                'password' => Hash::make('password123'),
                'role' => 'cashier',
                'is_active' => true,
            ]);
        }

        $response = $this->post('/login', [
            'email' => 'kasir1@sigma.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('pos.index'));
        $this->assertAuthenticatedAs($user);
    }
}
