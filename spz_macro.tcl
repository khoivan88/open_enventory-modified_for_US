#!/usr/bin/tclsh


# Copyright 2006-2009 Felix Rudolphi and Lukas Goossen
# open enventory is distributed under the terms of the GNU Affero General Public License, see COPYING for details. You can also find the license under http://www.gnu.org/licenses/agpl.txt
# 
# open enventory is a registered trademark of Felix Rudolphi and Lukas Goossen. Usage of the name "open enventory" or the logo requires prior written permission of the trademark holders. 
# 
# This file is part of open enventory.
# 
# open enventory is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# open enventory is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
# 
# You should have received a copy of the GNU Affero General Public License
# along with open enventory.  If not, see <http://www.gnu.org/licenses/>.

package require Tk
package require Img ; # libtk-img
#~ package require twapi

proc findfile { dir fname } {
	if {[llength [set x [glob -nocomplain -dir $dir $fname]]]} {
		return [lindex $x 0]
	} else {
		foreach i [glob -nocomplain -type d -dir $dir *] {
			if {$i != $dir && [llength [set x [findfile $i $fname]]]} {
				return $x
			}
		}
	}
}

if {$argc == 0} {
	tk_messageBox -message "No parameter given."
	exit 1
}

if {[file isfile $argv] == 0} {
	tk_messageBox -message "SPZ-File $argv not found."
	exit 1
}

set now [clock seconds ]

# read makro.ini (unzip/curl/etc. command should be in path, so no setting in ini)
set filename "makro.ini"

if {[file exists $filename]} {
	set macro_ini [open $filename]
	while {[gets $macro_ini line] >= 0} {
		if {[regexp {^\[(.+)\]$} $line -> section]} {
			# if starts with [ and ends with ]: set section
			# puts "Section: $section"
		} elseif {[regexp {(\w+)=(.*)$} $line -> name value]} {
			# split at = if exists
			if {$section == "Programs"} {
				# build hash table
				set programs($name) $value
			}
			# puts "$name = $value"
		}
	}
	close $macro_ini
}

# get temp dir
switch $tcl_platform(platform) {
	unix {
		set tmpdir /tmp
		# or even $::env(TMPDIR), at times.
	} macintosh {
		set tmpdir $::env(TRASH_FOLDER)
		# a better place?
	} default {
		set tmpdir [pwd]
		catch {set tmpdir $::env(TMP)}
		catch {set tmpdir $::env(TEMP)}
	}
}

# move spz to tmpdir as tgz
set zipname [file tail $argv]
# set zipname [file rootname $zipname]
set zipname "${tmpdir}/${zipname}"
# puts "$zipname"
# exit 0
#~ file copy -force $argv "${zipname}.tgz" ; # testing only
file rename -force $argv "${zipname}.tgz"

# uncompress $argv
file mkdir ${tmpdir}/$now
puts [exec gzip -df ${zipname}.tgz] ; # removes zipname.tgz
catch { puts [exec tar -xf ${zipname}.tar -C ${tmpdir}/$now] }
catch { puts [exec chmod -R u+rw ${tmpdir}/$now] }
file delete ${zipname}.tar

# read .openenv (like ini file)
set filename "${tmpdir}/${now}/.openenv"
set openenv [open $filename]
while {[gets $openenv line] >= 0} {
	if {[regexp {^\[(.+)\]$} $line -> section]} {
		# if starts with [ and ends with ]: set section
		# puts "Section: $section"
	} elseif {[regexp {(\w+)=(.*)$} $line -> name value]} {
		# split at = if exists
		if {$section == "Spectrum parameters"} {
			switch $name {
				analytical_data_identifier {
					set dirname $value
				} uploadURL {
					set uploadURL $value
				} userAgent {
					set userAgent $value
				} analytics_device_driver {
					set driverCode $value
				} sessionId {
					set sessID $value
				}
			}
		}
		# puts "$name = $value"
	}
}
close $openenv

set file_found 0

# special handling depending on $driverCode etc.

if {$file_found == 0} {
	# search for simple file
	set Extensions [split "pdf,gif,png,jpg,jpeg,doc,xls,ppt,odt,ods,odp" ","]
	foreach Ext $Extensions {
		# find file
		set cmdParam [findfile ${tmpdir}/$now *.$Ext]
		if {$cmdParam != ""} {
			# start respective program using xdg-open
			switch $tcl_platform(platform) {
				unix {
					set programPath "xdg-open"
				} macintosh {
					set programPath "open"
				} default {
					set programPath "start"
				}
			}
			set file_found 1
			break;
		}
	}
}

if {$file_found} {
	catch { exec $programPath $cmdParam }
	# would be nice to detect if program is closed
}

# functions

proc copy_clip {} {
	# create image from clipboard
	tk_messageBox -message [clipboard get -type XA_BITMAP]

	#~ if {[catch {selection get} _s] && [catch {selection get -selection CLIPBOARD} _s]} {
		#~ tk_messageBox -message "Nix"
		#~ return
	#~ }
	#~ tk_messageBox -message "$_s"
}

proc upload {} {
	global tmpdir
	global now
	global uploadURL
	global sessID
	global userAgent
	
	# compress
	set newSpz ${tmpdir}/${now}.spz
	cd ${tmpdir}/${now}
	#~ tk_messageBox -message "[pwd] [glob *]"
	catch { eval exec tar -czf ${newSpz} . } output
	#~ tk_messageBox -message "tar -czf ${newSpz} [glob *] $output"
	
	# upload
	set cmd1 "spzfile=@${newSpz};type=application/x-gzip"
	set cmd2 "enventory=${sessID}"
	set cmd3 "${tmpdir}/${now}.log"
	catch { exec curl -F $cmd1 -F mode=plain -b $cmd2 -A ${userAgent} -k -o $cmd3 ${uploadURL}} output
	#~ tk_messageBox -message "curl -F $cmd1 -F mode=plain -b $cmd2 -A ${userAgent} -k -o $cmd3 ${uploadURL} $output"
	
	set f [open ${tmpdir}/${now}.log]
	if {[read $f] == "success"} {	
		close $f
		file delete -force ${tmpdir}/${now}.log
		file delete -force ${newSpz}.tgz
		cleanup
	} else {
		close $f
		tk_messageBox -message "Speichern in der Datenbank fehlgeschlagen / Saving in the database failed"
	}
}

proc cleanup {} {
	global tmpdir
	global now
	
	# cleanup
	file delete -force ${tmpdir}/$now
	
	exit 0
}

# ask for action
button .m_clip -text "Mit Grafik aus der Zwischenablage hochladen/Upload with image from clipboard" -command {
	copy_clip
	upload
}
button .o_clip -text "Nur hochladen/Upload only" -command {
	upload
}
button .clean -text "Änderungen verwerfen/Discard changes" -command {
	cleanup
}
#~ pack .m_clip
pack .o_clip
pack .clean

