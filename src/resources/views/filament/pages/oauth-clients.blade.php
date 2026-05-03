<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Circular Modules OAuth
            </x-slot>

            <x-slot name="description">
                Crea y rota credenciales OAuth2 (Password Grant) para circular-modules sin comandos de consola.
            </x-slot>

            <div class="space-y-2 text-sm">
                @if ($this->activeClientId)
                    <p><strong>Cliente activo:</strong> {{ $this->activeClientName }} ({{ $this->activeClientId }})</p>
                    <p><strong>Provider:</strong> {{ $this->activeClientProvider ?? 'users' }}</p>
                @else
                    <p>No hay clientes password grant activos.</p>
                @endif
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Variables para circular-modules
            </x-slot>

            <x-slot name="description">
                Copia este bloque en el archivo .env de circular-modules. El secret se muestra solo cuando se crea o
                rota.
            </x-slot>

            <pre
                class="overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs dark:border-white/10 dark:bg-gray-900">{{ $this->getEnvSnippet() }}</pre>
        </x-filament::section>
    </div>
</x-filament-panels::page>