<?php

namespace Tests\Unit;

use App\Filament\Pages\OAuthClientDetail;
use Tests\TestCase;

class OAuthClientDetailPageTest extends TestCase
{
    public function test_it_uses_the_recently_generated_secret_or_the_session_secret(): void
    {
        $page = new OAuthClientDetail();
        $page->generatedClientSecret = 'generated-secret';

        $this->assertSame('generated-secret', $page->getClientSecretForDisplay());

        $page->generatedClientSecret = null;
        session()->put('oauth_client_secret.client-123', 'session-secret');
        $page->clientId = 'client-123';

        $this->assertSame('session-secret', $page->getClientSecretForDisplay());
    }
}
