<?php

return array(
	'controllers' => array(
		'invokables' => array(
			'Album\Controller\Album' => 'Album\Controller\AlbumController'
		)
	),

	'router' => array(
		'routes' => array(
			'album' => array(
				'type' => 'segment',
				'options' => array(
					'route' => '/album[/:id]',
					'constraints' => array(
						'id' => '[0-9]+'
					),
					'defaults' => array(
						'controller' => 'Album/Controller/Album',
						'action' => 'index'
					)
				)
			)
		)
	),

	'view_manager' => array(
		'template_path_stack' => array(
			'album' => __DIR__ . '/../view'
		)
	)
);