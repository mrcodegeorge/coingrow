<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Services\BankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_guests_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_dashboard(): void
    {
        $user = User::factory()->create([
            'username' => 'tester',
        ]);

        app(BankingService::class)->createPrimaryAccountForUser($user);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Virtual Account');
    }

    public function test_registration_creates_virtual_account_with_transaction_log(): void
    {
        $this->post(route('register.store'), [
            'username' => 'newcustomer',
            'name' => 'New Customer',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('username', 'newcustomer')->firstOrFail();
        $account = $user->account()->first();

        $this->assertNotNull($account);
        $this->assertStringStartsWith('CG', $account->account_number);
        $this->assertSame('COINGROW DIGITAL', $account->bank_name);
        $this->assertSame('internal', $account->provider);
        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'type' => 'account_created',
        ]);
    }

    public function test_webhook_deposit_funds_virtual_account_and_rejects_duplicates(): void
    {
        config(['services.coingrow.webhook_secret' => 'test-secret']);

        $user = User::factory()->create(['username' => 'webhookuser']);
        $account = app(BankingService::class)->createPrimaryAccountForUser($user);

        $payload = [
            'account_number' => $account->account_number,
            'amount' => 250.75,
            'reference' => 'paystack-ref-1001',
            'provider' => 'paystack',
            'description' => 'Inbound funding',
        ];

        $signature = hash_hmac('sha256', json_encode($payload), 'test-secret');

        $this->postJson(route('webhook.deposit'), $payload, [
            'X-Coingrow-Signature' => $signature,
        ])->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'external_reference' => 'paystack-ref-1001',
            'type' => 'deposit',
        ]);

        $this->assertSame('250.75', $account->fresh()->balance);

        $this->postJson(route('webhook.deposit'), $payload, [
            'X-Coingrow-Signature' => $signature,
        ])->assertOk()
            ->assertJson(['status' => 'duplicate']);

        $this->assertSame(1, Transaction::where('external_reference', 'paystack-ref-1001')->count());
    }
}
