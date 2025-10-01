<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Unit Cost')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="images/logo.png">
    @stack('head')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div x-data="{ sidebarOpen: false, sidebarCollapsed: JSON.parse(localStorage.getItem('sidebarCollapsed') ?? 'false') }"
         x-init="$watch('sidebarOpen', () => setTimeout(() => window.dispatchEvent(new Event('resize')), 200)); $watch('sidebarCollapsed', v => localStorage.setItem('sidebarCollapsed', JSON.stringify(v)))"
         class="min-h-screen flex">
        <!-- Sidebar -->
        <div :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', sidebarCollapsed ? 'w-20 lg:w-20' : 'w-64 lg:w-64']" 
             class="fixed inset-y-0 left-0 z-50 bg-green-700 shadow-lg transform transition-all duration-300 ease-in-out shrink-0 lg:shrink-0 flex-none lg:translate-x-0 lg:static lg:inset-0 overflow-hidden">
            
            <!-- Logo/Brand -->
            <div class="flex items-center h-20 px-4 border-b border-green-600">
                <div class="bg-white rounded-xl border border-green-200 shadow-sm p-2">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-8 w-auto object-contain">
                </div>
                <h1 x-show="!sidebarCollapsed" x-transition.opacity.duration.200 class="ml-3 text-xl font-bold text-white tracking-wide">Unit Cost</h1>
            </div>

            <!-- Sidebar Navigation -->
            <nav class="px-4 py-6">
                <div class="mb-6">
                    <h3 x-show="!sidebarCollapsed" x-transition.opacity.duration.150 class="text-xs font-semibold text-green-200 uppercase tracking-wider mb-3">MENU UTAMA</h3>
                </div>
                
                <ul class="space-y-2">
                    @if(auth()->user()->hasPermission('view_dashboard'))
                    <li>
                        <a href="{{ route('dashboard') }}" title="Dashboard" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('dashboard') ? 'bg-green-800' : '' }}">
                            <i class="fas fa-tachometer-alt w-5 text-center" :class="sidebarCollapsed ? 'mr-0' : 'mr-3'"></i>
                            <span x-show="!sidebarCollapsed" x-transition.opacity.duration.150>Dashboard</span>
                        </a>
                    </li>
                    @endif
                    

                    @if(auth()->user()->hasPermission('manage_users'))
                    <li>
                        <a href="{{ route('users.index') }}" title="Users" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('users.*') ? 'bg-green-800' : '' }}">
                            <i class="fas fa-users w-5 text-center" :class="sidebarCollapsed ? 'mr-0' : 'mr-3'"></i>
                            <span x-show="!sidebarCollapsed" x-transition.opacity.duration.150>Users</span>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasPermission('manage_roles'))
                    <li>
                        <a href="{{ route('roles.index') }}" title="Roles" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('roles.*') ? 'bg-green-800' : '' }}">
                            <i class="fas fa-user-shield w-5 text-center" :class="sidebarCollapsed ? 'mr-0' : 'mr-3'"></i>
                            <span x-show="!sidebarCollapsed" x-transition.opacity.duration.150>Roles</span>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasPermission('manage_permissions'))
                    <li>
                        <a href="{{ route('permissions.index') }}" title="Permissions" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('permissions.*') ? 'bg-green-800' : '' }}">
                            <i class="fas fa-key w-5 text-center" :class="sidebarCollapsed ? 'mr-0' : 'mr-3'"></i>
                            <span x-show="!sidebarCollapsed" x-transition.opacity.duration.150>Permissions</span>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasPermission('manage_kategori'))
                    <li>
                        <a href="{{ route('kategori.index') }}" title="Kategori" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('kategori.*') ? 'bg-green-800' : '' }}">
                            <i class="fas fa-tags w-5 text-center" :class="sidebarCollapsed ? 'mr-0' : 'mr-3'"></i>
                            <span x-show="!sidebarCollapsed" x-transition.opacity.duration.150>Kategori</span>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasPermission('manage_layanan'))
                    <li>
                        <a href="{{ route('layanan.index') }}" title="Layanan" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('layanan.*') ? 'bg-green-800' : '' }}">
                            <i class="fas fa-stethoscope w-5 text-center" :class="sidebarCollapsed ? 'mr-0' : 'mr-3'"></i>
                            <span x-show="!sidebarCollapsed" x-transition.opacity.duration.150>Layanan</span>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasPermission('access_simulation'))
                    <li>
                        <a href="{{ route('simulation.index') }}" title="Simulasi" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('simulation.index') ? 'bg-green-800' : '' }}">
                            <i class="fas fa-calculator w-5 text-center" :class="sidebarCollapsed ? 'mr-0' : 'mr-3'"></i>
                            <span x-show="!sidebarCollapsed" x-transition.opacity.duration.150>Simulasi</span>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasPermission('access_simulation_qty'))
                    <li>
                        <a href="{{ route('simulation.qty') }}" title="Simulasi (Qty)" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('simulation.qty') ? 'bg-green-800' : '' }}">
                            <i class="fas fa-layer-group w-5 text-center" :class="sidebarCollapsed ? 'mr-0' : 'mr-3'"></i>
                            <span x-show="!sidebarCollapsed" x-transition.opacity.duration.150>Simulasi (Qty)</span>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasPermission('manage_simulation_qty_presets'))
                    <li>
                        <a href="{{ route('simulation.qty.presets.page') }}" title="Master Preset Qty" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('simulation.qty.presets.page') ? 'bg-green-800' : '' }}">
                            <i class="fas fa-sliders-h w-5 text-center" :class="sidebarCollapsed ? 'mr-0' : 'mr-3'"></i>
                            <span x-show="!sidebarCollapsed" x-transition.opacity.duration.150>Master Preset Qty</span>
                        </a>
                    </li>
                    @endif

                </ul>

                
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 min-w-0 flex flex-col lg:ml-0">
            <!-- Top Navigation Bar -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between h-16 px-6">
                    <div class="flex items-center">
                        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden mr-2 p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                            <i class="fas fa-bars"></i>
                        </button>
                        <button @click="sidebarCollapsed = !sidebarCollapsed" class="hidden lg:inline-flex mr-4 p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100" :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                            <i :class="sidebarCollapsed ? 'fas fa-angles-right' : 'fas fa-angles-left'"></i>
                        </button>
                        
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800">@yield('title', 'Dashboard')</h2>
                            <p class="text-sm text-gray-500">Unit Cost System</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="text-sm text-gray-600 hidden sm:block">
                            {{ now()->format('d M Y, H:i') }}
                        </div>

                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 rounded-md border border-gray-200 hover:bg-gray-50 text-gray-700">
                                <div class="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-sm"></i>
                                </div>
                                <div class="hidden md:flex flex-col items-start leading-tight">
                                    <span class="text-sm font-medium">{{ auth()->user()->name }}</span>
                                    <span class="text-xs text-gray-500">{{ auth()->user()->role->display_name ?? 'User' }}</span>
                                </div>
                                <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                            </button>

                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-md shadow-lg overflow-hidden z-50">
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <div class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</div>
                                    <div class="text-xs text-gray-500">{{ auth()->user()->role->display_name ?? 'User' }}</div>
                                </div>
                                <div class="py-1">
                                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                                            <i class="fas fa-sign-out-alt mr-2"></i> Keluar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 min-w-0 p-6 bg-gray-50">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            {{ session('success') }}
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            {{ session('error') }}
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>

        <!-- Mobile Overlay -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false" 
             class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"></div>
    </div>
    @stack('scripts')
</body>
</html>
