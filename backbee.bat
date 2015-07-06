@ECHO OFF
SET BIN_TARGET=%~dp0vendor\bin\backbee.bat
"%BIN_TARGET%" --app=\BackBee\Standard\Application %*
