#SingleInstance Force

iniFile = %A_ScriptDir%\balance.ini

IfNotExist %iniFile%
{
	MsgBox %iniFile% not found
}
else
{
	IniRead,protocol,%iniFile%,Connection,protocol,test
	IniRead,title_check,%iniFile%,Setup,title_check
}

Quit_var = 0

IfEqual,protocol,test
{
	Sleep, 5000
	; send 12.34 g and 543.0 g alternating
	while 1
	{
		SendWeight("12.34 g")
		Sleep, 3000
		SendWeight("543.0 g")
		;CTRL-F1 sets Quit_var=1
		if Quit_var = 1
			Break
		Sleep, 3000
	}
}
else
{
	IniRead,RS232_Port,%iniFile%,Connection,RS232_Port,COM1
	IniRead,RS232_Baud,%iniFile%,Connection,RS232_Baud,9600
	IniRead,RS232_Parity,%iniFile%,Connection,RS232_Parity,N
	IniRead,RS232_Data,%iniFile%,Connection,RS232_Data,8
	IniRead,RS232_Stop,%iniFile%,Connection,RS232_Stop,1

	RS232_Settings	 = %RS232_Port%:baud=%RS232_Baud% parity=%RS232_Parity% data=%RS232_Data% stop=%RS232_Stop% dtr=Off

	RS232_FileHandle:=RS232_Initialize(RS232_Settings)
	ASCII =

	;RS232 COM port receive loop
	Loop
	{
		;0xFF in the line below sets the size of the read buffer.
		Read_Data := RS232_Read(RS232_FileHandle,"0xFF",RS232_Bytes_Received)
		;MsgBox,RS232_FileHandle=%RS232_FileHandle% `n RS232_Bytes_Received=%RS232_Bytes_Received% `n Read_Data=%Read_Data% ; Variable that is set by RS232_Read()

		;Process the data, if there is any.
		If (RS232_Bytes_Received > 0)
		{
			;MsgBox, Read_Data=%Read_Data%

			;Prevent interruption during execution of this loop.
			Critical, On

			;Begin Data to ASCII conversion
			;ASCII =
			Read_Data_Num_Bytes := StrLen(Read_Data) / 2 ;RS232_Read() returns 2 characters for each byte

			Loop %Read_Data_Num_Bytes%
			{
				StringLeft, Byte, Read_Data, 2
				StringTrimLeft, Read_Data, Read_Data, 2
				Byte = 0x%Byte%
				Byte := Byte + 0 ;Convert to Decimal			 
				ASCII_Chr := Chr(Byte)
				ASCII = %ASCII%%ASCII_Chr%
			}
			IfNotInString ASCII,`n
			{
				continue
			}
			;Send, ^{END}
			
			IfEqual,protocol,kern
			{
				SendWeight(ASCII)
			}
			else IfEqual,protocol,mettler
			{
				StringLeft,PREFIX,ASCII,2
				IfEqual,PREFIX,SS
				{
					StringReplace,ASCII,ASCII,`r`n,,All
					StringTrimLeft,ASCII,ASCII,2
					SendWeight(ASCII)
				}
			}
			ASCII =
			
			Critical, Off
		}

		;CTRL-F1 sets Quit_var=1
		if Quit_var = 1
			Break
	}

	RS232_Close(RS232_FileHandle)
}

SendWeight(text)
{
	global title_check
	; make sure we send to a browser window
	WinGetActiveTitle,winTitle
	if (title_check = "" or InStr(winTitle,title_check)) and (InStr(winTitle,"Firefox") or InStr(winTitle,"Internet Explorer") or InStr(winTitle,"Safari") or InStr(winTitle,"Chrome") or InStr(winTitle,"Opera"))
	{
		SendInput, {Esc}%text%{Enter}
	}
}

;########################################################################
;###### Initialize RS232 COM Subroutine #################################
;########################################################################
RS232_Initialize(RS232_Settings)
{
	;###### Extract/Format the RS232 COM Port Number ######
	;7/23/08 Thanks krisky68 for finding/solving the bug in which RS232 COM Ports greater than 9 didn't work.
	StringSplit, RS232_Temp, RS232_Settings, `:
	RS232_Temp1_Len := StrLen(RS232_Temp1)	;For COM Ports > 9 \\.\ needs to prepended to the COM Port name.
	If (RS232_Temp1_Len > 4)									 ;So the valid names are
		RS232_COM = \\.\%RS232_Temp1%						 ; ... COM8	COM9	 \\.\COM10	\\.\COM11	\\.\COM12 and so on...
	Else																					;
		RS232_COM = %RS232_Temp1%

	;8/10/09 A BIG Thanks to trenton_xavier for figuring out how to make COM Ports greater than 9 work for USB-Serial Dongles.
	StringTrimLeft, RS232_Settings, RS232_Settings, RS232_Temp1_Len+1 ;Remove the COM number (+1 for the semicolon) for BuildCommDCB.
	;MsgBox, RS232_COM=%RS232_COM% `nRS232_Settings=%RS232_Settings%

	;###### Build RS232 COM DCB ######
	;Creates the structure that contains the RS232 COM Port number, baud rate,...
	VarSetCapacity(DCB, 28)
	BCD_Result := DllCall("BuildCommDCB"
			 ,"str" , RS232_Settings ;lpDef
			 ,"UInt", &DCB)				;lpDCB
	If (BCD_Result <> 1)
	{
		MsgBox, There is a problem with Serial Port communication. `nFailed Dll BuildCommDCB, BCD_Result=%BCD_Result% `nThe Script Will Now Exit.
		Exit
	}

	;###### Create RS232 COM File ######
	;Creates the RS232 COM Port File Handle
	RS232_FileHandle := DllCall("CreateFile"
			 ,"Str" , RS232_COM		 ;File Name				 
			 ,"UInt", 0xC0000000	 ;Desired Access
			 ,"UInt", 3						;Safe Mode
			 ,"UInt", 0						;Security Attributes
			 ,"UInt", 3						;Creation Disposition
			 ,"UInt", 0						;Flags And Attributes
			 ,"UInt", 0						;Template File
			 ,"Cdecl Int")

	If (RS232_FileHandle < 1)
	{
		MsgBox, There is a problem with Serial Port communication. `nFailed Dll CreateFile, RS232_FileHandle=%RS232_FileHandle% `nThe Script Will Now Exit.
		Exit
	}

	;###### Set COM State ######
	;Sets the RS232 COM Port number, baud rate,...
	SCS_Result := DllCall("SetCommState"
			 ,"UInt", RS232_FileHandle ;File Handle
			 ,"UInt", &DCB)					;Pointer to DCB structure
	If (SCS_Result <> 1)
	{
		MsgBox, There is a problem with Serial Port communication. `nFailed Dll SetCommState, SCS_Result=%SCS_Result% `nThe Script Will Now Exit.
		RS232_Close(RS232_FileHandle)
		Exit
	}

	;###### Create the SetCommTimeouts Structure ######
	ReadIntervalTimeout				= 0xffffffff
	ReadTotalTimeoutMultiplier = 0x00000000
	ReadTotalTimeoutConstant	 = 0x00000000
	WriteTotalTimeoutMultiplier= 0x00000000
	WriteTotalTimeoutConstant	= 0x00000000

	VarSetCapacity(Data, 20, 0) ; 5 * sizeof(DWORD)
	NumPut(ReadIntervalTimeout,				 Data,	0, "UInt")
	NumPut(ReadTotalTimeoutMultiplier,	Data,	4, "UInt")
	NumPut(ReadTotalTimeoutConstant,		Data,	8, "UInt")
	NumPut(WriteTotalTimeoutMultiplier, Data, 12, "UInt")
	NumPut(WriteTotalTimeoutConstant,	 Data, 16, "UInt")

	;###### Set the RS232 COM Timeouts ######
	SCT_result := DllCall("SetCommTimeouts"
		 ,"UInt", RS232_FileHandle ;File Handle
		 ,"UInt", &Data)				 ;Pointer to the data structure
	If (SCT_result <> 1)
	{
		MsgBox, There is a problem with Serial Port communication. `nFailed Dll SetCommState, SCT_result=%SCT_result% `nThe Script Will Now Exit.
		RS232_Close(RS232_FileHandle)
		Exit
	}
 
	Return %RS232_FileHandle%
}

;########################################################################
;###### Close RS23 COM Subroutine #######################################
;########################################################################
RS232_Close(RS232_FileHandle)
{
	;###### Close the COM File ######
	CH_result := DllCall("CloseHandle", "UInt", RS232_FileHandle)
	If (CH_result <> 1)
		MsgBox, Failed Dll CloseHandle CH_result=%CH_result%

	Return
}

;########################################################################
;###### Write to RS232 COM Subroutines ##################################
;########################################################################
RS232_Write(RS232_FileHandle,Message)
{
	SetFormat, Integer, DEC

	;Parse the Message. Byte0 is the number of bytes in the array.
	StringSplit, Byte, Message, `,
	Data_Length := Byte0
	;MsgBox, Data_Length=%Data_Length% b1=%Byte1% b2=%Byte2% b3=%Byte3% b4=%Byte4%

	;Set the Data buffer size, prefill with 0xFF.
	VarSetCapacity(Data, Byte0, 0xFF)

	;Write the Message into the Data buffer
	i=1
	Loop %Byte0%
	{
		NumPut(Byte%i%, Data, (i-1) , "UChar")
		;MsgBox, %i%
		i++
	}
	;MsgBox, Data string=%Data%

	;###### Write the data to the RS232 COM Port ######
	WF_Result := DllCall("WriteFile"
			 ,"UInt" , RS232_FileHandle ;File Handle
			 ,"UInt" , &Data					;Pointer to string to send
			 ,"UInt" , Data_Length		;Data Length
			 ,"UInt*", Bytes_Sent		 ;Returns pointer to num bytes sent
			 ,"Int"	, "NULL")
	If (WF_Result <> 1 or Bytes_Sent <> Data_Length)
		MsgBox, Failed Dll WriteFile to RS232 COM, result=%WF_Result% `nData Length=%Data_Length% `nBytes_Sent=%Bytes_Sent%
}

;########################################################################
;###### Read from RS232 COM Subroutines #################################
;########################################################################
RS232_Read(RS232_FileHandle,Num_Bytes,ByRef RS232_Bytes_Received)
{
	SetFormat, Integer, HEX

	;Set the Data buffer size, prefill with 0x55 = ASCII character "U"
	;VarSetCapacity won't assign anything less than 3 bytes. Meaning: If you
	;	tell it you want 1 or 2 byte size variable it will give you 3.
	Data_Length	:= VarSetCapacity(Data, Num_Bytes, 0x55)
	;MsgBox, Data_Length=%Data_Length%

	;###### Read the data from the RS232 COM Port ######
	;MsgBox, RS232_FileHandle=%RS232_FileHandle% `nNum_Bytes=%Num_Bytes%
	Read_Result := DllCall("ReadFile"
			 ,"UInt" , RS232_FileHandle	 ; hFile
			 ,"Str"	, Data						 ; lpBuffer
			 ,"Int"	, Num_Bytes				; nNumberOfBytesToRead
			 ,"UInt*", RS232_Bytes_Received	 ; lpNumberOfBytesReceived
			 ,"Int"	, 0)							 ; lpOverlapped

	;MsgBox, RS232_FileHandle=%RS232_FileHandle% `nRead_Result=%Read_Result% `nBR=%RS232_Bytes_Received% ,`nData=%Data%
	If (Read_Result <> 1)
	{
		MsgBox, There is a problem with Serial Port communication. `nFailed Dll ReadFile on RS232 COM, result=%Read_Result% - The Script Will Now Exit.
		RS232_Close(RS232_FileHandle)
		Exit
	}

	;###### Format the received data ######
	;This loop is necessary because AHK doesn't handle NULL (0x00) characters very nicely.
	;Quote from AHK documentation under DllCall:
	;		 "Any binary zero stored in a variable by a function will hide all data to the right
	;		 of the zero; that is, such data cannot be accessed or changed by most commands and
	;		 functions. However, such data can be manipulated by the address and dereference operators
	;		 (& and *), as well as DllCall itself."
	i = 0
	Data_HEX =
	Loop %RS232_Bytes_Received%
	{
		;First byte into the Rx FIFO ends up at position 0

		Data_HEX_Temp := NumGet(Data, i, "UChar") ;Convert to HEX byte-by-byte
		StringTrimLeft, Data_HEX_Temp, Data_HEX_Temp, 2 ;Remove the 0x (added by the above line) from the front

		;If there is only 1 character then add the leading "0'
		Length := StrLen(Data_HEX_Temp)
		If (Length =1)
			Data_HEX_Temp = 0%Data_HEX_Temp%

		i++

		;Put it all together
		Data_HEX := Data_HEX . Data_HEX_Temp
	}
	;MsgBox, Read_Result=%Read_Result% `nRS232_Bytes_Received=%RS232_Bytes_Received% ,`nData_HEX=%Data_HEX%

	SetFormat, Integer, DEC
	Data := Data_HEX

	Return Data
}

;########################################################################
;###### Exit Console Receive Loop #######################################
;########################################################################
^F1::
Quit_var = 1
return
