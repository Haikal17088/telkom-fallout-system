@php
    $kuUsers = collect($users ?? []);
    $kuTotal = $kuUsers->count();
    $kuAdminCount = $kuUsers->where('role', 'admin')->count();
    $kuUserCount = $kuUsers->where('role', 'user')->count();
    $kuInitialMode = old('_ku_modal_mode');
    $kuInitialUserId = old('_ku_modal_user_id');
@endphp

<div class="ku-wrap">
    <header class="ku-head">
        <div>
            <div class="ku-kicker">ADMINISTRASI</div>
            <h2>Kelola User</h2>
            <p>Kelola akun pengguna Telkom Fallout System.</p>
        </div>
        <button type="button" class="ku-add-btn" data-ku-open-create>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            <span>Tambah User</span>
        </button>
    </header>

    <section class="ku-stats" aria-label="Ringkasan pengguna">
        <article class="ku-stat-card">
            <span class="ku-stat-icon ku-stat-icon-total" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM20 8v6M23 11h-6"/></svg>
            </span>
            <div><span class="ku-stat-label">Total User</span><strong>{{ number_format($kuTotal, 0, ',', '.') }}</strong></div>
        </article>
        <article class="ku-stat-card">
            <span class="ku-stat-icon ku-stat-icon-admin" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/></svg>
            </span>
            <div><span class="ku-stat-label">Administrator</span><strong>{{ number_format($kuAdminCount, 0, ',', '.') }}</strong></div>
        </article>
        <article class="ku-stat-card">
            <span class="ku-stat-icon ku-stat-icon-user" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>
            </span>
            <div><span class="ku-stat-label">Pengguna</span><strong>{{ number_format($kuUserCount, 0, ',', '.') }}</strong></div>
        </article>
    </section>

    <section class="ku-panel">
        <div class="ku-toolbar">
            <label class="ku-search" for="kuSearchInput">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input id="kuSearchInput" type="search" placeholder="Cari nama, username, atau email..." autocomplete="off">
            </label>
            <label class="ku-role-filter" for="kuRoleFilter">
                <span class="ku-sr-only">Filter role</span>
                <select id="kuRoleFilter">
                    <option value="all">Semua Role</option>
                    <option value="admin">Admin</option>
                    <option value="user">User</option>
                </select>
            </label>
        </div>

        <div class="ku-table-card">
            <table class="ku-table">
                <thead>
                    <tr>
                        <th class="ku-col-number">No</th>
                        <th>Pengguna</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th class="ku-action-head">Aksi</th>
                    </tr>
                </thead>
                <tbody id="kuUsersTableBody">
                    @forelse($kuUsers as $user)
                        <tr class="ku-user-row"
                            data-ku-role="{{ strtolower($user->role ?? 'user') }}"
                            data-ku-search="{{ strtolower(($user->name ?? '') . ' ' . ($user->username ?? '') . ' ' . ($user->email ?? '')) }}">
                            <td class="ku-row-number">{{ $loop->iteration }}</td>
                            <td>
                                <div class="ku-user-person">
                                    <span class="ku-avatar">{{ strtoupper(substr((string) ($user->name ?? 'U'), 0, 1)) }}</span>
                                    <span class="ku-person-meta">
                                        <strong class="ku-name">{{ $user->name }}</strong>
                                        <small>
                                            @if($user->id === auth()->id())
                                                <span class="ku-self-badge">Anda</span>
                                            @else
                                                Bergabung {{ optional($user->created_at)->format('d M Y') ?? '-' }}
                                            @endif
                                        </small>
                                    </span>
                                </div>
                            </td>
                            <td><span class="ku-username">@if(filled($user->username)){{ '@' . $user->username }}@else—@endif</span></td>
                            <td class="ku-email">{{ $user->email }}</td>
                            <td><span class="ku-role {{ $user->role === 'admin' ? 'ku-role-admin' : 'ku-role-user' }}">
                                @if($user->role === 'admin')
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg>
                                @endif
                                {{ ucfirst($user->role ?? 'user') }}
                            </span></td>
                            <td class="ku-action-cell">
                                <div class="ku-actions">
                                    <button type="button"
                                        class="ku-icon-action ku-edit-trigger"
                                        aria-label="Edit {{ $user->name }}"
                                        title="Edit user"
                                        data-ku-open-edit
                                        data-user-id="{{ $user->id }}"
                                        data-user-name="{{ $user->name }}"
                                        data-user-username="{{ $user->username }}"
                                        data-user-email="{{ $user->email }}"
                                        data-user-role="{{ $user->role }}"
                                        data-update-url="{{ route('admin.users.update', $user->id) }}">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                    </button>
                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="ku-delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ku-icon-action ku-delete-trigger" aria-label="Hapus {{ $user->name }}" title="Hapus user">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 10v6M14 10v6"/></svg>
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="ku-icon-action ku-delete-trigger is-disabled" aria-label="Akun sendiri tidak dapat dihapus" title="Akun yang sedang digunakan tidak dapat dihapus" disabled>
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 10v6M14 10v6"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="ku-empty-row"><td colspan="6" class="ku-empty">Belum ada user.</td></tr>
                    @endforelse
                    <tr id="kuNoMatchRow" class="ku-no-match-row" hidden><td colspan="6" class="ku-empty">Tidak ada user yang cocok dengan pencarian atau filter.</td></tr>
                </tbody>
            </table>
        </div>
        <footer class="ku-table-footer">
            <span id="kuUserCountText">Menampilkan {{ $kuTotal }} dari {{ $kuTotal }} user</span>
            <span class="ku-security-note"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg> Pengelolaan akun khusus administrator</span>
        </footer>
    </section>
