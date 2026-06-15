<nav class="navbar-figma">
    <div class="navbar-figma-container">
        <!-- Logo Section (Left) -->
        <div class="navbar-logo-group">
            <a href="{{ route('dashboard') }}" class="navbar-logo-link">
                <div class="logo-icon-box">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div class="logo-text-box">
                    <span class="logo-title">BIXO</span>
                    <span class="logo-subtitle">BUSINESS OS</span>
                </div>
            </a>
        </div>

        <!-- Center: Search Bar -->
        <div class="navbar-search-group">
            <div class="search-wrapper">
                <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" placeholder="Buscar" class="search-input" />
                <div class="search-shortcut">⌘K</div>
            </div>
        </div>

        <!-- Right: Icons + User -->
        <div class="navbar-right-group">
            <!-- Notifications -->
            <button class="navbar-icon-btn" title="Notificaciones">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </button>

            <!-- Settings -->
            <button class="navbar-icon-btn" title="Configuración">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </button>

            <!-- User Menu -->
            <div class="navbar-user-dropdown" x-data="{ open: false }">
                <button @click="open = !open" class="user-menu-btn">
                    <div class="user-avatar-circle">{{ Auth::user()->name[0] ?? 'A' }}</div>
                </button>

                <!-- Dropdown Menu -->
                <div @click.away="open = false"
                     :class="{'block': open, 'hidden': !open}"
                     class="hidden user-dropdown-panel">

                    <div class="dropdown-user-card">
                        <div class="dropdown-avatar-large">{{ Auth::user()->name[0] ?? 'A' }}</div>
                        <div class="dropdown-user-text">
                            <p class="dropdown-user-name">{{ Auth::user()->name }}</p>
                            <p class="dropdown-user-email">{{ Auth::user()->email }}</p>
                        </div>
                    </div>

                    <div class="dropdown-separator"></div>

                    <a href="{{ route('profile.edit') }}" class="dropdown-link">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>Mi Perfil</span>
                    </a>

                    <a href="#" class="dropdown-link">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Configuración</span>
                    </a>

                    <div class="dropdown-separator"></div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-link dropdown-logout">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span>Cerrar sesión</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
    .navbar-figma {
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        padding: 12px 24px;
        position: sticky;
        top: 0;
        z-index: 40;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    .navbar-figma-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        max-width: 100%;
        gap: 20px;
    }

    /* Logo Section */
    .navbar-logo-group {
        display: flex;
        align-items: center;
        flex-shrink: 0;
    }

    .navbar-logo-link {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        transition: opacity 0.2s ease;
    }

    .navbar-logo-link:hover {
        opacity: 0.8;
    }

    .logo-icon-box {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        flex-shrink: 0;
    }

    .logo-text-box {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .logo-title {
        font-size: 13px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1;
    }

    .logo-subtitle {
        font-size: 9px;
        color: #94a3b8;
        font-weight: 500;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        line-height: 1;
    }

    /* Search Bar */
    .navbar-search-group {
        flex: 1;
        display: flex;
        justify-content: center;
        min-width: 0;
    }

    .search-wrapper {
        position: relative;
        width: 100%;
        max-width: 380px;
    }

    .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        width: 18px;
        height: 18px;
        color: #94a3b8;
        flex-shrink: 0;
    }

    .search-input {
        width: 100%;
        padding: 9px 12px 9px 38px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        color: #1e293b;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s ease;
        outline: none;
    }

    .search-input::placeholder {
        color: #94a3b8;
    }

    .search-input:focus {
        background: #ffffff;
        border-color: #cbd5e1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .search-shortcut {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 11px;
        font-weight: 600;
        color: #94a3b8;
        background: #f1f5f9;
        padding: 4px 8px;
        border-radius: 4px;
        pointer-events: none;
    }

    /* Right Section */
    .navbar-right-group {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }

    .navbar-icon-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .navbar-icon-btn:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #1e293b;
    }

    /* User Dropdown */
    .navbar-user-dropdown {
        position: relative;
    }

    .user-menu-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        padding: 0;
    }

    .user-menu-btn:hover {
        background: #f8fafc;
    }

    .user-avatar-circle {
        width: 28px;
        height: 28px;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
        font-weight: 700;
    }

    /* Dropdown Panel */
    .user-dropdown-panel {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        width: 240px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        z-index: 50;
    }

    .dropdown-user-card {
        padding: 12px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid #e2e8f0;
    }

    .dropdown-avatar-large {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 14px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .dropdown-user-text {
        min-width: 0;
    }

    .dropdown-user-name {
        font-size: 13px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }

    .dropdown-user-email {
        font-size: 11px;
        color: #94a3b8;
        margin: 2px 0 0 0;
    }

    .dropdown-separator {
        height: 1px;
        background: #e2e8f0;
    }

    .dropdown-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        color: #475569;
        text-decoration: none;
        font-size: 13px;
        transition: all 0.2s ease;
        border-left: 2px solid transparent;
        background: transparent;
        border: none;
        width: 100%;
        text-align: left;
        cursor: pointer;
        font-family: inherit;
    }

    .dropdown-link:hover {
        background: #f8fafc;
        color: #1e293b;
        border-left-color: #6366f1;
    }

    .dropdown-link svg {
        flex-shrink: 0;
        opacity: 0.6;
    }

    .dropdown-link:hover svg {
        opacity: 1;
    }

    .dropdown-logout {
        color: #ef4444;
    }

    .dropdown-logout:hover {
        background: #fef2f2;
        border-left-color: #ef4444;
    }

    @media (max-width: 768px) {
        .navbar-search-group {
            display: none;
        }

        .logo-text-box {
            display: none;
        }

        .navbar-figma-container {
            gap: 12px;
        }
    }
</style>
