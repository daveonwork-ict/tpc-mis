@extends('backend.master')

@section('title')
    {{ ___('common.module_manage') }}
@endsection

@section('css')
    <style>
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }

        .module-card {
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid var(--border-1);
        }

        .module-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .module-thumbnail {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .module-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .module-thumbnail .default-icon {
            font-size: 64px;
            color: rgba(255, 255, 255, 0.9);
        }

        .module-body {
            padding: 20px;
        }

        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .module-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0 0 4px 0;
        }

        .module-version {
            font-size: 12px;
            color: var(--text-muted);
            background: var(--bg-2);
            padding: 2px 8px;
            border-radius: 4px;
        }

        .module-description {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 63px;
        }

        .module-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .module-meta i {
            margin-right: 4px;
        }

        .module-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid var(--border-1);
        }

        .module-status {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .module-actions {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
        }

        .btn-action:hover {
            transform: scale(1.1);
        }

        .btn-toggle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-toggle:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        .btn-options {
            background: var(--bg-2);
            color: var(--text-dark);
        }

        .btn-options:hover {
            background: var(--bg-3);
        }

        .status-badge-active {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(40, 167, 69, 0.95);
            color: #ffffff;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
            z-index: 10;
        }

        .status-badge-inactive {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(108, 117, 125, 0.95);
            color: #ffffff;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
            z-index: 10;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .breadcrumb-area {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .breadcrumb-area a {
            color: var(--text-muted);
            text-decoration: none;
        }

        .breadcrumb-area a:hover {
            color: var(--primary);
        }

        .btn-upload {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        .btn-action:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .default-module-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(255, 193, 7, 0.95);
            color: #856404;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
            z-index: 10;
        }

        .pro-module-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(240, 30, 30, 0.95);
            color: #ffffff;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
            z-index: 10;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-container {
            background: #ffffff;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 24px;
            border-bottom: 1px solid var(--border-1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 32px;
            color: white;
            cursor: pointer;
            line-height: 1;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 24px;
            max-height: calc(90vh - 180px);
            overflow-y: auto;
            background: #ffffff;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333333;
            font-size: 14px;
        }

        .form-group label i {
            margin-right: 6px;
            color: var(--primary);
        }

        .required {
            color: #dc3545;
            margin-left: 4px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-text {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .file-upload-wrapper {
            position: relative;
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .file-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 40px 20px;
            border: 2px dashed var(--border-1);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .file-label:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .file-label i {
            font-size: 32px;
            color: #667eea;
        }

        .file-label span {
            font-size: 14px;
            color: var(--text-muted);
        }

        .upload-progress {
            margin-top: 20px;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
            width: 0%;
        }

        .progress-text {
            margin-top: 8px;
            font-size: 14px;
            color: var(--text-muted);
            text-align: center;
        }

        .upload-message {
            margin-top: 16px;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            display: none;
        }

        .upload-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }

        .upload-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border-1);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: #f8f9fa;
        }

        .btn {
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-secondary {
            background: #e9ecef;
            color: #333333;
        }

        .btn-secondary:hover {
            background: var(--bg-4);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .module-section {
            margin-bottom: 40px;
        }

        .module-section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border-1);
        }

        .module-section-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0;
        }

        .module-section-header .module-count {
            background: var(--bg-2);
            color: var(--text-muted);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }

        .module-section-header.active-section h3 {
            color: #28a745;
        }

        .module-section-header.active-section .module-count {
            background: #d4edda;
            color: #155724;
        }

        .module-section-header.inactive-section h3 {
            color: #6c757d;
        }

        .module-section-header.inactive-section .module-count {
            background: #e9ecef;
            color: #495057;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h2 class="mb-2">{{ ___('common.module_manage') }}</h2>
                <div class="breadcrumb-area">
                    <a href="{{ route('dashboard') }}">{{ ___('common.dashboard') }}</a>
                    <span>/</span>
                    <a href="#">{{ ___('common.settings') }}</a>
                    <span>/</span>
                    <span>{{ ___('common.module_manage') }}</span>
                </div>
            </div>
            <button class="btn-upload" onclick="openUploadModal()">
                <i class="las la-cloud-upload-alt"></i>
                {{ ___('common.upload_update_module') }}
            </button>
        </div>

        <!-- Module Grid -->
        @if (count($moduleData) > 0)
            @php
                // Separate modules into active and inactive groups
                $activeModules = collect($moduleData)
                    ->filter(function ($module) {
                        return $module['is_enabled'] === true;
                    })
                    ->values();

                $inactiveModules = collect($moduleData)
                    ->filter(function ($module) {
                        return $module['is_enabled'] === false;
                    })
                    ->values();
            @endphp

            <!-- Active Modules Section -->
            @if ($activeModules->count() > 0)
                <div class="module-section">
                    <div class="module-section-header active-section">
                        <h3><i class="las la-check-circle"></i> Active Modules</h3>
                        <span class="module-count">{{ $activeModules->count() }}
                            {{ $activeModules->count() === 1 ? 'Module' : 'Modules' }}</span>
                    </div>
                    <div class="module-grid">
                        @foreach ($activeModules as $module)
                            <div class="module-card">
                                <!-- Thumbnail -->
                                <div class="module-thumbnail">
                                    @if ($module['thumbnail'] && $module['thumbnail'] !== 'default-module.png')
                                        <img src="{{ asset('modules/' . strtolower($module['name']) . '/' . $module['thumbnail']) }}"
                                            alt="{{ $module['name'] }}"
                                            onerror="this.style.display='none'; var icon = document.createElement('i'); icon.className = 'las la-puzzle-piece default-icon'; this.parentElement.insertBefore(icon, this.parentElement.firstChild);">
                                    @else
                                        <i class="las la-puzzle-piece default-icon"></i>
                                    @endif

                                    {{-- Status Badge (Active/Inactive) --}}
                                    @if ($module['is_enabled'])
                                        <div class="status-badge-active">
                                            <i class="las la-check-circle"></i>
                                            Active
                                        </div>
                                    @endif

                                    @if ($module['is_default'])
                                        <div class="default-module-badge">
                                            <i class="las la-lock"></i>
                                            Default
                                        </div>
                                    @else
                                        <div class="pro-module-badge">
                                            <i class="las la-star"></i>
                                            Pro
                                        </div>
                                    @endif
                                </div>

                                <!-- Body -->
                                <div class="module-body">
                                    <div class="module-header">
                                        <div>
                                            <h3 class="module-title">{{ $module['name'] }}</h3>
                                            <span class="module-version">Version {{ $module['version'] }}</span>
                                        </div>
                                    </div>

                                    <p class="module-description">
                                        {{ $module['description'] ?: 'This is ' . $module['name'] . ' module for live virtual class and meeting in this system at a time. Thanks for using.' }}
                                    </p>

                                    <div class="module-meta">
                                        <span>
                                            <i class="las la-calendar"></i>
                                            Published on {{ $module['published_date'] }}
                                        </span>
                                    </div>

                                    @if ($module['verified'])
                                        <div class="module-meta">
                                            <span style="color: #28a745;">
                                                <i class="las la-check-circle"></i>
                                                Verified
                                            </span>
                                        </div>
                                    @endif

                                    @if (!$module['purchased'])
                                        <div class="module-meta">
                                            <span style="color: #dc3545;">
                                                Not Purchased
                                            </span>
                                        </div>
                                    @endif

                                    <!-- Footer -->
                                    <div class="module-footer">
                                        <div class="module-status">
                                            <span class="status-badge {{ $module['is_enabled'] ? 'active' : 'inactive' }}">
                                                {{ $module['is_enabled'] ? 'Active' : 'Disable' }}
                                            </span>
                                        </div>

                                        <div class="module-actions">
                                            @if ($module['purchased'])
                                                {{-- Module is purchased (directory exists) --}}
                                                @if (!$module['is_default'])
                                                    {{-- Regular module - can be toggled --}}
                                                    <button class="btn-action btn-toggle"
                                                        onclick="toggleModuleStatus('{{ $module['name'] }}', this)"
                                                        title="{{ $module['is_enabled'] ? 'Disable' : 'Enable' }} Module">
                                                        <i class="las la-power-off"></i>
                                                    </button>
                                                @else
                                                    {{-- Default module - cannot be toggled --}}
                                                    <button class="btn-action btn-toggle" disabled
                                                        title="Default module cannot be disabled">
                                                        <i class="las la-lock"></i>
                                                    </button>
                                                @endif
                                                <button class="btn-action btn-options"
                                                    onclick="showModuleOptions('{{ $module['name'] }}')"
                                                    title="More Options">
                                                    <i class="las la-ellipsis-v"></i>
                                                </button>
                                            @else
                                                {{-- Module not purchased (directory doesn't exist) --}}
                                                <button class="btn-action btn-toggle" disabled
                                                    title="Module not purchased. Please purchase to enable.">
                                                    <i class="las la-shopping-cart"></i>
                                                </button>
                                                <button class="btn-action btn-options" disabled
                                                    title="Purchase module to access options">
                                                    <i class="las la-ellipsis-v"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Inactive Modules Section -->
            @if ($inactiveModules->count() > 0)
                <div class="module-section">
                    <div class="module-section-header inactive-section">
                        <h3><i class="las la-times-circle"></i> Inactive Modules</h3>
                        <span class="module-count">{{ $inactiveModules->count() }}
                            {{ $inactiveModules->count() === 1 ? 'Module' : 'Modules' }}</span>
                    </div>
                    <div class="module-grid">
                        @foreach ($inactiveModules as $module)
                            <div class="module-card">
                                <!-- Thumbnail -->
                                <div class="module-thumbnail">
                                    @if ($module['thumbnail'] && $module['thumbnail'] !== 'default-module.png')
                                        <img src="{{ asset('modules/' . strtolower($module['name']) . '/' . $module['thumbnail']) }}"
                                            alt="{{ $module['name'] }}"
                                            onerror="this.style.display='none'; var icon = document.createElement('i'); icon.className = 'las la-puzzle-piece default-icon'; this.parentElement.insertBefore(icon, this.parentElement.firstChild);">
                                    @else
                                        <i class="las la-puzzle-piece default-icon"></i>
                                    @endif

                                    {{-- Status Badge (Active/Inactive) --}}
                                    @if ($module['is_enabled'])
                                        <div class="status-badge-active">
                                            <i class="las la-check-circle"></i>
                                            Active
                                        </div>
                                    @endif

                                    @if ($module['is_default'])
                                        <div class="default-module-badge">
                                            <i class="las la-lock"></i>
                                            Default
                                        </div>
                                    @else
                                        <div class="pro-module-badge">
                                            <i class="las la-star"></i>
                                            Pro
                                        </div>
                                    @endif
                                </div>

                                <!-- Body -->
                                <div class="module-body">
                                    <div class="module-header">
                                        <div>
                                            <h3 class="module-title">{{ $module['name'] }}</h3>
                                            <span class="module-version">Version {{ $module['version'] }}</span>
                                        </div>
                                    </div>

                                    <p class="module-description">
                                        {{ $module['description'] ?: 'This is ' . $module['name'] . ' module for live virtual class and meeting in this system at a time. Thanks for using.' }}
                                    </p>

                                    <div class="module-meta">
                                        <span>
                                            <i class="las la-calendar"></i>
                                            Published on {{ $module['published_date'] }}
                                        </span>
                                    </div>

                                    @if ($module['verified'])
                                        <div class="module-meta">
                                            <span style="color: #28a745;">
                                                <i class="las la-check-circle"></i>
                                                Verified
                                            </span>
                                        </div>
                                    @endif

                                    @if (!$module['purchased'])
                                        <div class="module-meta">
                                            <span style="color: #dc3545;">
                                                Not Purchased
                                            </span>
                                        </div>
                                    @endif

                                    <!-- Footer -->
                                    <div class="module-footer">
                                        <div class="module-status">
                                            <span class="status-badge {{ $module['is_enabled'] ? 'active' : 'inactive' }}">
                                                {{ $module['is_enabled'] ? 'Active' : 'Disable' }}
                                            </span>
                                        </div>

                                        <div class="module-actions">
                                            @if ($module['purchased'])
                                                {{-- Module is purchased (directory exists) --}}
                                                @if (!$module['is_default'])
                                                    {{-- Regular module - can be toggled --}}
                                                    <button class="btn-action btn-toggle"
                                                        onclick="toggleModuleStatus('{{ $module['name'] }}', this)"
                                                        title="{{ $module['is_enabled'] ? 'Disable' : 'Enable' }} Module">
                                                        <i class="las la-power-off"></i>
                                                    </button>
                                                @else
                                                    {{-- Default module - cannot be toggled --}}
                                                    <button class="btn-action btn-toggle" disabled
                                                        title="Default module cannot be disabled">
                                                        <i class="las la-lock"></i>
                                                    </button>
                                                @endif
                                                <button class="btn-action btn-options"
                                                    onclick="showModuleOptions('{{ $module['name'] }}')"
                                                    title="More Options">
                                                    <i class="las la-ellipsis-v"></i>
                                                </button>
                                            @else
                                                {{-- Module not purchased (directory doesn't exist) --}}
                                                
                                                <a href="{{ $module['purchase_link'] }}" class="btn-action btn-toggle"
                                                    title="Purchase module" target="_blank">
                                                    <i class="las la-shopping-cart"></i>
                                                </a>    
                                                <button class="btn-action btn-options" disabled
                                                    title="Purchase module to access options">
                                                    <i class="las la-ellipsis-v"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <div class="empty-state">
                <i class="las la-puzzle-piece"></i>
                <h3>{{ ___('common.no_modules_found') }}</h3>
                <p>{{ ___('common.no_modules_installed') }}</p>
            </div>
        @endif
    </div>

    <!-- Upload Module Modal -->
    <div id="uploadModuleModal" class="modal-overlay" style="display: none;">
        <div class="modal-container">
            <div class="modal-header">
                <h3><i class="las la-cloud-upload-alt"></i> Upload/Update Module</h3>
                <button class="modal-close" onclick="closeUploadModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="moduleUploadForm" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label for="purchaseCode">
                            <i class="las la-key"></i> Purchase Code
                            <span class="required">*</span>
                        </label>
                        <input type="text" id="purchaseCode" name="purchase_code" class="form-control"
                            placeholder="Enter your purchase code" required>
                        <small class="form-text">Enter the purchase code you received after buying the module</small>
                    </div>

                    <div class="form-group">
                        <label for="moduleZip">
                            <i class="las la-file-archive"></i> Module ZIP File
                            <span class="required">*</span>
                        </label>
                        <div class="file-upload-wrapper">
                            <input type="file" id="moduleZip" name="module_zip" accept=".zip" class="file-input"
                                required onchange="updateFileName(this)">
                            <label for="moduleZip" class="file-label">
                                <i class="las la-cloud-upload-alt"></i>
                                <span id="fileName">Choose ZIP file...</span>
                            </label>
                        </div>
                        <small class="form-text">Only .zip files are allowed. File name must match module name (e.g.,
                            Forums.zip)</small>
                    </div>

                    <div id="uploadProgress" class="upload-progress" style="display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                        <p class="progress-text" id="progressText">Uploading...</p>
                    </div>

                    <div id="uploadMessage" class="upload-message"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUploadModal()">
                    <i class="las la-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="uploadModule()">
                    <i class="las la-upload"></i> Upload Module
                </button>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        function toggleModuleStatus(moduleName, button) {
            // Show loading state
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="las la-spinner la-spin"></i>';
            button.disabled = true;

            $.ajax({
                url: '{{ url('settings/modules/toggle') }}/' + moduleName,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        // Update status badge
                        const card = button.closest('.module-card');
                        const statusBadge = card.querySelector('.status-badge');

                        if (response.status === 'active') {
                            statusBadge.classList.remove('inactive');
                            statusBadge.classList.add('active');
                            statusBadge.textContent = 'Active';
                            button.title = 'Disable Module';
                        } else {
                            statusBadge.classList.remove('active');
                            statusBadge.classList.add('inactive');
                            statusBadge.textContent = 'Disable';
                            button.title = 'Enable Module';
                        }

                        // Show success message
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    toastr.error('Failed to toggle module status');
                },
                complete: function() {
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                }
            });
        }

        function showModuleOptions(moduleName) {
            // You can implement a modal or dropdown menu here
            console.log('Show options for:', moduleName);
            toastr.info('Module options coming soon!');
        }

        // Upload Modal Functions
        window.openUploadModal = function() {
            document.getElementById('uploadModuleModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        window.closeUploadModal = function() {
            document.getElementById('uploadModuleModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            // Reset form
            document.getElementById('moduleUploadForm').reset();
            document.getElementById('fileName').textContent = 'Choose ZIP file...';
            document.getElementById('uploadProgress').style.display = 'none';
            document.getElementById('uploadMessage').className = 'upload-message';
            document.getElementById('uploadMessage').textContent = '';
        }

        window.updateFileName = function(input) {
            const fileName = input.files[0] ? input.files[0].name : 'Choose ZIP file...';
            document.getElementById('fileName').textContent = fileName;
        }

        window.uploadModule = function() {
            const form = document.getElementById('moduleUploadForm');
            const purchaseCode = document.getElementById('purchaseCode').value.trim();
            const moduleZip = document.getElementById('moduleZip').files[0];
            const uploadButton = event.target;
            const messageDiv = document.getElementById('uploadMessage');
            const progressDiv = document.getElementById('uploadProgress');
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');

            // Validation
            if (!purchaseCode) {
                showMessage('error', 'Please enter a purchase code');
                return;
            }

            if (purchaseCode.length < 24) {
                showMessage('error', 'Purchase code must be at least 24 characters long');
                return;
            }

            if (!moduleZip) {
                showMessage('error', 'Please select a ZIP file');
                return;
            }

            // Validate file extension
            if (!moduleZip.name.endsWith('.zip')) {
                showMessage('error', 'Only .zip files are allowed');
                return;
            }

            // Prepare form data
            const formData = new FormData();
            formData.append('purchase_code', purchaseCode);
            formData.append('module_zip', moduleZip);
            formData.append('_token', '{{ csrf_token() }}');

            // Disable button and show progress
            uploadButton.disabled = true;
            uploadButton.innerHTML = '<i class="las la-spinner la-spin"></i> Uploading...';
            progressDiv.style.display = 'block';
            messageDiv.className = 'upload-message';
            messageDiv.textContent = '';

            // Upload with progress
            $.ajax({
                url: '{{ route('modules.upload') }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const percentComplete = (e.loaded / e.total) * 100;
                            progressFill.style.width = percentComplete + '%';
                            progressText.textContent = 'Uploading... ' + Math.round(
                                    percentComplete) +
                                '%';
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    if (response.success) {
                        progressText.textContent = 'Upload complete!';
                        showMessage('success', response.message);

                        // Reload page after 2 seconds
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        showMessage('error', response.message);
                        uploadButton.disabled = false;
                        uploadButton.innerHTML = '<i class="las la-upload"></i> Upload Module';
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Failed to upload module';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMessage = xhr.responseJSON.error;
                    }

                    showMessage('error', errorMessage);
                    uploadButton.disabled = false;
                    uploadButton.innerHTML = '<i class="las la-upload"></i> Upload Module';
                    progressDiv.style.display = 'none';
                }
            });
        }

        function showMessage(type, message) {
            const messageDiv = document.getElementById('uploadMessage');
            messageDiv.className = 'upload-message ' + type;
            messageDiv.textContent = message;
        }

        // Close modal on outside click
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('uploadModuleModal');
            if (event.target === modal) {
                closeUploadModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('uploadModuleModal');
                if (modal.style.display === 'flex') {
                    closeUploadModal();
                }
            }
        });
    </script>
@endpush
