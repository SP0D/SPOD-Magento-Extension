<?php

namespace Spod\Sync\Model\ApiReader;

use Spod\Sync\Model\ApiResult;

/**
 * Contains the methods which handle
 * all requests to the /articles resource.
 *
 * @package Spod\Sync\Model\ApiReader
 */
class ArticleHandler extends AbstractHandler
{
    const ACTION_BASE_URL = '/articles';

    public function getAllArticles(): ApiResult
    {
        return $this->fetchResult(self::ACTION_BASE_URL);
    }

    public function getArticleById(int $articleId): ApiResult
    {
        $url = sprintf('%s/%d', self::ACTION_BASE_URL, $articleId);
        $result = $this->fetchResult($url);

        if ($result->getHttpCode() !== 200) {
            throw new \Exception(sprintf("articleId not found: %s", $articleId));
        }

        return $result;
    }
}
