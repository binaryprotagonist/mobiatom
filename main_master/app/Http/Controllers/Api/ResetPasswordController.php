<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Foundation\Auth\ResetsPasswords;

class ResetPasswordController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    public function resetPassword(Request $request)
    {
        $input = $request->json()->all();
        $validate = $this->validations($input, "reset");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating reason", $this->unprocessableEntity);
        }
        $token = $request->token;

        $password_resets = \DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!is_object($password_resets)) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (\Hash::check($token, $password_resets->token)) {
            $password_resets = \DB::table('users')
                ->where('email', $request->email)
                ->update(['password' => \Hash::make($request->password)]);

            \DB::table('password_resets')
                ->where('email', $request->email)
                ->delete();

            return prepareResult(true, [], [], 'Password reset successfully', $this->success);
        } else {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "reset") {
            $validator = \Validator::make($input, [
                'email' => 'required',
                'token' => 'required',
                'password' => [
                    'required',
                    'min:6',
                    'confirmed'
                ]
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
