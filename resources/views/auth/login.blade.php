@extends('layouts.app')

@section('content')
<div class="min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #0f5132 0%, #198754 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7 col-sm-9">
                <div class="card shadow-lg border-0" style="border-radius: 15px;">
                    <div class="card-body p-5">
                        <!-- Logo/Header Section -->
                        <div class="text-center mb-4">
                            <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-user text-white" style="font-size: 2rem;"></i>
                            </div>
                            <h3 class="fw-bold text-dark mb-2">{{ __('Welcome Back') }}</h3>
                            <p class="text-muted mb-0">{{ __('Please sign in to your account') }}</p>
                        </div>

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <!-- Email Field -->
                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold text-dark">
                                    <i class="fas fa-envelope me-2 text-success"></i>{{ __('Email Address') }}
                                </label>
                                <div class="input-group">
                                    <input id="email" 
                                           type="email" 
                                           class="form-control form-control-lg @error('email') is-invalid @enderror" 
                                           name="email" 
                                           value="{{ old('email') }}" 
                                           required 
                                           autocomplete="email" 
                                           autofocus
                                           placeholder="Enter your email"
                                           style="border-radius: 10px; border: 2px solid #e9ecef; padding: 15px;">
                                </div>
                                @error('email')
                                    <div class="invalid-feedback d-block mt-2">
                                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Password Field -->
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold text-dark">
                                    <i class="fas fa-lock me-2 text-success"></i>{{ __('Password') }}
                                </label>
                                <div class="input-group">
                                    <input id="password" 
                                           type="password" 
                                           class="form-control form-control-lg @error('password') is-invalid @enderror" 
                                           name="password" 
                                           required 
                                           autocomplete="current-password"
                                           placeholder="Enter your password"
                                           style="border-radius: 10px; border: 2px solid #e9ecef; padding: 15px;">
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block mt-2">
                                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Remember Me Checkbox -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="remember" 
                                           id="remember" 
                                           {{ old('remember') ? 'checked' : '' }}
                                           style="border: 2px solid #198754;">
                                    <label class="form-check-label text-muted fw-medium" for="remember">
                                        {{ __('Keep me signed in') }}
                                    </label>
                                </div>
                            </div>

                            <!-- Login Button -->
                            <div class="mb-3">
                                <button type="submit" 
                                        class="btn btn-success btn-lg w-100 fw-bold shadow-sm"
                                        style="border-radius: 10px; padding: 15px; background: linear-gradient(135deg, #198754 0%, #20c997 100%); border: none; transition: all 0.3s ease;">
                                    <i class="fas fa-sign-in-alt me-2"></i>{{ __('Sign In') }}
                                </button>
                            </div>

                            <!-- Forgot Password Link -->
                            @if (Route::has('password.request'))
                                <div class="text-center">
                                    <a class="text-success text-decoration-none fw-medium" 
                                       href="{{ route('password.request') }}"
                                       style="transition: all 0.3s ease;">
                                        <i class="fas fa-key me-1"></i>{{ __('Forgot Your Password?') }}
                                    </a>
                                </div>
                            @endif
                        </form>

                        <!-- Additional Security Info -->
                        <div class="mt-4 pt-4 border-top">
                            <div class="text-center">
                                <small class="text-muted d-flex align-items-center justify-content-center">
                                    <i class="fas fa-shield-alt text-success me-2"></i>
                                    {{ __('Your data is secured with industry-standard encryption') }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS for enhanced styling -->
<style>
    /* Input focus effects */
    .form-control:focus {
        border-color: #198754 !important;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25) !important;
        transform: translateY(-2px);
        transition: all 0.3s ease;
    }

    /* Button hover effects */
    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(25, 135, 84, 0.3) !important;
        background: linear-gradient(135deg, #157347 0%, #1aa179 100%) !important;
    }

    /* Link hover effects */
    a:hover {
        color: #157347 !important;
        text-decoration: underline !important;
    }

    /* Card animation */
    .card {
        animation: slideUp 0.6s ease-out;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Checkbox styling */
    .form-check-input:checked {
        background-color: #198754;
        border-color: #198754;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .card-body {
            padding: 2rem !important;
        }
        
        .min-vh-100 {
            padding: 2rem 0;
        }
    }
</style>
@endsection