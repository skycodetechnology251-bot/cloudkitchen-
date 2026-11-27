@extends('install._layout')
@section('title', 'Finishing an update')
@section('content')
  <h1>Finishing an update</h1>
  <p class="muted">New files have been uploaded, but the database has not been updated yet.</p>
  <ul class="muted" style="padding-left:20px">
    <li>Admin: sign in and open <b>Admin → System → Back up and update now</b> (<a href="{{ url('/admin/system') }}">open</a>).</li>
    <li>Or on the server: <code>php artisan dailytable:update</code></li>
  </ul>
  <p class="hint">Customers: please try again in a minute.</p>
@endsection
