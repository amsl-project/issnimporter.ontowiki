 <?php

            $publication_title = '' ;
            $title_id = '' ;
            $price = '' ;


            // COLUMN 0: publication_title

                    // Search for publication_title
                    if ($csvColumn[0] !== '') {
                        $publication_title = $csvColumn[0];
                    }   

                    // write statement for title_id as dc:title
                    if ($publication_title !== '') {
                        $data[$itemUri][$nsDc . 'title'][] = array(
                            'type' => 'literal',
                            'value' => $publication_title
                        );
                    }



            // COLUMS 1 and two:  print_identifier and online_identifier

                    //SEARCH

                    // Search for E-ISSN in online_identifier
                    if (preg_match_all('/\d{4}\-\d{3}[\dxX]/', $csvColumn[2], $eissn)) {
                        # Found an EISSN, create URI and write first statemntes
                        $foundEISSN = true;                
                    } else {
                        $eissn = null;
                    }

                    // Search for P-ISSN in print_identifier
                    if (preg_match_all('/\d{4}\-\d{3}[\dxX]/', $csvColumn[1], $pissn)) {
                        $foundPISSN = true;                        
                    } else {
                        $pissn = null;
                    }


                    // Search for E-ISBN in online_identifier
                    if (preg_match_all($regISBN, str_replace('-', '', $csvColumn[2]), $eisbn)) {
                        // it is possible to match a ISSN expression within a ISBN string
                        // if this is the case reset the ISSN values
                        if ($foundEISSN === true) {
                            $eissn = null;
                        }
                    } else {
                        $eisbn = null;
                    }

                    // Search for P-ISBN in print_identifier
                    if (preg_match_all($regISBN, str_replace('-', '', $csvColumn[1]), $pisbn)) {
                        // it is possible to match a ISSN expression within a ISBN string
                        // if this is the case reset the ISSN values
                        if ($foundPISSN === true) {
                            $pissn = null;
                        }
                    } else {
                        $pisbn = null;
                    }

                    //WRITE STATEMENTS
                        // write statements linking to found E-ISSNs
                        if (isset($eissn[0])) {
                            foreach ($eissn[0] as $value) {
                                $data[$itemUri][$nsAmsl . 'eissn'][] = array(
                                    'type' => 'uri',
                                    'value' => 'urn:ISSN:' . $value
                                );
                            }
                        }

                        // write statements linking to found E-ISBNs
                        if (isset($eisbn[0])) {
                            foreach ($eisbn[0] as $value) {
                                $data[$itemUri][$nsAmsl . 'eisbn'][] = array(
                                    'type' => 'uri',
                                    'value' => 'urn:ISBN:' . $value
                                );
                            }
                        }

                        # write statements linking to found P-ISSNs
                        if (isset($pissn[0])) {
                            foreach ($pissn[0] as $value) {
                                $data[$itemUri][$nsAmsl . 'pissn'][] = array(
                                    'type' => 'uri',
                                    'value' => 'urn:ISSN:' . $value
                                );
                            }
                        }

                        // write statements linking to found P-ISBNs
                        if (isset($pisbn[0])) {
                            foreach ($pisbn[0] as $value) {
                                $data[$itemUri][$nsAmsl . 'pisbn'][] = array(
                                    'type' => 'uri',
                                    'value' => 'urn:ISBN:' . $value
                                );
                            }
                        }

            
            // COLUMN 3: Title ID aka Proprietary ID

                    // Search for title_id
                    if ($csvColumn[3] !== '') {
                        $title_id = $csvColumn[3];
                    }   

                    // write statement for title_id as proprietaryID
                    if ($title_id !== '') {
                        $data[$itemUri][$nsAmsl . 'proprietaryID'][] = array(
                            'type' => 'literal',
                            'value' => $title_id
                        );
                    }




            // COLUMN 4: item price
                    if (preg_match('/\d+(?:[\.,]\d{1,2})?/',$csvColumn[4],$price)) {
                        $value = $price[0];
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
                        $data[$itemUri][$nsAmsl . 'itemPrice'][] = array(
                            'type'  => 'literal',
                            'value' => $value
                        );
                    }

            // END IMPORT