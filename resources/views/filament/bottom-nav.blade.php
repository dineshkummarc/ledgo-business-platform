@if(\Filament\Facades\Filament::getTenant())
<style>@media(max-width:640px){.fi-sidebar{display:none!important}.fi-main{padding-bottom:90px!important}.ledgo-nav{position:fixed;bottom:0;left:0;right:0;background:#14532d;color:white;display:flex;justify-content:space-around;padding:8px;z-index:40}.ledgo-nav a{min-height:44px;display:flex;align-items:center;padding:8px 20px}}@media(min-width:641px){.ledgo-nav{display:none}}.fi-btn,.fi-input{min-height:44px}</style>
<nav class="ledgo-nav" aria-label="{{ __('ledgo.navigation') }}"><a href="{{ \Filament\Facades\Filament::getPanel('app')->getUrl() }}">{{ __('ledgo.home') }}</a><a href="/onboarding">{{ __('ledgo.create_business') }}</a></nav>
@endif
