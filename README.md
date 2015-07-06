# OntoWiki titlelist import

This OntoWiki extension was developed for and during [amsl project](http://amsl.technology) at [Leipzig University Library](http://ub.uni-leipzig.de/).

This extension adds an option to import a list of titles of electronic resources (in context of libraries) by uploading a csv file. 
The resulting triples will be resources (rdf) with type amsl:ContractItems (for each line) linked to a resource of type amsl:LicensePackage or amsl:AnnualContractData.

The expected order of columns will be: Title, ISBN/ISSN (print), ISBN/ISSN (electronic), Proprietary-ID, DOI, price.

For more information about amsl vocabulary see (https://github.com/amsl-project/amsl.vocab/blob/master/amsl.ttl).

![Screenshot of titlelist upload form](http://amsl.technology/wp-content/uploads/2015/07/form_csv.png "Upload form")
