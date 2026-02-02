<?php
// /public_html/includes/qr_crypto.php
// AES-256-GCM + Base64URL

function b64url_encode(string $bin): string {
  return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}

function b64url_decode(string $s): string {
  $s = strtr($s, '-_', '+/');
  $pad = strlen($s) % 4;
  if ($pad) $s .= str_repeat('=', 4 - $pad);
  return base64_decode($s);
}

function qr_get_key(): string {
  // 32 bytes (256-bit)
  // Guardala en config o env. De momento: hardcode (pero cambiála y no la subas a GitHub)
  $hex = '20d7c37896f56bb32d324e6c276eb4d86343269db5829e23f5e96f61cd054c2a';
  // ejemplo real: bin2hex(random_bytes(32)) y lo pegás acá
  $key = @hex2bin($hex);
  if ($key === false || strlen($key) !== 32) {
    throw new Exception('QR_KEY inválida (debe ser 32 bytes en hex: 64 caracteres)');
  }
  return $key;
}

function qr_encrypt_payload(array $payload): string {
  $key = qr_get_key();
  $iv = random_bytes(12); // recomendado para GCM
  $plaintext = json_encode($payload, JSON_UNESCAPED_UNICODE);
  $tag = '';
  $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
  if ($cipher === false) throw new Exception('No se pudo cifrar');

  // token = v1.<iv>.<tag>.<cipher>
  return 'v1.' . b64url_encode($iv) . '.' . b64url_encode($tag) . '.' . b64url_encode($cipher);
}

function qr_decrypt_token(string $token): array {
  $parts = explode('.', $token);
  if (count($parts) !== 4 || $parts[0] !== 'v1') throw new Exception('Token inválido');

  $iv = b64url_decode($parts[1]);
  $tag = b64url_decode($parts[2]);
  $cipher = b64url_decode($parts[3]);

  $key = qr_get_key();

  $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
  if ($plain === false) throw new Exception('Token inválido o corrupto');

  $data = json_decode($plain, true);
  if (!is_array($data)) throw new Exception('Payload inválido');
  return $data;
}
