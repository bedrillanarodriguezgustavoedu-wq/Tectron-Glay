@echo off
"C:\Program Files (x86)\cloudflared\cloudflared.exe" tunnel --url http://127.0.0.1:8000 --no-autoupdate > "%~dp0tunel.log" 2>&1
