<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

class SSOService
{
    public function verifySSOToken(Request $request)
    {
        $pemPath = storage_path((string) config('services.up_sso.client_pem_path'));

        $pem = file_get_contents($pemPath);
        if ($pem === false) abort(500, "Cannot read public key file: {$pemPath}");

        $publicKey = openssl_pkey_get_public($pem);
        if ($publicKey === false) abort(500, 'Invalid public key PEM: ' . openssl_error_string());

        $sigB64 = trim((string) $request->header('X-Signature'));
        if (!$sigB64) abort(401, 'Missing X-Signature header');

        $signature = base64_decode($sigB64, true);
        if ($signature === false) abort(401, 'Invalid base64 signature');

        $rawBody = $request->getContent();

        $isValid = openssl_verify($rawBody, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        // dd($isValid);
        if ($isValid !== 1) {
            // Helpful debug: compare hashes (remove after fix)
            logger()->error('SSO signature invalid', [
                'verify_result' => $isValid,
                'openssl_error' => openssl_error_string(),
                'raw_body'      => $rawBody,
                'raw_sha256'    => hash('sha256', $rawBody),
                'sig_b64'       => $sigB64,
            ]);

            abort(401, 'Invalid signature');
        }

        return response()->json(['status' => true, 'message' => 'Valid signature']);
    }


    /**
     * Find user by email
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}
