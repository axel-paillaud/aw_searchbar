<?php
/**
 * 2007-2020 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Aw_Searchbar extends Module implements WidgetInterface
{
    /**
     * @var string Name of the module running on PS 1.6.x. Used for data migration.
     */
    const PS_16_EQUIVALENT_MODULE = 'blocksearch';

    private $templateFile;

    public function __construct()
    {
        $this->name = 'aw_searchbar';
        $this->tab = 'front_office_features';
        $this->author = 'Axelweb';
        $this->version = '2.1.3';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->trans('Search bar', [], 'Modules.Searchbar.Admin');
        $this->description = $this->trans('Help your visitors find what they are looking for, add a quick search field to your store.', [], 'Modules.Searchbar.Admin');

        $this->ps_versions_compliancy = ['min' => '1.7.8.0', 'max' => _PS_VERSION_];

        $this->templateFile = 'module:aw_searchbar/aw_searchbar.tpl';
    }

    public function install()
    {
        // Migrate data from 1.6 equivalent module (if applicable), then uninstall
        if (Module::isInstalled(self::PS_16_EQUIVALENT_MODULE)) {
            $oldModule = Module::getInstanceByName(self::PS_16_EQUIVALENT_MODULE);
            if ($oldModule) {
                $oldModule->uninstall();
            }
        }

        return parent::install()
            && $this->registerHook('displaySearch')
            && $this->registerHook('displayCenterBanner')
            && $this->registerHook('displayHeader')
        ;
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->addJqueryUI('ui.autocomplete');

        $this->context->controller->registerStylesheet('modules-searchbar', 'modules/' . $this->name . '/aw_searchbar.css');
        $this->context->controller->registerJavascript('modules-searchbar', 'modules/' . $this->name . '/aw_searchbar.js', ['position' => 'bottom', 'priority' => 150]);
    }

    /*
     * Add only useful jquery-ui lib, instead of the entire jquery-ui.min.js file
     * This optimized import method can't work if another module import jquery-ui.min.js
     * (eq. gformbuilderpro)
     */
    private function addJqueryUi()
    {
        $theme = 'base';
        $css_theme_path = '/js/jquery/ui/themes/' . $theme . '/minified/jquery.ui.theme.min.css';
        $css_path = '/js/jquery/ui/themes/' . $theme . '/minified/jquery-ui.min.css';

        // Charge uniquement les fichiers nécessaires pour Autocomplete
        $this->context->controller->registerJavascript('jquery-ui-core', '/js/jquery/ui/jquery.ui.core.min.js', ['position' => 'bottom', 'priority' => 49]);
        $this->context->controller->registerJavascript('jquery-ui-widget', '/js/jquery/ui/jquery.ui.widget.min.js', ['position' => 'bottom', 'priority' => 49]);
        $this->context->controller->registerJavascript('jquery-ui-position', '/js/jquery/ui/jquery.ui.position.min.js', ['position' => 'bottom', 'priority' => 49]);
        $this->context->controller->registerJavascript('jquery-ui-menu', '/js/jquery/ui/jquery.ui.menu.min.js', ['position' => 'bottom', 'priority' => 49]);
        $this->context->controller->registerJavascript('jquery-ui-autocomplete', '/js/jquery/ui/jquery.ui.autocomplete.min.js', ['position' => 'bottom', 'priority' => 49]);

        $this->context->controller->registerStylesheet('jquery-ui-theme', $css_theme_path, ['media' => 'all', 'priority' => 95]);
        $this->context->controller->registerStylesheet('jquery-ui', $css_path, ['media' => 'all', 'priority' => 90]);
    }

    public function getWidgetVariables($hookName, array $configuration = [])
    {
        $widgetVariables = [
            'search_controller_url' => $this->context->link->getPageLink('search', null, null, null, false, null, true),
        ];

        /** @var array $templateVars */
        $templateVars = $this->context->smarty->getTemplateVars();
        if (is_array($templateVars) && !array_key_exists('search_string', $templateVars)) {
            $widgetVariables['search_string'] = '';
        }

        return $widgetVariables;
    }

    public function renderWidget($hookName, array $configuration = [])
    {
        $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));

        return $this->fetch($this->templateFile);
    }
}