</div>

{{-- MODAL TAMBAH / EDIT USER: satu form yang memakai route controller yang sudah ada --}}
<div class="ku-user-modal-overlay" id="kuUserModalOverlay" aria-hidden="true">
    <section class="ku-user-modal" role="dialog" aria-modal="true" aria-labelledby="kuUserModalTitle" tabindex="-1">
        <header class="ku-modal-head">
            <div class="ku-modal-heading-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM20 8v6M23 11h-6"/></svg>
            </div>
            <div class="ku-modal-heading-copy">
                <div class="ku-kicker">ADMINISTRASI</div>
                <h3 id="kuUserModalTitle">Tambah User</h3>
                <p id="kuUserModalSubtitle">Buat akun baru untuk pengguna sistem.</p>
            </div>
            <button type="button" class="ku-modal-x" data-ku-close-modal aria-label="Tutup">×</button>
        </header>

        <form id="kuUserForm" action="{{ route('admin.users.store') }}" method="POST" novalidate>
            @csrf
            <input type="hidden" name="_method" id="kuUserMethodOverride" value="PUT" disabled>
            <input type="hidden" name="_ku_modal_mode" id="kuUserModalMode" value="{{ old('_ku_modal_mode') }}">
            <input type="hidden" name="_ku_modal_user_id" id="kuUserModalUserId" value="{{ old('_ku_modal_user_id') }}">

            <div class="ku-modal-body">
                <div class="ku-form-intro" id="kuUserFormIntro">Lengkapi informasi akun di bawah ini.</div>
                <div class="ku-form-grid">
                    <div class="ku-field">
                        <label for="kuUserName">Nama Lengkap <span>*</span></label>
                        <input id="kuUserName" name="name" type="text" value="{{ in_array(old('_ku_modal_mode'), ['create', 'edit'], true) ? old('name') : '' }}" placeholder="Masukkan nama lengkap" autocomplete="name" required maxlength="255">
                        @error('name')<small class="ku-field-error" data-ku-error="name">{{ $message }}</small>@else<small class="ku-field-error" data-ku-error="name" hidden></small>@enderror
                    </div>
                    <div class="ku-field">
                        <label for="kuUserUsername">Username <span>*</span></label>
                        <input id="kuUserUsername" name="username" type="text" value="{{ in_array(old('_ku_modal_mode'), ['create', 'edit'], true) ? old('username') : '' }}" placeholder="Masukkan username" autocomplete="username" required maxlength="50">
                        @error('username')<small class="ku-field-error" data-ku-error="username">{{ $message }}</small>@else<small class="ku-field-error" data-ku-error="username" hidden></small>@enderror
                    </div>
                    <div class="ku-field ku-field-full">
                        <label for="kuUserEmail">Email <span>*</span></label>
                        <input id="kuUserEmail" name="email" type="email" value="{{ in_array(old('_ku_modal_mode'), ['create', 'edit'], true) ? old('email') : '' }}" placeholder="nama@telkom.co.id" autocomplete="email" required maxlength="255">
                        @error('email')<small class="ku-field-error" data-ku-error="email">{{ $message }}</small>@else<small class="ku-field-error" data-ku-error="email" hidden></small>@enderror
                    </div>
                    <div class="ku-field">
                        <label for="kuUserPassword">Password <span id="kuPasswordRequiredMark">*</span></label>
                        <div class="ku-password-wrap">
                            <input id="kuUserPassword" name="password" type="password" placeholder="Buat password" autocomplete="new-password" minlength="8" required>
                            <button type="button" class="ku-password-toggle" data-ku-toggle-password="kuUserPassword" aria-label="Tampilkan password" title="Tampilkan password">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                            </button>
                        </div>
                        <small class="ku-field-hint" id="kuPasswordHint">Gunakan minimal 8 karakter.</small>
                        @error('password')<small class="ku-field-error" data-ku-error="password">{{ $message }}</small>@else<small class="ku-field-error" data-ku-error="password" hidden></small>@enderror
                    </div>
                    <div class="ku-field">
                        <label for="kuUserPasswordConfirmation">Konfirmasi Password <span id="kuPasswordConfirmationRequiredMark">*</span></label>
                        <div class="ku-password-wrap">
                            <input id="kuUserPasswordConfirmation" name="password_confirmation" type="password" placeholder="Ulangi password" autocomplete="new-password" required>
                            <button type="button" class="ku-password-toggle" data-ku-toggle-password="kuUserPasswordConfirmation" aria-label="Tampilkan konfirmasi password" title="Tampilkan konfirmasi password">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                            </button>
                        </div>
                        <small class="ku-field-error" data-ku-error="password_confirmation" hidden></small>
                    </div>
                    <div class="ku-field ku-field-full">
                        <label for="kuUserRole">Role <span>*</span></label>
                        <select id="kuUserRole" name="role" required>
                            <option value="user" {{ old('role', 'user') === 'user' ? 'selected' : '' }}>User</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                        @error('role')<small class="ku-field-error" data-ku-error="role">{{ $message }}</small>@else<small class="ku-field-error" data-ku-error="role" hidden></small>@enderror
                    </div>
                </div>
            </div>

            <footer class="ku-modal-footer">
                <button type="button" class="ku-btn ku-btn-secondary" data-ku-close-modal>Batal</button>
                <button type="submit" class="ku-btn ku-btn-primary" id="kuUserSubmitBtn">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    <span>Simpan User</span>
                </button>
            </footer>
        </form>
    </section>
