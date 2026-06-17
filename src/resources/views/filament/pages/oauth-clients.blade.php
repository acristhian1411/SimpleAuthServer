<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Clientes OAuth
            </x-slot>

            <x-slot name="description">
                Lista de clientes OAuth activos. Abre un cliente para ver sus tokens, redirect URIs y acciones.
            </x-slot>

            <div class="space-y-3 text-sm">
                @forelse ($this->clients() as $client)
                    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-950">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="space-y-1">
                                <p class="font-medium text-gray-950 dark:text-gray-100">{{ $client->name }}</p>
                                <p class="text-xs text-gray-500">ID: {{ $client->getKey() }}</p>
                                <p class="text-xs text-gray-500">Provider: {{ $client->provider ?? 'users' }}</p>
                            </div>

                            <div class="flex flex-col items-start gap-2 sm:items-end">
                                <span
                                    class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-[11px] font-medium text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                    {{ $this->clientGrantLabel($client) }}
                                </span>

                                <a href="{{ $this->clientDetailUrl($client) }}"
                                    class="inline-flex items-center rounded-lg bg-amber-500 px-3 py-2 text-xs font-semibold text-white transition hover:bg-amber-600">
                                    Ver detalle
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <p>No hay clientes OAuth activos.</p>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Variables para Expo
            </x-slot>

            <x-slot name="description">
                Copia este bloque en el .env de tu app Expo. Para local en emulador Android, usa 10.0.2.2 o un
                tunnel (ngrok/cloudflare).
            </x-slot>

            <pre
                class="overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs dark:border-white/10 dark:bg-gray-900">{{ $this->getMobileEnvSnippet() }}</pre>
        </x-filament::section>
    </div>
</x-filament-panels::page>