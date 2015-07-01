<?php

/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2013, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * Helper class for the Issnimporter form.
 *
 * @category OntoWiki
 * @package Extensions_Issnimporter
 * @copyright Copyright (c) 2015, {@link http://ub.uni-leipzig.de University Leipzig Library}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class IssnimporterHelper extends OntoWiki_Component_Helper
{
    
    /**
     * The module view
     *
     * @var Zend_View_Interface
     */
    public $view = null;
    
    public function init() {

        // init view
        if (null === $this->view) {
            $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
            if (null === $viewRenderer->view) {
                $viewRenderer->initView();
            }
            $this->view = clone $viewRenderer->view;
            $this->view->clearVars();
        }

        $this->view->headScript()->appendFile($this->_config->urlBase . 'extensions/issnimporter/templates/issnimporter/js/typeahead.bundle.js');
        $this->view->headScript()->appendFile($this->_config->urlBase . 'extensions/issnimporter/templates/issnimporter/js/handlebars.js');
        $this->view->headScript()->appendFile($this->_config->urlBase . 'extensions/issnimporter/templates/issnimporter/js/search.js');
        $this->view->headScript()->appendFile($this->_config->urlBase . 'extensions/themes/silverblue/scripts/libraries/jquery-ui.js');
        $this->view->headLink()->appendStylesheet($this->_config->urlBase . 'extensions/issnimporter/templates/issnimporter/issnimporter.css');
        $this->view->headLink()->appendStylesheet($this->_config->urlBase . 'extensions/issnimporter/templates/issnimporter/hint.min.css');
    }
}

