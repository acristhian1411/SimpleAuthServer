<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Throwable;

class OAuthClientDetail extends OAuthClients
{
    protected static ?string $slug = 'o-auth-clients/{client}';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.oauth-client-detail';

    public string $clientId;

    public function mount(?string $client = null): void
    {
        $this->clientId = $client ?? '';
        $this->resolveClient();
    }

    public function getTitle(): string
    {
        return $this->client->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rotateClientSecret')
                ->label('Rotar secret')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn(): bool => $this->client->hasGrantType('password'))
                ->action(function (): void {
                    try {
                        app(ClientRepository::class)->regenerateSecret($this->client);

                        $this->generatedClientId = (string) $this->client->getKey();
                        $this->generatedClientSecret = $this->client->plainSecret;

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
                }),

            Action::make('revokeClient')
                ->label('Revocar cliente')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (): void {
                    try {
                        $this->revokeClientAndTokens($this->client);

                        Notification::make()
                            ->title('Cliente revocado')
                            ->success()
                            ->body('Tambien se revocaron access y refresh tokens emitidos para este cliente.')
                            ->send();

                        $this->redirect(OAuthClients::getUrl());
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('No se pudo revocar el cliente')
                            ->danger()
                            ->body($exception->getMessage())
                            ->send();
                    }
                }),
        ];
    }

    public function getClientProperty(): Client
    {
        return $this->resolveClient();
    }

    protected function resolveClient(): Client
    {
        return Passport::client()
            ->newQuery()
            ->with('tokens.refreshToken')
            ->whereKey($this->clientId)
            ->where('revoked', false)
            ->firstOrFail();
    }
}
