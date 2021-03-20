<?php

namespace Spod\Sync\Model\ApiReader;

class ArticleHandler extends AbstractHandler
{
    const ACTION_BASE_URL = '/articles';

    public function getAllArticles()
    {
        return $this->getParsedApiResult(self::ACTION_BASE_URL);
    }

    public function getArticleById(int $articleId)
    {
        $url = sprintf('%s/%d', self::ACTION_BASE_URL, $articleId);
        return $this->getParsedApiResult($url);
    }
}
