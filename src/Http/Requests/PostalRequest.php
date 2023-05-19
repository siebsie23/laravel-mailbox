<?php

namespace BeyondCode\Mailbox\Http\Requests;

use BeyondCode\Mailbox\InboundEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class PostalRequest extends FormRequest
{
    public function validator()
    {
        return Validator::make($this->all(), [
            'message' => 'required',
            'base64' => 'required',
        ]);
    }

    public function email()
    {
        /** @var InboundEmail $modelClass */
        $modelClass = config('mailbox.model');

        // Decode the base64 encoded message if the base64 parameter is true
        if ($this->get('base64')) {
            $message = base64_decode($this->get('message'));
        } else {
            $message = $this->get('message');
        }

        return $modelClass::fromMessage($message);
    }
}
