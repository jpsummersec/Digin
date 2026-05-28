<?php

// Spotify token helper utilities
// This file stores and refreshes Spotify tokens in the existing user.spotify_token field

// Open a database connection using the app's Docker environment variables

function getDbConnection(): mysqli
{
    $host = getenv('DB_SERVER') ?: 'mysql';
    $user = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    $database = getenv('DB_NAME') ?: 'digin';

    $db = new mysqli($host, $user, $password, $database);
    if ($db->connect_errno) {
        die('DB connection failed: ' . $db->connect_error);
    }
    $db->set_charset('utf8mb4');
    return $db;
}


// Return the current logged-in user's ID from session state

function getCurrentUserId(): ?int
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    return (int) $_SESSION['user_id'];
}


// Load the stored Spotify token JSON payload from the user record

function loadSpotifyTokenFromUser(mysqli $db, int $userId): ?array
{
    $stmt = $db->prepare('SELECT spotify_token FROM `user` WHERE user_id = ? LIMIT 1');
    if (!$stmt) {
        die('DB query failed: ' . $db->error);
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($spotifyToken);
    $stmt->fetch();
    $stmt->close();

    if (empty($spotifyToken)) {
        return null;
    }

    $decoded = json_decode($spotifyToken, true);
    if (!is_array($decoded) || empty($decoded['access_token'])) {
        return null;
    }

    return [
        'access_token' => $decoded['access_token'],
        'refresh_token' => $decoded['refresh_token'] ?? '',
        'expires_at' => isset($decoded['expires_at']) ? (int) $decoded['expires_at'] : 0,
    ];
}


// Save or update the user's Spotify token payload in the database

function saveSpotifyTokenToUser(mysqli $db, int $userId, string $accessToken, string $refreshToken, int $expiresAt): bool
{
    $payload = json_encode([
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'expires_at' => $expiresAt,
    ], JSON_UNESCAPED_SLASHES);

    $stmt = $db->prepare('UPDATE `user` SET spotify_token = ? WHERE user_id = ?');
    if (!$stmt) {
        die('DB query failed: ' . $db->error);
    }

    $stmt->bind_param('si', $payload, $userId);
    $success = $stmt->execute();
    if (!$success) {
        die('DB save failed: ' . $stmt->error);
    }

    $stmt->close();
    return true;
}


// Refresh an expired Spotify access token using the stored refresh token
 
function refreshSpotifyAccessToken(string $refreshToken, string $clientId, string $clientSecret): array
{
    $ch = curl_init('https://accounts.spotify.com/api/token');
    $data = [
        'grant_type' => 'refresh_token',
        'refresh_token' => $refreshToken,
    ];

    $headers = [
        'Authorization: Basic ' . base64_encode("$clientId:$clientSecret"),
        'Content-Type: application/x-www-form-urlencoded',
    ];

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        return ['error' => 'cURL error: ' . curl_error($ch)];
    }

    if ($httpCode !== 200) {
        return ['error' => 'Spotify refresh error (HTTP ' . $httpCode . '): ' . $response];
    }

    $result = json_decode($response, true);
    if (!is_array($result) || empty($result['access_token'])) {
        return ['error' => 'Spotify refresh returned invalid data'];
    }

    return [
        'access_token' => $result['access_token'],
        'refresh_token' => $result['refresh_token'] ?? $refreshToken,
        'expires_in' => $result['expires_in'] ?? 3600,
    ];
}

// Store Spotify auth state in the current PHP session for the current request

function setSpotifySessionTokens(array $data): void
{
    $_SESSION['access_token'] = $data['access_token'];
    $_SESSION['refresh_token'] = $data['refresh_token'];
    $_SESSION['spotify_token_expires'] = time() + ($data['expires_in'] ?? 3600);
}
