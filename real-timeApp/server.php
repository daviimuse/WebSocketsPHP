<?php
    //php -q server.php
    function handshake($client, $headers){
        $this->console("Getting client WebSocket version...");
        if(preg_match("/Sec-WebSocket-Version: (.*)\r\n/", $headers, $match))
            $version = $match[1];
        else {
            $this->console("The client doesn't support WebSocket");
            return false;
        }
     
        $this->console("Client WebSocket version is {$version}, (required: 13)");
        if($version == 13) {
            // Extract header variables
            $this->console("Getting headers...");
            if(preg_match("/GET (.*) HTTP/", $headers, $match))
                $root = $match[1];
            if(preg_match("/Host: (.*)\r\n/", $headers, $match))
                $host = $match[1];
            if(preg_match("/Origin: (.*)\r\n/", $headers, $match))
                $origin = $match[1];
            if(preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $headers, $match))
                $key = $match[1];
     
            $this->console("Client headers are:");
            $this->console("\t- Root: ".$root);
            $this->console("\t- Host: ".$host);
            $this->console("\t- Origin: ".$origin);
            $this->console("\t- Sec-WebSocket-Key: ".$key);
     
            $this->console("Generating Sec-WebSocket-Accept key...");
            $acceptKey = $key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
            $acceptKey = base64_encode(sha1($acceptKey, true));
     
            $upgrade = "HTTP/1.1 101 Switching Protocols\r\n".
                       "Upgrade: websocket\r\n".
                       "Connection: Upgrade\r\n".
                       "Sec-WebSocket-Accept: $acceptKey".
                       "\r\n\r\n";
     
            $this->console(
                "Sending this response to the client #{$client->getId()}:"
                ."\r\n".$upgrade
            );
            socket_write($client->getSocket(), $upgrade);
            $client->setHandshake(true);
            $this->console("Handshake is successfully done!");
            return true;
        }
        else {
            $this->console(
                "WebSocket version 13 required"
                ."(the client supports version {$version})"
            );
            return false;
        }
    }

    function unmask($payload) {
        $length = ord($payload[1]) & 127;
     
        if($length == 126) {
            $masks = substr($payload, 4, 4);
            $data = substr($payload, 8);
        }
        elseif($length == 127) {
            $masks = substr($payload, 10, 4);
            $data = substr($payload, 14);
        }
        else {
            $masks = substr($payload, 2, 4);
            $data = substr($payload, 6);
        }
     
        $text = '';
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i%4];
        }
        return $text;
    }

    function encode($text) {
        // 0x1 text frame (FIN + opcode)
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);
     
        if($length == 125 && $length < 65536)
            $header = pack('CCS', $b1, 126, $length);
        elseif($length >= 65536)
            $header = pack('CCN', $b1, 127, $length);
     
        return $header.$text;
    }

    function startProcess($client) {
        $this->console("Start a child process");
        $pid = pcntl_fork();
        if($pid == -1) {
            die('could not fork');
        }
        elseif($pid) { // process
            $client->setPid($pid);
        }
        else {
            // we are the child
            while(true) {
                // push something to the client
                $seconds = rand(2, 5);
                $this->send($client, "I am waiting {$seconds} seconds");
                sleep($seconds);
            }
        }
?>