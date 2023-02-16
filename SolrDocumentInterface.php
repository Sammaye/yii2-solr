<?php

namespace b0rner\solr;

use Yii;

/**
 * This is the interface you should implement
 * 
 * populateFromSolr should return a single instance of the model, whether this one or another depending upon document type.
 */
interface SolrDocumentInterface
{
	public static function populateFromSolr($doc);
}