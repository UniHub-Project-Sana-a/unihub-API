<?php
namespace App\Http\Requests\V1\College;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCollegeRequest extends FormRequest {
    public function authorize(): bool { return true; }
    
    public function rules(): array {
        // 1. استلام الكائن من الراوت
        $college = $this->route('college'); 
        
        // 2. استخراج الـ ID (سواء كان الكائن موجوداً أو هو مجرد ID نصي)
        // في بعض الحالات النادرة إذا فشل الـ Binding قد يعود كنص، لذا نحتاط
        $id = $college instanceof \App\Models\College ? $college->college_id : $college;

        return [
            'college_name' => ['sometimes', 'string', 'max:100', 'unique:colleges,college_name,' . $id . ',college_id'],
            'college_code' => ['nullable', 'string', 'max:20', 'unique:colleges,college_code,' . $id . ',college_id'],
        ];
    }
}