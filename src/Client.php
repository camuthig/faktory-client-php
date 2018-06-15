<?php

declare(strict_types=1);

namespace Camuthig\Faktory;

use Camuthig\Faktory\Exception\FaktoryException;

class Client
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var ?array
     */
    private $workerOptions;

    /**
     * @var resource
     */
    private $socket;

    public function __construct(string $host, int $port, array $workerOptions = [])
    {
        $this->host = $host;
        $this->port = $port;
        $this->workerOptions = $workerOptions;
    }

    public function getWorkerOptions(): array
    {
        return $this->workerOptions;
    }

    /**
     * Connect to the server.
     *
     */
    public function connect(): void
    {
        if ($this->socket === null) {
            $this->socket = @fsockopen($this->host, $this->port, $errNo, $errStr);

            if (!$this->socket) {
                throw new FaktoryException('Unable to connect to server: ' . $errStr);
            }

            $hi = $this->read();

            if (strpos($hi, "HI") !== 0) {
                $this->disconnect();
                throw new FaktoryException('Did not receive HI from server');
            }

            $data = json_decode(substr($hi, 2), true);

            if ($data['v'] !== 2) {
                $this->disconnect();
                throw new FaktoryException('Unsupported server version ' . $data['v']);
            }

            $options = $this->workerOptions;
            $options['v'] = 2;

            $this->writeCommand('HELLO', json_encode($options));
        }
    }

    /**
     * Gracefully end the session with the server.
     */
    public function end(): void
    {
        if ($this->socket !== null) {
            // It seems like the server is closing this connection before responding, possibly?
            $ok = $this->writeCommand('END', '');

            if ($ok === 'OK') {
                $this->disconnect();
            }

            throw new FaktoryException('Unable to end connection');
        }
    }

    public function disconnect(): void
    {
        if ($this->socket !== null) {
            fclose($this->socket);

            $this->socket = null;
        }
    }

    /**
     * Read the next line of data from the server.
     *
     * This logic is based on the `StreamConnection` from the prdis library:
     * https://github.com/nrk/predis/blob/v1.1/src/Connection/StreamConnection.php#L308
     *
     * @return string
     */
    public function read(): string
    {
        $chunk = fgets($this->getSocket());

        if ($chunk === false || $chunk === '') {
            throw new FaktoryException('Error while reading line from the server.');
        }

        $prefix = $chunk[0];
        $payload = substr($chunk, 1, -2);

        switch ($prefix) {
            case '+':
                return $payload;

            case '$':
                $size = (int) $payload;

                if ($size === -1) {
                    return '';
                }

                $bulkData = '';
                $bytesLeft = ($size += 2);

                do {
                    $chunk = fread($this->getSocket(), min($bytesLeft, 4096));

                    if ($chunk === false || $chunk === '') {
                        throw new FaktoryException('Error while reading bytes from the server.');
                    }

                    $bulkData .= $chunk;
                    $bytesLeft = $size - strlen($bulkData);
                } while ($bytesLeft > 0);

                return substr($bulkData, 0, -2);

            case ':':
                return $payload;

            case '-':
                throw new FaktoryException('Server returned an error: ' . $payload);

            default:
                throw new FaktoryException("Unknown response prefix: '$prefix'.");
        }
    }

    public function writeCommand(string $verb, ?string $data): string
    {
        // TODO: Check for closed connection and reconnect
        fwrite($this->getSocket(), $verb . ' ' . $data . "\r\n");

        return $this->read();
    }

    private function getSocket()
    {
        if ($this->socket === null) {
            $this->connect();
        }

        return $this->socket;
    }
}
