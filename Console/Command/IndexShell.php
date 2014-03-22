<?php

/**
 *
 *
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@package Elasticsearch.Console.Command
 *	@license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class IndexShell extends AppShell {

	/**
	 *
	 */

	public function getOptionParser( ) {

		$Parser = parent::getOptionParser( );

		$Parser->addOption( 'model', [
			'help' => __( 'Model to index.' ),
			'short' => 'm',
			'required' => true
		]);

		$Parser->addOption( 'start', [
			'short' => 's',
			'default' => 0
		]);

		$Parser->addOption( 'block', [
			'short' => 'b',
			'default' => 500
		]);

		return $Parser;
	}



	/**
	 *
	 */

	public function main( ) {

		$this->out( 'Basic usage:' );
		$this->out( 'create -m Model' );
		$this->out( 'delete -m Model' );
		$this->out( 'build -m Model' );
		$this->out( 'rebuild -m Model' );
	}



	/**
	 *
	 */

	public function create( ) {

		$alias = $this->params['model'];

		$Model = ClassRegistry::init( $alias );
		$Model->createIndex( );
	}



	/**
	 *
	 */

	public function delete( ) {

		$alias = $this->params['model'];

		$Model = ClassRegistry::init( $alias );
		$Model->deleteIndex( );
	}



	/**
	 *
	 *
	 *	@todo Find a workaround to actually test the model.
	 *		As cake creates a default model if the alias isn't registered,
	 *		it will never be null.
	 */

	public function build( ) {

		$alias = $this->params['model'];
		$start = $this->params['start'];
		$block = $this->params['block'];

		$Model = ClassRegistry::init( $alias );

		if ( !$Model ) {
			return $this->out( "<error>unable to load model `$alias`</error>" );
		}

		$this->out( 'indexing...' );

		do {
			$records = $Model->find( 'all', [
				'offset' => $start,
				'limit' => $block,
				'order' => "$alias.id",
				'callbacks' => false
			]);

			$this->out( $start . '-' . ( $start + count( $records )));
			$start += $block;

			foreach ( $records as $record ) {
				$Model->index( $record );
			}

		} while ( count( $records ) === $block );
	}



	/**
	 *
	 */

	public function rebuild( ) {

		$this->delete( );
		$this->create( );
		$this->build( );
	}
}
