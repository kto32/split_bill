<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\SplitBill\InputDto;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class SplitBillRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'regex:/^\d+(\.\d{1,2})?$/',
                'numeric',
                'gt:0',
            ],
            'tip_percent' => [
                'required',
                'regex:/^\d+(\.\d{1,2})?$/',
                'numeric',
                'min:0',
                'max:100',
            ],
            'participants' => [
                'required',
                'array',
                'min:1',
                'max:50',
            ],
            'participants.*.name' => [
                'required',
                'string',
                'max:255',
            ],
            'participants.*.weight' => [
                'sometimes',
                'integer',
                'min:1',
            ],
        ];
    }

    public function toDto(): InputDto
    {
        $valid = $this->validated();

        return InputDto::fromArray($valid);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validation failed.',
            'errors' => $validator->errors(),
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));
    }
}
