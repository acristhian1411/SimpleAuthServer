<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
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

    public ?string $activeMobileClientId = null;

    public ?string $activeMobileClientName = null;

    /** @var string[] */
    public array $activeMobileRedirectUris = [];

    public ?string $generatedMobileClientId = null;

    /** @var string[] */
    public array $generatedMobileRedirectUris = [];

    public function mount(): void
    {
        $this->refreshActiveClient();
        $this->refreshActiveMobileClient();
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

            Action::make('createMobilePkceClient')
                ->label('Crear cliente mobile PKCE')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('success')
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre del cliente')
                        ->default('Expo Mobile App')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('redirect_uris')
                        ->label('Redirect URIs')
                        ->rows(4)
                        ->required()
                        ->default("myapp://auth/callback\nexp://127.0.0.1:19000/--/auth/callback")
                        ->helperText('Una URI por linea. Tambien puedes separarlas por comas.'),
                ])
                ->action(function (array $data): void {
                    try {
                        $redirectUris = $this->parseRedirectUris((string) $data['redirect_uris']);

                        if (empty($redirectUris)) {
                            Notification::make()
                                ->title('Redirect URIs requeridas')
                                ->danger()
                                ->body('Agrega al menos una redirect URI valida para PKCE.')
                                ->send();

                            return;
                        }

                        $repository = app(ClientRepository::class);

                        $client = $repository->createAuthorizationCodeGrantClient(
                            name: (string) $data['name'],
                            redirectUris: $redirectUris,
                            confidential: false,
                        );

                        $this->activeMobileClientId = (string) $client->getKey();
                        $this->activeMobileClientName = $client->name;
                        $this->activeMobileRedirectUris = $client->redirect_uris;
                        $this->generatedMobileClientId = (string) $client->getKey();
                        $this->generatedMobileRedirectUris = $client->redirect_uris;

                        Notification::make()
                            ->title('Cliente mobile PKCE creado')
                            ->success()
                            ->body('Usa el client_id en Expo. No se requiere client_secret para apps moviles.')
                            ->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('No se pudo crear el cliente mobile')
                            ->danger()
                            ->body($exception->getMessage())
                            ->send();
                    }
                }),

            Action::make('revokeMobileClient')
                ->label('Revocar cliente mobile')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->schema([
                    Select::make('client_id')
                        ->label('Cliente mobile')
                        ->options(fn(): array => $this->mobilePkceClientOptions()->all())
                        ->default($this->activeMobileClientId)
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

                    $this->revokeClientAndTokens($client);

                    if ($this->activeMobileClientId === (string) $client->getKey()) {
                        $this->generatedMobileClientId = null;
                        $this->generatedMobileRedirectUris = [];
                    }

                    $this->refreshActiveMobileClient();

                    Notification::make()
                        ->title('Cliente mobile revocado')
                        ->success()
                        ->body('Tambien se revocaron access y refresh tokens emitidos para este cliente.')
                        ->send();
                })
                ->visible(fn(): bool => $this->mobilePkceClientOptions()->isNotEmpty()),

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

                    $this->revokeClientAndTokens($client);

                    if ($this->activeClientId === (string) $client->getKey()) {
                        $this->generatedClientId = null;
                        $this->generatedClientSecret = null;
                    }

                    $this->refreshActiveClient();

                    Notification::make()
                        ->title('Cliente revocado')
                        ->success()
                        ->body('Tambien se revocaron access y refresh tokens emitidos para este cliente.')
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

    public function getMobileEnvSnippet(): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $clientId = $this->generatedMobileClientId ?: $this->activeMobileClientId;
        $redirectUri = $this->generatedMobileRedirectUris[0]
            ?? $this->activeMobileRedirectUris[0]
            ?? 'myapp://auth/callback';

        return implode("\n", [
            "EXPO_PUBLIC_OAUTH_ISSUER={$baseUrl}",
            'EXPO_PUBLIC_OAUTH_AUTHORIZE_URL=' . $baseUrl . '/oauth/authorize',
            'EXPO_PUBLIC_OAUTH_TOKEN_URL=' . $baseUrl . '/oauth/token',
            'EXPO_PUBLIC_OAUTH_CLIENT_ID=' . ($clientId ?? ''),
            "EXPO_PUBLIC_OAUTH_REDIRECT_URI={$redirectUri}",
            'EXPO_PUBLIC_OAUTH_SCOPES=expenses:read expenses:write profile:read',
        ]);
    }

    protected function refreshActiveClient(): void
    {
        $client = $this->passwordGrantClients()->first();

        $this->activeClientId = $client ? (string) $client->getKey() : null;
        $this->activeClientName = $client?->name;
        $this->activeClientProvider = $client?->provider;
    }

    protected function refreshActiveMobileClient(): void
    {
        $client = $this->mobilePkceClients()->first();

        $this->activeMobileClientId = $client ? (string) $client->getKey() : null;
        $this->activeMobileClientName = $client?->name;
        $this->activeMobileRedirectUris = $client?->redirect_uris ?? [];
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

    protected function mobilePkceClients(): Collection
    {
        return Passport::client()
            ->newQuery()
            ->where('revoked', false)
            ->latest('created_at')
            ->get()
            ->filter(fn(Client $client): bool => $client->hasGrantType('authorization_code') && !$client->confidential())
            ->values();
    }

    protected function mobilePkceClientOptions(): Collection
    {
        return $this->mobilePkceClients()
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

    /**
     * @return string[]
     */
    protected function parseRedirectUris(string $redirectUris): array
    {
        return collect(preg_split('/[\n,]+/', $redirectUris) ?: [])
            ->map(fn(string $uri): string => trim($uri))
            ->filter(fn(string $uri): bool => $uri !== '')
            ->unique()
            ->values()
            ->all();
    }

    protected function revokeClientAndTokens(Client $client): void
    {
        $client->tokens()
            ->with('refreshToken')
            ->get()
            ->each(function (Token $token): void {
                $token->refreshToken?->revoke();
                $token->revoke();
            });

        $client->forceFill(['revoked' => true])->save();
    }
}
