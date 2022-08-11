<?php

namespace BeyondCode\Mailbox\Tests\Console;

use BeyondCode\Mailbox\InboundEmail;
use BeyondCode\Mailbox\Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CleanEmailsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2018, 1, 1, 00, 00, 00));

        $this->app['config']->set('mailbox.store_incoming_emails_for_days', 31);
    }

    /** @test */
    public function it_can_clean_the_statistics()
    {
        $this->makeMailForDays();

        $this->assertCount(60, InboundEmail::all());

        $this->artisan('mailbox:clean');

        $this->assertCount(31, InboundEmail::all());

        $cutOffDate = Carbon::now()->subDays(31)->format('Y-m-d H:i:s');

        $this->assertCount(0, InboundEmail::where('created_at', '<', $cutOffDate)->get());
    }

    /** @test */
    public function it_respects_store_incoming_emails_for_days_config()
    {
        $this->app['config']->set('mailbox.store_incoming_emails_for_days', 1);
        $this->makeMailForDays(3);

        $this->artisan('mailbox:clean');

        $this->assertCount(1, InboundEmail::all());

    }


    /** @test */
    public function it_errors_if_max_age_inf()
    {
        $this->app['config']->set('mailbox.store_incoming_emails_for_days', INF);

        $this->makeMailForDays(3);

        $this->assertCount(3, InboundEmail::all());

        $this->artisan('mailbox:clean')
             ->expectsOutput('mailbox:clean is disabled because store_incoming_emails_for_days is set to INF.')
             ->assertExitCode(1);

        $this->assertCount(3, InboundEmail::all());
    }

    /**
     * @return void
     */
    private function makeMailForDays(int $days = 60): void
    {
        Collection::times($days)->each(function (int $index) {
            InboundEmail::forceCreate([
                'message' => Str::random(),
                'created_at' => Carbon::now()->subDays($index)->startOfDay(),
            ]);
        });
    }
}
