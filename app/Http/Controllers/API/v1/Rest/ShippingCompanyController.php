<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use Illuminate\Http\JsonResponse;
use App\Http\Requests\FilterParamsRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

use App\Http\Resources\ShippingCompanyResource;
use App\Repositories\ShippingCompanyRepository\ShippingCompanyRepository ;

class ShippingCompanyController extends RestBaseController
{
    public function __construct(
        private ShippingCompanyRepository $repository,
    )
    {
        parent::__construct();
    }
     /**
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $model = $this->repository->index($request->all());

        return ShippingCompanyResource::collection($model);
    }

    /**
     * Display the specified resource.
     *
     * @param Region $region
     * @return JsonResponse
     */


}
