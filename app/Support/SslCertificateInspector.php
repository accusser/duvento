<?php

namespace App\Support;

use Carbon\Carbon;

class SslCertificateInspector
{
    public function expiryFor(string $host, int $timeout = 10): ?Carbon
    {
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
                'peer_name' => $host,
            ],
        ]);

        $client = @stream_socket_client(
            "ssl://{$host}:443",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context,
        );

        if ($client === false) {
            return null;
        }

        $params = stream_context_get_params($client);
        fclose($client);

        $cert = $params['options']['ssl']['peer_certificate'] ?? null;

        if ($cert === null) {
            return null;
        }

        $info = openssl_x509_parse($cert);
        $timestamp = $info['validTo_time_t'] ?? null;

        return $timestamp ? Carbon::createFromTimestamp($timestamp) : null;
    }
}
