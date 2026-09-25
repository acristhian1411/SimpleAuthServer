<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                {{ $this->client->name }}
            </x-slot>

            <x-slot name="description">
                Detalle del cliente OAuth y sus tokens asociados.
            </x-slot>

            <div class="grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-950">
                    <p class="text-xs uppercase tracking-wide text-gray-500">ID</p>
                    <p class="mt-1 font-medium text-gray-950 dark:text-gray-100">{{ $this->client->getKey() }}</p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-950">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Provider</p>
                    <p class="mt-1 font-medium text-gray-950 dark:text-gray-100">
                        {{ $this->client->provider ?? 'users' }}
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-950">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Grant types</p>
                    <p class="mt-1 font-medium text-gray-950 dark:text-gray-100">
                        {{ implode(', ', $this->client->grant_types) }}
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-950">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Tipo</p>
                    <p class="mt-1 font-medium text-gray-950 dark:text-gray-100">
                        {{ $this->client->confidential() ? 'Confidencial' : 'Público' }}
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-950">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Tokens emitidos</p>
                    <p class="mt-1 font-medium text-gray-950 dark:text-gray-100">{{ $this->client->tokens->count() }}
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-950">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Secret</p>
                        @if ($this->getClientSecretForDisplay())
                            <button type="button" wire:click="toggleClientSecretVisibility"
                                class="text-xs font-semibold text-primary-600 transition hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                                {{ $this->showClientSecret ? 'Ocultar' : 'Ver' }}
                            </button>
                        @endif
                    </div>

                    @if ($secret = $this->getClientSecretForDisplay())
                        <p class="mt-2 break-all font-mono text-xs text-gray-950 dark:text-gray-100">
                            {{ $this->showClientSecret ? $secret : str_repeat('•', min(strlen($secret), 24)) }}
                        </p>
                        <p class="mt-2 text-xs text-gray-500">Se conserva para esta sesión tras crear o rotar el secret.</p>
                    @else
                        <p class="mt-2 text-sm text-gray-500">No hay un secret disponible para mostrar en este momento.</p>
                    @endif
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-950">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Estado</p>
                    <p class="mt-1 font-medium text-gray-950 dark:text-gray-100">Activo</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Control de Acceso
            </x-slot>

            <x-slot name="description">
                Define qué usuarios y roles pueden acceder a este cliente. Puedes combinar múltiples roles y usuarios.
            </x-slot>

            <form wire:submit="saveAccessSettings">
                {{ $this->form }}

                <div class="flex justify-end pt-6 border-t mt-6">
                    <x-filament::button type="submit">
                        Guardar cambios
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        @if ($this->client->hasGrantType('authorization_code'))
            <x-filament::section>
                <x-slot name="heading">
                    Redirect URIs
                </x-slot>

                <div class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    @forelse ($this->client->redirect_uris as $redirectUri)
                        <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-900">{{ $redirectUri }}</div>
                    @empty
                        <p>No hay redirect URIs configuradas.</p>
                    @endforelse
                </div>
            </x-filament::section>
        @endif

        @if ($this->generatedClientId && $this->generatedClientSecret)
            <x-filament::section>
                <x-slot name="heading">
                    Secret reciente
                </x-slot>

                <x-slot name="description">
                    Este bloque solo aparece cuando el secret fue generado o rotado durante esta sesión.
                </x-slot>

                <pre
                    class="overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs dark:border-white/10 dark:bg-gray-900">{{ $this->getEnvSnippet() }}</pre>
            </x-filament::section>
        @endif

        <x-filament::section>
            <x-slot name="heading">
                Tokens
            </x-slot>

            <x-slot name="description">
                Accesos emitidos para este cliente, con su refresh token asociado.
            </x-slot>

            <div class="space-y-3 text-sm">
                @forelse ($this->client->tokens as $token)
                    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-950">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div class="space-y-1">
                                <p class="font-medium text-gray-950 dark:text-gray-100">Token {{ $token->getKey() }}</p>
                                <p class="text-xs text-gray-500">Expira:
                                    {{ optional($token->expires_at)->format('Y-m-d H:i') ?? 'sin fecha' }}
                                </p>
                                <p class="text-xs text-gray-500">Scopes:
                                    {{ blank($token->scopes) ? 'sin scopes' : implode(', ', $token->scopes) }}
                                </p>
                                <p class="text-xs text-gray-500">Refresh token:
                                    {{ $token->refreshToken ? ($token->refreshToken->revoked ? 'revocado' : 'activo') : 'sin refresh token' }}
                                </p>
                            </div>

                            <span
                                class="inline-flex w-fit rounded-full px-2 py-1 text-[11px] font-medium {{ $token->revoked ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' }}">
                                {{ $token->revoked ? 'Revocado' : 'Activo' }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Este cliente todavía no tiene tokens emitidos.</p>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>