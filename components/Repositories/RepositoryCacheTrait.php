<?php

namespace chervand\yii2\oauth2\server\components\Repositories;

trait RepositoryCacheTrait
{
    private $_cacheDuration;
    private $_cacheDependency;


    /**
     * {@inheritdoc}
     */
    public function getCacheDuration()
    {
        return $this->_cacheDuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDependency()
    {
        return $this->_cacheDependency;
    }

    /**
     * {@inheritdoc}
     */
    public function setCacheDuration($cacheDuration)
    {
        $this->_cacheDuration = $cacheDuration;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCacheDependency($cacheDependency)
    {
        $this->_cacheDependency = $cacheDependency;
        return $this;
    }

    /**
     * @param array|null $config
     * @return RepositoryCacheTrait
     */
    public function setCache($config)
    {
        if (isset($config['cacheDuration'])) {
            $this->setCacheDuration($config['cacheDuration']);
        }

        if (isset($config['cacheDependency'])) {
            $this->setCacheDependency($config['cacheDependency']);
        }

        return $this;
    }
}
