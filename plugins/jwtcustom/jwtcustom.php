<?php
defined("_JEXEC") or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;


class PlgWebservicesJwtcustom extends CMSPlugin
{
    protected $autoloadLanguage = true;

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

    public function onBeforeApiRoute(&$router)
    {
        $router->createCRUDRoutes(
            "v1/token",
            "jwtauth",
            ["component" => "com_jwtauth"]
        );

        $this->handleJwtTokenRequest();
    }

    private function handleJwtTokenRequest()
    {
        $requestUri = $_SERVER["REQUEST_URI"];

        if (strpos($requestUri, "/v1/token") !== false) {
            $app = Factory::getApplication();

            // Αρχικό input
            $input = $app->input;
            $username = $input->get('username', '', 'STRING');
            $password = $input->get('password', '', 'STRING');

            // Log
            $this->logJwt("Username: $username, Password: $password");

            // Pass POST data (ώστε ο helper να τα πάρει)
            $_POST['username'] = $username;
            $_POST['password'] = $password;

            // Φόρτωση helper
            require_once JPATH_SITE . '/components/com_jwtauth/helpers/token.php';

            // Εκτέλεση
            \JwtauthHelperToken::generate();

            $app->close();
        }
    }
}