</div>

{{-- POPUP KONFIRMASI DAN PESAN --}}
<div class="ku-ui-alert-overlay" id="kuUiAlertOverlay" aria-hidden="true">
    <div class="ku-ui-alert-card" role="dialog" aria-modal="true" aria-labelledby="kuUiAlertTitle">
        <div class="ku-ui-alert-icon" id="kuUiAlertIcon"></div>
        <h3 id="kuUiAlertTitle">Informasi</h3>
        <div class="ku-ui-alert-message" id="kuUiAlertMessage"></div>
        <div class="ku-ui-alert-actions" id="kuUiAlertActions">
            <button type="button" class="ku-ui-alert-btn ku-ui-alert-cancel" id="kuUiAlertCancel">Batal</button>
            <button type="button" class="ku-ui-alert-btn ku-ui-alert-confirm" id="kuUiAlertConfirm">OK</button>
        </div>
    </div>
</div>

<style>
    .ku-wrap{width:100%;min-width:0;color:var(--ink,#20161A);font-family:'Inter',sans-serif}
    .ku-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:20px}
    .ku-kicker{font-size:10px;font-weight:800;letter-spacing:.1em;color:#C8102E;margin-bottom:6px}
    .ku-head h2{margin:0;font-family:'Space Grotesk',sans-serif;font-size:24px;font-weight:700;color:var(--ink,#20161A)}
    .ku-head p{margin:5px 0 0;font-size:12.5px;color:var(--ink-lo,#7A6B6F)}
    .ku-add-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:42px;padding:10px 15px;border:0;border-radius:11px;background:#C8102E;color:#fff;font:700 12px 'Inter',sans-serif;box-shadow:0 8px 18px rgba(200,16,46,.15);cursor:pointer;transition:transform .18s ease,background .18s ease,box-shadow .18s ease}
    .ku-add-btn:hover{background:#A80E27;transform:translateY(-1px);box-shadow:0 12px 24px rgba(200,16,46,.2)}
    .ku-add-btn svg,.ku-stat-icon svg,.ku-role svg,.ku-icon-action svg,.ku-search svg,.ku-security-note svg,.ku-modal-heading-icon svg,.ku-password-toggle svg,.ku-btn svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;flex:none}
    .ku-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:18px}
    .ku-stat-card{display:flex;align-items:center;gap:13px;min-width:0;padding:16px 18px;background:rgba(255,255,255,.82);border:1px solid #eee7e6;border-radius:15px;box-shadow:0 8px 22px -22px rgba(58,4,16,.28)}
    .ku-stat-icon{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;flex:none}
    .ku-stat-icon-total{background:#FCEAEC;color:#C8102E}.ku-stat-icon-admin{background:#F8E9EC;color:#8A0F26}.ku-stat-icon-user{background:#EAF2FF;color:#2563EB}
    .ku-stat-label{display:block;margin-bottom:4px;color:#80767A;font-size:11px;font-weight:600}.ku-stat-card strong{display:block;color:#271D20;font:700 22px 'Space Grotesk',sans-serif;line-height:1.1}
    .ku-panel{overflow:hidden;background:rgba(255,255,255,.82);border:1px solid #eee7e6;border-radius:17px;box-shadow:0 9px 28px -24px rgba(58,4,16,.32)}
    .ku-toolbar{display:flex;align-items:center;gap:12px;padding:16px;border-bottom:1px solid #F0E8E7}
    .ku-search{display:flex;align-items:center;gap:10px;flex:1;min-width:0;height:44px;padding:0 13px;border:1px solid #E7DEDD;border-radius:11px;background:#fff;color:#93878A}
    .ku-search svg{width:19px;height:19px;color:#988D90}.ku-search input{width:100%;min-width:0;height:100%;padding:0;border:0;outline:0;background:transparent;color:#2A2023;font:12px 'Inter',sans-serif}.ku-search input::placeholder{color:#A49A9D}
    .ku-role-filter select{width:145px;height:44px;padding:0 12px;border:1px solid #E7DEDD;border-radius:11px;background:#fff;color:#55494D;font:12px 'Inter',sans-serif;cursor:pointer}
    .ku-table-card{width:100%;overflow:auto;-webkit-overflow-scrolling:touch}
    .ku-table{width:100%;min-width:850px;border-collapse:collapse;table-layout:fixed;font-size:12px}
    .ku-table th{padding:13px 14px;background:#FBF9F9;color:#796D71;text-align:left;font-size:10px;font-weight:800;letter-spacing:.025em;white-space:nowrap;border-bottom:1px solid #EEE7E7}
    .ku-table td{padding:13px 14px;border-bottom:1px solid #F1EAEA;color:#3C3034;vertical-align:middle}
    .ku-table tbody tr:last-child td{border-bottom:0}.ku-table tbody tr{transition:background .15s ease}.ku-table tbody tr:hover{background:rgba(252,246,247,.75)}
    .ku-table .ku-col-number{width:52px}.ku-table th:nth-child(2){width:24%}.ku-table th:nth-child(3){width:15%}.ku-table th:nth-child(4){width:24%}.ku-table th:nth-child(5){width:12%}.ku-table .ku-action-head{width:106px;text-align:center}.ku-row-number{color:#9A8F92;font-variant-numeric:tabular-nums}
    .ku-user-person{display:flex;align-items:center;gap:11px;min-width:0}.ku-avatar{width:39px;height:39px;border-radius:12px;display:grid;place-items:center;flex:none;background:#FCEFF1;color:#B40022;font-weight:800;font-size:13px}.ku-person-meta{display:flex;flex-direction:column;gap:4px;min-width:0}.ku-name{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#292024;font-weight:700;font-size:12px}.ku-person-meta small{display:block;color:#9A8F92;font-size:10px;white-space:nowrap}.ku-self-badge{display:inline-block;padding:2px 7px;border-radius:999px;background:#E9F9EF;color:#23834A;font-size:9px;font-weight:800}
    .ku-username{display:block;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#6C5D62}.ku-email{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#71656A!important}
    .ku-role{display:inline-flex;align-items:center;gap:6px;padding:6px 9px;border-radius:999px;font-size:10px;font-weight:800;white-space:nowrap}.ku-role svg{width:13px;height:13px;stroke-width:2}.ku-role-admin{background:#FFF0F1;color:#C8102E}.ku-role-user{background:#EEF5FF;color:#2563EB}
    .ku-action-cell{text-align:center}.ku-actions{display:flex;justify-content:center;align-items:center;gap:8px}.ku-icon-action{width:32px;height:32px;padding:0;border:0;border-radius:9px;background:transparent;display:grid;place-items:center;cursor:pointer;transition:background .15s ease,transform .15s ease}.ku-icon-action:hover{transform:translateY(-1px)}.ku-icon-action svg{width:17px;height:17px}.ku-edit-trigger{color:#2563EB}.ku-edit-trigger:hover{background:#EDF4FF}.ku-delete-trigger{color:#E11D48}.ku-delete-trigger:hover{background:#FFF0F2}.ku-icon-action.is-disabled{color:#C8BFC2;cursor:not-allowed}.ku-icon-action.is-disabled:hover{transform:none;background:transparent}.ku-delete-form{display:inline-flex;margin:0;padding:0}
    .ku-empty{text-align:center!important;padding:34px!important;color:#93878A!important;font-size:12px}.ku-no-match-row[hidden]{display:none!important}.ku-table-footer{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:13px 16px;border-top:1px solid #F0E8E7;color:#8B8084;font-size:11px}.ku-security-note{display:inline-flex;align-items:center;gap:5px;color:#8B8084}.ku-security-note svg{width:15px;height:15px;color:#26945B}

    html.ku-modal-lock,body.ku-modal-lock,html.ku-ui-lock,body.ku-ui-lock{overflow:hidden!important}
    .ku-user-modal-overlay{position:fixed;inset:0;z-index:2147482000;display:flex;align-items:center;justify-content:center;padding:18px;background:rgba(24,15,18,.58);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);visibility:hidden;opacity:0;pointer-events:none;transition:opacity .2s ease,visibility .2s ease;box-sizing:border-box}
    .ku-user-modal-overlay.open{visibility:visible;opacity:1;pointer-events:auto}
    .ku-user-modal{width:min(650px,100%);max-height:calc(100vh - 36px);overflow:auto;background:#fff;border:1px solid rgba(255,255,255,.9);border-radius:21px;box-shadow:0 30px 90px rgba(0,0,0,.28);transform:translateY(14px) scale(.985);transition:transform .22s cubic-bezier(.2,.8,.2,1);outline:none}
    .ku-user-modal-overlay.open .ku-user-modal{transform:translateY(0) scale(1)}
    .ku-modal-head{display:flex;align-items:center;gap:13px;padding:21px 24px;border-bottom:1px solid #F0E7E7}.ku-modal-heading-icon{width:46px;height:46px;display:grid;place-items:center;border-radius:13px;background:#FCEAEC;color:#C8102E;flex:none}.ku-modal-heading-icon svg{width:22px;height:22px}.ku-modal-heading-copy{min-width:0;flex:1}.ku-modal-heading-copy .ku-kicker{margin-bottom:4px}.ku-modal-heading-copy h3{margin:0;color:#24191D;font:700 20px 'Space Grotesk',sans-serif}.ku-modal-heading-copy p{margin:5px 0 0;color:#897D81;font-size:11.5px;line-height:1.5}.ku-modal-x{width:34px;height:34px;border:0;border-radius:10px;background:#F7F3F3;color:#77696D;font:24px/1 Arial,sans-serif;cursor:pointer;flex:none;transition:background .15s,color .15s}.ku-modal-x:hover{background:#FCEAEC;color:#C8102E}
    .ku-modal-body{padding:21px 24px 24px}.ku-form-intro{margin-bottom:17px;color:#8A7F82;font-size:11px}.ku-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px 14px}.ku-field{min-width:0}.ku-field-full{grid-column:1/-1}.ku-field label{display:block;margin:0 0 7px;color:#3F3337;font-size:11px;font-weight:800}.ku-field label span{color:#C8102E}.ku-field input,.ku-field select{display:block;width:100%;height:43px;padding:0 12px;border:1px solid #E7DEDD;border-radius:10px;background:#FBFAF9;color:#2F2428;font:12px 'Inter',sans-serif;outline:none;transition:border-color .15s,box-shadow .15s}.ku-field input:focus,.ku-field select:focus{border-color:#C8102E;box-shadow:0 0 0 3px rgba(200,16,46,.08)}.ku-field input::placeholder{color:#B0A5A8}.ku-password-wrap{position:relative}.ku-password-wrap input{padding-right:44px}.ku-password-toggle{position:absolute;top:50%;right:7px;transform:translateY(-50%);width:30px;height:30px;display:grid;place-items:center;padding:0;border:0;border-radius:7px;background:transparent;color:#8C8084;cursor:pointer}.ku-password-toggle:hover{background:#FCEAEC;color:#C8102E}.ku-password-toggle svg{width:17px;height:17px}.ku-field-hint{display:block;margin-top:6px;color:#9A9093;font-size:10px;line-height:1.4}.ku-field-error{display:block;margin-top:6px;color:#C8102E;font-size:10.5px;line-height:1.45}.ku-field-error[hidden]{display:none!important}
    .ku-modal-footer{display:flex;justify-content:flex-end;gap:9px;padding:15px 24px 20px;border-top:1px solid #F0E7E7;background:#FFFEFE}.ku-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:41px;padding:10px 15px;border:1px solid transparent;border-radius:10px;font:700 11.5px 'Inter',sans-serif;cursor:pointer;transition:background .16s,border-color .16s,transform .16s}.ku-btn:disabled{opacity:.65;cursor:wait}.ku-btn-secondary{border-color:#E7DEDD;background:#fff;color:#766A6E}.ku-btn-secondary:hover{background:#F8F4F4}.ku-btn-primary{background:#C8102E;color:#fff;box-shadow:0 8px 18px rgba(200,16,46,.16)}.ku-btn-primary:hover:not(:disabled){background:#A80E27;transform:translateY(-1px)}.ku-btn-primary svg{width:15px;height:15px}

    .ku-ui-alert-overlay{display:none;position:fixed!important;inset:0!important;width:100vw!important;height:100vh!important;z-index:2147483000!important;background:rgba(20,16,18,.56);backdrop-filter:blur(7px);-webkit-backdrop-filter:blur(7px);align-items:center;justify-content:center;padding:16px;overflow:hidden!important;box-sizing:border-box}.ku-ui-alert-overlay.open{display:flex;animation:kuUiAlertFadeIn .18s ease}.ku-ui-alert-card{width:min(440px,calc(100vw - 32px));max-width:440px;max-height:calc(100vh - 32px);overflow:auto;background:#fff;border-radius:22px;padding:24px 22px 20px;box-shadow:0 34px 90px rgba(0,0,0,.3);text-align:center;animation:kuUiAlertPop .18s ease;box-sizing:border-box}.ku-ui-alert-icon{width:54px;height:54px;margin:0 auto 14px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(200,16,46,.08);color:#C8102E}.ku-ui-alert-icon.success{background:rgba(30,122,70,.08);color:#16A34A}.ku-ui-alert-icon.warning{background:rgba(216,150,35,.10);color:#B86E00}.ku-ui-alert-icon svg{width:27px;height:27px;display:block;stroke:currentColor}.ku-ui-alert-card h3{margin:0 0 8px;font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:700;color:#252331}.ku-ui-alert-message{min-height:30px;margin:0 auto 20px;max-width:380px;font:12.5px/1.65 'Inter',sans-serif;color:#7A808B;overflow-wrap:anywhere;word-break:break-word}.ku-ui-alert-message strong{color:#2C3442;font-weight:800}.ku-ui-alert-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px}.ku-ui-alert-actions.single{grid-template-columns:1fr}.ku-ui-alert-btn{height:42px;border-radius:11px;font:700 13px 'Inter',sans-serif;cursor:pointer;transition:.18s ease}.ku-ui-alert-cancel{border:1px solid #E5E0E1;background:#fff;color:#6F7177}.ku-ui-alert-confirm{border:1px solid #B40000;background:#B40000;color:#fff;box-shadow:0 10px 22px -14px rgba(180,0,0,.8)}.ku-ui-alert-confirm.success-btn{background:#650014;border-color:#650014}
    @keyframes kuUiAlertFadeIn{from{opacity:0}to{opacity:1}}@keyframes kuUiAlertPop{from{opacity:0;transform:translateY(10px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}
    .ku-sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
    @media(max-width:900px){.ku-stats{gap:10px}.ku-stat-card{padding:14px}.ku-stat-card strong{font-size:20px}}
    @media(max-width:700px){.ku-stats{grid-template-columns:1fr}.ku-stat-card{padding:13px 15px}.ku-stat-icon{width:38px;height:38px}.ku-toolbar{align-items:stretch;flex-direction:column}.ku-role-filter select{width:100%}.ku-table-footer{align-items:flex-start;flex-direction:column}.ku-user-modal-overlay{padding:10px}.ku-user-modal{max-height:calc(100vh - 20px);border-radius:17px}.ku-modal-head{padding:17px}.ku-modal-body{padding:17px}.ku-modal-footer{padding:13px 17px 17px}}
    @media(max-width:480px){.ku-head h2{font-size:21px}.ku-add-btn{width:100%}.ku-form-grid{grid-template-columns:1fr}.ku-field-full{grid-column:auto}.ku-modal-heading-copy h3{font-size:18px}.ku-modal-heading-icon{width:40px;height:40px}.ku-modal-footer .ku-btn{flex:1}}
    @media(prefers-reduced-motion:reduce){.ku-user-modal-overlay,.ku-user-modal,.ku-add-btn,.ku-btn{transition:none!important;animation:none!important}}
</style>

<script>
(function () {
    'use strict';

    const modalOverlay = document.getElementById('kuUserModalOverlay');
    const modal = modalOverlay?.querySelector('.ku-user-modal');
    const form = document.getElementById('kuUserForm');
    const methodOverride = document.getElementById('kuUserMethodOverride');
    const modeInput = document.getElementById('kuUserModalMode');
    const userIdInput = document.getElementById('kuUserModalUserId');
    const nameInput = document.getElementById('kuUserName');
    const usernameInput = document.getElementById('kuUserUsername');
    const emailInput = document.getElementById('kuUserEmail');
    const roleInput = document.getElementById('kuUserRole');
    const passwordInput = document.getElementById('kuUserPassword');
    const passwordConfirmInput = document.getElementById('kuUserPasswordConfirmation');
    const passwordRequiredMark = document.getElementById('kuPasswordRequiredMark');
    const passwordConfirmRequiredMark = document.getElementById('kuPasswordConfirmationRequiredMark');
    const modalTitle = document.getElementById('kuUserModalTitle');
    const modalSubtitle = document.getElementById('kuUserModalSubtitle');
    const formIntro = document.getElementById('kuUserFormIntro');
    const submitBtn = document.getElementById('kuUserSubmitBtn');
    const submitText = submitBtn?.querySelector('span');

    const alertOverlay = document.getElementById('kuUiAlertOverlay');
    const alertIcon = document.getElementById('kuUiAlertIcon');
    const alertTitle = document.getElementById('kuUiAlertTitle');
    const alertMessage = document.getElementById('kuUiAlertMessage');
    const alertActions = document.getElementById('kuUiAlertActions');
    const alertCancel = document.getElementById('kuUiAlertCancel');
    const alertConfirm = document.getElementById('kuUiAlertConfirm');

    if (!modalOverlay || !form || !alertOverlay || !alertIcon || !alertTitle || !alertMessage || !alertActions || !alertCancel || !alertConfirm) {
        return;
    }

    // Overlay ditempatkan langsung di body agar tidak terpotong layout dashboard.
    if (modalOverlay.parentElement !== document.body) document.body.appendChild(modalOverlay);
    if (alertOverlay.parentElement !== document.body) document.body.appendChild(alertOverlay);

    let alertResolver = null;
    let lastFocusedElement = null;

    function escapeHtml(value) {
        return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function clearFieldErrors() {
        form.querySelectorAll('[data-ku-error]').forEach(function (element) {
            element.textContent = '';
            element.hidden = true;
        });
        form.querySelectorAll('.ku-field-invalid').forEach(function (element) {
            element.classList.remove('ku-field-invalid');
        });
    }

    function setModalOpen(open) {
        modalOverlay.classList.toggle('open', open);
        modalOverlay.setAttribute('aria-hidden', open ? 'false' : 'true');
        document.documentElement.classList.toggle('ku-modal-lock', open);
        document.body.classList.toggle('ku-modal-lock', open);
        if (open) {
            window.setTimeout(function () { nameInput?.focus(); }, 70);
        } else if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
            lastFocusedElement.focus();
        }
    }

    function resetFormForCreate(preserveValues) {
        if (!preserveValues) {
            form.reset();
            clearFieldErrors();
            passwordInput.value = '';
            passwordConfirmInput.value = '';
            passwordInput.type = 'password';
            passwordConfirmInput.type = 'password';
            roleInput.value = 'user';
            nameInput.value = '';
            usernameInput.value = '';
            emailInput.value = '';
        }
        form.action = @json(route('admin.users.store'));
        methodOverride.disabled = true;
        methodOverride.value = 'PUT';
        modeInput.value = 'create';
        userIdInput.value = '';
        passwordInput.required = true;
        passwordConfirmInput.required = true;
        passwordRequiredMark.hidden = false;
        passwordConfirmRequiredMark.hidden = false;
        passwordInput.placeholder = 'Buat password';
        passwordConfirmInput.placeholder = 'Ulangi password';
        modalTitle.textContent = 'Tambah User';
        modalSubtitle.textContent = 'Buat akun baru untuk pengguna sistem.';
        formIntro.textContent = 'Lengkapi informasi akun di bawah ini.';
        submitText.textContent = 'Simpan User';
        form.dataset.mode = 'create';
    }

    function openCreateModal(preserveValues) {
        lastFocusedElement = document.activeElement;
        resetFormForCreate(Boolean(preserveValues));
        setModalOpen(true);
    }

    function openEditModal(button, preserveValues) {
        if (!button) return;
        lastFocusedElement = document.activeElement;
        if (!preserveValues) {
            form.reset();
            clearFieldErrors();
            nameInput.value = button.dataset.userName || '';
            usernameInput.value = button.dataset.userUsername || '';
            emailInput.value = button.dataset.userEmail || '';
            roleInput.value = button.dataset.userRole || 'user';
            passwordInput.value = '';
            passwordConfirmInput.value = '';
            passwordInput.type = 'password';
            passwordConfirmInput.type = 'password';
        }
        form.action = button.dataset.updateUrl;
        methodOverride.disabled = false;
        methodOverride.value = 'PUT';
        modeInput.value = 'edit';
        userIdInput.value = button.dataset.userId || '';
        passwordInput.required = false;
        passwordConfirmInput.required = false;
        passwordRequiredMark.hidden = true;
        passwordConfirmRequiredMark.hidden = true;
        passwordInput.placeholder = 'Kosongkan jika tidak diubah';
        passwordConfirmInput.placeholder = 'Konfirmasi password baru';
        modalTitle.textContent = 'Edit User';
        modalSubtitle.textContent = 'Perbarui informasi akun pengguna.';
        formIntro.textContent = 'Perubahan akan disimpan ke akun yang dipilih. Password boleh dikosongkan jika tidak ingin mengubahnya.';
        submitText.textContent = 'Simpan Perubahan';
        form.dataset.mode = 'edit';
        setModalOpen(true);
    }

    document.querySelectorAll('[data-ku-open-create]').forEach(function (button) {
        button.addEventListener('click', function () { openCreateModal(false); });
    });
    document.querySelectorAll('[data-ku-open-edit]').forEach(function (button) {
        button.addEventListener('click', function () { openEditModal(button, false); });
    });
    document.querySelectorAll('[data-ku-close-modal]').forEach(function (button) {
        button.addEventListener('click', function () { setModalOpen(false); });
    });
    modalOverlay.addEventListener('click', function (event) {
        if (event.target === modalOverlay) setModalOpen(false);
    });

    document.querySelectorAll('[data-ku-toggle-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = document.getElementById(button.dataset.kuTogglePassword);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
            button.setAttribute('title', show ? 'Sembunyikan password' : 'Tampilkan password');
        });
    });

    // Filter pencarian dan role berjalan langsung tanpa meninggalkan halaman.
    const searchInput = document.getElementById('kuSearchInput');
    const roleFilter = document.getElementById('kuRoleFilter');
    const countText = document.getElementById('kuUserCountText');
    const noMatchRow = document.getElementById('kuNoMatchRow');
    const userRows = Array.from(document.querySelectorAll('.ku-user-row'));

    function applyUserFilters() {
        const query = (searchInput?.value || '').trim().toLocaleLowerCase('id-ID');
        const role = roleFilter?.value || 'all';
        let visible = 0;
        userRows.forEach(function (row) {
            const matchesText = (row.dataset.kuSearch || '').toLocaleLowerCase('id-ID').includes(query);
            const matchesRole = role === 'all' || row.dataset.kuRole === role;
            const show = matchesText && matchesRole;
            row.hidden = !show;
            if (show) {
                visible += 1;
                const numberCell = row.querySelector('.ku-row-number');
                if (numberCell) numberCell.textContent = String(visible);
            }
        });
        if (noMatchRow) noMatchRow.hidden = visible !== 0;
        if (countText) countText.textContent = `Menampilkan ${visible} dari ${userRows.length} user`;
    }
    searchInput?.addEventListener('input', applyUserFilters);
    roleFilter?.addEventListener('change', applyUserFilters);

    function iconMarkup(type) {
        if (type === 'success') return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.2 4.2L19 7"/></svg>';
        if (type === 'warning') return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2.8 20h18.4L12 3Z"/><path d="M12 9v5M12 17.2h.01"/></svg>';
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5h.01"/></svg>';
    }

    function openAlert(options) {
        return new Promise(function (resolve) {
            alertResolver = resolve;
            const type = options.type || 'error';
            alertIcon.className = 'ku-ui-alert-icon ' + type;
            alertIcon.innerHTML = iconMarkup(type);
            alertTitle.textContent = options.title || 'Informasi';
            alertMessage.innerHTML = options.message || '';
            alertCancel.textContent = options.cancelText || 'Batal';
            alertConfirm.textContent = options.confirmText || 'OK';
            const showCancel = options.showCancel === true;
            alertCancel.style.display = showCancel ? '' : 'none';
            alertActions.classList.toggle('single', !showCancel);
            alertConfirm.classList.toggle('success-btn', type === 'success');
            document.documentElement.classList.add('ku-ui-lock');
            document.body.classList.add('ku-ui-lock');
            alertOverlay.classList.add('open');
            alertOverlay.setAttribute('aria-hidden', 'false');
            window.setTimeout(function () { alertConfirm.focus(); }, 20);
        });
    }

    function closeAlert(value) {
        alertOverlay.classList.remove('open');
        alertOverlay.setAttribute('aria-hidden', 'true');
        document.documentElement.classList.remove('ku-ui-lock');
        document.body.classList.remove('ku-ui-lock');
        if (alertResolver) {
            const resolve = alertResolver;
            alertResolver = null;
            resolve(value);
        }
    }
    alertConfirm.addEventListener('click', function () { closeAlert(true); });
    alertCancel.addEventListener('click', function () { closeAlert(false); });
    alertOverlay.addEventListener('click', function (event) { if (event.target === alertOverlay) closeAlert(false); });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (!form.reportValidity()) return;
        const mode = form.dataset.mode || 'create';
        const userName = nameInput.value.trim() || (mode === 'edit' ? 'user ini' : 'user baru');
        const confirmed = await openAlert({
            type: 'warning',
            title: mode === 'edit' ? 'Simpan Perubahan?' : 'Tambah User?',
            message: mode === 'edit'
                ? `Perubahan untuk akun <strong>${escapeHtml(userName)}</strong> akan disimpan.`
                : `Akun <strong>${escapeHtml(userName)}</strong> akan dibuat dan disimpan ke sistem.`,
            confirmText: mode === 'edit' ? 'Simpan' : 'Buat User',
            cancelText: 'Batal',
            showCancel: true
        });
        if (!confirmed) return;
        submitBtn.disabled = true;
        submitText.textContent = mode === 'edit' ? 'Menyimpan...' : 'Membuat User...';
        HTMLFormElement.prototype.submit.call(form);
    });

    document.querySelectorAll('.ku-delete-form').forEach(function (deleteForm) {
        deleteForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            const userName = deleteForm.closest('tr')?.querySelector('.ku-name')?.textContent?.trim() || 'user ini';
            const confirmed = await openAlert({
                type: 'warning',
                title: 'Hapus User?',
                message: `Akun <strong>${escapeHtml(userName)}</strong> akan dihapus dari sistem. Tindakan ini tidak dapat dibatalkan.`,
                confirmText: 'Hapus',
                cancelText: 'Batal',
                showCancel: true
            });
            if (confirmed) HTMLFormElement.prototype.submit.call(deleteForm);
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (alertOverlay.classList.contains('open')) closeAlert(false);
            else if (modalOverlay.classList.contains('open')) setModalOpen(false);
        }
    });

    // Saat validasi Laravel gagal, buka kembali modal yang tadi disubmit.
    const initialMode = @json($kuInitialMode);
    const initialUserId = @json((string) $kuInitialUserId);
    const serverErrors = @json($errors->all());
    const successMessage = @json(session('success'));
    const errorMessage = @json(session('error'));

    if (Array.isArray(serverErrors) && serverErrors.length && initialMode === 'create') {
        form.dataset.mode = 'create';
        openCreateModal(true);
    } else if (Array.isArray(serverErrors) && serverErrors.length && initialMode === 'edit' && initialUserId) {
        const trigger = document.querySelector('[data-ku-open-edit][data-user-id="' + CSS.escape(initialUserId) + '"]');
        if (trigger) openEditModal(trigger, true);
        else openCreateModal(true);
    } else if (successMessage || errorMessage || (Array.isArray(serverErrors) && serverErrors.length)) {
        window.setTimeout(function () {
            if (successMessage) {
                openAlert({ type: 'success', title: 'Berhasil', message: escapeHtml(successMessage), confirmText: 'OK', showCancel: false });
            } else if (errorMessage) {
                openAlert({ type: 'error', title: 'Gagal', message: escapeHtml(errorMessage), confirmText: 'OK', showCancel: false });
            } else {
                openAlert({ type: 'error', title: 'Terjadi Kesalahan', message: serverErrors.map(escapeHtml).join('<br>'), confirmText: 'OK', showCancel: false });
            }
        }, 120);
    }
})();
</script>
