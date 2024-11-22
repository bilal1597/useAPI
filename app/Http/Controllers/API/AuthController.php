<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;

class AuthController extends Controller
{
    public function signup(Request $request)
    {
        ///validator for different methods with validation

        $userValidate = Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required'
            ]

        );

        ///Signup error response

        if ($userValidate->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $userValidate->errors()->all()
            ], 401);
        }

        ////////////// user create
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password
        ]);
        ///// user created response
        return response()->json([
            'status' => true,
            'message' => 'User Created Successfully',
            'user' => $user,
        ], 200);
    }



    public function login(Request $request)
    {

        $userValidate = Validator::make(
            $request->all(),
            [
                'email' => 'required|email',
                'password' => 'required'
            ]
        );
        //////////// login fail response
        if ($userValidate->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Login Failed',
                'errors' => $userValidate->errors()->all()
            ], 404);
        }

        //////////// auth check for user in login with token creation
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $authUser = Auth::user();
            return response()->json([
                'status' => true,
                'message' => 'Logged In Successfully',
                'token' => $authUser->createToken("API_Token")->plainTextToken,
                'token_type' => 'bearer'
            ], 200);
        } //////////// login error response
        else {
            return response()->json([
                'status' => false,
                'message' => 'Email or Password does not Match',
                'errors' => $userValidate->errors()->all()
            ], 404);
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();

        return response()->json([
            'status' => true,
            'user' => $user,
            'message' => 'You Logout Successfully',
        ], 200);
    }
}
