<?php
defined('BASE_PATH') || exit('No direct script access allowed');

/**
 * 
 * @author arthas
 * @see @ref fpdrp
 */
class FPPacket
{
    const COMMAND_NONE = 0;
    const COMMAND_DISCONNECT = 1;
    const COMMAND_TEST = 2;
    const COMMON_EDIT_VHOST = 3;

    private $command;
    private $entity;
    private $order;

    public function __construct($order, $command = self::COMMAND_NONE, $entity = '')
    {
        $this->order = $order;
        $this->command = $command;
        $this->entity = $entity;
    }
    
    
    public function pack()
    {
        $header = pack('vvCCvPPP'
            , 0x5046    // sign
            , 1 // freepanel version
            , $this->order    // packet order
            , $this->command
            , 0 // reserved1 2bytes
            , 0 // reserved2 8bytes
            , 0 // reserved3 8bytes
            , strlen($this->entity) // content size, included command field
            );
        $footer = pack("V"
            , 0 // reserved 4bytes
            );
        return $header . $this->entity . $footer;
    }
    
    public function getCommand()
    {
        return $this->command;
    }
    
    public function getOrder()
    {
        return $this->order;
    }
    
    
    static public function unpackHeader($buffer)
    {
        return unpack('vsign/vversion/Corder/Ccommand/vReserved1/Preserved2/Preserved3/PcontentSize', $buffer);
    }
}

class FreePaneld
{
    private $api;
    private $hostname;
    private $port;
    private $socket;
    private $connected;
    private $packetOrder = 1;
    
    const HEADER_SIZE = 32;
    const FOOTER_SIZE = 4;
    
    public function __construct($hostname, $port)
    {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->api = "http://$hostname:$port";
    }
    
    
    public function __destruct()
    {
        if (isset($this->socket)) {
            if ($this->connected === true) {
                $this->sendPacket(new FPPacket($this->packetOrder++, FPPacket::COMMAND_DISCONNECT));
            }
            socket_close($this->socket);
        }
    }
    
    /**
     * @return bool Returns true if connect successful, otherwise returns false
     */
    public function connect()
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $error = NULL;
        $attempts = 0;
        $timeout = 8000;  // adjust because we sleeping in 1 millisecond increments
        $connected;
        socket_set_nonblock($this->socket);
        while (!($this->connected = @socket_connect($this->socket, $this->hostname, $this->port)) && $attempts++ < $timeout) {
            $error = socket_last_error();
            if ($error != SOCKET_EINPROGRESS && $error != SOCKET_EALREADY) {
                $this->errstr = "Error Connecting Socket: ".socket_strerror($error);
                socket_close($this->socket);
                return false;
            }
            usleep(1000);
        }
        socket_set_block($this->socket);
        // send test packet
        return $this->connected && $this->sendPacket(new FPPacket($this->packetOrder++, FPPacket::COMMAND_TEST)) == 'success';
    }
    
    
    public function getSystemInformation()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$this->api/system");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($statusCode != 200) {
            return false;
        }
        $result = json_decode($response);
        return $result;
    }
    
    
    public function setFreePanel($port)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$this->api/settings");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['type' => 'freepanel', 'port' => $port]));
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($statusCode != 200) {
            return false;
        }
        return $response;
    }
    
    
    /**
     * 
     * @param string $domain
     * @param array $configs
     * DOCUMENT_ROOT
     * ALIAS
     */
    public function createHost($domain, $configs)
    {
        return $this->editHost(0, $domain, $configs);
    }
    
    
    public function updateHost($domain, $configs)
    {
        return $this->editHost(1, $domain, $configs);
    }
    
    
    public function deleteHost($domain, $configs)
    {
        return $this->editHost(2, $domain, $configs);
    }
    
    
    public function getHost($domain)
    {
        $configs = [];
        if (!empty($domain)) {
            $configs['DOMAIN'] = $domain;
        }
        $entity = pack('C', 3) . json_encode($configs, JSON_FORCE_OBJECT);
        $packet = new FPPacket($this->packetOrder++, FPPacket::COMMON_EDIT_VHOST, $entity);
        $response = $this->sendPacket($packet);
        return $response;
    }
    
    
    private function editHost($action, $domain, $configs)
    {
        if (empty($domain))
            return false;
        $configs['DOMAIN'] = $domain;
        $entity = pack('C', $action) . json_encode($configs, JSON_FORCE_OBJECT);
        $packet = new FPPacket($this->packetOrder++, FPPacket::COMMON_EDIT_VHOST, $entity);
        try {
            $response = $this->sendPacket($packet);
        } catch (Exception $e) {
            if ($e->getCode() == 4) {
                return true;
            } else {
                throw $e;
            }
        }
        return $response == 'success' ? true : $response;
    }
    
    
    private function sendPacket($packet)
    {
        if (!$this->connected)
            throw Exception('Did not connect to freepaneld');
        $buffer = $packet->pack();
        socket_write($this->socket, $buffer, strlen($buffer));
        if ($packet->getCommand() == FPPacket::COMMAND_DISCONNECT) {
            // server will response nothing, return directly.
            return null;
        }
        $dataSize = socket_recv($this->socket, $buffer, self::HEADER_SIZE, 0);
        if ($dataSize != self::HEADER_SIZE) {
            if ($dataSize == 0) {
                // close by remote peer
                throw new Exception(socket_strerror(), socket_last_error());
            } else {
                throw new Exception('recviced a invalid Packet');
            }
        }
        $header = FPPacket::unpackHeader($buffer);
        if ($packet->getOrder() != $header['order']) {
            throw new Exception('packet order incorrect');
        }
        $contentSize = $header['contentSize'];
        $content = null;
        if ($contentSize > 0) {
            $content = socket_read($this->socket, $contentSize);
        }
        // footer
        $buffer = socket_read($this->socket, self::FOOTER_SIZE);
        if (strlen($buffer) != self::FOOTER_SIZE) {
            throw new Exception('recviced a invalid packet');
        }
        return $content;
    }
}
