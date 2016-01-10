# WP Options Framework #

This framework help implements WP Options API and generate setup pages for two steps:) 

### Setup ###

* Deploy repo to wp-content dir
* Add string to wp-config.php after define ABSPATH:

```
#!php
if ( !defined ('WOF'))
	define ('WOF', ABSPATH . 'wp-content/wp-options-framework/wp-options-framework.php');
```

### How to use ###

* In you plugin main file

```
#!php
if ( defined ('WOF') ) {
  require_once WOF;
$wof = new \WP_Options_Framework ( 'Sample Plugin', 'sample_page', 'options-general.php', FALSE, FALSE );
$wof->addTab ( 'Main options', 'global', array ('player_options'=>'Define player options', 'api_options'=>'RM API') );
$wof->addTab ( 'Searh options', 'search', array ('sphinxql_options'=>'Setup SphinxQL') );

$wof->addFields ( 'global', array (
array (
	'fid'         => 'width',
	'title'       => 'Player width',
	'type'        => 'text',
	'section'     => 'player_options',
	'std'         => 620,
	'validator'   => 'intval',
	'class'       => NULL,
	'desc'        => 'Player width by default',
),
array (
	'fid'         => 'height',
	'title'       => 'Player height',
	'type'        => 'text',
	'section'     => 'player_options',
	'std'         => 465,
	'validator'   => 'intval',
	'class'       => NULL,
	'desc'        => 'Player height by default',
)
));

}
```