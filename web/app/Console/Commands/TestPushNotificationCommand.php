<?php

declare(strict_types = 1);

namespace App\Console\Commands;

use App\Models\PushToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestPushNotificationCommand extends Command
{
    protected $signature = 'push:test {email? : Email do usuário para enviar a notificação}';

    protected $description = 'Envia uma notificação de teste para um usuário';

    public function handle(): int
    {
        $email = $this->argument('email');

        // Busca o token
        $query = PushToken::with('user');

        if ($email) {
            $query->whereHas('user', fn ($q) => $q->where('email', $email));
        }

        $pushToken = $query->latest()->first();

        if (! $pushToken) {
            $this->error('Nenhum push token encontrado.');
            $this->info('Certifique-se de que você fez login no app mobile e aceitou as permissões de notificação.');

            return self::FAILURE;
        }

        $this->info("Enviando notificação para: {$pushToken->user->name} ({$pushToken->user->email})");
        $this->info("Token: {$pushToken->token}");

        $response = Http::post('https://exp.host/--/api/v2/push/send', [
            'to'    => $pushToken->token,
            'title' => '🎉 Teste DoaFarma',
            'body'  => 'Se você está vendo isso, as notificações estão funcionando!',
            'sound' => 'default',
            'data'  => [
                'type' => 'test',
            ],
        ]);

        if ($response->successful()) {
            $this->info('✅ Notificação enviada com sucesso!');
            $this->info('Verifique seu celular (pode bloquear a tela para ver na central de notificações).');

            return self::SUCCESS;
        }

        $this->error('Erro ao enviar notificação:');
        $this->error($response->body());

        return self::FAILURE;
    }
}
