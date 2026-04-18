<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'forwarder_code'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed'
    ];

    /**
     * Get the forwarder for this user
     */
    public function forwarder()
    {
        return $this->belongsTo(Forwarder::class, 'forwarder_code', 'code');
    }

    /**
     * Check if user is export staff
     */
    public function isExport()
    {
        return $this->role === 'export';
    }

    /**
     * Check if user is import staff
     */
    public function isImport()
    {
        return $this->role === 'import';
    }

    /**
     * Check if user is forwarder
     */
    public function isForwarder()
    {
        return $this->role === 'forwarder';
    }

    /**
     * Get user's dashboard route based on role
     */
    public function getDashboardRoute()
    {
        switch ($this->role) {
            case 'export':
                return route('export.dashboard');
            case 'import':
                return route('import.dashboard');
            case 'forwarder':
                return route('forwarder.dashboard');
            default:
                return route('dashboard');
        }
    }

    /**
     * Scope for specific role
     */
    public function scopeWithRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope for forwarder users
     */
    public function scopeForwarders($query)
    {
        return $query->where('role', 'forwarder');
    }

    /**
     * Get forwarder name if applicable
     */
    public function getForwarderNameAttribute()
    {
        return $this->forwarder ? $this->forwarder->name : null;
    }
}