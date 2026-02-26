<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Mail\VerificationCodeMail;

class AccountController extends Controller
{
    public function index()
    {
        return response()->json(Account::all());
    }

    // ---------------- LOGIN ----------------
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $account = Account::where('email', $request->email)->first();

        if (!$account || !Hash::check($request->password, $account->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Check if email verified
        if (!$account->email_verified_at) {
            return response()->json(['message' => 'Please verify your email first'], 403);
        }

        $token = $account->createToken('auth_token')->plainTextToken;

        $cookie = cookie(
            'jem8_token', 
            $token, 
            60*24*30, 
            '/', null, true, true, false, 'None'
        );

        return response()->json([
            'message' => 'Login successful',
            'account' => $account
        ])->withCookie($cookie);
    }

    // ---------------- LOGOUT ----------------
    public function logout(Request $request)
    {
        $cookie = cookie()->forget('jem8_token');

        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out successfully'])
            ->withCookie($cookie);
    }

    // ---------------- REGISTER ----------------
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'required|string|unique:accounts,phone_number',
            'email' => 'required|string|email|unique:accounts,email',
            'password' => 'required|string|min:6',
        ]);

        $account = Account::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone_number' => $validated['phone_number'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Generate 6-digit verification code
        $code = rand(100000, 999999);
        $account->update([
            'email_verification_code' => $code,
            'email_verification_expires_at' => now()->addMinutes(10),
        ]);

        // Send code via email
        Mail::to($account->email)->send(new VerificationCodeMail($code));

        // event(new Registered($account));

        return response()->json([
            'message' => 'Account created. Please verify your email with the code sent.',
            'data' => $account
        ], 201);
    }

    // ---------------- VERIFY EMAIL ----------------
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
        ]);

        $account = Account::where('email', $request->email)->first();

        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        if ($account->email_verified_at) {
            return response()->json(['message' => 'Email already verified']);
        }

        if ($account->email_verification_code != $request->code || 
            $account->email_verification_expires_at < now()) {
            return response()->json(['message' => 'Invalid or expired code'], 400);
        }

        $account->update([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_expires_at' => null,
        ]);
        return response()->json(['message' => 'Email verified successfully']);
    }

    // ---------------- FORGOT PASSWORD ----------------
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $account = Account::where('email', $request->email)->first();
        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $code = rand(100000, 999999); // 6-digit code
        $account->update([
            'password_reset_code' => $code,
            'password_reset_expires_at' => now()->addMinutes(15),
        ]);

        // Send code via email
        Mail::to($account->email)->send(new \App\Mail\VerificationCodeMail($code));

        return response()->json(['message' => 'Password reset code sent to email']);
    }

    // ---------------- RESET PASSWORD WITH CODE ----------------
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $account = Account::where('email', $request->email)->first();
        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        if ($account->password_reset_code != $request->code || $account->password_reset_expires_at < now()) {
            return response()->json(['message' => 'Invalid or expired code'], 400);
        }

        $account->update([
            'password' => Hash::make($request->password),
            'password_reset_code' => null,
            'password_reset_expires_at' => null,
        ]);

        return response()->json(['message' => 'Password reset successful']);
    }

    // ---------------- SHOW, UPDATE, DELETE ----------------
    public function show(string $id)
    {
        $account = Account::findOrFail($id);
        return response()->json($account);
    }

    public function update(Request $request, string $id)
    {
        $account = Account::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|unique:accounts,email,' . $id,
            'password' => 'sometimes|string|min:6',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $account->update($validated);

        return response()->json([
            'message' => 'Account updated successfully',
            'data' => $account
        ]);
    }

    public function destroy(string $id)
    {
        $account = Account::findOrFail($id);
        $account->delete();

        return response()->json([
            'message' => 'Account deleted successfully'
        ]);
    }
}