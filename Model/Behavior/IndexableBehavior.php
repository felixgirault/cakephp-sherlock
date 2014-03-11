<?php

use Sherlock\Sherlock;



/**
 *
 *
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@package Sherlock.Model.Behavior
 *	@license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class IndexableBehavior extends ModelBehavior {

	/**
	 *	Setup this behavior with the specified configuration settings.
	 *
	 *	### Settings
	 *
	 *	- 'nodes' array
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
				'nodes' => [[
					'host' => 'localhost',
					'port' => 9200
				]],
				'index' => 'sherlock',
				'type' => $Model->table,
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

	public function index( Model $Model, $data ) {

		$a = $Model->alias;
		$settings = $this->settings[ $a ];
		$document = [ ];

		foreach ( $settings['fields'] as $field ) {
			list( $alias, $field ) = pluginSplit( $field, false, $a );

			if ( isset( $data[ $alias ][ $field ])) {
				$document[ $field ] = $data[ $alias ][ $field ];
			} else if ( isset( $data[ $alias ][ 0 ][ $field ])) {
				foreach ( $data[ $alias ] as $i => $assoc ) {
					$table = $Model->{$alias}->table;
					$document[ $table ][ $i ][ $field ] = $assoc[ $field ];
				}
			}
		}

		if ( is_numeric( $data )) {
			$data = $Model->findById( $data );
		}

		$Document = $this->sherlock( $Model )
			->document( )
			->index( $settings['index'])
			->type( $settings['type'])
			->document(
				$document,
				isset( $data[ $a ][ $Model->primaryKey ])
					? $data[ $a ][ $Model->primaryKey ]
					: null
			);

		$Document->execute( );
	}



	/**
	 *
	 */

	public function sherlock( Model $Model ) {

		$settings = $this->settings[ $Model->alias ];

		if ( isset( $settings['Sherlock'])) {
			return $settings['Sherlock'];
		}

		$Sherlock = new Sherlock( );

		foreach ( $settings['nodes'] as $node ) {
			$Sherlock->addNode( $node['host'], $node['port']);
		}

		$settings['Sherlock'] = $Sherlock;
		return $Sherlock;
	}



	/**
	 *
	 */

	public function search( Model $Model ) {

		$settings = $this->settings[ $Model->alias ];

		return $this->sherlock( $Model )->search( )
			->index( $settings['index'])
			->type( $settings['type']);
	}
}
