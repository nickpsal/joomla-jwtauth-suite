<?php
// components/com_jwtauth/helpers/token.php

defined('_JEXEC') or die;

\JLoader::registerNamespace(
    'Firebase\\JWT',
    JPATH_LIBRARIES . '/jwtauth/utility/firebase',
    false,
    false
);

use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\User\User;

use Firebase\JWT\JWT;

class JwtauthHelperToken
{
    public static function generate()
    {
        self::log("Generate Token helper...");
        $app = Factory::getApplication();
        $input = $app->input;

        // Read credentials from multiple sources (API-safe)
        $raw = json_decode(file_get_contents('php://input'), true);
        $username = $input->get('username', '', 'STRING') ?: ($raw['username'] ?? $_POST['username'] ?? '');
        $password = $input->get('password', '', 'STRING') ?: ($raw['password'] ?? $_POST['password'] ?? '');

        // Debug log
        // self::log("Username: " . var_export($username, true));
        // self::log("Password: " . var_export($password, true));

        if (empty($username) || empty($password)) {
            echo new JsonResponse(['success' => false, 'message' => 'Missing credentials'], 400);
            $app->close();
        }

        // Validate user
        $userId = UserHelper::getUserId($username);
        $user = User::getInstance($userId);

        if (!$user || !$user->id || !UserHelper::verifyPassword($password, $user->password, $user->id)) {
            //self::log("Authentication failed for user: {$username}");
            echo new JsonResponse(['success' => false, 'message' => 'Authentication failed'], 401);
            $app->close();
        }

        // Generate JWT
        $params = ComponentHelper::getParams('com_jwtauth');
        $secret = $params->get('jwt_secret', 'MY_SECRET');

        $issuedAt = time();
        $expiration = $issuedAt + (5 * 60);

        $payload = [
            'sub' => $user->id,
            'name' => $user->username,
            'iat' => $issuedAt,
            'exp' => $expiration
        ];

        $token = JWT::encode($payload, $secret, 'HS256');

        self::log("Token issued for user: {$user->id}");
        header('Content-Type: application/json');
        echo new JsonResponse(['token' => $token]);
        $app->close();
    }

    private static function log($msg)
    {
        $logFile = JPATH_ROOT . '/jwt.log';
        $timestamp = date('[Y-m-d H:i:s] ');
        file_put_contents($logFile, $timestamp . $msg . "\n", FILE_APPEND);
    }
}
