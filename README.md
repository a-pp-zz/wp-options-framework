# WP Options Framework #

This framework help implements WP Options API and generate setup pages for two steps:) 

### Setup ###

* Require composer autoload to wp-config.php after define ABSPATH:

```
/** Composer */
require_once(ABSPATH . 'composer/vendor/autoload.php');
```

* Install package appzz/wof

### How to use ###

* In you plugin main file

```
#!php
use AppZz\Wp\Options\Framework as WP_Options_Framework;

if ( ! class_exists('AppZz\Wp\Options\Framework')) {
	return;
}

$wof = new WP_Options_Framework ( 'Sample Plugin', 'sample_page', 'options-general.php', FALSE, FALSE );
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