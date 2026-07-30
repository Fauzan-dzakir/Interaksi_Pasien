<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class UserManager extends Component
{
    use WithPagination;

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    #[Url(as: 'role', keep: false)]
    public string $filterRole = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $role = 'nakes';

    public string $unit_id = '';

    public bool $is_active = true;

    public string $password = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterRole(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role->value;
        $this->unit_id = (string) ($user->unit_id ?? '');
        $this->is_active = $user->is_active;
        $this->password = '';

        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        $role = UserRole::tryFrom($this->role);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'role' => ['required', Rule::enum(UserRole::class)],
            // Admin tidak terikat unit; role lain wajib punya unit asal.
            'unit_id' => [
                $role?->requiresUnit() ? 'required' : 'nullable',
                'nullable', 'exists:units,id',
            ],
            'is_active' => ['boolean'],
            // Kata sandi wajib saat buat akun baru, opsional saat mengubah.
            'password' => [
                $this->editingId ? 'nullable' : 'required',
                'nullable', 'string', Password::min(8),
            ],
        ], [
            'unit_id.required' => 'Peran ini wajib terikat ke satu unit.',
        ]);

        $attributes = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'unit_id' => $role?->requiresUnit() ? (int) $data['unit_id'] : null,
            'is_active' => $data['is_active'],
        ];

        if (! empty($data['password'])) {
            $attributes['password'] = Hash::make($data['password']);
        }

        $user = User::updateOrCreate(['id' => $this->editingId], $attributes);

        if (! $this->editingId) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $this->showForm = false;
        $this->resetForm();

        session()->flash('status', 'Data pengguna berhasil disimpan.');
    }

    /** Akun tidak dihapus, hanya dinonaktifkan — jejak audit tindakannya harus tetap utuh. */
    public function toggleActive(int $id): void
    {
        if ($id === auth()->id()) {
            $this->addError('general', 'Anda tidak bisa menonaktifkan akun Anda sendiri.');

            return;
        }

        $user = User::findOrFail($id);
        $user->update(['is_active' => ! $user->is_active]);

        session()->flash('status', "Akun \"{$user->name}\" kini ".($user->is_active ? 'aktif' : 'nonaktif').'.');
    }

    #[On('close-modal')]
    public function closeModal(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'role', 'unit_id', 'is_active', 'password']);
        $this->role = 'nakes';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $users = User::query()
            ->with('unit')
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->filterRole, fn ($q) => $q->where('role', $this->filterRole))
            ->orderBy('name')
            ->paginate(12);

        return view('livewire.admin.user-manager', [
            'users' => $users,
            'roleOptions' => UserRole::options(),
            'unitOptions' => Unit::active()->orderBy('name')->get(['id', 'code', 'name']),
            'roleNeedsUnit' => UserRole::tryFrom($this->role)?->requiresUnit() ?? true,
        ]);
    }
}
