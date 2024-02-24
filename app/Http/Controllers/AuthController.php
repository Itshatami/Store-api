<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => "required",
            'email' => "required|email|unique:users,email",
            'password' => "required|min:5",
            'c_password' => "required|same:password"
        ]);
        if ($validator->fails()) {
            return $this->eResponse($validator->messages(), 400);
        }
        try {
            DB::beginTransaction();
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'province_id' => 1,
                'city_id' => 1
            ]);
            if ($user) {
                DB::commit();
                $token = $user->createToken('myToken')->accessToken;
            } else {
                throw new Exception("cant create user");
            }
            return $this->sResponse(['user' => $user, 'token' => $token]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->eResponse($e->getMessage(), 400);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => "required|email|unique:users,email",
            'password' => "required|min:5",
        ]);
        if ($validator->fails()) {
            return $this->eResponse($validator->messages(), 400);
        }
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->eResponse('user not found', 422);
        }
        if (!Hash::check($user->password, $request->password)) {
            return $this->eResponse('password does not match', 422);
        };

        $token = $user->createToken('myToken')->accessToken;
        return $this->sResponse(['user' => $user, 'token' => $token]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        return $this->sResponse('', 'your logged out');
    }
}
