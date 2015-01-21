yii2-solr
=========

A Yii2 Solr Extension built on top of Solarium.

Essentially this is a Yii2 plugin for Solarium and it is dirt simple to use, all it does is abstract certain parts of Solarium into Yii2.

There are only two files you need to pay attention to in this repository:

- Client - The Solr client, representing the connection
- SolrDataProvider - The data provider you can use with all your widgets etc.

Normal querying of Solr is very simple with this extension and I will in fact point you towards the [Solarium Documentation](http://wiki.solarium-project.org/index.php/V3:Manual_for_version_3.x).
 
The only part Yii2 really has is in providing the Solarium client class as a application component, to show an example:

```php
$query = Yii::$app->solr->createSelect();
$query->setQuery('edismax&qf=title^20.0 OR description^0.3');
$result = Yii::$app->solr->select($query);
var_dump($result->getNumFound());
```

That is what it takes to query Solarium. As you notice the only part of the Solarium documentation you need to replace is where they use `$client` and instead you 
should use `Yii::$app->solr` (or whatever you have called the Solr application component in your configuration).

To setup the application you merely add it to your configuration. As an example:

```php
	'solr' => [
		'class' => 'sammaye\solr\Client',
		'options' => [
			'endpoint' => [
				'solr1' => [
					'host' => '10.208.225.66',
					'port' => '8983',
					'path' => '/solr'
				]
			]
		]
	],
```

The `options` part of the configuration is a one-to-one match to Solariums own constructor and options.

Using the data provider for widgets is just as easy, as another example:

```php
$query = Yii::$app->solr->createSelect();
$query->setQuery('(alt_subject_mpath:' . $model->path . ' OR alt_subject_mpath:' . $model->path . '.*) AND live:1');

new SolrDataProvider([
    'query' => $query,
    // an exmaple class which assigns variables to the model(s)
    // and returns the model 
    'modelClass' => 'SolrResult',
    'sort' => [
        'attributes' => [
            'title',
            'sales',
            'score'
        ]
    ]
]);
```

As you will notice the Solarium query object can go straight into the data providers `query` property. Just like in Yii1 you need to provide a `modelClass` since this extension is not 
connected to active record directly.

The reasoning for not implementing a `QueryInterface` and making the query hook into an active record is because in many cases the Solr index represents many active records all at once 
as such I wanted to make it free form and give the user the ability to produce a specific Solr model that can return any active record they see fit while the data provider just feeds the 
multiple classes into a widget.

So now the basics are understood, you will see there are two others files:

- Solr - A helper that I used in my application and I just added in case it would be useful to others
- SolrDocumentInterface - An interface that defines a single function to be used within Solr models

The `Solr` class is just a helper that you don't really need if you don't want it so I will move onto the `SolrDocumentInterface`. The interface class just defines a single function 
`populateFromSolr` which takes one argument: the Solarium document object (from a loop). It returns a single Yii2 model. The `populateFromSolr` function is called every 
iteration of the data providers `prepareModels()` function and only ever takes a single document.
