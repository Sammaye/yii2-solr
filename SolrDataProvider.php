<?php

namespace sammaye\solr;

use Yii;
use yii\base\Model;
use yii\base\InvalidConfigException;
use yii\data\BaseDataProvider;
use yii\di\Instance;
use Solarium\Core\Query\Query as SolrQuery;
use sammaye\solr\Client;

/**
 * This is the SolrDataProvider
 * 
 * You use this to interact with widgets etc to provide them data.
 * 
 * Basic usage of this class would be:
 * 
 * $query = Yii::$app->solr->createSelect();
 * $query->setQuery('(alt_subject_mpath:' . $model->path . ' OR alt_subject_mpath:' . $model->path . '.*) AND live:1');
 * 
 * new SolrDataProvider([
 * 		'query' => $query,
 * 		'modelClass' => 'common\models\SolrResult',
 * 		'sort' => [
 * 			'attributes' => [
 * 				'title',
 * 				'sales',
 * 				'score'
 * 			]
 * 		]
 * ]);
 * 
 */
class SolrDataProvider extends BaseDataProvider
{
	/**
	 * @var SolrQuery the query that is used to fetch data models and [[totalCount]]
	 * if it is not explicitly set.
	 */
	public $query;
	
	/**
	 * @var string|callable the column that is used as the key of the data models.
	 * This can be either a column name, or a callable that returns the key value of a given data model.
	 *
	 * If this is not set, the following rules will be used to determine the keys of the data models:
	 *
	 * - If [[modelClass]] is an [[\yii\db\ActiveRecord]], the primary keys of [[\yii\db\ActiveRecord]] will be used.
	 * - Otherwise, the keys of the [[models]] array will be used.
	 *
	 * @see getKeys()
	 */
	public $key;
	
	/**
	 * @var Connection|string the Solr connection object or the application component ID of the Solr connection.
	 * If not set, the default solr connection will be used.
	 */
	public $solr;
	
	/**
	 * Just like in Yii1 this tells the data provider what class/model to populate
	 */
	public $modelClass;
	
	public function init()
	{
		parent::init();
		if (is_string($this->solr)) {
			$this->solr = Instance::ensure($this->solr, Connection::className());
		}elseif($this->solr === null){
			$this->solr = Instance::ensure('solr', Client::className());
		}
	}
	
	public function prepareModels()
	{
		if (!$this->query instanceof SolrQuery) {
			throw new InvalidConfigException('The "query" property must be an instance of a Solarium Query.');
		}
		if (($pagination = $this->getPagination()) !== false) {
			$pagination->totalCount = $this->getTotalCount();
			$this->query->setRows($pagination->getLimit())->setStart($pagination->getOffset());
		}
		if (($sort = $this->getSort()) !== false) {
			foreach($sort->getAttributeOrders() as $k => $order){
				$query = $this->query;
				$this->query->addSort($k, $order === SORT_ASC ? $query::SORT_ASC : $query::SORT_DESC);
			}
		}
		$resultset = $this->solr->select($this->query);
		$models = [];
		foreach($resultset as $result){
			$cname = $this->modelClass;
			$models[] = $cname::populateFromSolr($result);
		}
		return $models;
	}
	
	public function prepareKeys($models)
	{
		$keys = [];
		if ($this->key !== null) {
			foreach ($models as $model) {
				if (is_string($this->key)) {
					$keys[] = $model[$this->key];
				} else {
					$keys[] = call_user_func($this->key, $model);
				}
			}
			return $keys;
		} else {
			
			if($this->modelClass){
				/** @var \yii\db\ActiveRecord $class */
				$class = $this->modelClass;
				$model = new $class;
				
				if($model instanceof \yii\db\ActiveRecord){
					
					$pks = $class::primaryKey();
					if (count($pks) === 1) {
						$pk = $pks[0];
						foreach ($models as $model) {
							$keys[] = $model[$pk];
						}
					} else {
						foreach ($models as $model) {
							$kk = [];
							foreach ($pks as $pk) {
								$kk[$pk] = $model[$pk];
							}
							$keys[] = $kk;
						}
					}
					return $keys;
				}
			}
			return array_keys($models);
		}
	}
	
	public function prepareTotalCount()
	{
		if (!$this->query instanceof SolrQuery) {
			throw new InvalidConfigException('The "query" property must be an instance of a Solarium Query.');
		}
		$query = clone $this->query;
		$resultset = $this->solr->select($query);
		
		return (int) $resultset->getNumFound();
	}
	
	public function setSort($value)
	{
		parent::setSort($value);
		if (($sort = $this->getSort()) !== false && empty($sort->attributes)) {
			/** @var Model $model */
			$model = new $this->modelClass;
			if($model instanceof Model){
				foreach ($model->attributes() as $attribute) {
					$sort->attributes[$attribute] = [
					'asc' => [$attribute => SORT_ASC],
					'desc' => [$attribute => SORT_DESC],
					'label' => $model->getAttributeLabel($attribute),
					];
				}
			}
		}
	}
}