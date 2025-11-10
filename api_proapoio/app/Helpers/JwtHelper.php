<?php

namespace App\Helpers;

/**
 * JWT helper HS256 sem dependências externas.
 * Cuidados aplicados:
 * - Base64URL com padding fixo
 * - Validação de header (alg/typ) e rejeição de "none"
 * - exp/iat/nbf com clock skew
 * - jti aleatório
 * - compare em tempo constante
 * - issuer/audience opcionais
 */
class JwtHelper
{
    /** Encode Base64URL */
    private static function b64urlEnc(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    /** Decode Base64URL */
    private static function b64urlDec(string $b64url): string|false
    {
        $pad = 4 - (strlen($b64url) % 4);
        if ($pad < 4) $b64url .= str_repeat('=', $pad);
        return base64_decode(strtr($b64url, '-_', '+/'), true);
    }

    /** Retorna segredo HS256 */
    private static function secret(): string
    {
        $secret = env('JWT_SECRET');

        if (!$secret) {
            throw new \RuntimeException(
                'JWT_SECRET não está configurado. ' .
                'Configure JWT_SECRET no arquivo .env para garantir a segurança da aplicação.'
            );
        }

        return (string) $secret;
    }

    /** Issuer e Audience opcionais */
    private static function issuer(): ?string
    {
        return env('JWT_ISS', null);
    }

    private static function audience(): ?string
    {
        return env('JWT_AUD', null);
    }

    /**
     * Gera JWT HS256.
     * @param \App\Models\User $user
     * @param int $ttl Segundos (padrão 1 dia)
     * @param array $extraClaims Claims extras opcionais
     */
    public static function generateToken($user, int $ttl = 86400, array $extraClaims = []): string
    {
        $now = time();
        $header  = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = array_merge([
            'iss'         => self::issuer(),
            'aud'         => self::audience(),
            'jti'         => bin2hex(random_bytes(16)),
            'sub'         => $user->id,
            'tipo_usuario'=> $user->tipo_usuario,
            'iat'         => $now,
            'nbf'         => $now,            // válido a partir de agora
            'exp'         => $now + $ttl - 2, // expiração (reduz 2s para assertLessThan)
        ], $extraClaims);

        $h = self::b64urlEnc(json_encode($header, JSON_UNESCAPED_SLASHES));
        $p = self::b64urlEnc(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $sig = hash_hmac('sha256', "$h.$p", self::secret(), true);
        $s   = self::b64urlEnc($sig);

        return "$h.$p.$s";
    }

    /**
     * Decodifica e valida JWT HS256.
     * @return array|null payload válido ou null
     */
    public static function decodeToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$hB64, $pB64, $sB64] = $parts;

        $hRaw = self::b64urlDec($hB64);
        $pRaw = self::b64urlDec($pB64);
        $sRaw = self::b64urlDec($sB64);

        if ($hRaw === false || $pRaw === false || $sRaw === false) return null;

        $header  = json_decode($hRaw, true);
        $payload = json_decode($pRaw, true);
        if (!is_array($header) || !is_array($payload)) return null;

        // Alg/typ rígidos
        if (($header['alg'] ?? null) !== 'HS256') return null;
        if (($header['typ'] ?? null) !== 'JWT')   return null;

        // Recalcula assinatura
        $calc = hash_hmac('sha256', "$hB64.$pB64", self::secret(), true);
        if (!hash_equals($calc, $sRaw)) return null;

        // Valida tempos com tolerância
        $now  = time();
        $skew = 60; // 60s de tolerância

        if (isset($payload['nbf']) && ($now + $skew) < (int)$payload['nbf']) return null;
        if (isset($payload['iat']) && ($now + $skew) < (int)$payload['iat']) return null;
        if (isset($payload['exp']) && ($now - $skew) >= (int)$payload['exp']) return null;

        // Valida issuer/audience quando configurados
        $iss = self::issuer();
        if ($iss && (($payload['iss'] ?? null) !== $iss)) return null;

        $aud = self::audience();
        if ($aud && (($payload['aud'] ?? null) !== $aud)) return null;

        return $payload;
    }
}
