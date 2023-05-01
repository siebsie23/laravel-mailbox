<?php

namespace BeyondCode\Mailbox\Http\Requests;

use BeyondCode\Mailbox\InboundEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class MailCareRequest extends FormRequest
{
    public function rules()
    {
        return [
            "content_type" => "required|in:message/rfc2822",
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            "content_type" => $this->headers->get("Content-type"),
        ]);
    }

    public function email()
    {
        return InboundEmail::fromMessage($this->getContent());
    }
}
