<?php

require __DIR__ . '/../../app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
/** @var \Magento\Framework\App\Http $app */
$app = $bootstrap->createApplication('Magento\Framework\App\Http');
$app->launch();

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

$row = 1;
if (($handle = fopen(__DIR__."/reviewimport/import.csv", "r")) !== FALSE) {

    while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
        try {
            if ($row == 1) {
                $row++;
                continue;
            }
            $num = count($data);

            $status = 2;

            if ($data[5]) {
                $status = 1;
            }


            $review = $objectManager->create('\Magento\Review\Model\Review');

            $date = date("Y-m-d H:i:s", strtotime($data[8]));

            $review
                ->setEntityId($review->getEntityIdByCode(\Magento\Review\Model\Review::ENTITY_PRODUCT_CODE))
                ->setEntityPkValue($data[4])
                ->setTitle($data[0])
                ->setDetail($data[1])
                ->setNickname($data[2])
                ->setStatusId($status)
                ->setStoreId($data[7])
                ->setStores($data[7])
                ->setCreatedAt($data[8]);

            if ($data[3]) {
                $review->setCustomerId($data[3]);
            }

            $review->save();

            /** @var \Magento\Review\Model\ResourceModel\Review\Collection $ratingCollection */
            $rating = $objectManager->create('\Magento\Review\Model\Rating')->load(1);

            $rating->setReviewId($review->getId())
                ->addOptionVote($data[6], $data[4]);

            $review->aggregate();
            $row++;
        } catch (Exception $e) {
            echo $row;
            echo $e->getMessage();
        }
    }
    fclose($handle);
}
