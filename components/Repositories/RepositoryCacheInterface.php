<?php

namespace chervand\yii2\oauth2\server\components\Repositories;

use yii\caching\Dependency;

interface RepositoryCacheInterface
{
    /**
     * @return int|null
     */
    public function getCacheDuration();

    /**
     * @return Dependency|null
     */
    public function getCacheDependency();

    /**
     * @param int $cacheDuration
     * @return $this
     */
    public function setCacheDuration($cacheDuration);

    /**
     * @param Dependency $cacheDependency
     * @return $this
     */
    public function setCacheDependency($cacheDependency);

    /**
     * @param array|null $cache
     * @return $this
     */
    public function setCache($cache);
}
