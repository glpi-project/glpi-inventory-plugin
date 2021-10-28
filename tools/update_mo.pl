#!/usr/bin/env perl
#!/usr/bin/perl -w 
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

if (@ARGV!=0){
print "USAGE update_mo.pl\n\n";

exit();
}

opendir(DIRHANDLE,'locales')||die "ERROR: can not read current directory\n"; 
foreach (readdir(DIRHANDLE)){ 
	if ($_ ne '..' && $_ ne '.'){

            if(!(-l "$dir/$_")){
                     if (index($_,".po",0)==length($_)-3) {
                        $lang=$_;
                        $lang=~s/\.po//;
                        
                        `msgfmt locales/$_ -o locales/$lang.mo`;
                     }
            }

	}
}
closedir DIRHANDLE; 

#  
#  
