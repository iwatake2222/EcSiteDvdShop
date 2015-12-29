#!/usr/bin/php
<?php
	$log_file = $_SERVER["OPENSHIFT_LOG_DIR"] . "test.log";
	file_put_contents( $log_file, time() );
?>
