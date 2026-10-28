' Envoltorio oculto de programador.bat.
'
' El Programador de tareas de Windows siempre abre una consola cuando su acción
' es un .bat: la ventana parpadea cada minuto y roba el foco. El modificador
' -Hidden de la tarea no lo evita (solo la oculta de la lista del panel). Este
' archivo lanza el mismo .bat con ventana 0, que es la forma de que corra sin
' que se vea nada.
'
' Acción de la tarea:  wscript.exe "E:\ecommerce (4)\ecommerce\programador.vbs"
Dim shell
Set shell = CreateObject("WScript.Shell")
shell.Run """E:\ecommerce (4)\ecommerce\programador.bat""", 0, False
Set shell = Nothing
