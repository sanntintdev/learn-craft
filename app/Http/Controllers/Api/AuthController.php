<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginUserRequest;
use App\Http\Requests\Api\RegisterUserRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterUserRequest $request)
    {
        try
        // Create a new user with the request data and the hashed password
        // Send the email verification notification to the user
        // Return a success response) 
        {
            $hashedPassword = Hash::make($request->password);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $hashedPassword,
            ]);

            event(new Registered($user));

            $token = $user->createToken(
                'API TOKEN for ' . $user->name,
                ['*'],
                now()->addMonth()
            )->plainTextToken;

            return $this->success('User registered successfully. Please check your email for the verification link.', 201, [
                'token' => $token,
            ]);
        } catch (BadRequestException $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function login(LoginUserRequest $request)
    {
        try {
            if (!Auth::attempt($request->only(['email', 'password']))) {
                return $this->error('Invalid credentials', 401);
            }

            $user = User::FirstWhere('email', $request->email);

            $token = $user->createToken(
                'API TOKEN for ' . $user->name,
                ['*'],
                now()->addMonth()
            )->plainTextToken;


            return $this->success('Authenticated.', 200, ['token' => $token]);
        } catch (BadRequestException $e) {
            return $this->error('User not found', 400);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success('Logged out successfully');
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        try {
            $user = User::findOrFail($id);

            if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
                return $this->error('Invalid verification link', 400);
            }

            if ($user->hasVerifiedEmail()) {
                return $this->error('Email already verified', 400);
            }

            $user->markEmailAsVerified();

            return $this->success('Email verified successfully');
        } catch (ModelNotFoundException $e) {
            return $this->error('User not found', 404);
        }
    }
}
