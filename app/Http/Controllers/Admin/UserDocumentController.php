<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateUserDocumentAction;
use App\Actions\Admin\VerifyUserDocumentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserDocumentRequest;
use App\Http\Resources\Admin\UserDocumentResource;
use App\Http\Wrappers\Admin\UserDocumentWrapper;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class UserDocumentController extends Controller
{
    public function __construct(
        private readonly CreateUserDocumentAction $creator,
        private readonly VerifyUserDocumentAction $verifier,
    ) {}

    public function index(User $user): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', UserDocument::class);

        return UserDocumentResource::collection(
            $user->documents()->with(['attachmentType', 'verifiedBy'])->get()
        );
    }

    public function store(StoreUserDocumentRequest $request, User $user): UserDocumentResource
    {
        $document = $this->creator->handle(
            $user->id,
            $request->file('file'),
            new UserDocumentWrapper(['attachment_type_id' => $request->validated()['attachment_type_id']])
        );

        return new UserDocumentResource($document->load(['attachmentType', 'verifiedBy']));
    }

    public function destroy(User $user, UserDocument $document): Response
    {
        Gate::authorize('delete', $document);

        $document->delete();

        return response()->noContent();
    }

    public function verify(Request $request, User $user, UserDocument $document): UserDocumentResource
    {
        Gate::authorize('verify', $document);

        $document = $this->verifier->handle($document, $request->user()->id);

        return new UserDocumentResource($document->load(['attachmentType', 'verifiedBy']));
    }
}
