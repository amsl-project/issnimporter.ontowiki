<?php
/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2013, {@link http://aksw.org AKSW}
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
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
        $myImportActions = array(
            'issnimporter-titlelist' => array(
                'controller' => 'issnimporter',
                'action' => 'titlelistimport',
                'label' => 'Import an ISSN titlelist with csv upload',
                'description' => 'Tries to generate triples out of a csv file containing ' .
                    ' PISSN, EISSN, title and (optional) price (in that order)'
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
