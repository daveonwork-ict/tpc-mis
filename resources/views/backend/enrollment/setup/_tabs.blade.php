<div class="tpc-adm-chips mb-24">
    <a href="{{ route('enrollment.setup.curricula') }}" class="{{ request()->routeIs('enrollment.setup.curricula*') ? 'active' : '' }}">{{ ___('enrollment.curricula') }}</a>
    <a href="{{ route('enrollment.setup.courses') }}" class="{{ request()->routeIs('enrollment.setup.courses*') ? 'active' : '' }}">{{ ___('enrollment.course_catalog') }}</a>
    <a href="{{ route('enrollment.setup.fees') }}" class="{{ request()->routeIs('enrollment.setup.fees*') ? 'active' : '' }}">{{ ___('enrollment.fee_schedule') }}</a>
    <a href="{{ route('enrollment.setup.settings') }}" class="{{ request()->routeIs('enrollment.setup.settings*') ? 'active' : '' }}">{{ ___('enrollment.enrollment_settings') }}</a>
</div>
