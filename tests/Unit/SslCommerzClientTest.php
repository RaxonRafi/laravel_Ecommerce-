<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Payments\Exceptions\SslCommerzRequestFailed;
use App\Payments\SslCommerz\SslCommerzClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SslCommerzClientTest extends TestCase
{
    private function client(bool $sandbox): SslCommerzClient
    {
        config([
            'payment.gateways.sslcommerz.sandbox' => $sandbox,
            'payment.gateways.sslcommerz.store_id' => 'teststore',
            'payment.gateways.sslcommerz.store_password' => 'testpass',
        ]);

        return new SslCommerzClient;
    }

    /** @return array<string, mixed> */
    private function successfulSession(): array
    {
        return [
            'status' => 'SUCCESS',
            'GatewayPageURL' => 'https://sandbox.sslcommerz.com/EasyCheckOut/testcde123',
            'sessionkey' => 'abc123',
        ];
    }

    public function test_it_uses_the_sandbox_host_when_sandbox_mode_is_on(): void
    {
        Http::fake(['*' => Http::response($this->successfulSession())]);

        $this->client(sandbox: true)->createSession(['total_amount' => '10.00']);

        Http::assertSent(fn (Request $request) => str_starts_with(
            $request->url(),
            'https://sandbox.sslcommerz.com/gwprocess/v4/api.php',
        ));
    }

    public function test_it_uses_the_live_host_when_sandbox_mode_is_off(): void
    {
        Http::fake(['*' => Http::response($this->successfulSession())]);

        $this->client(sandbox: false)->createSession(['total_amount' => '10.00']);

        Http::assertSent(fn (Request $request) => str_starts_with(
            $request->url(),
            'https://securepay.sslcommerz.com/gwprocess/v4/api.php',
        ));
    }

    public function test_validation_and_query_follow_the_same_host_choice(): void
    {
        Http::fake(['*' => Http::response(['status' => 'VALID'])]);

        $client = $this->client(sandbox: false);
        $client->validateByValId('val-1');
        $client->queryByTransactionId('ORD-2026-000001-AAAAAA');

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'securepay.sslcommerz.com/validator/api/validationserverAPI.php'));
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'securepay.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php'));
    }

    public function test_it_sends_the_configured_credentials(): void
    {
        Http::fake(['*' => Http::response($this->successfulSession())]);

        $this->client(sandbox: true)->createSession(['total_amount' => '10.00']);

        Http::assertSent(fn (Request $request) => $request['store_id'] === 'teststore'
            && $request['store_passwd'] === 'testpass');
    }

    public function test_it_throws_when_the_gateway_cannot_be_reached(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

        $this->expectException(SslCommerzRequestFailed::class);
        $this->expectExceptionMessageMatches('/Could not reach the SSLCommerz session endpoint/');

        $this->client(sandbox: true)->createSession(['total_amount' => '10.00']);
    }

    public function test_it_throws_when_the_session_response_reports_failure(): void
    {
        Http::fake(['*' => Http::response([
            'status' => 'FAILED',
            'failedreason' => 'Store Credential Error',
        ])]);

        $this->expectException(SslCommerzRequestFailed::class);
        $this->expectExceptionMessageMatches('/Store Credential Error/');

        $this->client(sandbox: true)->createSession(['total_amount' => '10.00']);
    }

    public function test_it_throws_when_the_session_response_carries_no_payment_page(): void
    {
        Http::fake(['*' => Http::response(['status' => 'SUCCESS'])]);

        $this->expectException(SslCommerzRequestFailed::class);

        $this->client(sandbox: true)->createSession(['total_amount' => '10.00']);
    }

    public function test_it_throws_on_a_server_error(): void
    {
        Http::fake(['*' => Http::response('upstream down', 502)]);

        $this->expectException(SslCommerzRequestFailed::class);
        $this->expectExceptionMessageMatches('/HTTP 502/');

        $this->client(sandbox: true)->createSession(['total_amount' => '10.00']);
    }
}
