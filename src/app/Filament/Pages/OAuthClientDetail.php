<?php

namespace App\Filament\Pages;

use App\Models\AppClient;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
// use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Laravel\Passport\ClientRepository;
use Throwable;
use Filament\Schemas\Schema;

class OAuthClientDetail extends OAuthClients implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'o-auth-clients/{client}';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.oauth-client-detail';

    public string $clientId;

    public ?array $data = [];

    public function mount(?string $client = null): void
    {
        $this->clientId = $client ?? '';
        $resolved = $this->resolveClient();

        $this->form->fill([
            'accessMode' => $resolved->access_mode,
            'roles' => $resolved->roles->pluck('role_id')->toArray(),
            'users' => $resolved->allowedUsers->pluck('id')->toArray(),
        ]);
    }

    public function getTitle(): string
    {
        return $this->resolveClient()->name;
    }

    public function getClientProperty(): AppClient
    {
        return $this->resolveClient();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Radio::make('accessMode')
                    ->label('Modo de acceso')
                    ->options([
                        'all' => 'Todos los usuarios',
                        'roles' => 'Por rol',
                        'users' => 'Usuarios específicos',
                        'roles_or_users' => 'Rol o usuario específico',
                    ])
                    ->descriptions([
                        'all' => 'Todos los usuarios pueden acceder sin restricciones',
                        'roles' => 'Solo usuarios con los roles seleccionados',
                        'users' => 'Solo los usuarios específicos seleccionados',
                        'roles_or_users' => 'Usuarios con el rol O el usuario específico seleccionado',
                    ])
                    ->columns(2)
                    ->live()
                    ->required(),

                Select::make('roles')
                    ->label('Roles permitidos')
                    ->multiple()
                    ->searchable()
                    ->preload(false)
                    ->getSearchResultsUsing(
                        fn(string $search): array => Role::query()
                            ->where('role_name', 'like', "%{$search}%")
                            ->limit(50)
                            ->pluck('role_name', 'role_id')
                            ->toArray()
                    )
                    ->getOptionLabelsUsing(
                        fn(array $values): array => Role::query()
                            ->whereIn('role_id', $values)
                            ->pluck('role_name', 'role_id')
                            ->toArray()
                    )
                    ->placeholder('Buscar roles por nombre...')
                    ->helperText('Escribí para buscar. No se muestra la lista completa.')
                    ->visible(fn($get) => in_array($get('accessMode'), ['roles', 'roles_or_users'])),

                Select::make('users')
                    ->label('Usuarios permitidos')
                    ->multiple()
                    ->searchable()
                    ->preload(false)
                    ->getSearchResultsUsing(
                        fn(string $search): array => User::query()
                            ->where(function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn(User $user) => [$user->id => "{$user->name} ({$user->email})"])
                            ->toArray()
                    )
                    ->getOptionLabelsUsing(
                        fn(array $values): array => User::query()
                            ->whereIn('id', $values)
                            ->get()
                            ->mapWithKeys(fn(User $user) => [$user->id => "{$user->name} ({$user->email})"])
                            ->toArray()
                    )
                    ->placeholder('Buscar usuarios por nombre o email...')
                    ->helperText('Escribí para buscar. No se muestra la lista completa.')
                    ->visible(fn($get) => in_array($get('accessMode'), ['users', 'roles_or_users'])),
            ])
            ->statePath('data');
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
                        $this->rememberClientSecret($this->client, $this->client->plainSecret);

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
                            ->body('También se revocaron access y refresh tokens emitidos para este cliente.')
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

    public function saveAccessSettings(): void
    {
        try {
            $state = $this->form->getState();
            $client = $this->resolveClient();

            $client->update([
                'access_mode' => $state['accessMode'],
            ]);

            if (in_array($state['accessMode'], ['roles', 'roles_or_users'])) {
                $client->roles()->sync($state['roles'] ?? []);
            } else {
                $client->roles()->detach();
            }

            if (in_array($state['accessMode'], ['users', 'roles_or_users'])) {
                $client->allowedUsers()->sync($state['users'] ?? []);
            } else {
                $client->allowedUsers()->detach();
            }

            Notification::make()
                ->title('Configuración guardada')
                ->success()
                ->body('Las reglas de acceso se actualizaron correctamente.')
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Error al guardar')
                ->danger()
                ->body($exception->getMessage())
                ->send();
        }
    }

    protected function resolveClient(): AppClient
    {
        return AppClient::query()
            ->with('tokens.refreshToken', 'roles', 'allowedUsers')
            ->whereKey($this->clientId)
            ->where('revoked', false)
            ->firstOrFail();
    }
}