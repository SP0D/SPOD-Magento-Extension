<?php

namespace Spod\Sync\Model\ApiReader;

use Spod\Sync\Model\ApiResult;

class ArticleHandler extends AbstractHandler
{
    const ACTION_BASE_URL = '/articles';

    public function getAllArticles(): ApiResult
    {
        $result = $this->getParsedApiResult(self::ACTION_BASE_URL);

        return $result;
    }

    public function getArticleById(int $articleId): ApiResult
    {
        $url = sprintf('%s/%d', self::ACTION_BASE_URL, $articleId);
        $result = $this->getParsedApiResult($url);

        if ($result->getHttpCode() !== 200) {
            throw new \Exception(sprintf("articleId not found: %s", $articleId));
        }

        return $result;
    }
}
