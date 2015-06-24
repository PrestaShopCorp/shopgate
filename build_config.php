<?php
$buildConfig = array (
	'major' => 2,
	'minor' => 9,
	'build' => 39,
	'shoppingsystem_id' => 102,
	'shopgate_library_path' => "vendors",
	'plugin_name' => "prestashop",
	'display_name' => "Prestashop",
	'zip_filename' => "prestashop.zip",
	'version_files' => array (
		'0' => array (
			'path' => "/shopgate.php",
			'match' => "/define\(\'SHOPGATE_PLUGIN_VERSION\',(.+)\)/",
			'replace' => "define('SHOPGATE_PLUGIN_VERSION', '{PLUGIN_VERSION}')",
		),
		'1' => array (
			'path' => "/config.xml",
			'match' => "/CDATA\[(\d*\.*-*)+\]\]/",
			'replace' => "CDATA[{PLUGIN_VERSION}]]",
		),
		'2' => array (
			'path' => "./shopgate.php",
			'match' => "/this->version = '(.+)'/",
			'replace' => "this->version = '{PLUGIN_VERSION}'",
		),
	),
	'wiki' => array (
		'version' => array (
			'pages' => array (
				'Prestashop/de' => array (
					'title' => "Prestashop/de",
					'match' => "#Aktuelle Plugin-Version \|\| \d+.\d+.\d+#",
					'replace' => "Aktuelle Plugin-Version || {PLUGIN_VERSION}",
				),
				'Prestashop/pl' => array (
					'title' => "Prestashop/pl",
					'match' => "#Bieżąca wersja wtyczki \|\| \d+.\d+.\d+#",
					'replace' => "Bieżąca wersja wtyczki || {PLUGIN_VERSION}",
				),
				'Prestashop' => array (
					'title' => "Prestashop",
					'match' => "#Current plugin version \|\| \d+.\d+.\d+#",
					'replace' => "Current plugin version || {PLUGIN_VERSION}",
				),
			),
		),
		'changelog' => array (
			'path' => "./",
			'pages' => array (
				'Prestashop/de' => array (
					'title' => "Template:Prestashop_Changelog/de",
					'languages' => array (
						'0' => "Deutsch",
						'1' => "English",
					),
				),
				'Prestashop/pl' => array (
					'title' => "Template:Prestashop_Changelog/pl",
					'languages' => array (
						'0' => "English",
					),
				),
				'Prestashop' => array (
					'title' => "Template:Prestashop_Changelog",
					'languages' => array (
						'0' => "English",
					),
				),
			),
		),
	),
	'zip_basedir' => "shopgate",
	'exclude_files' => array (
		'0' => "_orig",
		'1' => "missing_classes.php",
	),
);
