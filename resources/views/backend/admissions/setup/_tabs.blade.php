<div class="tpc-adm-chips mb-24">
    <a href="{{ route('admissions.setup.programs') }}" class="{{ request()->routeIs('admissions.setup.programs') ? 'active' : '' }}">{{ ___('admissions.programs') }}</a>
    <a href="{{ route('admissions.setup.terms') }}" class="{{ request()->routeIs('admissions.setup.terms') ? 'active' : '' }}">{{ ___('admissions.terms') }}</a>
    <a href="{{ route('admissions.setup.requirements') }}" class="{{ request()->routeIs('admissions.setup.requirements') ? 'active' : '' }}">{{ ___('admissions.requirements') }}</a>
</div>
