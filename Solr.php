<?php

namespace b0rner\solr;

use Yii;

/**
 * This is a static hleper for Solr, this is specific to things that have been done 
 * in our Solr deployment, however, others may find it useful, or not, it is upto you to use it.
 */
class Solr
{
	/**
	 * Make sure not to set value if it is just an _ . This means that the value was missing in the Solr search result.
	 * @param string $value The value to set in the bo
	 */
	public static function string($value)
	{
		if($value !== '_'){
			return $value;
		}
	}
	
	/**
	 * Make sure not to set value if it is just an 0000-01-01T00:00:00Z . This means that value is
	 * a null representation.
	 *
	 * Convert back to a MySql date representation
	 * @param string $value The value to set in the bo
	 */
	public static function date($value)
	{
		if($value != '0000-01-01T00:00:00Z' && $value != '1-01-01T00:00:00Z' && $value != '0001-01-01T00:00:00Z' ){
			//convert back to Mysql representation of a date
			$value = date('Y-m-d', strtotime($value));
			return $value;
		}
	}
	
	/**
	 * Make sure not to set value if it is just an 0 . This means that the value was missing in the Solr search result.
	 * @param string $value The value to set in the bo
	 */
	public static function int($value)
	{
		if($value != '0'){
			return $value;
		}
	}
	
	/**
	 * Make sure not to set value if it is just an 0 . This means that the value was missing in the Solr search result.
	 * @param string $value The value to set in the bo
	 */
	public static function float($value)
	{
		if($value != '0.00'){
			return $value;
		}
	}
}