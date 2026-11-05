<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Troca login e senha de um usuário direto no servidor. A senha é digitada
 * na hora (oculta), então não fica em arquivo, no git nem no histórico do shell.
 */
class AlterarCredenciais extends Command
{
    protected $signature = 'usuario:credenciais
        {login_atual : Login (e-mail) atual do usuário, ex: admin}
        {novo_login? : Novo login; se omitido, mantém o atual}';

    protected $description = 'Altera o login e a senha de um usuário e derruba as sessões dele';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('login_atual'))->first();

        if (! $user) {
            $this->error('Usuário não encontrado.');

            return self::FAILURE;
        }

        $novoLogin = $this->argument('novo_login') ?: $user->email;

        if ($novoLogin !== $user->email && User::where('email', $novoLogin)->exists()) {
            $this->error('Esse login já está em uso.');

            return self::FAILURE;
        }

        $senha = (string) $this->secret('Nova senha (mínimo 8 caracteres)');
        $repete = (string) $this->secret('Repita a nova senha');

        if (mb_strlen($senha) < 8) {
            $this->error('A senha precisa ter pelo menos 8 caracteres.');

            return self::FAILURE;
        }

        if ($senha !== $repete) {
            $this->error('As senhas não conferem.');

            return self::FAILURE;
        }

        $user->email = $novoLogin;
        $user->password = $senha; // cast 'hashed'
        $user->save();
        $user->tokens()->delete();

        $this->info("Pronto. Login: {$novoLogin}. Sessões antigas foram encerradas.");

        return self::SUCCESS;
    }
}
