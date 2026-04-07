{{--
    Login Page — Livewire component view
    Layout: auth (split desktop, centered mobile)
    TODO: Hubungkan ke Livewire component App\Livewire\Auth\LoginForm
--}}

<x-layouts.auth title="Login">
    <div class="flex flex-col gap-6">
        {{-- Heading --}}
        <div>
            <h2 class="text-2xl font-bold text-text" style="font-family: 'DM Sans', sans-serif;">Login</h2>
            <p class="text-sm text-muted mt-1">Masuk untuk melanjutkan ke sistem</p>
        </div>

        {{-- Error alert --}}
        {{-- TODO: Tampilkan jika $errors->has('auth') atau session error --}}
        <div
            x-data="{ showError: {{ $errors->any() ? 'true' : 'false' }} }"
            x-show="showError"
            x-transition
            style="display: none;"
        >
            <div class="flex items-center gap-2 p-3 rounded-lg bg-danger-bg text-danger text-sm">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Email atau password salah. Coba lagi.</span>
            </div>
        </div>

        {{-- Login form --}}
        {{-- TODO: Ganti form action dengan wire:submit.prevent="login" --}}
        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5" x-data="{ showPassword: false, loading: false }">
            @csrf

            {{-- Email --}}
            <div>
                <label for="email" class="label">
                    Email Address <span class="text-danger">*</span>
                </label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@perusahaan.com"
                    class="input @error('email') input-error @enderror"
                />
                {{-- TODO: Ganti dengan wire:model.defer="email" --}}
                @error('email')
                    <p class="text-xs text-danger mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="label">
                    Password <span class="text-danger">*</span>
                </label>
                <div class="relative">
                    <input
                        id="password"
                        name="password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        placeholder="Masukkan password"
                        class="input pr-11 @error('password') input-error @enderror"
                    />
                    {{-- TODO: Ganti dengan wire:model.defer="password" --}}
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-text transition-colors"
                        tabindex="-1"
                    >
                        {{-- Eye icon --}}
                        <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        {{-- Eye off icon --}}
                        <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
                @error('password')
                    <p class="text-xs text-danger mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remember me --}}
            <div class="flex items-center gap-2">
                <input
                    id="remember"
                    name="remember"
                    type="checkbox"
                    {{ old('remember') ? 'checked' : '' }}
                    class="w-4 h-4 rounded border-border text-primary focus:ring-primary/30 accent-primary"
                />
                {{-- TODO: Ganti dengan wire:model="remember" --}}
                <label for="remember" class="text-sm text-text select-none cursor-pointer">
                    Ingat saya
                </label>
            </div>

            {{-- Submit button --}}
            {{-- TODO: Tambahkan wire:loading directives --}}
            <button
                type="submit"
                class="btn-primary w-full"
                :class="{ 'btn-disabled': loading }"
                :disabled="loading"
                @click="loading = true"
                data-test="login-button"
            >
                <svg x-show="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24" style="display: none;">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="loading ? 'Masuk...' : 'Login'">Login</span>
            </button>
        </form>
    </div>
</x-layouts.auth>
