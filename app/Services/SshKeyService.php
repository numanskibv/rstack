<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class SshKeyService
{
    public function keyDirectory(User $user): string
    {
        return storage_path('app/ssh/users/' . $user->id);
    }

    public function privateKeyPath(User $user): string
    {
        return $this->keyDirectory($user) . '/id_ed25519';
    }

    public function publicKeyPath(User $user): string
    {
        return $this->privateKeyPath($user) . '.pub';
    }

    public function hasKey(User $user): bool
    {
        return file_exists($this->privateKeyPath($user));
    }

    public function generate(User $user): string
    {
        $dir = $this->keyDirectory($user);

        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $privateKey = $this->privateKeyPath($user);

        // Remove existing keypair first
        foreach ([$privateKey, $privateKey . '.pub'] as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $comment = "rstack-user-{$user->id}@" . config('app.name', 'rstack');

        $result = Process::run([
            'ssh-keygen',
            '-t',
            'ed25519',
            '-C',
            $comment,
            '-f',
            $privateKey,
            '-N',
            '', // empty passphrase
        ]);

        if (! $result->successful()) {
            throw new RuntimeException('ssh-keygen failed: ' . $result->errorOutput());
        }

        // Set strict permissions on private key
        chmod($privateKey, 0600);

        $publicKey = trim(file_get_contents($privateKey . '.pub'));
        $fingerprint = $this->readFingerprint($privateKey);

        $user->update([
            'ssh_public_key'       => $publicKey,
            'ssh_key_fingerprint'  => $fingerprint,
        ]);

        return $publicKey;
    }

    private function readFingerprint(string $privateKeyPath): string
    {
        $result = Process::run(['ssh-keygen', '-lf', $privateKeyPath]);

        if (! $result->successful()) {
            return '';
        }

        // Output: "256 SHA256:xxxx comment (ED25519)"
        $parts = explode(' ', trim($result->output()));

        return $parts[1] ?? '';
    }
}
