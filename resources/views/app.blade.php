<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Portal EXIM - Container Booking System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-green: #0d4f3c;
            --secondary-green: #1a5f4f;
            --accent-green: #2d8a6b;
            --light-green: #4a9d7f;
            --gold-accent: #d4af37;
            --dark-bg: #0a3b2e;
            --text-light: #ffffff;
            --text-dark: #2c3e50;
            --shadow: rgba(13, 79, 60, 0.3);
        }

        body {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(90deg, var(--dark-bg) 0%, var(--primary-green) 100%);
            border-bottom: 3px solid var(--gold-accent);
            box-shadow: 0 4px 20px var(--shadow);
        }

        .navbar-brand {
            color: var(--text-light) !important;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
        }

        .navbar-nav .nav-link:hover {
            color: var(--gold-accent) !important;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 32px var(--shadow);
            backdrop-filter: blur(10px);
            margin-bottom: 2rem;
        }

        .card-header {
            background: linear-gradient(90deg, var(--accent-green) 0%, var(--light-green) 100%);
            color: var(--text-light);
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
            border-bottom: 2px solid var(--gold-accent);
            font-weight: 600;
            font-size: 1.2rem;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--accent-green) 0%, var(--light-green) 100%);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(45, 138, 107, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(45, 138, 107, 0.4);
            background: linear-gradient(45deg, var(--light-green) 0%, var(--accent-green) 100%);
        }

        .form-control {
            border: 2px solid rgba(45, 138, 107, 0.3);
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--accent-green);
            box-shadow: 0 0 0 0.2rem rgba(45, 138, 107, 0.25);
        }

        .logo-container {
            background: var(--gold-accent);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--primary-green);
            font-weight: bold;
            font-size: 1.5rem;
        }

        .page-title {
            color: var(--text-light);
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 2rem;
        }

        .container-fluid {
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-dark">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                    <div class="logo-container me-3">
                        <i class="fas fa-ship"></i>
                    </div>
                    Portal EXIM
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto">
                        @auth
                            @if(auth()->user()->role == 'export')
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('export.dashboard') }}">
                                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                                    </a>
                                </li>
                            @elseif(auth()->user()->role == 'import')
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('import.dashboard') }}">
                                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                                    </a>
                                </li>
                            @elseif(auth()->user()->role == 'forwarder')
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('forwarder.dashboard') }}">
                                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                                    </a>
                                </li>
                            @endif
                        @endauth
                    </ul>

                    <ul class="navbar-nav ms-auto">
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle me-2"></i>
                                    {{ Auth::user()->name }}
                                    @if(Auth::user()->role == 'forwarder')
                                        <span class="badge bg-warning ms-2">{{ Auth::user()->forwarder_code }}</span>
                                    @else
                                        <span class="badge bg-info ms-2">{{ ucfirst(Auth::user()->role) }}</span>
                                    @endif
                                </a>

                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        <i class="fas fa-sign-out-alt me-2"></i>{{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>