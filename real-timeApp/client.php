<?php

    var ws = new WebSocket('ws://127.0.0.1:5001');

    ws.onopen = function(msg) {
        write('Connection successfully opened (readyState ' + this.readyState+')');
    };
    ws.onclose = function(msg) {
        if(this.readyState == 2)
            write(
                'Closing... The connection is going throught'
                + 'the closing handshake (readyState '+this.readyState+')'
            );
        else if(this.readyState == 3)
            write(
                'Connection closed... The connection has been closed'
                + 'or could not be opened (readyState '+this.readyState+')'
            );
        else
            write('Connection closed... (unhandled readyState '+this.readyState+')');
    };
    ws.onerror = function(event) {
        terminal.innerHTML = '<li style="color: red;">'+event.data+'</li>'
        + terminal.innerHTML;
    };

    ws.onmessage = function(msg) {
        write('Server says: '+msg.data);
    };

    window.onbeforeunload = function() {
        ws.send('quit');
    };

    if($action == "quit") {
        $this->console("Killing a child process");
        posix_kill($client->getPid(), SIGTERM);
        $this->console("Process {$client->getPid()} is killed!");
    }
?>