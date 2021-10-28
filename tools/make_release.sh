#!/bin/bash
#
# ---------------------------------------------------------------------
# GLPI Inventory Plugin
# Copyright (C) 2021 Teclib' and contributors.
#
# http://glpi-project.org
#
# based on FusionInventory for GLPI
# Copyright (C) 2010-2021 by the FusionInventory Development Team.
#
# ---------------------------------------------------------------------
#
# LICENSE
#
# This file is part of GLPI Inventory Plugin.
#
# GLPI Inventory Plugin is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# GLPI Inventoruy Plugin is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with GLPI Inventory Plugin. If not, see <https://www.gnu.org/licenses/>.
# ---------------------------------------------------------------------
#

if [ ! "$#" -eq 2 ]
then
 echo "Usage $0 fi_git_dir release";
 exit ;
fi

read -p "Are translations up to date? [Y/n] " -n 1 -r
echo    # (optional) move to a new line
if [[ ! $REPLY =~ ^[Yy]$ ]]
then
    [[ "$0" = "$BASH_SOURCE" ]] && exit 1 || return 1 # handle exits from shell or function but don't exit interactive shell
fi

INIT_DIR=$1;
RELEASE=$2;

# test glpi_cvs_dir
if [ ! -e $INIT_DIR ]
then
 echo "$1 does not exist";
 exit ;
fi

INIT_PWD=$PWD;

if [ -e /tmp/glpiinventory ]
then
 echo "Delete existing temp directory";
\rm -rf /tmp/glpiinventory;
fi

echo "Copy to  /tmp directory";
git checkout-index -a -f --prefix=/tmp/glpiinventory/

echo "Move to this directory";
cd /tmp/glpiinventory;

echo "Check version"
if grep --quiet $RELEASE setup.php; then
  echo "$RELEASE found in setup.php, OK."
else
  echo "$RELEASE has not been found in setup.php. Exiting."
  exit 1;
fi
if grep --quiet $RELEASE glpiinventory.xml; then
  echo "$RELEASE found in glpiinventory.xml, OK."
else
  echo "$RELEASE has not been found in glpiinventory.xml. Exiting."
  exit 1;
fi

echo "Check XML WF"
if ! xmllint --noout glpiinventory.xml; then
   echo "XML is *NOT* well formed. Exiting."
   exit 1;
fi

echo "Retrieve PHP vendor"
composer install --no-dev --optimize-autoloader --prefer-dist --quiet

echo "Set version and official release"
sed \
   -e 's/"PLUGIN_GLPI_INVENTORY_OFFICIAL_RELEASE", "0"/"PLUGIN_GLPI_INVENTORY_OFFICIAL_RELEASE", "1"/' \
   -e 's/ SNAPSHOT//' \
   -i '' setup.php

echo "Minify stylesheets and javascripts"
find /tmp/glpiinventory/css /tmp/glpiinventory/lib \( -iname "*.css" ! -iname "*.min.css" \) \
    -exec sh -c 'echo "> {}" && "'$INIT_PWD'"/../../node_modules/.bin/csso {} --output $(dirname {})/$(basename {} ".css").min.css' \;

echo "Minify javascripts"
find /tmp/glpiinventory/js /tmp/glpiinventory/lib \( -iname "*.js" ! -iname "*.min.js" \) \
    -exec sh -c 'echo "> {}" && "'$INIT_PWD'"/../../node_modules/.bin/terser {} --mangle --output $(dirname {})/$(basename {} ".js").min.js' \;

echo "Compile locale files"
./tools/update_mo.pl

echo "Delete various scripts and directories"
\rm -rf .github;
\rm -rf vendor;
\rm -rf tools;
\rm -rf phpunit;
\rm -rf tests;
\rm -rf .gitignore;
\rm -rf .travis.yml;
\rm -rf .coveralls.yml;
\rm -rf phpunit.xml.dist;
\rm -rf composer.json;
\rm -rf composer.lock;
\rm -rf .composer.hash;
\rm -rf ISSUE_TEMPLATE.md;
\rm -rf PULL_REQUEST_TEMPLATE.md;
\rm -rf .tx;
\rm -rf glpiinventory.xml;
\rm -rf screenshots;
\find pics/ -type f -name "*.eps" -exec rm -rf {} \;

echo "Creating tarball";
cd ..;
tar cjf "glpiinventory-$RELEASE.tar.bz2" glpiinventory

cd $INIT_PWD;

echo "Deleting temp directory";
\rm -rf /tmp/glpiinventory;

echo "The Tarball is in the /tmp directory";
