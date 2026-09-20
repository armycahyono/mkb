<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KasTunaiStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'funding_source' => ['required', 'string', 'max:255'],
            'transaction_date' => ['required', 'date'],
            'transaction_type' => ['required', Rule::in(['masuk', 'keluar'])],
            'category' => ['required', 'string', 'max:255'],
            'kas_category_id' => ['nullable', 'exists:kas_categories,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'person_name' => ['nullable', 'string', 'max:255'],
            'guru_id' => ['nullable', 'exists:gurus,id'],
            'pencairan_bank_id' => ['nullable', 'exists:pencairan_banks,id'],
            'bos_rencana_belanja_id' => ['nullable', 'exists:bos_rencana_belanja,id'],
            'perjadin_item_id' => ['nullable', 'exists:perjadin_items,id'],
            'parent_kas_id' => ['nullable', 'exists:kas_tunais,id'],
            'panjar_status' => ['nullable', Rule::in(['none', 'pending', 'partial', 'settled'])],
            'year' => ['nullable', 'integer', 'digits:4'],
            'sequence' => ['nullable', 'integer'],
            'evidence_number' => ['nullable', 'string'],
            'receipt_file' => ['nullable', 'string'],
        ];
    }
}
