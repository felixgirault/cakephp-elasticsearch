<?php

use Elasticsearch\Client;



/**
 *
 *
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@package Elasticsearch.Model.Behavior
 *	@license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class IndexableBehavior extends ModelBehavior {

	/**
	 *	Setup this behavior with the specified configuration settings.
	 *
	 *	### Settings
	 *
	 *	- 'config' array
	 *	- 'index' string Elasticsearch index.
	 *	- 'type' string Elasticsearch type.
	 *	- 'fields' array
	 *
	 *	@param Model $Model Model using this behavior.
	 *	@param array $config Configuration settings.
	 */

	public function setup( Model $Model, $settings = [ ]) {

		$a = $Model->alias;

		if ( !isset( $this->settings[ $a ])) {
			$this->settings[ $a ] = [
				'config' => [
					'hosts' => [ '127.0.0.1:9200' ]
				],
				'index' => 'cakephp',
				'type' => $Model->table,
				'mapping' => [ ],
				'fields' => [ ]
			];
		}

		$this->settings[ $a ] = array_merge(
			$this->settings[ $a ],
			( array )$settings
		);
	}



	/**
	 *
	 */

	public function createIndex( Model $Model ) {

		$settings = $this->settings[ $Model->alias ];
		$params = [
			'body' => [
				'mappings' => [
					$settings['type'] => $settings['mapping']
				]
			]
		];

		$Client = $this->client( $Model );

		return $Client->indices( )->create( $params + [
			'index' => $settings['index']
		]);
	}



	/**
	 *
	 */

	public function deleteIndex( Model $Model ) {

		return $this->client( $Model )->indices( )->delete([
			'index' => $this->settings[ $Model->alias ]['index']
		]);
	}



	/**
	 *
	 */

	public function index( Model $Model, $data ) {

		$a = $Model->alias;

		if ( is_numeric( $data )) {
			$data = $Model->find( 'first', [
				'conditions' => [
					$a . '.' . $Model->primaryKey => $data
				],
				'callbacks' => false
			]);
		}

		if ( empty( $data )) {
			throw new Exception( );
		}

		$params = [ ];

		foreach ( $this->settings[ $a ]['fields'] as $field ) {
			list( $alias, $field ) = pluginSplit( $field, false, $a );

			if ( isset( $data[ $alias ][ $field ])) {
				$params['body'][ $field ] = $data[ $alias ][ $field ];
			} else if ( isset( $data[ $alias ][ 0 ][ $field ])) {
				foreach ( $data[ $alias ] as $i => $assoc ) {
					$table = $Model->{$alias}->table;
					$params['body'][ $table ][ $i ][ $field ] = $assoc[ $field ];
				}
			}
		}

		if ( isset( $data[ $a ][ $Model->primaryKey ])) {
			$params['id'] = $data[ $a ][ $Model->primaryKey ];
		}

		$Client = $this->client( $Model );
		return $Client->index( $params + $this->params( $Model ));
	}



	/**
	 *
	 */

	public function deindex( Model $Model, $id ) {

		return $this->client( $Model )->delete(
			compact( 'id' ) + $this->params( $Model )
		);
	}



	/**
	 *
	 */

	public function client( Model $Model ) {

		$settings =& $this->settings[ $Model->alias ];

		if ( empty( $settings['client'])) {
			$Client = new Client( $settings['config']);
			$settings['client'] = $Client;
		}

		return $settings['client'];
	}



	/**
	 *
	 */

	public function params( Model $Model ) {

		return [
			'index' => $this->settings[ $Model->alias ]['index'],
			'type' => $this->settings[ $Model->alias ]['type']
		];
	}



	/**
	 *
	 */

	public function search( Model $Model, $params ) {

		$Client = $this->client( $Model );
		return $Client->search( $params + $this->params( $Model ));
	}
}
