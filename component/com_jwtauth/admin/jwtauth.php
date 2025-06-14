<?php
// admin/jwtauth.php

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

$controller = BaseController::getInstance('JwtAuth');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
