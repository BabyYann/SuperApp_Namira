@echo off
title Daftar Sisa Nomor Belum Terkirim - SPMB SD Namira Kraksaan
cd /d "%~dp0"
call npm run export-sisa
start "" "%~dp0SISA_BELUM_TERKIRIM.html"
