<?php
defined('_JEXEC') or die;

\JLoader::registerNamespace(
    'Firebase\\JWT',
    JPATH_LIBRARIES . '/jwtauth/utility/firebase',
    false,
    false
);

// require_once JPATH_LIBRARIES . '/jwtauth/utility/firebase/JWT.php';
// require_once JPATH_LIBRARIES . '/jwtauth/utility/firebase/Key.php';

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Authentication\Authentication;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class PlgApiAuthenticationJwtlegacy extends CMSPlugin
{
    private function logJwt($msg)
    {
        $logFile = JPATH_ROOT . '/jwt.log';
        $timestamp = date('[Y-m-d H:i:s] ');

        // Optional: limit log size to 1MB
        if (file_exists($logFile) && filesize($logFile) > 1048576) {
            file_put_contents($logFile, "[Log truncated on {$timestamp}]\n");
        }

        file_put_contents($logFile, $timestamp . $msg . "\n", FILE_APPEND);
    }

    public function onUserAuthenticate($credentials, $options, &$response)
    {
        //$this->logJwt("onUserAuthenticate triggered");
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($requestUri, '/api/index.php/v1/token') !== false) {
            //$this->logJwt("JWT check skipped for /v1/token");
            return;
        }

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
        //$this->logJwt("Authorization header: " . ($authHeader ? 'found' : 'missing'));

        if (!$authHeader && function_exists('apache_request_headers')) {
            //$this->logJwt("Trying apache_request_headers fallback");
            $requestHeaders = apache_request_headers();
            $authHeader = $requestHeaders['Authorization'] ?? $requestHeaders['authorization'] ?? '';
        }

        $allowedMethods = ['POST', 'GET', 'PUT'];
        $currentMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->logJwt("HTTP method: $currentMethod");

        if (!in_array($currentMethod, $allowedMethods)) {
            $this->logJwt("Method blocked: $currentMethod");
            $response->status = Authentication::STATUS_FAILURE;
            $response->error_message = 'HTTP method not allowed';
            return false;
        }

        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';

        $allowedIpsRaw = $this->params->get('allowed_ips', '');
        $allowedIps = array_map('trim', explode(',', $allowedIpsRaw));
        $allowedIps = array_filter($allowedIps, fn($ip) => filter_var($ip, FILTER_VALIDATE_IP));

        if (!in_array($clientIp, $allowedIps)) {
            $this->logJwt("Blocked IP: $clientIp");
            $response->status = Authentication::STATUS_FAILURE;
            $response->error_message = 'Access denied from this IP address';
            return false;
        }

        if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
            $this->logJwt("Bearer token missing or invalid");
            $response->status = Authentication::STATUS_FAILURE;
            $response->error_message = 'Missing Authorization header';
            return false;
        }

        $jwt = trim(str_ireplace('Bearer', '', $authHeader));
        //$this->logJwt("JWT received");

        $secret = $this->params->get('jwt_secret', 'MY_SECRET');

        try {
            $decoded = JWT::decode($jwt, new Key($secret, 'HS256'));
            $this->logJwt("JWT decoded successfully");
        } catch (\Exception $e) {
            $this->logJwt("JWT decode failed: " . $e->getMessage());
            $response->status = Authentication::STATUS_FAILURE;
            $response->error_message = 'Invalid JWT: ' . $e->getMessage();
            return false;
        }

        $userId = $decoded->sub ?? $decoded->user_id ?? null;
        $this->logJwt("User ID from token: " . ($userId ?? 'null'));

        if (!$userId) {
            $response->status = Authentication::STATUS_FAILURE;
            $response->error_message = 'No user id in JWT token';
            return false;
        }

        /** @var UserFactoryInterface $userFactory */
        $userFactory = Factory::getContainer()->get(UserFactoryInterface::class);
        $user = $userFactory->loadUserById((int) $userId);

        if (!$user || !$user->id) {
            $this->logJwt("User not found");
            $response->status = Authentication::STATUS_FAILURE;
            $response->error_message = 'User not found';
            return false;
        }

        $response->status = Authentication::STATUS_SUCCESS;
        $response->username = $user->username;
        $response->email = $user->email;
        $response->fullname = $user->name;

        $this->logJwt("Authentication successful for user ID: {$user->id}");
        return true;
    }
}
