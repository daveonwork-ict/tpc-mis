<aside class="sidebar" id="sidebar">

    <x-sidebar-header />

    <div class="sidebar-menu srollbar">
        <div class="sidebar-menu-section">

            <ul class="sidebar-dropdown-menu">
                <li class="sidebar-menu-item {{ set_menu(['student-panel-dashboard*']) }}">
                    <a href="{{ route('student-panel-dashboard.index') }}" class="parent-item-content">
                        <i class="las la-desktop"></i>
                        <span class="on-half-expanded">{{ ___('dashboard.Dashboard') }}</span>
                    </a>
                </li>
                @if (hasFeature('academic'))
                    <li class="sidebar-menu-item {{ set_menu(['student-panel-subject-list*']) }}">
                        <a href="{{ route('student-panel-subject-list.index') }}" class="parent-item-content">
                            <i class="las la-book-reader"></i>
                            <span class="on-half-expanded">{{ ___('settings.subject') }}</span>
                        </a>
                    </li>
                @endif
                @if (hasFeature('online_examination'))
                    <li class="sidebar-menu-item {{ set_menu(['student-panel-online-examination*']) }}">
                        <a href="{{ route('student-panel-online-examination.index') }}" class="parent-item-content">
                            <i class="las la-laptop-code"></i>
                            <span class="on-half-expanded">{{ ___('online-examination.online_examination') }}</span>
                        </a>
                    </li>
                @endif
                <li class="sidebar-menu-item {{ set_menu(['student-panel-notice*']) }}">
                    <a href="{{ route('student-panel-notices.index') }}" class="parent-item-content">
                        <i class="las la-bullhorn"></i>
                        <span class="on-half-expanded">{{ ___('common.notice_board') }}</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>
