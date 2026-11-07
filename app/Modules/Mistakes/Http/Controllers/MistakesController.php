<?php

declare(strict_types=1);

namespace App\Modules\Mistakes\Http\Controllers;

use App\Modules\Mistakes\Http\Requests\NotebookRequest;
use App\Modules\Mistakes\Http\Resources\MistakeResource;
use App\Modules\Mistakes\Models\Mistake;
use App\Modules\Mistakes\Services\Notebook;
use App\Shared\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class MistakesController extends Controller
{
    private const PAGE = 30;

    public function __construct(private readonly Notebook $notebook) {}

    /** `GET /mistakes`: the notebook, most recently made first, a page at a time. */
    public function index(NotebookRequest $request): AnonymousResourceCollection
    {
        $page = $this->notebook->of($request->user(), $request->language())
            ->when($request->category(), fn ($query, $category) => $query->where('category', $category->value))
            ->when($request->status(), fn ($query, $status) => $query->where('status', $status->value))
            ->orderByDesc('last_seen_at')->orderByDesc('id')
            ->cursorPaginate(self::PAGE);

        return MistakeResource::collection($page);
    }

    /** `GET /mistakes/summary`: the counts the notebook screen and home card show. */
    public function summary(NotebookRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->notebook->summary($request->user(), $request->language())]);
    }

    /** `GET /mistakes/practice`: the next drill round. Reading it changes nothing; answers do. */
    public function practice(NotebookRequest $request): AnonymousResourceCollection
    {
        return MistakeResource::collection($this->notebook->practice($request->user(), $request->language(), $request->limit()));
    }

    /** `DELETE /mistakes/{id}`: "this was not a mistake". Kept, so making it again does not bring it back. */
    public function dismiss(Request $request, string $id): Response
    {
        $this->find($request, $id)->forceFill(['dismissed_at' => now()])->save();

        return response()->noContent();
    }

    /** `POST /mistakes/{id}/restore`: undo of the above. */
    public function restore(Request $request, string $id): MistakeResource
    {
        $mistake = $this->find($request, $id);
        $mistake->forceFill(['dismissed_at' => null])->save();

        return new MistakeResource($mistake);
    }

    private function find(Request $request, string $id): Mistake
    {
        // Another learner's mistake is simply not found: its existence is not theirs to know.
        return Mistake::query()->where('user_id', $request->user()->id)->findOrFail($id);
    }
}
