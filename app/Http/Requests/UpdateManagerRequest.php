<?php

namespace App\Http\Requests;

use App\Models\Survey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateManagerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $manager = $this->route('manager');
        return [
            'name' => 'required|string',
            'email' => 'required|email|string|unique:users,email,'.$manager->id,
            'password' => [
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols()
            ]
        ];
    }
}
