<?php

namespace Spod\Sync\Model;

use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Spod\Sync\Api\SpodLoggerInterface;

class ImageHandler
{
    /** @var DirectoryList */
    private $directoryList;

    /** @var Filesystem */
    private $filesystem;

    /** @var ProductRepository */
    private $productRepository;

    /** @var SpodLoggerInterface  */
    private $logger;

    public function __construct(
        DirectoryList $directoryList,
        Filesystem $filesystem,
        ProductRepository $productRepository,
        SpodLoggerInterface $logger
    ) {
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * @param $configurableProduct
     * @param $images
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function assignConfigurableImages($configurableProduct, $images)
    {
        $imagePaths = [];
        foreach ($images as $image) {
            $imageUrl = $image->imageUrl;
            $imagePaths[] = $this->downloadImage($configurableProduct->getId(), $imageUrl);
        }

        $this->assignImages($configurableProduct, $imagePaths);
    }

    /**
     * @param $variantInfo
     * @param $images
     */
    public function downloadAndAssignImages($variantInfo, $images)
    {
        try {
            $product = $this->productRepository->get($variantInfo->sku);
            $imagePaths = $this->getImagesForVariant($variantInfo, $images);
            $this->assignImages($product, $imagePaths);
        } catch (\Exception $e) {
            $this->logger->logError($e->getMessage());
        }
    }

    /**
     * @param $variantInfo
     * @param $images
     * @return array
     */
    private function getImagesForVariant($variantInfo, $images)
    {
        $urls = [];
        foreach ($variantInfo->imageIds as $imageId) {
            foreach ($images as $imageInfo) {
                if ($imageInfo->id == $imageId) {
                    $urls[$imageId] = $this->downloadImage($imageId, $imageInfo->imageUrl);
                }
            }
        }

        return $urls;
    }

    /**
     * @param $product
     * @param $imageUrls
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function assignImages($product, $imageUrls)
    {
        $this->resetOldImages($product);

        foreach ($imageUrls as $imageId => $imagePath) {
            try {
                $product->addImageToMediaGallery($imagePath, ['image', 'small_image', 'thumbnail'], true, false);
            } catch (\Exception $e) {
                $this->logger->logError($e->getMessage());
            }

            $this->productRepository->save($product);
        }
    }

    /**
     * @param $imageId
     * @param $imageUrl
     * @return false|string
     */
    private function downloadImage($imageId, $imageUrl)
    {
        $dir = sprintf("%s/spod_download", $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath());
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        $tmpFile = tempnam($dir, sprintf('spod-image-%s', $imageId));
        $imageFile = $tmpFile . '.png';
        rename($tmpFile, $imageFile);

        $this->fetchImageToFile($imageUrl, $imageFile);

        return $imageFile;
    }

    /**
     * @param $imageUrl
     * @param string $imageFile
     */
    private function fetchImageToFile($imageUrl, string $imageFile): void
    {
        $ch = curl_init($imageUrl);
        $fp = fopen($imageFile, 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    /**
     * @param $product
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function resetOldImages($product): void
    {
        $existingMediaGalleryEntries = $product->getMediaGalleryEntries();
        foreach ($existingMediaGalleryEntries as $key => $entry) {
            unset($existingMediaGalleryEntries[$key]);
        }

        $product->setMediaGalleryEntries($existingMediaGalleryEntries);
        $this->productRepository->save($product);
    }
}
