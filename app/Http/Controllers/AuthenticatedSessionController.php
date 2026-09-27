<?php
namespace App\Http\Controllers;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
class AuthenticatedSessionController extends Controller { public function create(): Response { return Inertia::render('Auth/Login'); } public function store(Request $request): RedirectResponse { $data=$request->validate(['username'=>['required','string'],'password'=>['required','string']]); $user=User::where('username',$data['username'])->first(); if(!$user || !$user->is_active || !Hash::check($data['password'],$user->password)){ return back()->withErrors(['username'=>'Invalid username or password.'])->onlyInput('username'); } Auth::login($user); $request->session()->regenerate(); ActivityLog::create(['user_id'=>$user->user_id,'activity'=>'Logged in as '.$user->role,'timestamp'=>now()]); return redirect()->intended($user->isAdmin()?'/admin':'/employee'); } public function destroy(Request $request): RedirectResponse { if($request->user()){ ActivityLog::create(['user_id'=>$request->user()->user_id,'activity'=>'Logged out','timestamp'=>now()]); } Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect('/login'); } }
