<?php
/**
 * This file is part of the {@link http://amsl.technology amsl} project.
 *
 * @author Norman Radtke
 * @copyright Copyright (c) 2015, {@link http://ub.uni-leipzig.de Leipzig University Library}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * The main class for the basicimporter plugin.
 *
 * @category   OntoWiki
 * @package    Extensions_Issnimporter
 * @author     Norman Radtke <radtke@informatik.uni-leipzig.de>
 * @author     Sebastian Tramp <tramp@informatik.uni-leipzig.de>
 */
class IssnimporterPlugin extends OntoWiki_Plugin
{
    /*
     * our event method
     */
    public function onProvideImportActions($event)
    {
        $this->provideImportActions($event);
    }

    /*
     * here we add new import actions
     */
    private function provideImportActions($event)
    {
        $translate = OntoWiki::getInstance()->translate;
        $myImportActions = array(
            'issnimporter-titlelist' => array(
                'controller' => 'issnimporter',
                'action' => 'titlelistimport',
                'label' => $translate->translate('Import a titlelist with csv upload'),
                'description' => 'Tries to generate triples out of a csv file containing ' .
                    ' titles, identifiers and (optional) price'
            )
        );

        // sad but true, some php installation do not allow this
        if (!ini_get('allow_url_fopen')) {
            unset($myImportActions['basicimporter-rdfwebimport']);
        }

        $event->importActions = array_merge($event->importActions, $myImportActions);
        return $event;
    }
}
