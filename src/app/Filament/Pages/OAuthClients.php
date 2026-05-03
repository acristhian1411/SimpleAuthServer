<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Throwable;

class OAuthClients extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'OAuth Clients';

    protected static string|\UnitEnum|null $navigationGroup = 'Integrations';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.oauth-clients';

    public ?string $activeClientId = null;

    public ?string $activeClientName = null;

    public ?string $activeClientProvider = null;

    public ?string $generatedClientId = null;

    public ?string $generatedClientSecret = null;

    public function mount(): void
    {
        $this->refreshActiveClient();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createPasswordClient')
                ->label('Crear cliente password grant')
                ->icon('heroicon-o-plus')
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre del cliente')
                        ->default('Circular Modules')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('provider')
                        ->label('Provider de usuarios')
                        ->default('users')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    try {
                        $repository = app(ClientRepository::class);

                        $client = $repository->createPasswordGrantClient(
                            name: (string) $data['name'],
                            provider: (string) $data['provider'],
                            confidential: true,
                        );

                        $this->activeClientId = (string) $client->getKey();
                        $this->activeClientName = $client->name;
                        $this->activeClientProvider = $client->provider;
                        $this->generatedClientId = (string) $client->getKey();
                        $this->generatedClientSecret = $client->plainSecret;

                        Notification::make()
                            ->title('Cliente OAuth creado')
                            ->success()
                            ->body('Copia el secret ahora: por seguridad no se puede volver a ver luego.')
                            ->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('No se pudo crear el cliente')
                            ->danger()
                            ->body($exception->getMessage())
                            ->send();
                    }
                }),

            Action::make('rotateClientSecret')
                ->label('Rotar secret')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->schema([
                    Select::make('client_id')
                        ->label('Cliente')
                        ->options(fn(): array => $this->passwordGrantClientOptions()->all())
                        ->default($this->activeClientId)
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        $repository = app(ClientRepository::class);
                        $client = $this->findClientById((string) $data['client_id']);

                        if (!$client) {
                            Notification::make()
                                ->title('Cliente no encontrado')
                                ->danger()
                                ->send();

                            return;
                        }

                        $repository->regenerateSecret($client);

                        $this->activeClientId = (string) $client->getKey();
                        $this->activeClientName = $client->name;
                        $this->activeClientProvider = $client->provider;
                        $this->generatedClientId = (string) $client->getKey();
                        $this->generatedClientSecret = $client->plainSecret;

                        Notification::make()
                            ->title('Secret rotado')
                            ->success()
                            ->body('El secret anterior ya no funciona.')
                            ->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('No se pudo rotar el secret')
                            ->danger()
                            ->body($exception->getMessage())
                            ->send();
                    }
                })
                ->visible(fn(): bool => $this->passwordGrantClientOptions()->isNotEmpty()),

            Action::make('revokeClient')
                ->label('Revocar cliente')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->schema([
                    Select::make('client_id')
                        ->label('Cliente')
                        ->options(fn(): array => $this->passwordGrantClientOptions()->all())
                        ->default($this->activeClientId)
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $client = $this->findClientById((string) $data['client_id']);

                    if (!$client) {
                        Notification::make()
                            ->title('Cliente no encontrado')
                            ->danger()
                            ->send();

                        return;
                    }

                    $client->forceFill(['revoked' => true])->save();

                    if ($this->activeClientId === (string) $client->getKey()) {
                        $this->generatedClientId = null;
                        $this->generatedClientSecret = null;
                    }

                    $this->refreshActiveClient();

                    Notification::make()
                        ->title('Cliente revocado')
                        ->success()
                        ->send();
                })
                ->visible(fn(): bool => $this->passwordGrantClientOptions()->isNotEmpty()),
        ];
    }

    public function getEnvSnippet(): string
    {
        if (!$this->generatedClientId || !$this->generatedClientSecret) {
            return "AUTH_CLIENT_ID=\nAUTH_CLIENT_SECRET=\nAUTH_SCOPE=";
        }

        return implode("\n", [
            "AUTH_CLIENT_ID={$this->generatedClientId}",
            "AUTH_CLIENT_SECRET={$this->generatedClientSecret}",
            'AUTH_SCOPE=',
        ]);
    }

    protected function refreshActiveClient(): void
    {
        $client = $this->passwordGrantClients()->first();

        $this->activeClientId = $client ? (string) $client->getKey() : null;
        $this->activeClientName = $client?->name;
        $this->activeClientProvider = $client?->provider;
    }

    protected function passwordGrantClients(): Collection
    {
        return Passport::client()
            ->newQuery()
            ->where('revoked', false)
            ->latest('created_at')
            ->get()
            ->filter(fn(Client $client): bool => $client->hasGrantType('password'))
            ->values();
    }

    protected function passwordGrantClientOptions(): Collection
    {
        return $this->passwordGrantClients()
            ->mapWithKeys(fn(Client $client): array => [
                (string) $client->getKey() => sprintf('%s (%s)', $client->name, $client->getKey()),
            ]);
    }

    protected function findClientById(string $clientId): ?Client
    {
        return Passport::client()
            ->newQuery()
            ->whereKey($clientId)
            ->where('revoked', false)
            ->first();
    }
}
