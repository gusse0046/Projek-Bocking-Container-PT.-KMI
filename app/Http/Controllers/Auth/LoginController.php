<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected function redirectTo()
    {
        $user = auth()->user();
        
        switch ($user->role) {
            case 'export':
                return '/export/dashboard';
            case 'import':
                return '/import/dashboard';
            case 'forwarder':
                return '/forwarder/dashboard';
            default:
                return '/home';
        }
    }

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}