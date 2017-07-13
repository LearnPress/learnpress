#!/bin/sh

set -x

export WP_CORE_DIR=/tmp/wordpress

plugin_slug=$(basename $(pwd))
plugin_dir=$WP_CORE_DIR/wp-content/plugins/$plugin_slug

# Init database
mysql -e 'CREATE DATABASE wordpress_test;' -uroot

# Grab specified version of WordPress from github
wget -nv -O /tmp/wordpress.tar.gz https://github.com/WordPress/WordPress/tarball/$WP_VERSION
mkdir -p $WP_CORE_DIR
tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C $WP_CORE_DIR

# move plugin into place
cd ..
mv $plugin_slug $plugin_dir

# Grab Travis-CI specific config file
wget -nv -O $plugin_dir/tests/wp-tests-config.php https://raw.github.com/benbalter/wordpress-plugin-tests/setup/wp-tests-config.php

# Make sure wp-tests-lib is available
if [ ! -d $plugin_dir/tests/lib ]
then
	svn co --quiet http://unit-tests.svn.wordpress.org/trunk/includes/ $plugin_dir/tests/lib
fi

# prepare shell for phpunit call
cd $plugin_dir/tests

set +x