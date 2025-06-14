<?php
// site/controllers/token.php

defined('_JEXEC') or die;

\JLoader::registerNamespace(
    'Firebase\\JWT',
    JPATH_LIBRARIES . '/jwtauth/utility/firebase',
    false,
    false
);

require_once JPATH_LIBRARIES . '/jwtauth/utility/firebase/JWT.php';

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Firebase\JWT\JWT;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\User\User;

class JwtAuthControllerToken extends BaseController
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

    public function generate()
    {
        $app = Factory::getApplication();
        $input = $app->input;

        $username = $input->getString('username');
        $password = $input->getString('password');

        $credentials = ['username' => $username, 'password' => $password];
        $options = ['remember' => false];

        $loginSuccess = $app->login($credentials, $options);

        header('Content-Type: application/json; charset=utf-8');

        if (!$loginSuccess) {
            $this->logJwt("Authentication failed");
            echo new JsonResponse(['success' => false, 'message' => 'Authentication failed'], 401);
            $app->close();
        }

        $user = Factory::getUser();

        $params = ComponentHelper::getParams('com_jwtauth');
        $secret = $params->get('jwt_secret', 'MY_SECRET');

        $issuedAt = time();
        $expiration = $issuedAt + (5 * 60); // 5 λεπτά

        $payload = [
            'sub' => $user->id,
            'name' => $user->username,
            'iat' => $issuedAt,
            'exp' => $expiration
        ];

        $token = JWT::encode($payload, $secret, 'HS256');

        $this->logJwt("Token issued for user");
        echo new JsonResponse(['token' => $token]);
        $app->close();
    }
}
