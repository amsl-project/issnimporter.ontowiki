/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2013, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
/**
 /**
 * Twitter Typehead tokenizer initialization.
 */
var contracts;
var i = 10;
contracts = new Bloodhound({
    datumTokenizer: function (dat) {
        return Bloodhound.tokenizers.whitespace(dat.label);
    },
    queryTokenizer: Bloodhound.tokenizers.whitespace,
    limit: i,
    prefetch: {
        url: urlBase + 'issnimporter/getcontracts'
    }
});
contracts.initialize();

/**
 * Now use the input fields to find SUSHI settings while typing
 */
$(document).ready(function() {

    // every result gets a paragraph containing the label and other helpfull information
    contracts.clearPrefetchCache();
    source= '<p>';
    source+= '<span class="highlight-title">{{label}}</span><br><!--<span class="origin-index">Report-Typ: {{reportName}}</span><--><br>';
    source+= '</p>';
    var noResults = 'No results found';
    $('#contracts-input.typeahead').typeahead(null, {
        name: 'contracts-matches',
        displayKey: 's',
        source: contracts.ttAdapter(),
        templates: {
            empty: ['<div class="empty-message">', '<strong>' + noResults + '</strong>', '</div>'].join('\n'),
            suggestion: Handlebars.compile(source),
            footer: '<div class="empty-message">A maximum of ' + i + ' results are shown.</div>'
        }
    });
});
