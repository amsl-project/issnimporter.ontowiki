<?php
/**
 * This file is part of the {@link http://amsl.technology amsl} project.
 *
 * @author Norman Radtke
 * @author Annika Domin
 * @copyright Copyright (c) 2015, {@link http://ub.uni-leipzig.de Leipzig University Library}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * Controller for OntoWiki Basicimporter Extension
 *
 * @category OntoWiki
 * @package  Extensions_Issnimporter
 * @author   Norman Radtke <radtke@informatik.uni-leipzig.de>
 * @author   Annika Domin <domin@ub.uni-leipzig.de>
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

    /**
     * This action will return a json_encoded array containing contracts and license packages
     * it will be used for suggestions in import form
     */
    public function getcontractsAction()
    {
        // tells OntoWiki to not apply the template to this action
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

/*        if ($this->_owApp->selectedModel === null) {
            $this->_response->setBody(json_encode(array()));
        } else {*/
            $this->_response->setBody($this->_getContractsJSONData());
        //}


    }

    public function titlelistimportAction()
    {
        $this->view->placeholder('main.window.title')->set('Upload CSV title list');

        if ($this->_request->isPost()) {
            $data           = array();
            $nsAmsl         = 'http://vocab.ub.uni-leipzig.de/amsl/';
            $nsDc          = 'http://purl.org/dc/elements/1.1/';
            $nsDct          = 'http://purl.org/dc/terms/';
            $nsXsd          = 'http://www.w3.org/2001/XMLSchema#';
            $post           = $this->_request->getPost();
            $upload         = new Zend_File_Transfer();
            $filesArray     = $upload->getFileInfo();
            $year           = $post['validityyear'];
            $csv_format     = $post['csv_format'];
            $label          = $post['resourcelabel'];
            $targetType     = $post['collectIn'];
            $targetResource = $post['contracts-input'];
            $regISBN        = '/\b(?:ISBN(?:: ?| ))?((?:97[89])?\d{9}[\dx])\b/i';
            $no_enclosure   =  true ;
            $expected_min_columns = '' ;
       


        //Check for csv_format

        if ($csv_format == '0' || '') {
                $this->_owApp->appendErrorMessage($this->_translate->translate(
                    'Please select a CSV format.'
                ));
                return;
            } 



            //csv configuration according to csv_format:

            // 1 = KBART 
            if ($csv_format == '1') {
                $delimiter     = "\t" ; 
                $skipFirst     = true ;
                $expected_min_columns = '16' ;

            // 2 = Simple CSV

            } elseif ($csv_format =='2') {
                $delimiter      = $post['delimiter'];
                $enclosure      = $post['enclosure'];
                $no_enclosure   = false ;
                $expected_min_columns = '5';

                if (isset($post['title'])) {
                    if ($post['title'] === 'on') {
                        $skipFirst = true;
                    }
                } else {
                $skipFirst = false;
                }
            }



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

            # Check file encoding
            $fileContent = file_get_contents($file);

            if (!mb_check_encoding($fileContent, 'UTF-8')) {
                $message = 'The file needs to be UTF-8 encoded. Please change encoding and retry.';
                $this->_owApp->appendErrorMessage($this->_translate->translate($message));
                return;
            }

            # READING CSV file
            $handle = fopen($file, 'r');
            $csvData = Array();
            if ($handle != FALSE) {

                switch ($no_enclosure) {
                    case TRUE:
                        while (($line = fgetcsv($handle,600,$delimiter)) !== FALSE) {
                            $csvData[] = $line;
                        }
                        break;
                    case FALSE:
                        while (($line = fgetcsv($handle,600,$delimiter,$enclosure)) !== FALSE) {
                            $csvData[] = $line;
                        }
                        break;
                    }    

            } else {
                $this->_owApp->appendErrorMessage("Could not read from CSV File");
                return;
            }
            fclose($handle);
        } else {
            return;
        }

        $modelIri = (string)$this->_owApp->selectedModel;
        $hash = md5(rand()) ;
        $item = $modelIri . 'resource/item/' . $hash . '/';
        //$xsdDateTime = date('Y-m-d') . 'T' . date('H:i:s');

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

        /* excluded due to virtuoso problems
        $data[$mainResource][$nsDct . 'created'][] = array(
            'type' => 'literal',
            'datatype' => $nsXsd . 'dateTime',
            'value' => $xsdDateTime
        );*/



        $errorCount = 0;
        $lineNumber = 0;


        // iterate through CSV lines
        $ignoredLines = array();
        $skipped = false;
        foreach ($csvData as $csvColumn) {
            $lineNumber++;
            if ($skipFirst === true) {
                $skipped = true;
                $skipFirst = false;
                continue;
            }
            $foundEISSN = false;
            $foundPISSN = false;
            $itemUri    = '';
                                 




            $columnCount = count($csvColumn);
            if ($columnCount >= $expected_min_columns) {

                $title = trim($csvColumn[0]);
                if ($title === '') {
                    $errorCount++;
                    $msg = $this->_translate->translate('Row');
                    $msg.= ' ' . $lineNumber . ' ';
                    $msg.= $this->_translate->translate('ignored: Title is missing.');
                    $ignoredLines[] = $msg;
                    continue;
                }

                $itemUri = $item . $lineNumber;


            // START IMPORT 
            
              

             // GENERAL TRIPLES    
                    
                    $data[$itemUri][EF_RDF_TYPE][] = array(
                        'type'     => 'uri',
                        'value'    => $nsAmsl . 'ContractItem'
                    );
                    $data[$itemUri][$nsAmsl . 'contractItemOf'][] = array(
                        'type'     => 'uri',
                        'value'    => $mainResource
                    );
                    /* excluded due to virtuoso problems
                    $data[$itemUri][$nsDct . 'created'][] = array(
                        'type'     => 'literal',
                        'datatype' => $nsXsd . 'dateTime',
                        'value'    => $xsdDateTime
                    );*/
                    $data[$itemUri][EF_RDFS_LABEL][] = array(
                        'type'     => 'literal',
                        'value'    => $title . ' (' . $year . ')'
                    );

            // Include various import actions

                    // 1 = KBART
                    if($csv_format == '1') {
                        require('ImportKBART.php') ;
                    // 2 = Simple CSV
                    } elseif ($csv_format == '2') {
                        require('ImportSimpleCSV.php') ;
                    }

 

            } else {
                $errorCount++;
                $msg = $this->_translate->translate('Row');
                $msg.= ' ' . $lineNumber . ' ';
                $msg.= $this->_translate->translate('ignored: Missing columns or wrong seperators.');
                $ignoredLines[] = $msg;
            }
        }

        if (($skipped === false && $errorCount === count($csvData)) ||
            ($skipped === true && $errorCount === count($csvData) - 1))
        {
            $msg = $this->_translate->translate('Nothing was imported');
            $this->_owApp->appendErrorMessage($msg);
            foreach ($ignoredLines as $msg) {
                $this->_owApp->appendInfoMessage($msg);
            }
            return;
        } elseif ($errorCount === 1) {
            $msg = $this->_translate->translate('Some data not imported');
            $this->_owApp->appendErrorMessage($msg);
        } elseif ($errorCount > 1) {
            $msg = $this->_translate->translate('Some data not imported');
            $this->_owApp->appendErrorMessage($msg);
        }

        foreach ($ignoredLines as $msg) {
            $this->_owApp->appendInfoMessage($msg);
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
        $model = $this->_owApp->selectedModel;
        $modelIri = (string)$model;
        $versioning = $this->_erfurt->getVersioning();
        // action spec for versioning
        $actionSpec = array();
        $actionSpec['type'] = 11;
        $actionSpec['modeluri'] = $modelIri;
        $actionSpec['resourceuri'] = $modelIri;

        try {
            // starting action
            $versioning->startAction($actionSpec);
            $model->addMultipleStatements($data);
            // stopping action
            $versioning->endAction();
            // Trigger Reindex
            $indexEvent             = new Erfurt_Event('onReindexAction');
            $indexEvent->model      = $modelIri;
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

    private function _getContractsJSONData () {
        $model = $this->_owApp->selectedModel;

        if ($model === null) {
            return json_encode(array());
        }

        $nsAMSL = 'http://vocab.ub.uni-leipzig.de/amsl/';
        $query = 'SELECT DISTINCT *  WHERE {' . PHP_EOL ;
        $query.= '{ ?s a <' . $nsAMSL  . 'AnnualContractData> . } ' . PHP_EOL;
        $query.= 'UNION ' . PHP_EOL;
        $query.= '{ ?s a <' . $nsAMSL  . 'LicensePackage> . } ' . PHP_EOL;
        $query.= '?s <' . EF_RDFS_LABEL   . '> ' . '?label .' . PHP_EOL;
        $query.= '}' . PHP_EOL;

        $result = $model->sparqlQuery($query);
        // Delete duplicates -> returns an associative array
        $temp = $this->_super_unique($result);
        // Create a new non associative array
        $json = array();
        foreach ($temp as $value) {
            $json[] = $value;
        }
        return json_encode($json);
    }

    private function _super_unique($array)
    {
        $result = array_map("unserialize", array_unique(array_map("serialize", $array)));

        foreach ($result as $key => $value)
        {
            if ( is_array($value) )
            {
                $result[$key] = $this->_super_unique($value);
            }
        }
        return $result;
    }
}
