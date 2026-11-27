@extends('install._layout')
@section('content')
  @php $blocked = collect($checks)->contains(fn ($c) => $c['required'] && ! $c['ok']); @endphp
  <h1>Set up Daily Table</h1>
  <p class="muted">This takes about two minutes. You need the MySQL database details from your hosting panel (cPanel or hPanel → MySQL Databases). Afterwards this page disappears.</p>
  <p class="muted">If this database already has Daily Table, the same form <b>updates</b> it: a backup is taken first and all customers, orders, wallets and invoices are kept.</p>

  <h2>Server check</h2>
  <ul class="checks">
    @foreach ($checks as $c)
      <li><span>{{ $c['label'] }}</span><span class="{{ $c['ok'] ? 'ok' : 'bad' }}">{{ $c['ok'] ? 'OK' : 'Missing' }}</span></li>
    @endforeach
  </ul>
  @if ($blocked)
    <div class="flash flash-error">Fix the items marked “Missing”, then reload this page. In your hosting panel: <b>Select PHP Version / PHP Configuration</b> → PHP 8.2 or newer and tick the listed extensions. For folders, give write access to <code>storage</code> and <code>bootstrap/cache</code> (permissions 755 or 775).</div>
  @endif

  @if (session('error'))<div class="flash flash-error">{{ session('error') }}</div>@endif
  @if ($errors->any())<div class="flash flash-error">Please check the fields marked in red.</div>@endif

  <form method="post" action="{{ route('install.store') }}" novalidate autocomplete="off">
    @csrf
    <fieldset>
      <legend>Database (MySQL)</legend>
      <p class="hint" style="margin:0 0 12px">Create a MySQL database and user in your hosting panel first and give the user ALL PRIVILEGES on it — or tick the box below. On most hosting the host is <code>localhost</code>.</p>
      <div class="fields">
        <div>
          <label for="db_host">Host</label>
          <input id="db_host" name="db_host" type="text" required value="{{ old('db_host', $defaults['db_host']) }}" @error('db_host') class="is-invalid" @enderror>
          @error('db_host')<span class="err">{{ $message }}</span>@enderror
        </div>
        <div>
          <label for="db_port">Port</label>
          <input id="db_port" name="db_port" type="number" required value="{{ old('db_port', $defaults['db_port']) }}" @error('db_port') class="is-invalid" @enderror>
          @error('db_port')<span class="err">{{ $message }}</span>@enderror
        </div>
        <div>
          <label for="db_database">Database name</label>
          <input id="db_database" name="db_database" type="text" required value="{{ old('db_database', $defaults['db_database']) }}" placeholder="u123456_dailytable" @error('db_database') class="is-invalid" @enderror>
          <span class="hint">On shared hosting it usually starts with your account name, e.g. <code>u123456_dailytable</code>.</span>
          @error('db_database')<span class="err">{{ $message }}</span>@enderror
        </div>
        <div>
          <label for="db_username">Database user</label>
          <input id="db_username" name="db_username" type="text" required value="{{ old('db_username', $defaults['db_username']) }}" @error('db_username') class="is-invalid" @enderror>
          @error('db_username')<span class="err">{{ $message }}</span>@enderror
        </div>
        <div class="wide">
          <label for="db_password">Database password</label>
          <input id="db_password" name="db_password" type="password" autocomplete="new-password" @error('db_password') class="is-invalid" @enderror>
          @error('db_password')<span class="err">{{ $message }}</span>@enderror
        </div>
        <div class="wide">
          <label class="check"><input type="checkbox" name="create_database" value="1" id="create_database" @checked(old('create_database'))> <span>Create the database and user for me <span class="hint">Needs a MySQL admin login such as root — usually only on your own VPS, not on shared hosting.</span></span></label>
        </div>
      </div>
      <div class="fields" id="admin-fields" style="margin-top:12px" @unless (old('create_database')) hidden @endunless>
        <div>
          <label for="admin_username">MySQL admin user</label>
          <input id="admin_username" name="admin_username" type="text" value="{{ old('admin_username', 'root') }}" @error('admin_username') class="is-invalid" @enderror>
          @error('admin_username')<span class="err">{{ $message }}</span>@enderror
        </div>
        <div>
          <label for="admin_password">MySQL admin password</label>
          <input id="admin_password" name="admin_password" type="password" autocomplete="new-password">
          <span class="hint">Used once to create the database; it is not saved.</span>
        </div>
      </div>
    </fieldset>

    <fieldset>
      <legend>Your website</legend>
      <div class="fields">
        <div>
          <label for="app_name">Site name</label>
          <input id="app_name" name="app_name" type="text" required value="{{ old('app_name', $defaults['app_name']) }}" @error('app_name') class="is-invalid" @enderror>
          <span class="hint">Shown on the website, invoices and messages.</span>
          @error('app_name')<span class="err">{{ $message }}</span>@enderror
        </div>
        <div>
          <label>Website address</label>
          <input type="text" value="{{ $url }}" disabled>
          <span class="hint">Taken from this page. The mobile apps must use the same address.</span>
        </div>
      </div>
      @unless (str_starts_with($url, 'https://'))
        <div class="flash flash-warn" style="margin-bottom:0">This page is not on <b>https://</b>. Turn on SSL for your domain in the hosting panel and open <code>https://…/install</code> before installing — payments and app logins need HTTPS.</div>
      @endunless
    </fieldset>

    <fieldset>
      <legend>Admin account</legend>
      <p class="hint" style="margin:0 0 12px">You — full control of kitchens, menus, staff, payments and settings. Skipped when updating a database that already has an admin.</p>
      <div class="fields">
        <div>
          <label for="admin_name">Full name</label>
          <input id="admin_name" name="admin_name" type="text" required autocomplete="name" value="{{ old('admin_name') }}" @error('admin_name') class="is-invalid" @enderror>
          @error('admin_name')<span class="err">{{ $message }}</span>@enderror
        </div>
        <div>
          <label for="admin_email">Email</label>
          <input id="admin_email" name="admin_email" type="email" required autocomplete="email" value="{{ old('admin_email') }}" @error('admin_email') class="is-invalid" @enderror>
          @error('admin_email')<span class="err">{{ $message }}</span>@enderror
        </div>
        <div>
          <label for="admin_pass">Password</label>
          <input id="admin_pass" name="admin_pass" type="password" required minlength="10" autocomplete="new-password" @error('admin_pass') class="is-invalid" @enderror>
          <span class="hint">At least 10 characters with upper- and lower-case letters and a number.</span>
          @error('admin_pass')<span class="err">{{ $message }}</span>@enderror
        </div>
        <div>
          <label for="admin_pass_confirmation">Repeat password</label>
          <input id="admin_pass_confirmation" name="admin_pass_confirmation" type="password" required autocomplete="new-password">
        </div>
      </div>
    </fieldset>

    <fieldset>
      <legend>Options</legend>
      <label class="check"><input type="checkbox" name="demo" value="1" @checked(old('demo'))> <span>Add demo data (Adyar kitchen, 14 dishes, menus for today and tomorrow, banners, offers and demo staff) <span class="hint">Good for trying the apps. Demo staff share one password shown at the end; remove them before real use.</span></span></label>
    </fieldset>

    <div class="actions">
      <button class="btn" type="submit" id="install-btn" @disabled($blocked)>Install Daily Table</button>
      <span class="hint">Creates the tables, roles and your admin account. Can take a minute — do not close the page.</span>
    </div>
  </form>
@endsection
@push('scripts')
<script>
  (function () {
    var box = document.getElementById('create_database'), panel = document.getElementById('admin-fields');
    box.addEventListener('change', function () { panel.hidden = !box.checked; });
    document.querySelector('form').addEventListener('submit', function () {
      var b = document.getElementById('install-btn'); b.disabled = true; b.textContent = 'Installing… please wait';
    });
  })();
</script>
@endpush
