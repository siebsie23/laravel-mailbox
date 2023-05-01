<?php

namespace BeyondCode\Mailbox\Tests\Controllers;

use BeyondCode\Mailbox\Facades\Mailbox;
use BeyondCode\Mailbox\InboundEmail;
use BeyondCode\Mailbox\Tests\TestCase;
use Illuminate\Testing\TestResponse;
use Symfony\Component\Mime\Email;

class MailgunTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']['mailbox.driver'] = 'mailgun';
    }

    /** @test */
    public function it_verifies_mailgun_signatures()
    {
        $this->post('/laravel-mailbox/mailgun/mime', [
            'body-mime' => 'mime',
            'timestamp' => 1548104992,
            'token' => 'something',
            'signature' => 'something',
        ])->assertStatus(401);


        $this->callWithValidToken('mime')
            ->assertStatus(200);
    }

    /** @test */
    public function it_verifies_fresh_timestamps()
    {
        $timestamp = now()->subMinutes(5)->timestamp;
        $token = uniqid();

        $this->app['config']['mailbox.services.mailgun.key'] = '12345';

        $validSignature = hash_hmac('sha256', $timestamp.$token, '12345');

        $this->post('/laravel-mailbox/mailgun/mime', [
            'body-mime' => 'mime',
            'timestamp' => $timestamp,
            'token' => $token,
            'signature' => $validSignature,
        ])->assertStatus(401);
    }


    /**
     * @test
     */
    public function it_processes_mails_correctly()
    {
        $message = new Email();
        $message->subject("subject");
        $message->from("from@example.com");
        $message->to("to@example.com");
        $message->text("this is body text");

        Mailbox::shouldReceive("callMailboxes", function(InboundEmail $email){
            return $email->subject() === 'this is body text'
                && $email->from() === 'from@example.com'
                && $email->to()[0]->getEmail() === 'to@example.com'
                && $email->body() === 'this is body text';
        });

        $this->callWithValidToken($message->toString())
            ->assertStatus(200);
    }


    private function callWithValidToken($mimeMail = 'mime'): TestResponse
    {
        $timestamp = time();
        $token = uniqid();

        $this->app['config']['mailbox.services.mailgun.key'] = '12345';

        $validSignature = hash_hmac('sha256', $timestamp.$token, '12345');

        return $this->post('/laravel-mailbox/mailgun/mime', [
            'body-mime' => $mimeMail,
            'timestamp' => $timestamp,
            'token' => $token,
            'signature' => $validSignature,
        ]);
    }

}
