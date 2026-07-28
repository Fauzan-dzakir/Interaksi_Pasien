<div>
    <x-page-header title="Pengguna"
                   subtitle="Akun dibuat oleh Admin — tidak ada pendaftaran mandiri.">
        <x-slot:actions>
            <button wire:click="create" class="btn-primary">+ Tambah Pengguna</button>
        </x-slot:actions>
    </x-page-header>

    @error('general')
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    <div class="card">
        <div class="flex flex-wrap gap-3 border-b border-slate-200 p-4">
            <input wire:model.live.debounce.300ms="search" type="search"
                   placeholder="Cari nama atau email…"
                   class="field-input sm:max-w-xs">

            <select wire:model.live="filterRole" class="field-input sm:max-w-xs">
                <option value="">Semua peran</option>
                @foreach ($roleOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Peran</th>
                        <th>Unit</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td class="font-medium text-slate-900">
                                {{ $user->name }}
                                @if ($user->id === auth()->id())
                                    <span class="ml-1 rounded bg-teal-50 px-1.5 py-0.5 text-xs text-teal-700">Anda</span>
                                @endif
                            </td>
                            <td class="text-slate-500">{{ $user->email }}</td>
                            <td>{{ $user->role->label() }}</td>
                            <td>{{ $user->unit?->name ?? '—' }}</td>
                            <td><x-status-pill :active="$user->is_active" /></td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    <button wire:click="edit({{ $user->id }})" class="btn-secondary !px-3 !py-1.5">Ubah</button>
                                    @if ($user->id !== auth()->id())
                                        <button wire:click="toggleActive({{ $user->id }})"
                                                class="{{ $user->is_active ? 'btn-danger' : 'btn-secondary !px-3 !py-1.5' }}">
                                            {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-slate-500">Belum ada data pengguna.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-slate-200 p-4">{{ $users->links() }}</div>
        @endif
    </div>

    <x-modal :show="$showForm" :title="$editingId ? 'Ubah Pengguna' : 'Tambah Pengguna'">
        <form wire:submit="save" id="user-form" class="space-y-4">
            <div>
                <label class="field-label" for="user-name">Nama Lengkap</label>
                <input wire:model="name" id="user-name" type="text" class="field-input" placeholder="mis. Ns. Dewi Lestari">
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="field-label" for="user-email">Email</label>
                <input wire:model="email" id="user-email" type="email" class="field-input" placeholder="nama@rskemenkes.go.id">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="field-label" for="user-role">Peran</label>
                    <select wire:model.live="role" id="user-role" class="field-input">
                        @foreach ($roleOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="field-label" for="user-unit">Unit Asal</label>
                    <select wire:model="unit_id" id="user-unit" class="field-input" @disabled(! $roleNeedsUnit)>
                        <option value="">{{ $roleNeedsUnit ? '— Pilih unit —' : 'Tidak terikat unit' }}</option>
                        @foreach ($unitOptions as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    @error('unit_id') <p class="field-error">{{ $message }}</p> @enderror
                    @unless ($roleNeedsUnit)
                        <p class="mt-1 text-xs text-slate-400">Admin punya akses lintas unit.</p>
                    @endunless
                </div>
            </div>

            <div>
                <label class="field-label" for="user-password">
                    Kata Sandi
                    @if ($editingId)
                        <span class="font-normal text-slate-400">(kosongkan bila tidak diubah)</span>
                    @endif
                </label>
                <input wire:model="password" id="user-password" type="password" class="field-input"
                       autocomplete="new-password" placeholder="Minimal 8 karakter">
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input wire:model="is_active" type="checkbox" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                Akun aktif
            </label>
        </form>

        <x-slot:footer>
            <button type="button" wire:click="$dispatch('close-modal')" class="btn-secondary">Batal</button>
            <button type="submit" form="user-form" class="btn-primary">Simpan</button>
        </x-slot:footer>
    </x-modal>
</div>
