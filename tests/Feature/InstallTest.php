<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        @unlink(storage_path('install.lock'));
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('install.lock'));

        parent::tearDown();
    }

    public function test_install_page_renders_when_not_locked(): void
    {
        $this->get('/install')
            ->assertOk()
            ->assertSee('Install Open E-commerce')
            ->assertSee('PHP extension: intl');
    }

    public function test_install_page_404s_when_locked(): void
    {
        file_put_contents(storage_path('install.lock'), 'x');

        $this->get('/install')->assertNotFound();
    }

    public function test_root_redirects_to_install_when_not_locked(): void
    {
        $this->get('/')->assertRedirect('/install');
    }

    public function test_validation_errors(): void
    {
        $this->post('/install', [])
            ->assertSessionHasErrors(['db_connection', 'db_database', 'admin_name', 'admin_email', 'admin_password', 'tenant_slug', 'tenant_name']);
    }

    public function test_creates_tenant_and_admin_and_locks(): void
    {
        $this->post('/install', [
            'db_connection' => 'sqlite',
            'db_database' => ':memory:',
            'admin_name' => 'Test Admin',
            'admin_email' => 'admin@example.test',
            'admin_password' => 'secret123',
            'tenant_slug' => 'first',
            'tenant_name' => 'First Shop',
            'tenant_currency' => 'SEK',
            'tenant_locale' => 'sv',
        ])->assertRedirect('/');

        $this->assertFileExists(storage_path('install.lock'));
        $this->assertSame(1, Tenant::count());
        $this->assertSame('first', Tenant::first()->slug);
        $this->assertSame(1, User::count());
        $this->assertSame(User::ROLE_ADMIN, User::first()->role);
    }
}
