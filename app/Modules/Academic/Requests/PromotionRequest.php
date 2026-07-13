<?php

namespace App\Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromotionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from_classroom_id' => 'required|exists:classrooms,id',
            'from_academic_year_id' => 'required|exists:academic_years,id',
            'to_classroom_id' => 'nullable|exists:classrooms,id',
            'to_academic_year_id' => 'required|exists:academic_years,id',
            'promotions' => 'required|array|min:1',
            'promotions.*.student_id' => 'required|exists:students,id',
            'promotions.*.status' => 'required|in:naik,tinggal,lulus,pindah,keluar',
            'promotions.*.notes' => 'nullable|string',
        ];
    }
}
