/*
Copyright 2006-2009 Felix Rudolphi and Lukas Goossen
open enventory is distributed under the terms of the GNU Affero General Public License, see COPYING for details. You can also find the license under http://www.gnu.org/licenses/agpl.txt

open enventory is a registered trademark of Felix Rudolphi and Lukas Goossen. Usage of the name "open enventory" or the logo requires prior written permission of the trademark holders. 

This file is part of open enventory.

open enventory is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

open enventory is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with open enventory.  If not, see <http://www.gnu.org/licenses/>.
*/

#SingleInstance ignore

; functions
requote(string) {
	; * ? : =
	quote = \ `. + [ ^ ] $ ( ) { } ! < > |
	Loop, parse, quote, %A_Space%
	{
		StringReplace, string, string, %A_LoopField% , \%A_LoopField% , 1
	}
	return string
}

writeIniVars(Filename,Paths,CandidateFilenames,Filter="*.exe") {
	; Paths,Filenames as comma-separated list
	StringCaseSense Off
	
	; prepare wildcards
	CandidateFilenames := requote(CandidateFilenames)
	StringLower CandidateFilenames, CandidateFilenames
	StringReplace, CandidateFilenames, CandidateFilenames, * , `.* , 1
	StringReplace, CandidateFilenames, CandidateFilenames, ? , `. , 1
	
	; show GUI
	Gui, Show, W400 H100,Suchen nach installierten Applikationen/Searching for installed applications
	static FoundListControl = 0
	static CurrentFolderControl = 0
	FoundList := A_Space
	Gui, Add,Text,,Durchsuche/Searching:
	Gui, Add,Text,vCurrentFolderControl W380,
	Gui, Add,Text,vFoundListControl W380,
	
	; RetVal =
	Loop, parse, Paths, `,
	{
		Path = %A_LoopField%
		
		; Search path
		Loop, %Path%\%Filter%,0,1
		{
			; give feedback
			GuiControl, ,CurrentFolderControl,%A_LoopFileDir%
			
			; check if file matches one of the filenames
			Loop, parse, CandidateFilenames, `;
			{
				; split at =
				StringSplit, NameValue, A_LoopField , =
				Loop, parse, NameValue3, `,
				{
					; if (A_LoopFileName=A_LoopField)
					StringLower TestName, A_LoopFileName
					if (RegExMatch(TestName,"^" . A_LoopField . "$"))
					{
						; allow multiple ones, like bruker,bruker_xwin
						Loop, parse, NameValue2, `,
						{
							; did we have it already?
							ifNotInString FoundList,%A_Space%%A_LoopField%%A_Space%
							{
								IniWrite, "%A_LoopFileLongPath%",%Filename%,%NameValue1%,%A_LoopField%
								if ErrorLevel
								{
									msgbox Konnte nicht in %Filename% schreiben./Could not write to %Filename%.
									exitapp
								}
								FoundList := FoundList . A_LoopField . A_Space
								GuiControl, ,FoundListControl,Gefunden/found:%FoundList%
							}
						}
						; do not test more candidates, avoid problems with A_LoopField
						break
					}
				}
			}
		}
	}
	; return RetVal
}

; A_ProgramFiles
; Paths = %A_ProgramFiles%
Paths = c:

; find curl, 7-zip, nconvert
; find acdnmr, mestrenova,mestrec,topspin,win1d
; find chemstation
Filenames = System=nconvert=nconvert.exe;System=curl=curl.exe;System=sevenzip=7z.exe;Programs=agilent=HPCORE.EXE;Programs=bruker,bruker_xwin=specman.exe,MestReNova.exe,MestReC*.exe,MestRe-C.exe,MestReCLite.exe,spinworks.exe,win1d.exe,winnmr.exe;Programs=acd=specman.exe;Programs=varian_sms=satvw32.exe,amdis_*.exe;
Filename = %A_ScriptDir%\makro.ini
; msgbox %Filename%
writeIniVars(Filename,Paths,Filenames)

; apply post settings

GuiClose:
ExitApp