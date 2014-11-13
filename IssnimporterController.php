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

    /**
     * init() Method to init() normal and add tabbed Navigation
     */
    public function init()
    {
        parent::init();
        $action = $this->_request->getActionName();
        $this->view->placeholder('main.window.title')->set('Import Data');
        $this->view->formActionUrl    = $this->_config->urlBase . 'issnimporter/' . $action;
        $this->view->formEncoding     = 'multipart/form-data';
        $this->view->formClass        = 'simple-input input-justify-left';
        $this->view->formMethod       = 'post';
        $this->view->formName         = 'importdata';
        $this->view->supportedFormats = $this->_erfurt->getStore()->getSupportedImportFormats();

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
            $post = $this->_request->getPost();
            $upload = new Zend_File_Transfer();
            $filesArray = $upload->getFileInfo();
            $delimiter = $post['delimiter'];
            $enclosure = $post['enclosure'];
            $year = $post['validityyear'];
            $label = $post['resourcelabel'];
            $targetType = $post['collectIn'];
            $targetResource = $post['existendResource'];
            # Check for valid year
            if (preg_match_all('/[2][01]\d{2}/',$post['validityyear'],$result)) {
                $year = $result[0][0];
            } else {
                $year = date("Y");
            }

            $message = '';
            switch (true) {
                case empty($filesArray):
                    $message = 'upload went wrong. check post_max_size in your php.ini.';
                    break;
                case ($filesArray['source']['error'] == UPLOAD_ERR_INI_SIZE):
                    $message = 'The uploaded files\'s size exceeds the upload_max_filesize directive in php.ini.';
                    break;
                case ($filesArray['source']['error'] == UPLOAD_ERR_PARTIAL):
                    $message = 'The file was only partially uploaded.';
                    break;
                case ($filesArray['source']['error'] >= UPLOAD_ERR_NO_FILE):
                    $message = 'Please select a file to upload';
                    break;
            }

            if ($message != '') {
                $this->_owApp->appendErrorMessage($message);
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
        $hash = md5(date(DATE_ATOM)) ;
        # Write prefixes
        $data = '@prefix item: <' . $modelIri . 'resource/' . 'item/' . $hash . '/> .' . PHP_EOL;
        $data.= '@prefix bibrm: <http://vocab.ub.uni-leipzig.de/bibrm/> . ' . PHP_EOL;
        $data.= '@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> . ' . PHP_EOL;
        $data.= '@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> . ' . PHP_EOL;
        $data.= '@prefix dc: <http://purl.org/dc/elements/1.1/> . ' . PHP_EOL;
        $data.= '@prefix dct: <http://purl.org/dc/terms/> . ' . PHP_EOL;
        $data.= '@prefix xsd: <http://www.w3.org/2001/XMLSchema#> . ' . PHP_EOL;
        $data.= PHP_EOL;
        $xsdDateTime = date('Y-m-d') . 'T' . date('H:i:s');

        # set a flag for writing labels of contract/package resource
        $writeLabel = true;

        echo "targetResource: " . $targetResource . PHP_EOL;
        if ($targetResource !== "" && Erfurt_Uri::check($targetResource)) {
            $mainResource = '<' . $targetResource . '>';
            $writeLabel = false;
        } else {
            $mainResource = '<' . $modelIri . 'resource/' .  $targetType . '/' .  $hash . '>';

            if ($targetType === 'package') {
                $data.= $mainResource . ' a bibrm:LicensePackage .'. PHP_EOL;
            } else {
                $data.= $mainResource . ' a bibrm:AnnualContractData .'. PHP_EOL;
            }
        }

        if ($writeLabel === true) {
            if (isset($label) && $label !== '') {
                $data.= $mainResource . ' rdfs:label "' . $label . '" . ' . PHP_EOL;
            } else {
                $data.= $mainResource . ' rdfs:label "Ein license ' . $targetType . '".' . PHP_EOL;
            }
        }

        $data.= $mainResource . ' dct:created  "' . $xsdDateTime . '"^^xsd:dateTime .' . PHP_EOL;

        $items = '';
        $errorCount = 0;
        $lineNumber = 0;
        foreach ($csvData as $csvLine) {
            $lineNumber++;
            $foundEISSN = false;
            $columnCount = count($csvLine);
            if ($columnCount >= 4) {
                $title = trim($csvLine[2]);
                $price = preg_split('/\d+([\.,]\d+)?/',$csvLine[3]);

                # Create a 'unique' URI so the same ISSN can be used in other 
                # contracts/packages/packages/packages/packages/packages/packages/packages again

                # Search for EISSN
                if (preg_match_all('/\d{4}\-\d{3}[\dxX]/',$csvLine[1],$eissn)) {
                    # Found an EISSN, create URI and write first statemntes
                    $foundEISSN = true;
                    $itemUri =  'item:' . $eissn[0][0] ;
                    $items.= $itemUri . ' a bibrm:ContractItem ;' .PHP_EOL;
                    $items.= '  bibrm:contractItemOf ' . $mainResource . ' ;'. PHP_EOL;
                    $items.= '  dct:created  "' . $xsdDateTime . '"^^xsd:dateTime ;' . PHP_EOL;
                    $items.= '  rdfs:label ' . '"' . $title . ' (' . $year .')"  .' . PHP_EOL;
                    # if price exists, analyze value and write price statements
                    if (preg_match_all('/\d+(?:[\.,]\d+)?/',$csvLine[3],$price)) {
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
                            $items.= $itemUri . ' bibrm:itemPrice "' . $value .
                                '"^^xsd:decimal .' . PHP_EOL;
                        }
                    }

                    # write statements linking to found EISSNs
                    foreach ($eissn[0] as $value) {
                        $items.= $itemUri . ' bibrm:eissn <urn:ISSN:' . $value . '> .' . PHP_EOL;
                    }
                }

                # Search for PISSN
                if (preg_match_all('/\d{4}\-\d{3}[\dxX]/',$csvLine[0],$pissn)) {
                    # If no EISSN was found, create URI with PISSN and write 
                    # statements
                    if ($foundEISSN === false) {
                        $itemUri = 'item:' . $pissn[0][0];
                        $items.= $itemUri . ' a bibrm:ContractItem ;' .PHP_EOL;
                        $items.= '  bibrm:contractItemOf ' . $mainResource . ' ;'. PHP_EOL;
                        $items.= '  dct:created  "' . $xsdDateTime . '"^^xsd:dateTime ;' . PHP_EOL;
                        $items.= '  rdfs:label ' . '"' . $title . ' (' . $year .')"  .' . PHP_EOL;
                        # if price exists, write price statements
                        if (preg_match_all('/\d+(?:[\.,]\d+)?/',$csvLine[3],$price)) {
                            foreach($price[0] as $value) {
                            $items.= $itemUri . ' bibrm:itemPrice "' . $value . '" .' . PHP_EOL;
                            }
                        }
                    }
                    # write statements linking to found PISSNs
                    foreach ($pissn[0] as $value) {
                        $items.= $itemUri . ' bibrm:pissn <urn:ISSN:' . $value . '> .' .PHP_EOL;
                    }
                } else {
                    continue;
                }
            } else {
                $errorCount++;
                if ($errorCount === 1) {
                    $ignoredLines = $lineNumber ;
                } else {
                    $ignoredLines.= ", " . $lineNumber ;
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

        $data.= $items;

        $importFile = tempnam(sys_get_temp_dir(), 'ow');
        $tmp = fopen($importFile, 'wb');
        fwrite($tmp, $data);
        fclose($tmp);
        $locator  = Erfurt_Syntax_RdfParser::LOCATOR_FILE;

        try {
            $this->_import($importFile, $locator);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $this->_owApp->appendErrorMessage($message);
            return;
        }

        $this->_owApp->appendSuccessMessage('Data successfully imported.');
    }

    private function _import($fileOrUrl, $locator)
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
            $this->_erfurt->getStore()->importRdf($modelIri, $fileOrUrl, 'ttl', $locator);
            // stopping action
            $versioning->endAction(); 
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
