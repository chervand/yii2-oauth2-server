<?php
namespace chervand\yii2\oauth2\server\components;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ServerRequestInterface;
use yii\helpers\VarDumper;

/**
 * Class Mac
 * @package chervand\yii2\oauth2\server\components
 *
 * @property $kid
 * @property $ts
 * @property $access_token
 * @property $mac
 * @property $h
 * @property $seq_nr
 * @property $cb
 */
class Mac
{
    /**
     * @var ServerRequestInterface
     */
    private $_request;
    /**
     * @var array
     */
    private $_params = [];
    private $_jwt;


    public function __construct(ServerRequestInterface $request, $params = null)
    {
        if (!isset($params)) {
            $header = $request->getHeader('authorization');
            $params = empty($header) ? [] : $header[0];
        }

        if (is_string($params)) {
            $params = $this->prepare($params);
        }

        if (!is_array($params)) {
            throw OAuthServerException::serverError('MAC construction failed');
        }

        $this->_request = $request;
        $this->_params = $params;
    }

    /**
     * Prepares MAC params array from the `Authorization` header value.
     *
     * @internal
     * @param string $header
     * @param array $required required params
     * @param array $optional optional params, name => default
     * @return array parsed MAC params
     * @throws OAuthServerException
     */
    protected function prepare(
        $header,
        $required = ['kid', 'ts', 'access_token', 'mac'],
        $optional = ['h' => ['host'], 'seq-nr' => null, 'cb' => null]
    )
    {
        $mac = [];
        $params = explode(',', preg_replace('/^(?:\s+)?MAC\s/', '', $header));
        array_walk($params, function (&$param) use (&$mac) {
            $param = array_map('trim', explode('=', $param, 2));
            if (count($param) != 2) {
                throw OAuthServerException::accessDenied('Error while parsing MAC params');
            }
            if ($param[0] == 'h') {
                $mac[$param[0]] = explode(':', trim($param[1], '"'));
            } else {
                $mac[$param[0]] = trim($param[1], '"');
            }
        });

        foreach ($required as $param) {
            if (!array_key_exists($param, $mac)) {
                throw OAuthServerException::accessDenied("Required MAC param `$param` missing");
            }
        }

        return array_merge($optional, $mac);
    }

    public function __get($name)
    {
        if (isset($this->_params[$name])) {
            return $this->_params[$name];
        } else {
            $name = str_replace('_', '-', $name);
            if (isset($this->_params[$name])) {
                return $this->_params[$name];
            }
        }

        return null;
    }

    public function validate()
    {
        $values = array_merge(
            [$this->getStartLine()],
            $this->getHeaders(),
            [$this->ts, $this->seq_nr]
        );

        $mac = hash_hmac(
            $this->getAlgorithm(),
            implode('\n', array_filter($values)),
            $this->getJwt()->getClaim('mac_key')
        );

        if ($mac === $this->mac) {
            return $this;
        }

        throw OAuthServerException::accessDenied('MAC validation failed');
    }

    protected function getStartLine()
    {
        return implode(' ', [
            $this->_request->getMethod(),
            $this->_request->getUri(),
            'HTTP/' . $this->_request->getProtocolVersion()
        ]);
    }

    protected function getHeaders()
    {
        return array_map(function ($name) {
            $h = $this->_request->getHeader($name);
            return empty($h) ? null : $h[0];
        }, $this->h);
    }

    protected function getAlgorithm()
    {
        return 'sha256';
    }

    public function getJwt()
    {
        if (!isset($this->_jwt)) {
            $this->_jwt = (new Parser())->parse($this->access_token);
        }

        return $this->_jwt instanceof Token ? $this->_jwt : new Token();
    }
}
