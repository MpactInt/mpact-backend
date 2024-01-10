<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

class LogUserActivity
{
    public function handle($request, Closure $next)
    {
        if (Auth::check())  
        {
            
            $user = Auth::user();
            $currentDate = now()->toDateString();
            $lastActivity = ActivityLog::where('user_id', $user->id)
                                    ->whereDate('created_at', $currentDate)
                                    ->whereDate('log_type', "login")
                                    ->orderBy('login_time', 'desc')
                                    ->first();

            if ($lastActivity) 
            {
                $lastActivity->update([
                    // 'login_time' => now(),
                    'logout_time' => null,
                ]);
            } 
            else 
            {
                ActivityLog::create([
                    'log_type' => "login",
                    'user_id' => $user->id,
                    'login_time' => now(),
                ]);
            }

                
        }

        return $next($request);
    }
}
