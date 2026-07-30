<?php

namespace App\Livewire\Shared;

use Livewire\Component;

/**
 * Panduan penggunaan dalam aplikasi — supaya tiap peran tidak perlu bertanya
 * manual/WA tim IT untuk hal-hal dasar alur kerja sistem ini.
 */
class Guide extends Component
{
    public function render()
    {
        return view('livewire.shared.guide', [
            'role' => auth()->user()->role,
        ]);
    }
}
