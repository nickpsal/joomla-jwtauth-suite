<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class JwtAuthViewDashboard extends BaseHtmlView
{
    public function display($tpl = null)
    {
		ToolbarHelper::title('JWT Auth');
		ToolbarHelper::preferences('com_jwtauth');
        parent::display($tpl);
    }
}
