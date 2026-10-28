<aside class="sidebar" id="sidebar">
    <x-sidebar-header />

    <div class="sidebar-menu srollbar">
        <div class="sidebar-menu-section">

            <ul class="sidebar-dropdown-menu">
                <li class="sidebar-menu-item {{ set_menu(['parent-panel-dashboard*']) }}">
                    <a href="{{ route('parent-panel-dashboard.index') }}" class="parent-item-content">
                        <i class="las la-desktop"></i>
                        <span class="on-half-expanded">{{ ___('common.Dashboard') }}</span>
                    </a>
                </li>
                @if (hasFeature('academic'))
                    <li class="sidebar-menu-item {{ set_menu(['parent-panel-subject-list*']) }}">
                        <a href="{{ route('parent-panel-subject-list.index') }}" class="parent-item-content">
                            <i class="las la-book-reader"></i>
                            <span class="on-half-expanded">{{ ___('settings.subject') }}</span>
                        </a>
                    </li>
                @endif
                <li class="sidebar-menu-item {{ set_menu(['parent-panel-notice*']) }}">
                    <a href="{{ route('parent-panel-notices.index') }}" class="parent-item-content">
                        <i class="las la-bell"></i>
                        <span class="on-half-expanded">{{ ___('common.notice_board') }}</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>
