<?php return array(
	// Cowl configuration file
	
	'mode' => 'development', // Change to production in production
	'release_tag' => 'dev', // Change to version number or commit hash when in production mode, used for caches

	'paths.base'  => "cowl", // Leave base empty for the value to be set at run time
	'paths.cache' => "~/cache/",

	'paths.top'      => "",
	'paths.app'      => "app/",
	'paths.commands' => "app/commands/",
	'paths.model'    => "app/models/",
	'paths.view'     => "app/views/",
	'paths.layouts'  => "app/views/layouts/",

	'paths.system_js'  => "~/static/js/",
	'paths.system_css' => "~/static/css/",
	'paths.app_js'     => "app/js/",
	'paths.app_css'    => "app/css/",

	'paths.library'     => "~/library/",
	'paths.plugins'     => "~/library/plugins/",
	'paths.validators'  => "~/library/validators/",
	'paths.registries'  => "~/library/registries/",
	'paths.database'    => "~/library/database/",
	'paths.drivers'     => "~/library/database/drivers/",
	'paths.helpers'     => "~/library/helpers/",
	'paths.helpers_app' => "app/helpers/",

	'paths.urls.css'          => "css/",
	'paths.urls.css_packaged' => "css/release_",
	'paths.urls.gfx'          => "css/gfx/",
	'paths.urls.js'           => "js/",
	'paths.urls.js_packaged'  => "js/release_",
	'paths.urls.files'        => "files/",

	'paths.validator_messages' => '~/library/validators/error_strings.php',

	'config.other' => array("app/config/user.php"),

	// Plugins
	'plugins.load' => array("css", "js", "routing"),

	// CSS Compiler
	'plugins.css.path'         => "~/library/plugins/css/plugin.css.php",
	'plugins.css.cache'        => "static.css",
	'plugins.css.force_update' => true, // = Performance killer!
	
	// JS Handler and Compressor
	'plugins.js.path'  => "~/library/plugins/js/plugin.js.php",
	'plugins.js.cache' => "static.js",
	'plugins.js.force_update' => true,

	// Routing
	'plugins.routing.path' => "~/library/plugins/plugin.routing.php",

	'plugins.routing.routes'   => array(),
	'plugins.routing.host_routes' => array(), // Routes based on the host (domain, example.com, sub.example.com)

	// Add your routes to app/config/user.ini

	// Logging
	'plugins.logging.path' => "~/library/plugins/plugin.logging.php",
	'plugins.logging.log_file' => "~/log/info-%s.log", // Needs to exist and be writable
	'plugins.logging.error_file' => "~/log/error-%s.log",
	'plugins.logging.log_static_files' => false
);
