<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlutterwaveWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "event" => "required|string",
            "data" => "required|array",
            "data.id" => "required|string",
            "data.tx_ref" => "required|string",
            "data.flw_ref" => "required|string",
            
        ];
    }
}
