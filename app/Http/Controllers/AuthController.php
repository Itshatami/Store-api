<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => "required",
            'email' => "required|email|unique:users,email",
            'password' => "required|min:5",
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
                $token = $user->createToken('token')->accessToken;
            } else {
                throw new Exception("cant create user");
            }
            return $this->sResponse(['user' => $user, 'token' => $token]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->eResponse($e->getMessage(), 400);
        }
    }
}
