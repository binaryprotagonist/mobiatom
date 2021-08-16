<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Password;
use Illuminate\Contracts\Auth\UserProvider;

class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    /**
    * Send a reset link to the given user.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    */
    public function sendResetLink(Request $request)
    {
        $input = $request->json()->all();
        $validate = $this->validations($input, "forgot");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating forgot", $this->unprocessableEntity);
        }

        $user = User::where('email', $request->email)
        ->where('usertype', 1)
        ->first();

        if (!is_object($user)) {
            return prepareResult(false, [], [], "We can\'t find a user with that e-mail address.", $this->unprocessableEntity);
        }

        if (is_object($user)) {

            \DB::table('password_resets')
            ->where('email', $user->email)
            ->delete();

            $token = \Str::random(60);
            $final_token = hash('sha256', $token);
    
            \DB::table('password_resets')->insert([
                'email' => $user->email,
                'token' => Hash::make($final_token),
                'created_at' => now()
            ]);

            // Send mail
            $this->dispatch(new \App\Jobs\ForgotPasswordJob($user, $final_token));

            return prepareResult(true, [], [], trans(Password::RESET_LINK_SENT), $this->success);
        }
        
        return prepareResult(false, [], [], trans(Password::INVALID_USER), $this->unprocessableEntity);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "forgot") {
            $validator = \Validator::make($input, [
                'email' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
