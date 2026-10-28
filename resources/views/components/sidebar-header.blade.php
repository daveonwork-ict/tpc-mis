<div class="sidebar-header">
    <div class="sidebar-logo">
        <a href="{{ route('dashboard') }}" class="tpc-side-brand">
            <input type="hidden" name="global_light_logo" id="global_light_logo" value="{{ @globalAsset(setting('light_logo'), '154X38.webp') }}" />
            <input type="hidden" name="global_dark_logo" id="global_dark_logo" value="{{ @globalAsset(setting('dark_logo'), '154X38.webp') }}" />

            <span class="tpc-side-seals full-logo">
                <img class="tpc-side-seal" src="{{ @globalAsset(setting('dark_logo'), '40X40.webp') }}" alt="{{ settingLocale('application_name') }}">
                @if (setting('province_logo'))
                    <img class="tpc-side-seal" src="{{ @globalAsset(setting('province_logo'), '40X40.webp') }}" alt="{{ ___('common.official_seal_of_the_province_of_tarlac') }}">
                @endif
            </span>
            <span class="tpc-side-name full-logo">
                <small>{{ ___('common.republic_of_the_philippines') }}</small>
                <strong>{{ settingLocale('application_name') }}</strong>
                <span>{{ ___('common.province_of_tarlac') }}</span>
            </span>
            <img class="half-logo" src="{{ globalAsset(setting('favicon'), '40X40.webp') }}" alt="{{ settingLocale('application_name') }}">
        </a>
    </div>

    <button class="half-expand-toggle sidebar-toggle">
        <img class="arrow_lite" src="{{ global_asset('backend') }}/assets/images/icons/collapse-arrow.svg" alt="" />
        <img class="arrow_dark" src="{{ global_asset('backend') }}/assets/images/icons/collapse-arrow2.svg" alt="" />
    </button>
    <button class="close-toggle sidebar-toggle">
        <img class="arrow_lite" src="{{ global_asset('backend') }}/assets/images/icons/collapse-arrow.svg" alt="" />
        <img class="arrow_dark" src="{{ global_asset('backend') }}/assets/images/icons/collapse-arrow2.svg" alt="" />
    </button>
</div>