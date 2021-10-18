#
# ---------------------------------------------------------------------
# GLPI - Gestionnaire Libre de Parc Informatique
# Copyright (C) 2015-2021 Teclib' and contributors.
#
# http://glpi-project.org
#
# based on GLPI - Gestionnaire Libre de Parc Informatique
# Copyright (C) 2003-2014 by the INDEPNET Development Team.
#
# ---------------------------------------------------------------------
#
# LICENSE
#
# This file is part of GLPI.
#
# GLPI is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# GLPI is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with GLPI. If not, see <http://www.gnu.org/licenses/>.
# ---------------------------------------------------------------------
#

use File::Slurp;
use Data::Dumper;

my $file = read_file("../locales/glpi.pot");


my @lines = split("\n", $file);


my $toRemove = 0;
my $text = '';
my $newFile;
foreach my $line(@lines) {
   if ($line =~ /msgctxt/
        && $line =~ /fusioninventory/) {
      # No add this msgctxt
   } elsif ($line =~ /msgctxt/) {
      $toRemove = 1;
   } else {
      $text .= $line."\n";
   }

   if ($line eq '') {
      if ($toRemove == 0) {
         $newFile .= $text;
      }
      $text = '';
      $toRemove = 0;
   }
}

open (MYFILE, ">../locales/glpi.pot");
print MYFILE $newFile;
close (MYFILE);


