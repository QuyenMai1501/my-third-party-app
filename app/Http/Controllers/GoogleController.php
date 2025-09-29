<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()
                ->route('login')
                ->withErrors(['google' => 'Can not verify with Google']);
        }
        $user = User::where('provider', 'google')
            ->where('provider_id', $googleUser->getId())
            ->first();

        if (! $user && $googleUser->getEmail()) {
            $user = User::where('email', $googleUser->getEmail())->first();
        }

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'password' => Hash::make(Str::random(32)),
                'provider_id' => $googleUser->getId(),
                'provider' => 'google',
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ]);
        } else {
            $user = $user->forcefill([
                'name' => $googleUser->getName(),
                'provider_id' => $googleUser->getId(),
                'provider' => 'google',
                'avatar' => $googleUser->getAvatar(),
            ])->save();
        }
        Auth::login($user, true);

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Login By Google Success!');
    }
}