<?php

declare(strict_types=1);

namespace App\Http\Controllers\Application;

use App\Domains\Application\Actions\CreateApplicationAction;
use App\Domains\Application\Actions\DeleteApplicationAction;
use App\Domains\Application\Actions\MoveApplicationAction;
use App\Domains\Application\Actions\UpdateApplicationAction;
use App\Domains\Application\Dto\CreateApplicationDTO;
use App\Domains\Application\Dto\MoveApplicationDTO;
use App\Domains\Application\Dto\UpdateApplicationDTO;
use App\Domains\Application\Enums\ApplicationStatus;
use App\Domains\Application\Models\Application;
use App\Http\Requests\Application\CreateApplicationRequest;
use App\Http\Requests\Application\MoveApplicationRequest;
use App\Http\Requests\Application\UpdateApplicationRequest;
use App\Http\Resources\ApplicationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

final class ApplicationController
{
    public function index(Request $request): JsonResponse
    {
        $applications = Application::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('position')
            ->get()
            ->groupBy(fn (Application $application): string => $application->status->value);

        return response()->json(['data' => $this->grouped($request, $applications)]);
    }

    public function store(CreateApplicationRequest $request, CreateApplicationAction $action): JsonResponse
    {
        $application = $action->execute(CreateApplicationDTO::fromRequest($request));

        return (new ApplicationResource($application))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Application $application): ApplicationResource
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        return new ApplicationResource($application);
    }

    public function update(
        UpdateApplicationRequest $request,
        UpdateApplicationAction $action
    ): ApplicationResource {
        return new ApplicationResource($action->execute(UpdateApplicationDTO::fromRequest($request)));
    }

    public function move(MoveApplicationRequest $request, MoveApplicationAction $action): ApplicationResource
    {
        return new ApplicationResource($action->execute(MoveApplicationDTO::fromRequest($request)));
    }

    public function destroy(Request $request, Application $application, DeleteApplicationAction $action): Response
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $action->execute($application);

        return response()->noContent();
    }

    public function stats(Request $request): JsonResponse
    {
        $counts = Application::query()
            ->where('user_id', $request->user()->id)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return response()->json(['data' => $this->statsPayload($counts)]);
    }

    /**
     * @param  Collection<string, Collection<int, Application>>  $applications
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function grouped(Request $request, Collection $applications): array
    {
        return collect(ApplicationStatus::cases())
            ->mapWithKeys(fn (ApplicationStatus $status): array => [
                $status->value => ApplicationResource::collection(
                    $applications->get($status->value, collect())
                )->resolve($request),
            ])
            ->all();
    }

    /**
     * @param  Collection<string, int>  $counts
     * @return array<string, mixed>
     */
    private function statsPayload(Collection $counts): array
    {
        $byStatus = collect(ApplicationStatus::cases())
            ->mapWithKeys(fn (ApplicationStatus $status): array => [
                $status->value => (int) ($counts[$status->value] ?? 0),
            ]);

        return [
            'total' => $byStatus->sum(),
            'active' => $byStatus->except(ApplicationStatus::Rejected->value)->sum(),
            'by_status' => $byStatus->all(),
        ];
    }
}
