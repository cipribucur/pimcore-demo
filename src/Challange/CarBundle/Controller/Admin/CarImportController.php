<?php


namespace Challange\CarBundle\Controller\Admin;



use Challange\CarBundle\ImportManager\CsvImport;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Cars;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class CarImportController extends AdminController
{
    const MIMETYPE = 'text/csv';

    private array $failedArticles = [];

    /**
     * @Route("/carimport", name="pimcore_admin_asset_carimport", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function carImportAction(Request $request): JsonResponse
    {
        $asset = Asset::getById($request->get('id'));

        if (!$asset) {
            throw $this->createNotFoundException('Asset not found');
        }

        if (!$asset->isAllowed('view')) {
            //import the csv into objects
            throw $this->createNotFoundException('Asset not allowed to access');
        }

        if (!$asset->getMimetype() == static::MIMETYPE) {
            //import the csv into objects
            throw $this->createNotFoundException('Asset is not a CSV file.');
        }

        $this->carImport($asset);

        return $this->adminJson([
            'success' => true,
            'failedArticles' => implode('/', $this->getFailedArticles())
        ]);
    }

    private function carImport($asset): void
    {
        $csvImport = new CsvImport();
        $csvImport->setAsset($asset);
        $csvImport->setImportAttributes([
            'articleNumber',
            'manufacturer',
            'model',
            'cylinders',
            'horsepower',
            'productionYear'
        ]);
        $csvImport->setImportLocalizedAttributes(['description']);
        $csvImport->setImportLocales(['en', 'de']);

        $carObject = new Cars();

        $this->failedArticles = $csvImport->import($carObject, 'articleNumber', 1);
    }

    /**
     * @return array
     */
    public function getFailedArticles(): array
    {
        return $this->failedArticles;
    }

}
