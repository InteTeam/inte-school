<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateVapidKeys extends Command
{
    protected $signature = 'vapid:generate';

    protected $description = 'Generate VAPID key pair for Web Push notifications';

    public function handle(): int
    {
        $keyPair = $this->generateKeyPair();

        $this->line('');
        $this->info('VAPID key pair generated. Add these to your .env:');
        $this->line('');
        $this->line('VAPID_PUBLIC_KEY=' . $keyPair['publicKey']);
        $this->line('VAPID_PRIVATE_KEY=' . $keyPair['privateKey']);
        $this->line('');

        if ($this->confirm('Write these keys to .env automatically?', false)) {
            $this->writeToEnv($keyPair['publicKey'], $keyPair['privateKey']);
            $this->info('.env updated.');
        }

        return self::SUCCESS;
    }

    /** @return array{publicKey: string, privateKey: string} */
    private function generateKeyPair(): array
    {
        $keyPair = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        if ($keyPair === false) {
            $this->error('Failed to generate key pair. Ensure OpenSSL is available.');
            exit(1);
        }

        $details = openssl_pkey_get_details($keyPair);

        if ($details === false) {
            $this->error('Failed to extract key details.');
            exit(1);
        }

        $publicKey = base64_encode(
            "\x04" .
            str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT) .
            str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT)
        );

        openssl_pkey_export($keyPair, $privateKeyPem);
        $privateKeyDetails = openssl_pkey_get_details(openssl_pkey_get_private($privateKeyPem));
        $privateKey = base64_encode(str_pad($privateKeyDetails['ec']['d'] ?? '', 32, "\x00", STR_PAD_LEFT));

        return [
            'publicKey' => strtr($publicKey, '+/', '-_'),
            'privateKey' => strtr($privateKey, '+/', '-_'),
        ];
    }

    private function writeToEnv(string $publicKey, string $privateKey): void
    {
        $envPath = base_path('.env');
        $contents = file_get_contents($envPath);

        if ($contents === false) {
            $this->error('.env file not found.');
            return;
        }

        $contents = $this->replaceOrAppend($contents, 'VAPID_PUBLIC_KEY', $publicKey);
        $contents = $this->replaceOrAppend($contents, 'VAPID_PRIVATE_KEY', $privateKey);

        file_put_contents($envPath, $contents);
    }

    private function replaceOrAppend(string $contents, string $key, string $value): string
    {
        $line = "{$key}={$value}";

        if (preg_match("/^{$key}=/m", $contents)) {
            return preg_replace("/^{$key}=.*/m", $line, $contents) ?? $contents;
        }

        return $contents . PHP_EOL . $line;
    }
}
