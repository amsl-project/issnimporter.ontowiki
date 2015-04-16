<?php
/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2013, {@link http://aksw.org AKSW}
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * Controller for OntoWiki Basicimporter Extension
 *
 * @category OntoWiki
 * @package  Extensions_Issnimporter
 * @author   Norman Radtke <radtke@informatik.uni-leipzig.de>
 * @author   Sebastian Tramp <mail@sebastian.tramp.name>
 */
class IssnimporterController extends OntoWiki_Controller_Component
{
    private $_model = null;
    private $_post = null;
    private $_translate = null;

    /**
     * init() Method to init() normal and add tabbed Navigation
     */
    public function init()
    {
        parent::init();
        $action = $this->_request->getActionName();
        $this->view->headLink()->appendStylesheet($this->_config->urlBase .
            'extensions/issnimporter/templates/issnimporter/issnimporter.css');
        $this->view->placeholder('main.window.title')->set('Import Data');
        $this->view->formActionUrl    = $this->_config->urlBase . 'issnimporter/' . $action;
        $this->view->formEncoding     = 'multipart/form-data';
        $this->view->formClass        = 'simple-input input-justify-left';
        $this->view->formMethod       = 'post';
        $this->view->formName         = 'importdata';
        $this->view->supportedFormats = $this->_erfurt->getStore()->getSupportedImportFormats();
        $this->_translate             = $this->_owApp->translate;

        if (!$this->isSelectedModelEditable()) {
            return;
        } else {
            $this->_model = $this->_owApp->selectedModel;
        }

        // add a standard toolbar
        $toolbar = $this->_owApp->toolbar;
        $toolbar->appendButton(
            OntoWiki_Toolbar::SUBMIT,
            array('name' => 'Import Data', 'id' => 'importdata')
        )->appendButton(
            OntoWiki_Toolbar::RESET,
            array('name' => 'Cancel', 'id' => 'importdata')
        );
        $this->view->placeholder('main.window.toolbar')->set($toolbar);
        OntoWiki::getInstance()->getNavigation()->disableNavigation();

        if ($this->_request->isPost()) {
            $this->_post = $this->_request->getPost();
        }
    }

    public function titlelistimportAction()
    {
        $this->view->placeholder('main.window.title')->set('Upload CSV title list');

        if ($this->_request->isPost()) {
            $data           = array();
            $nsAmsl         = 'http://vocab.ub.uni-leipzig.de/amsl/';
            $nsDct          = 'http://purl.org/dc/terms/';
            $nsXsd          = 'http://www.w3.org/2001/XMLSchema#';
            $post           = $this->_request->getPost();
            $upload         = new Zend_File_Transfer();
            $filesArray     = $upload->getFileInfo();
            $titleRow       = $post['title'];
            $delimiter      = $post['delimiter'];
            $enclosure      = $post['enclosure'];
            $year           = $post['validityyear'];
            $label          = $post['resourcelabel'];
            $targetType     = $post['collectIn'];
            $targetResource = $post['existendResource'];
            $regISBN        = '/\b(?:ISBN(?:: ?| ))?((?:97[89])?\d{9}[\dx])\b/i';

            // Check for valid year
            if (preg_match('/[2][01]\d{2}/',$post['validityyear'],$result)) {
                $year = $result[0];
            } else {
                $this->_owApp->appendErrorMessage($this->_translate->translate(
                    'The given value for "year" is empty or wrong. (2000-2199)'
                ));
                return;
            }

            // Check if URI is given or valid if "existent resource"-option is checked
            if ($targetType === 'existent' &&
                ($targetResource === '' || !(Erfurt_Uri::check($targetResource)))
            ) {
                $this->_owApp->appendErrorMessage($this->_translate->translate(
                    'The value for existent resource is empty or wrong. Please check the value ' .
                    'and try again.')
                );
                return;
            }

            $message = '';
            switch (true) {
                case empty($filesArray):
                    $message = 'upload went wrong. check post_max_size in your php.ini.';
                    break;
                case ($filesArray['source']['error'] == UPLOAD_ERR_INI_SIZE):
                    $message = 'The uploaded files\'s size exceeds the upload_max_filesize';
                    $message.= ' directive in php.ini.';

                    break;
                case ($filesArray['source']['error'] == UPLOAD_ERR_PARTIAL):
                    $message = 'The file was only partially uploaded.';
                    break;
                case ($filesArray['source']['error'] >= UPLOAD_ERR_NO_FILE):
                    $message = 'Please select a file to upload';
                    break;
            }

            if ($message != '') {
                $this->_owApp->appendErrorMessage($this->_translate->translate($message));
                return;
            }

            $file = $filesArray['source']['tmp_name'];
            // setting permissions to read the tempfile for everybody
            // (e.g. if db and webserver owned by different users)
            chmod($file, 0644);

            # READING CSV file
            $handle = fopen($file, 'r');
            $csvData = Array();
            if ($handle != FALSE) {
                while (($line =
                    fgetcsv($handle,600,$delimiter,$enclosure)) !== FALSE) {
                    $csvData[] = $line;
                }
            } else {
                $this->_owApp->appendErrorMessage("Could not read from CSV File");
                return;
            }
            fclose($handle);
        } else {
            return;
        }


        $modelIri = (string)$this->_model;
        $hash = md5(rand()) ;
        $item = $modelIri . 'resource/item/' . $hash . '/';
        $xsdDateTime = date('Y-m-d') . 'T' . date('H:i:s');

        # set a flag for writing labels of contract/package resource
        $writeLabel = true;

        if ($targetResource !== "" && Erfurt_Uri::check($targetResource)) {
            $mainResource = $targetResource ;
            $writeLabel = false;
        } else {
            $mainResource = $modelIri . 'resource/' .  $targetType . '/' .  $hash ;

            if ($targetType === 'package') {
                $data[$mainResource][EF_RDF_TYPE][] = array(
                    'type' => 'uri',
                    'value' => $nsAmsl . 'LicensePackage'
                );
            } else {
                $data[$mainResource][EF_RDF_TYPE][] = array(
                    'type' => 'uri',
                    'value' => $nsAmsl . 'AnnualContractData'
                );
            }
        }

        if ($writeLabel === true) {
            if (isset($label) && $label !== '') {
                $data[$mainResource][EF_RDFS_LABEL][] = array(
                    'type' => 'literal',
                    'value' => $label
                );
            } else {
                $data[$mainResource][EF_RDFS_LABEL][] = array(
                    'type' => 'literal',
                    'value' => '"Ein license ' . $targetType
                );
            }
        }

        $data[$mainResource][$nsDct . 'created'][] = array(
            'type' => 'literal',
            'datatype' => $nsXsd . 'dateTime',
            'value' => $xsdDateTime
        );

        $items = '';
        $errorCount = 0;
        $lineNumber = 0;

        if (isset($titleRow) && $titleRow === 'on') {
            $csvData = array_shift($csvData);
            $lineNumber++;
            echo "Erste Zeile übersprungen";
        }

        // iterate through CSV lines
        foreach ($csvData as $csvLine) {
            $lineNumber++;
            $proprietaryID = '';
            $doi = '';
            $foundEISSN = false;
            $foundPISSN = false;
            $itemUri    = '';
            $columnCount = count($csvLine);
            if ($columnCount >= 6) {
                $title = trim($csvLine[0]);

                // Find identifiers

                // check if title is not identifierless
                if (!($csvLine[1] === 'NA' && $csvLine[2] === 'NA' &&
                    $csvLine[3] === 'NA' && $csvLine[4] === 'NA')) {

                    // Start identifier search
                    $skipIdentifier = false;

                    // Search for Proprietary ID
                    if ($csvLine[3] !== '') {
                        $itemUri = $item . $lineNumber;
                        $proprietaryID = $csvLine[3];
                    }

                    // Search for DOI
                    if (isset($csvLine[4]) && $csvLine[4] !== '') {
                        if (substr($csvLine[4], 0, 3) === '10.') {
                            $itemUri = $item . $lineNumber;
                            $doi = $csvLine[4];
                        }
                    }

                    # Search for E-ISSN
                    if (preg_match_all('/\d{4}\-\d{3}[\dxX]/', $csvLine[2], $eissn)) {
                        # Found an EISSN, create URI and write first statemntes
                        $foundEISSN = true;
                        $itemUri = $item . $lineNumber;
                    } else {
                        $eissn = null;
                    }

                    # Search for P-ISSN
                    if (preg_match_all('/\d{4}\-\d{3}[\dxX]/', $csvLine[1], $pissn)) {
                        $foundPISSN = true;
                        $itemUri = $item . $lineNumber;
                    } else {
                        $pissn = null;
                    }

                    # Search for E-ISBN
                    if (preg_match_all($regISBN, str_replace('-', '', $csvLine[2]), $eisbn)) {
                        $itemUri = $item . $lineNumber;
                        // it is possible to match a ISSN expression within a ISBN string
                        // if this is the case reset the ISSN values
                        if ($foundEISSN === true) {
                            $eissn = null;
                        }
                    } else {
                        $eisbn = null;
                    }

                    # Search for P-ISBN
                    if (preg_match_all($regISBN, str_replace('-', '', $csvLine[1]), $pisbn)) {
                        $itemUri = $item . $lineNumber;
                        // it is possible to match a ISSN expression within a ISBN string
                        // if this is the case reset the ISSN values
                        if ($foundPISSN === true) {
                            $pissn = null;
                        }
                    } else {
                        $pisbn = null;
                    }
                } else {
                    // all identifier values == 'NA' => import without identifier (grey literature)
                    $skipIdentifier = true;
                    $itemUri = $item . $lineNumber;
                }


                # If identifier were found or identifierless import go on
                if ($itemUri !== '') {
                    $items .= $itemUri . ' a amsl:ContractItem ;' . PHP_EOL;
                    $data[$itemUri][EF_RDF_TYPE][] = array(
                        'type'     => 'uri',
                        'value'    => $nsAmsl . 'ContractItem'
                    );
                    $items .= '  amsl:contractItemOf ' . $mainResource . ' ;' . PHP_EOL;
                    $data[$itemUri][$nsAmsl . 'contractItemOf'][] = array(
                        'type'     => 'uri',
                        'value'    => $mainResource
                    );
                    $items .= '  dct:created  "' . $xsdDateTime . '"^^xsd:dateTime ;' . PHP_EOL;
                    $data[$itemUri][$nsDct . 'created'][] = array(
                        'type'     => 'literal',
                        'datatype' => $nsXsd . 'dateTime',
                        'value'    => $xsdDateTime
                    );
                    $items .= '  rdfs:label ' . '"' . $title . ' (' . $year . ')"  .' . PHP_EOL;
                    $data[$itemUri][EF_RDFS_LABEL][] = array(
                        'type'     => 'literal',
                        'value'    => $title . ' (' . $year . ')'
                    );

                    if (preg_match_all('/\d+(?:[\.,]\d+)?/',$csvLine[5],$price)) {
                        foreach($price[0] as $value) {
                            # Check if price contains comma and replace with
                            # dot if so
                            if (strpos($value,',')!==FALSE) {
                                $value = str_replace(',','.',$value);
                                # Check for missing dot and build a valid price
                            } else {
                                if (strpos($value,'.')===FALSE) {
                                    $value.= '.00';
                                }
                            }
                            $items.= $itemUri . ' amsl:itemPrice "' . $value .
                                '"^^xsd:decimal .' . PHP_EOL;
                            $data[$itemUri][$nsAmsl . 'itemPrice'][] = array(
                                'type'  => 'literal',
                                'value' => $value
                            );
                        }
                    }

                    if ($skipIdentifier === false) {
                        // write statement for proprietary ID
                        if ($proprietaryID !== '') {
                            $data[$itemUri][$nsAmsl . 'proprietaryID'][] = array(
                                'type' => 'literal',
                                'value' => $proprietaryID
                            );
                        }

                        // write statement for DOI
                        if ($doi !== '' && Erfurt_Uri::check('http://doi.org/' . $doi)
                        ) {
                            $data[$itemUri][$nsAmsl . 'doi'][] = array(
                                'type' => 'uri',
                                'value' => 'http://doi.org/' . $doi
                            );
                        }

                        // write statements linking to found E-ISSNs
                        if (isset($eissn[0])) {
                            foreach ($eissn[0] as $value) {
                                $items .= $itemUri . ' amsl:eissn <urn:ISSN:' .
                                    $value . '> .' . PHP_EOL;
                                $data[$itemUri][$nsAmsl . 'eissn'][] = array(
                                    'type' => 'uri',
                                    'value' => 'urn:ISSN:' . $value
                                );
                            }
                        }

                        // write statements linking to found E-ISBNs
                        if (isset($eisbn[0])) {
                            foreach ($eisbn[0] as $value) {
                                $items .= $itemUri . ' amsl:eisbn <urn:ISBN:' .
                                    $value . '> .' . PHP_EOL;
                                $data[$itemUri][$nsAmsl . 'eisbn'][] = array(
                                    'type' => 'uri',
                                    'value' => 'urn:ISBN:' . $value
                                );
                            }
                        }

                        # write statements linking to found P-ISSNs
                        if (isset($pissn[0])) {
                            foreach ($pissn[0] as $value) {
                                $items .= $itemUri . ' amsl:pissn <urn:ISSN:' .
                                    $value . '> .' . PHP_EOL;
                                $data[$itemUri][$nsAmsl . 'pissn'][] = array(
                                    'type' => 'uri',
                                    'value' => 'urn:ISSN:' . $value
                                );
                            }
                        }

                        // write statements linking to found P-ISBNs
                        if (isset($pisbn[0])) {
                            foreach ($pisbn[0] as $value) {
                                $items .= $itemUri . ' amsl:pisbn <urn:ISBN:' .
                                    $value . '> .' . PHP_EOL;
                                $data[$itemUri][$nsAmsl . 'pisbn'][] = array(
                                    'type' => 'uri',
                                    'value' => 'urn:ISBN:' . $value
                                );
                            }
                        }
                    }
                } else {
                    continue;
                }
            } else {
                $errorCount++;
                if ($errorCount === 1) {
                    $ignoredLines = $lineNumber;
                } else {
                    $ignoredLines .= ", " . $lineNumber;
                }
            }
        }

        if ($errorCount === count($csvData)) {
            $this->_owApp->appendErrorMessage("Nothing was imported. Please check the content of your CSV file, there are not enough columns or the seperator might be wrong.");
            return;
        } elseif ($errorCount === 1) {
            $this->_owApp->appendInfoMessage("Some data imported, but line " . $ignoredLines . " ignored due to missing columns or wrong seperators.");
        } elseif ($errorCount > 1) {
            $this->_owApp->appendInfoMessage("Some data imported, but lines " . $ignoredLines . "ignored due to missing columns or wrong seperators.");
        }

        try {
            $this->_import($data);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $this->_owApp->appendErrorMessage($message);
            return;
        }

        $this->_owApp->appendSuccessMessage('Data successfully imported.');
    }

    private function _import($data)
    {
        $modelIri = (string)$this->_model;
        $versioning = $this->_erfurt->getVersioning();
        // action spec for versioning
        $actionSpec = array();
        $actionSpec['type'] = 11;
        $actionSpec['modeluri'] = $modelIri;
        $actionSpec['resourceuri'] = $modelIri;

        try {
            // starting action
            $versioning->startAction($actionSpec);
            $this->_model->addMultipleStatements($data);
            // stopping action
            $versioning->endAction();
            // Trigger Reindex
            $indexEvent = new Erfurt_Event('onFullreindexAction');
            $indexEvent->trigger();
        } catch (Erfurt_Exception $e) {
            // re-throw
            throw new OntoWiki_Controller_Exception(
                'Could not import given model: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
