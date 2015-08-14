 <?php

            $publication_title = '' ;
            $date_first_issue_online = '' ;
            $num_first_vol_online = '' ;
            $num_first_issue_online = '' ;
            $date_last_issue_online = '' ;
            $num_last_vol_online = '' ;
            $num_last_issue_online = '' ;
            $title_url = '' ;
            $first_author = '' ;
            $title_id = '' ;
            $embargo_info = '' ;
            $coverage_depth = null ;
            $coverage_notes = '' ;
            $publisher_name = '' ;
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



            // COLUMN 3: Date of first issue available online
                    
                    if ($csvColumn[3] !== '') {
                        $date_first_issue_online = $csvColumn[3] ;
                        $dateType = '' ;
                        // check for date Types
                        if (preg_match('(^\d{4}-\d{2}-\d{2}$)',$date_first_issue_online)) {
                            $dateType = 'date';                                           
                        } elseif (preg_match('(^\d{4}-\d{2}$)',$date_first_issue_online)) {
                            $dateType = 'gYearMonth';                                                      
                        } elseif (preg_match('(^\d{4}$)',$date_first_issue_online)) {
                            $dateType = 'gYear';
                        } else {
                            $dateType = FALSE ;
                        }
                    
                        // write statements
                        // doesn't yet throw error for incorrect dates, just doesn't write any triples
                        if ($dateType !== FALSE) {
                            $data[$itemUri][$nsAmsl . 'dateFirstIssueOnline'][] = array(
                                'type' => 'literal',
                               // 'datatype' => $nsXsd . $dateType , // doesn't work due to virtuoso bug
                                'value' => $date_first_issue_online
                            );

                        }                  
                    }  


            // COLUMN 4: Number of first volume available online

                    // Search for num_first_vol_online
                    if ($csvColumn[4] !== '') {
                        $num_first_vol_online = $csvColumn[4];
                    }   

                    // write statement for num_first_vol_online as numFirstVolumeOnline
                    if ($num_first_vol_online !== '') {
                        $data[$itemUri][$nsAmsl . 'numberFirstVolumeOnline'][] = array(
                            'type' => 'literal',
                            'value' => $num_first_vol_online
                        );
                    }


            // COLUMN 5: Number of first issue available online

                    // Search for num_first_issue_online
                    if ($csvColumn[5] !== '') {
                        $num_first_issue_online = $csvColumn[5];
                    }   

                    // write statement for num_first_issue_online as numFirstIssueOnline
                    if ($num_first_issue_online !== '') {
                        $data[$itemUri][$nsAmsl . 'numberFirstIssueOnline'][] = array(
                            'type' => 'literal',
                            'value' => $num_first_issue_online
                        );
                    }


            // COLUMN 6: Date of last issue available online
                    
                    if ($csvColumn[6] !== '') {
                        $date_last_issue_online = $csvColumn[6] ;
                        $dateType = '' ;
                        // check for date Types
                        if (preg_match('(^\d{4}-\d{2}-\d{2}$)',$date_last_issue_online)) {
                            $dateType = 'date';                                           
                        } elseif (preg_match('(^\d{4}-\d{2}$)',$date_last_issue_online)) {
                            $dateType = 'gYearMonth';                                                      
                        } elseif (preg_match('(^\d{4}$)',$date_last_issue_online)) {
                            $dateType = 'gYear';
                        } else {
                            $dateType = FALSE ;
                        }
                    
                        // write statements
                        // doesn't yet throw error for incorrect dates, just doesn't write any triples
                        if ($dateType !== FALSE) {
                            $data[$itemUri][$nsAmsl . 'dateLastIssueOnline'][] = array(
                                'type' => 'literal',
                               // 'datatype' => $nsXsd . $dateType , // doesn't work due to virtuoso bug
                                'value' => $date_last_issue_online
                            );

                        }                  
                    }  


            // COLUMN 7: Number of last volume available online

                    // Search for num_last_vol_online
                    if ($csvColumn[4] !== '') {
                        $num_last_vol_online = $csvColumn[4];
                    }   

                    // write statement for num_last_vol_online as numLastVolumeOnline
                    if ($num_last_vol_online !== '') {
                        $data[$itemUri][$nsAmsl . 'numberLastVolumeOnline'][] = array(
                            'type' => 'literal',
                            'value' => $num_last_vol_online
                        );
                    }


            // COLUMN 8: Number of last issue available online

                    // Search for num_last_issue_online
                    if ($csvColumn[5] !== '') {
                        $num_last_issue_online = $csvColumn[5];
                    }   

                    // write statement for num_last_issue_online as numLastIssueOnline
                    if ($num_last_issue_online !== '') {
                        $data[$itemUri][$nsAmsl . 'numberLastIssueOnline'][] = array(
                            'type' => 'literal',
                            'value' => $num_last_issue_online
                        );
                    }


            // COLUMN 9: Title-Level URL

                    // Search for title_url
                    if ($csvColumn[9] !== '') {
                        $title_url = $csvColumn[9];
                    }   

                    // write statement for title_url as primaryAccessURI
                    if ($title_url !== '') {
                        $data[$itemUri][$nsAmsl . 'primaryAccessURI'][] = array(
                            'type' => 'uri',
                            'value' => $title_url
                        );
                        //add label to URI-Resource to improve readability in OntoWiki Resource View
                        $data[$title_url][EF_RDFS_LABEL][] = array(
                            'type' => 'literal',
                            'value' => $title_url
                        );
                    }

            // COLUMN 10: First author (for monographs)

                    // Search for first_author
                    if ($csvColumn[10] !== '') {
                        $first_author = $csvColumn[10];
                    }   

                    // write statement for first_author as dc:creator
                    if ($first_author !== '') {
                        $data[$itemUri][$nsDc . 'creator'][] = array(
                            'type' => 'literal',
                            'value' => $first_author
                        );
                    }


            
            // COLUMN 11: Title ID

                    // Search for title_id
                    if ($csvColumn[11] !== '') {
                        $title_id = $csvColumn[11];
                    }   

                    // write statement for title_id as proprietaryID
                    if ($title_id !== '') {
                        $data[$itemUri][$nsAmsl . 'proprietaryID'][] = array(
                            'type' => 'literal',
                            'value' => $title_id
                        );
                    }



            // COLUMN 12: Embargo

                    // Search for embargo_info
                    if ($csvColumn[12] !== '') {
                        $embargo_info = $csvColumn[12];
                    }   

                    // write statement for embargo_info as embargoInfo
                    if ($embargo_info !== '') {
                        $data[$itemUri][$nsAmsl . 'embargoInfo'][] = array(
                            'type' => 'literal',
                            'value' => $embargo_info
                        );
                    }




            // COLUMN 13: Coverage Depth
                 // Can only have three values: fulltext, selected articles and abstracts - but spelling might vary in different files.
                 // --> Regex for the first three characters in each value (ful, sel, abs) as they are unique 

                    // Search for coverage depth values in coverage_depth
                   if ($csvColumn[13] !== '') {   
                        
                        preg_match_all('/(ful)|(sel)|(abs)/i', $csvColumn[13], $coverage_depth) ;

                        // write statements 
                        if (isset($coverage_depth[0])) {


                            foreach ($coverage_depth[0] as $value) {
                                $value = strtolower($value) ;

                                if ($value == 'ful') {

                                        $data[$itemUri][$nsAmsl . 'coverageDepth'][] = array(
                                            'type' => 'uri',
                                            'value' => $nsAmsl . 'FulltextCoverage'
                                         );
                                } elseif ($value == 'sel') {
                                        $data[$itemUri][$nsAmsl . 'coverageDepth'][] = array(
                                          'type' => 'uri',
                                          'value' => $nsAmsl . 'SelectedArticlesCoverage'
                                        );
                                } elseif ($value == 'abs' ) {
                                        $data[$itemUri][$nsAmsl . 'coverageDepth'][] = array(
                                            'type' => 'uri',
                                            'value' => $nsAmsl . 'AbstractCoverage'
                                        );
                                }

                            }
                        }
                   }

               

            // COLUMN 14: Coverage Note

                    // Search for coverage_note
                    if ($csvColumn[14] !== '') {
                        $coverage_notes = $csvColumn[14];
                    }   

                    // write statement for coverage_note as coverageNote
                    if ($coverage_notes !== '') {
                        $data[$itemUri][$nsAmsl . 'coverageNotes'][] = array(
                            'type' => 'literal',
                            'value' => $coverage_notes
                        );
                    }



            
            // COLUMN 15: Publisher Name

                    // Search for publisher_name
                    if ($csvColumn[15] !== '') {
                        $publisher_name = $csvColumn[15];
                    }   

                    // write statement for publisher_name as dc:publisher
                    if ($publisher_name !== '') {
                        $data[$itemUri][$nsDc . 'publisher'][] = array(
                            'type' => 'literal',
                            'value' => $publisher_name
                        );
                    }


            // COLUMN 16: item price
                    if (preg_match('/\d+(?:[\.,]\d{1,2})?/',$csvColumn[16],$price)) {
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