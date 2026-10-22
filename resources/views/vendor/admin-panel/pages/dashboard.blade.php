@extends('admin-panel::layouts.app')
@section('content')
<h1>{{ __('ledgo.platform') }}</h1><div class="grid gap-4"><x-admin-panel::card><h2>{{ __('ledgo.businesses') }}</h2><p>{{ \App\Models\Business::count() }}</p></x-admin-panel::card><x-admin-panel::card><h2>{{ __('ledgo.users') }}</h2><p>{{ \App\Models\User::count() }}</p></x-admin-panel::card></div>
@endsection
